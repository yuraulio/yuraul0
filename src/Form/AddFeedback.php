<?php

namespace Drupal\yuraul0\Form;

use Drupal;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Ajax\RedirectCommand;
use Drupal\file\Entity\File;
use Exception;

/**
 * Implements a form with AJAX validation for adding feedback.
 */
class AddFeedback extends FormBase {

  /**
   * Just returns the form ID.
   */
  public function getFormId() {
    return 'add_feedback';
  }

  /**
   * Builds the form to render.
   *
   * @param array $form
   *   An associative array containing the elements of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   An associative array containing the structure of the form.
   *
   * @return array
   *   Returns element for the render array.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Deleting error messages from messenger if page was unexpectedly reloaded.
    // But leave other because page will be reloaded after submitting.
    Drupal::messenger()->deleteByType('error');
    // Div element to show messages into.
    $form['system_messages'] = [
      '#markup' => '<div id="form-system-messages"></div>',
      '#weight' => -100,
    ];
    $form['username'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Your name'),
      '#description' => $this->t('Only letters, numbers and underscore, please. Up to 100 symbols'),
      '#required' => TRUE,
    ];

    $form['email'] = [
      '#type' => 'email',
      '#title' => $this->t('E-mail'),
      '#description' => $this->t('Only letters, numbers and underscore in account name. Only letters and "." (dot) in domain.'),
      '#required' => TRUE,
    ];

    $form['phone'] = [
      '#type' => 'tel',
      '#title' => $this->t('Phone number'),
      '#description' => $this->t('International format (+XXXXYYYYYYYYYYYY, X = 1-4 digits)'),
      '#required' => TRUE,
    ];

    $form['message'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Your feedback message'),
      '#description' => $this->t('Up to 500 symbols.'),
      '#required' => TRUE,
    ];

    $form['avatar'] = [
      '#type' => 'managed_file',
      '#title' => $this->t('Add your profile picture'),
      '#description' => $this->t('Only *.jpeg, *.jpg, *.png, up to 2MB.'),
      '#upload_location' => 'public://yuraul0/user',
      '#multiple' => FALSE,
      '#upload_validators' => [
        'file_validate_is_image' => [],
        'file_validate_extensions' => ['png jpg jpeg'],
        'file_validate_size' => [2097152],
      ],
    ];

    $form['picture'] = [
      '#type' => 'managed_file',
      '#title' => $this->t('Add picture to your feedback'),
      '#description' => $this->t('Only *.jpeg, *.jpg, *.png, up to 5MB.'),
      '#upload_location' => 'public://yuraul0/post',
      '#multiple' => FALSE,
      '#upload_validators' => [
        'file_validate_extensions' => ['png jpg jpeg'],
        'file_validate_size' => [5242880],
      ],
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#name' => 'submit',
      '#button_type' => 'primary',
      '#value' => $this->t('Send feedback'),
      '#ajax' => [
        'callback' => '::ajaxSubmitCallback',
        'event' => 'click',
        'progress' => [
          'type' => 'throbber',
        ],
      ],
    ];

    return $form;
  }

  /**
   * Checks the username string.
   *
   * Can contains only ASCII letters, numbers and underscore and should not
   * start with an underscore. Up to 100 symbols.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   An associative array containing the structure of the form.
   */
  protected function checkUsername(FormStateInterface $form_state) {
    $match = preg_match('/^([a-zA-Z][a-zA-Z_0-9]{0,99})|([a-zA-Z])$/s', $form_state->getValue('username'));
    if ($match === 0) {
      $form_state->setErrorByName('username', $this->t('Name is incorrect.'));
    }
  }

  /**
   * Checks the email string.
   *
   * Account name part can contain ASCII letters, numders,
   * dots (but not consecutive), should start and end with a letter.
   * Domain part can't contain meaningless combinations of symbols
   * (like '-.', '..', '.'.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   An associative array containing the structure of the form.
   */
  protected function checkEmail(FormStateInterface $form_state) {
    $match = preg_match('/^([a-zA-Z0-9](?!.*(\.\.).*)[a-zA-Z0-9\-.]*[a-zA-Z0-9]+)|([a-zA-Z0-9])@([a-zA-Z0-9]+((?!.*(\.\.).*)(?!.*(--).*)(?!.*(\.-).*)(?!.*(-\.).*))([a-zA-Z0-9\-.]*)[a-zA-Z0-9])|([a-zA-Z0-9])$/s', $form_state->getValue('email'));
    if ($match === 0) {
      $form_state->setErrorByName('email', $this->t('Email is incorrect.'));
    }
  }

  /**
   * Checks the phone string.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   An associative array containing the structure of the form.
   */
  protected function checkPhone(FormStateInterface $form_state) {
    $match = preg_match('/^\+\d{1,4}\d{8,15}$/s', $form_state->getValue('phone'));
    if ($match === 0) {
      $form_state->setErrorByName('phone', $this->t('Bad phone number'));
    }
  }

  /**
   * Checks the message string.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   An associative array containing the structure of the form.
   */
  protected function checkMessage(FormStateInterface $form_state) {
    $match = preg_match('/.{10,500}$/', $form_state->getValue('message'));
    if ($match === 0) {
      $form_state->setErrorByName('phone', $this->t('Bad message'));
    }
  }

  /**
   * Saves userpic and feedback image and returns file URL.
   *
   * @param string $name
   *   The name of the form element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   An associative array containing the structure of the form.
   *
   * @return string
   *   Returns URL of saved file.
   */
  protected function savePics(string $name, FormStateInterface $form_state) {
    // Trying to get fileID from the submitted form and save the file as entity.
    try {
      $fid = $form_state->getValue($name)[0];
      $file = File::load(($fid));
      // $file->setPermanent(); //Temporary disabled
      $file->save();
      return $file->createFileUrl();
    }
    // If error occured show it to user.
    catch (Exception $e) {
      $form_state->setErrorByName($name, $e->getMessage());
      return '';
    }
  }

  /**
   * Validates some form fields.
   *
   * @param array $form
   *   An associative array containing the elements of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   An associative array containing the structure of the form.
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // Deleting all messages if stayed from previous validation.
    Drupal::messenger()->deleteAll();
    // Validating some fields before saving to database.
    $this->checkUsername($form_state);
    $this->checkEmail($form_state);
    $this->checkPhone($form_state);
    $this->checkMessage($form_state);
  }

  /**
   * Saves received data to database.
   *
   * @param array $form
   *   An associative array containing the elements of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   An associative array containing the structure of the form.
   *
   * @throws \Exception
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Creating array contains a fields to create record to database.
    // Text fields first.
    foreach (['username', 'email', 'phone', 'message'] as $v) {
      $record[$v] = $form_state->getValue($v);
    }
    // URLs to user profile picture and message picture.
    $record['avatar'] = $this->savePics('avatar', $form_state);
    $record['picture'] = $this->savePics('picture', $form_state);
    // Adding timestamp.
    $record['timestamp'] = time();
    // Saving received and validated data to database.
    try {
      Drupal::database()->insert('guestbook')->fields($record)->execute();
      // Setting message of succesful adding of feedback message.
      Drupal::messenger()->addMessage($this->t('Thank you @name, for your feedback!', [
        '@name' => $form_state->getValue('username'),
      ]));
    }
    catch (Exception $e) {
      Drupal::messenger()->addError($e->getMessage());
    }

  }

  /**
   * AJAX submit callback.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   An associative array containing the structure of the form.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   AJAX response object with HTML or redirect command.
   */
  public function ajaxSubmitCallback(array &$form, FormStateInterface $form_state) {
    $ajax_response = new AjaxResponse();
    // If there are no validation errors sending response with redirect
    // to current path and deleting error messages from messenger (because it
    // will be there after redirect).
    if (count($form_state->getErrors()) === 0) {
      $current_path = Drupal::service('path.current')->getPath();
      $ajax_response->addCommand(new RedirectCommand($current_path));
      Drupal::messenger()->deleteByType('error');
    }
    // Else sending response with rendered errors to show it in form.
    else {
      $message = [
        '#theme' => 'status_messages',
        '#message_list' => Drupal::messenger()->all(),
        '#status_headings' => [
          'status' => t('Status message'),
          'error' => t('Error message'),
          'warning' => t('Warning message'),
        ],
        '#marckup' => time(),
      ];
      $messages = Drupal::service('renderer')->render($message);
      $ajax_response->addCommand(new HtmlCommand('#form-system-messages', $messages));
    }
    return $ajax_response;
  }

}
