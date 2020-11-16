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
      ];
      $messages = \Drupal::service('renderer')->render($message);
      $ajax_response->addCommand(new HtmlCommand('#form-system-messages', $messages));
    }
    return $ajax_response;
  }

}
