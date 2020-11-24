<?php

namespace Drupal\yuraul0\Form;

use Drupal;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Ajax\RedirectCommand;
use Drupal\file\Entity\File;

/**
 * Implements a form with AJAX validation for adding feedback.
 */
class AddFeedback extends FormBase {
  // Setting array with fields of record in DB and of form inputs.
  private const FIELDS = [
    'fid',
    'message',
    'picture',
    'timestamp',
    'username',
    'email',
    'phone',
    'avatar',
  ];

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
  public function buildForm(array $form, FormStateInterface $form_state, $post_ID = FALSE) {
    // Setting the form title.
    $title = $this->t('Add feedback');

    // If received $postID get appropriate post from DB if exists.
    if ($post_ID) {
      $query = Drupal::database()->select('guestbook');
      $query->fields('guestbook', self::FIELDS);
      $query->condition('fid', $post_ID);
      $post = $query->execute()->fetchAll();
      if ($post ?? FALSE) {
        $title = $this->t('Edit post# @postID', ['@postID' => $post_ID]);
      }
      else {
        Drupal::messenger()->addError("Post #$post_ID not found!");
      }
    }
    // Setting default values to empty string if postID was not received.
    else {
      foreach (self::FIELDS as $field) {
        $post[0][$field] = '';
      }
      // Casting array to object to access to it with object syntax.
      $post[0] = (object) $post[0];
    }

    $form['fieldset'] = [
      '#type' => 'fieldset',
      '#title' => $title,
    ];

    // Div element to show messages into.
    $form['fieldset']['system_messages'] = [
      '#markup' => '<div id="form-system-messages"></div>',
      '#weight' => -100,
    ];

    $form['fieldset']['upper'] = [
      '#type' => 'container',
      '#attributes' => [
        'class' => [
          'upper',
        ],
      ],
    ];

    $form['fieldset']['upper']['left'] = [
      '#type' => 'container',
      '#attributes' => [
        'class' => [
          'left',
        ],
      ],
    ];

    $form['fieldset']['upper']['right'] = [
      '#type' => 'container',
      '#attributes' => [
        'class' => [
          'right',
        ],
      ],
    ];

    $form['fieldset']['upper']['left']['username'] = [
      '#type' => 'textfield',
      '#maxlength' => 100,
      '#title' => $this->t('Your name'),
      '#description' => $this->t('Only letters, numbers and underscore, please. Up to 100 symbols'),
      '#required' => TRUE,
      '#default_value' => $post[0]->username,
    ];

    $form['fieldset']['upper']['left']['email'] = [
      '#type' => 'email',
      '#maxlength' => 100,
      '#title' => $this->t('E-mail'),
      '#description' => $this->t('Only letters, numbers and underscore in account name. Only letters and "." (dot) in domain.'),
      '#required' => TRUE,
      '#default_value' => $post[0]->email,
    ];

    $form['fieldset']['upper']['left']['phone'] = [
      '#type' => 'tel',
      '#maxlength' => 16,
      '#title' => $this->t('Phone number'),
      '#description' => $this->t('International format (+XXXXYYYYYYYYYYYY, X = 1-4 digits)'),
      '#required' => TRUE,
      '#resizable' => 'both',
      '#default_value' => $post[0]->phone,
    ];

    $form['fieldset']['upper']['right']['avatar'] = [
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
      '#default_value' => [$post[0]->avatar],
    ];

    $form['fieldset']['upper']['right']['picture'] = [
      '#type' => 'managed_file',
      '#title' => $this->t('Add picture to your feedback'),
      '#description' => $this->t('Only *.jpeg, *.jpg, *.png, up to 5MB.'),
      '#upload_location' => 'public://yuraul0/post',
      '#multiple' => FALSE,
      '#upload_validators' => [
        'file_validate_extensions' => ['png jpg jpeg'],
        'file_validate_size' => [5242880],
      ],
      '#default_value' => [$post[0]->picture],
    ];

    $form['fieldset']['message'] = [
      '#type' => 'textarea',
      '#maxlength' => 500,
      '#title' => $this->t('Your feedback message'),
      '#description' => $this->t('Up to 500 symbols.'),
      '#required' => TRUE,
      '#default_value' => $post[0]->message,
    ];

    $form['fieldset']['submit'] = [
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
    if (mb_strlen($form_state->getValue('message')) > 500) {
      $form_state->setErrorByName('phone', $this->t('Message is to long.'));
    }
  }

  /**
   * Saves userpic and post image and returns file URL.
   *
   * @param string $name
   *   The name of the form element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   An associative array containing the structure of the form.
   *
   * @return string
   *   Returns URL of saved file or empty string if file wasn't added.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  protected function savePics(string $name, FormStateInterface $form_state) {
    // Get file ID from the submitted form and save the file as entity.
    $fid = $form_state->getValue($name)[0] ?? NULL;
    if (!empty($fid)) {
      $file = File::load(($fid));
      $file->setPermanent();
      $file->save();
      return $file->id();
    }
    else {
      return ''; // TODO: Change returned default type after changing field in DB.
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

    // Adding posted time.
    $record['timestamp'] = time();

    // Saving received and validated data to database.
    Drupal::database()->insert('guestbook')->fields($record)->execute();

    // Setting message of succesful adding of feedback message.
    Drupal::messenger()->addMessage($this->t('Thank you @name for your feedback!', [
      '@name' => $form_state->getValue('username'),
    ]));
  }

  /**
   * AJAX submit callback.
   *
   * @param array $form
   *   An associative array containing the elements of the form.
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
      $ajax_response->addCommand(new RedirectCommand('/feedback')); // TODO: Check if a route name can be here
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
