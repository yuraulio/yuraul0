<?php

/**
 * @file
 * Contains Drupal\yuraul0\Form\AddFeedback.
 *
 * Implements a form with AJAX validation for adding feedback
 */

namespace Drupal\yuraul0\Form;

use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\File\File;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Ajax\RedirectCommand;

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
    $form['system_messages'] = [
      '#markup' => '<div id="form-system-messages"></div>',
      '#weight' => -100,
    ];

    $form['username'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Your name'),
      '#required' => TRUE,
    ];

    $form['email'] = [
      '#type' => 'email',
      '#title' => $this->t('E-mail'),
//      '#required' => TRUE,
    ];

    $form['phone'] = [
      '#type' => 'tel',
      '#title' => $this->t('Phone number'),
//      '#required' => TRUE,
    ];

    $form['message'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Your feedback message'),
//      '#required' => TRUE,
    ];

    $form['avatar'] = [
      '#type' => 'managed_file',
      '#title' => $this->t('Add your profile picture'),
      '#upload_location' => 'public://yuraul0/user',
      '#multiple' => FALSE,
      '#upload_validators' => [
        'file_validate_is_image' => array(),
        'file_validate_extensions' => ['png jpg jpeg'],
        'file_validate_size' => [2097152],
      ],
    ];

    $form['picture'] = [
      '#type' => 'managed_file',
      '#title' => $this->t('Add picture to your feedback'),
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
   * @inheritDoc
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    if (strlen($form_state->getValue('username')) < 5) {
      $form_state->setErrorByName('username', $this->t('Name is too short.'));
    }
  }

  /**
   * @inheritDoc
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    //    $record = $form_state->getValues() + ['tmestamp',time()];
    \Drupal::database()->insert('guestbook')->fields([
      'username' => $form_state->getValue('username'),
      'message' => $form_state->getValue('message'),
      'timestamp' => time(),
    ])->execute();
    \Drupal::messenger()->addMessage($this->t('Thank you @name, your phone number is @number', [
      '@name' => $form_state->getValue('username'),
      '@number' => $form_state->getValue('phone'),
    ]));

  }

  /**
   * {@inheritdoc}
   */
  public function ajaxSubmitCallback(array &$form, FormStateInterface $form_state) {
    $ajax_response = new AjaxResponse();
    $message = [
      '#theme' => 'status_messages',
      '#message_list' => \Drupal::messenger()->all(),
      '#status_headings' => [
        'status' => t('Status message'),
        'error' => t('Error message'),
        'warning' => t('Warning message'),
      ],
    ];
    $messages = \Drupal::service('renderer')->render($message);
//    \Drupal::messenger()->deleteAll();
    $ajax_response->addCommand(new HtmlCommand('#form-system-messages', $messages));
//    if (count($form_state->getErrors() < 1)) {
//      $ajax_response->addCommand(new RedirectCommand('https://yuraul/'));
//    }
//    \Drupal::messenger()->deleteAll();
    return $ajax_response;
  }

}
