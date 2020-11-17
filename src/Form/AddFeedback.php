<?php

namespace Drupal\yuraul0\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Ajax\RedirectCommand;

/**
 * Implements a form with AJAX validation for adding feedback.
 */
class AddFeedback extends FormBase {

  /**
   * @inheritDoc
   */
  public function getFormId() {
    return 'add_feedback';
  }

  /**
   * @inheritDoc
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    \Drupal::messenger()->deleteByType('error');
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
//      '#required' => TRUE,
    ];
//
    $form['phone'] = [
      '#type' => 'tel',
      '#title' => $this->t('Phone number'),
      '#description' => $this->t('International format (+XXXXYYYYYYYYYYYY, X = 1-4 digits)'),
//      '#required' => TRUE,
    ];

    $form['message'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Your feedback message'),
      '#description' => $this->t('Up to 500 symbols.'),
//      '#required' => TRUE,
    ];
//
//    $form['avatar'] = [
//      '#type' => 'managed_file',
//      '#title' => $this->t('Add your profile picture'),
//      '#description' => $this->t('Only *.jpeg, *.jpg, *.png, up to 2MB.'),
//      '#upload_location' => 'public://yuraul0/user',
//      '#multiple' => FALSE,
//      '#upload_validators' => [
//        'file_validate_is_image' => [],
//        'file_validate_extensions' => ['png jpg jpeg'],
//        'file_validate_size' => [2097152],
//      ],
//    ];
//
//    $form['picture'] = [
//      '#type' => 'managed_file',
//      '#title' => $this->t('Add picture to your feedback'),
//      '#description' => $this->t('Only *.jpeg, *.jpg, *.png, up to 5MB.'),
//      '#upload_location' => 'public://yuraul0/post',
//      '#multiple' => FALSE,
//      '#upload_validators' => [
//        'file_validate_extensions' => ['png jpg jpeg'],
//        'file_validate_size' => [5242880],
//      ],
//    ];

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
   * @inheritDoc
   */
  protected function checkUsername (FormStateInterface $form_state) {
    $username = preg_match('/^([a-zA-Z][a-zA-Z_0-9]{0,99})|([a-zA-Z])$/s', $form_state->getValue('username'));
    if ($username === 0) {
      $form_state->setErrorByName('username', $this->t('Name is incorrect.'));
    }
  }

  /**
   * @inheritDoc
   */
  protected function checkEmail (FormStateInterface $form_state) {
    $email = preg_match('/^([a-zA-Z0-9](?!.*(\.\.).*)[a-zA-Z0-9\-\.]*[a-zA-Z0-9]+)|([a-zA-Z0-9])@([a-zA-Z0-9]+((?!.*(\.\.).*)(?!.*(--).*)(?!.*(\.-).*)(?!.*(-\.).*))([a-zA-Z0-9\-\.]*)[a-zA-Z0-9])|([a-zA-Z0-9])$/s', $form_state->getValue('email'));
    if ($email === 0) {
      $form_state->setErrorByName('email', $this->t('Email is incorrect.'));
    }
  }

  protected function checkPhone (FormStateInterface $formState) {
    $phone = preg_match('/^+\d{1,4}\d{8,15}$/s', $formState->getValue('phone'));
    if ( $phone === 0) {
      $formState->setErrorByName('phone', $this->t('Bad phone number'));
    }
  }

  /**
   * @inheritDoc
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    \Drupal::messenger()->deleteAll();
    $v = $form_state->getValues();
    $this->checkUsername($form_state);
    $this->checkEmail($form_state);
    $this->checkPhone($form_state);

  }

  /**
   * @inheritDoc
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    \Drupal::database()->insert('guestbook')->fields([
      'username' => $form_state->getValue('username'),
      'email' => $form_state->getValue('email'),
      'message' => '',
      'timestamp' => time(),
    ])->execute();
    \Drupal::messenger()->addMessage($this->t('Thank you @name, for your feedback!', [
      '@name' => $form_state->getValue('username'),
    ]));

  }

  /**
   * {@inheritdoc}
   */
  public function ajaxSubmitCallback(array &$form, FormStateInterface $form_state) {
    $ajax_response = new AjaxResponse();
    if (count($form_state->getErrors()) === 0) {
      $current_path = \Drupal::service('path.current')->getPath();
      $ajax_response->addCommand(new RedirectCommand($current_path));
      \Drupal::messenger()->deleteByType('error');
    }
    else {
      $message = [
        '#theme' => 'status_messages',
        '#message_list' => \Drupal::messenger()->all(),
        '#status_headings' => [
          'status' => t('Status message'),
          'error' => t('Error message'),
          'warning' => t('Warning message'),
        ],
        '#marckup' => time(),
      ];
      $messages = \Drupal::service('renderer')->render($message);
      $ajax_response->addCommand(new HtmlCommand('#form-system-messages', $messages));
    }
    return $ajax_response;
  }

}
