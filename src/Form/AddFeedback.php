<?php

namespace Drupal\yuraul0\Form;

use ClassesWithParents\D;
use Drupal;
use Drupal\Core\Url;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Ajax\RedirectCommand;
use Drupal\file\Entity\File;
use Drupal\yuraul0\Utility\PostStorageTrait;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Implements a form with AJAX validation for adding feedback.
 */
class AddFeedback extends FormBase {

  use PostStorageTrait;

  /**
   * Just return the form ID.
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
  public function buildForm(array $form, FormStateInterface $form_state, $post = FALSE) {
    $form['fieldset'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Add feedback'),
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
    ];

    $form['fieldset']['upper']['left']['email'] = [
      '#type' => 'email',
      '#maxlength' => 100,
      '#title' => $this->t('E-mail'),
      '#description' => $this->t('Only letters, numbers and underscore in account name. Only letters and "." (dot) in domain.'),
      '#required' => TRUE,
    ];

    $form['fieldset']['upper']['left']['phone'] = [
      '#type' => 'tel',
      '#maxlength' => 16,
      '#title' => $this->t('Phone number'),
      '#description' => $this->t('International format (+XXXXYYYYYYYYYYYY, X = 1-4 digits)'),
      '#required' => TRUE,
      '#resizable' => 'both',
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
    ];

    $form['fieldset']['message'] = [
      '#type' => 'textarea',
      '#maxlength' => 500,
      '#title' => $this->t('Your feedback message'),
      '#description' => $this->t('Up to 500 symbols.'),
      '#required' => TRUE,
    ];

    $form['fieldset']['actions'] = [
      '#type' => 'container',
      '#attributes' => [
        'class' => [
          'actions',
        ],
      ],
    ];

    $form['fieldset']['actions']['save'] = [
      '#type' => 'submit',
      '#name' => 'add',
      '#button_type' => 'primary',
      '#value' => $this->t('Send feedback'),
      '#ajax' => [
        'callback' => '::showMessages',
        'event' => 'click',
        'progress' => [
          'type' => 'throbber',
        ],
      ],
    ];

    // Attaching style to the form.
    $form[] = ['#attached' => ['library' => ['yuraul0/form']]];
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
   * Validates some form fields.
   *
   * @param array $form
   *   An associative array containing the elements of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   An associative array containing the structure of the form.
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // Deleting all messages if stayed from previous validation.
    Drupal::messenger()->deleteByType('error');

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
    switch ($form_state->getTriggeringElement()['#name']) {
      case 'add':
        if ($this->add($form_state)) {
          // Setting message of succesful adding of feedback message.
          Drupal::messenger()
            ->addMessage($this->t('Thank you @name for your feedback!', [
              '@name' => $form_state->getValue('username'),
            ]));
        }
        break;

      case 'update':
        if ($this->update($form_state)) {
          // Setting message of successful editing of the post.
          Drupal::messenger()
            ->addMessage($this->t('Post with ID @postID was successfully updated!', [
              '@postID' => $form_state->getBuildInfo()['args'][0][0]->fid,
            ]));
        }
        break;

      case 'delete':
        if ($this->delete($form_state)) {
          // Setting message of successful editing of the post.
          Drupal::messenger()
            ->addMessage($this->t('Post with ID @postID was successfully deleted!', [
              '@postID' => $form_state->getBuildInfo()['args'][0][0]->fid,
            ]));
        }
    }
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
  public function showMessages(array &$form, FormStateInterface $form_state) {
    $ajax_response = new AjaxResponse();
    // If there are no validation errors sending response with redirect
    // to feedback page.
    if (!$form_state->hasAnyErrors()) {
      $url = Url::fromRoute('yuraul0.feedback')->toString();
      $ajax_response->addCommand(new RedirectCommand($url));
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

  protected function prepareToSave(FormStateInterface $form_state) {
    foreach ($form_state->getValues() as $key => $value) {
      if (in_array($key, $this->dbFields)) {
        $post[$key] = $value;
      }
    }
    $post['avatar'] = $post['avatar'][0] ?? '';
    $post['picture'] = $post['picture'][0] ?? '';
    return $post;
  }

  public function add(FormStateInterface $form_state) {
    $post = $this->prepareToSave($form_state);
    $post['timestamp'] = time();
    return $this->savePost($post);
  }

  public function update(FormStateInterface $form_state) {
    $old = $form_state->getBuildInfo()['args'][0][0];
    $post = $this->prepareToSave($form_state);
    if ($old->avatar != $post['avatar']) {
      $this->deleteFile($old->avatar);
    }
    if ($old->picture != $post['picture']) {
      $this->deleteFile($old->picture);
    }
    $post['timestamp'] = $old->timestamp;
    return $this->savePost($post, $old->fid);
  }

  public function delete(FormStateInterface $form_state) {
    $post = $form_state->getBuildInfo()['args'][0][0] ?? FALSE;
    return $this->deletePost($post->fid, $post->avatar, $post->picture);
  }

}

