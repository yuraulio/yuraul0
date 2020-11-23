<?php

namespace Drupal\yuraul0\Controller;

use Drupal;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Constructs a guestbook page and admin panel.
 */
class Yuraul0Controller {

  /**
   * Returns post data from database prepared to rendering.
   */
  protected function getFeedback() {
    // Get posts from database.
    $query = Drupal::database()->select('guestbook');
    $query->fields('guestbook', [
      'fid',
      'message',
      'picture',
      'timestamp',
      'username',
      'email',
      'phone',
      'avatar',
    ]);

    // Sort it newest first.
    $query->orderBy('guestbook.fid', 'DESC');
    $feedbacks = $query->execute()->fetchAll();

    // Setting default user profile picture of not exist.
    foreach ($feedbacks as $post) {
      if ($post->avatar === '') {
        $post->avatar = '/sites/default/files/yuraul0/user/default.png';
      }
      // And converting timestamp to human readable string.
      $post->timestamp = date('F/d/Y H:i:s', $post->timestamp);
    }
    // Deleting records if it's too much.
//    if (count($feedbacks) > 3) {
//      Drupal::database()->delete('guestbook')->execute();
//    }
    return $feedbacks;
  }

  /**
   * Builds the guestbook page.
   */
  public function feedback() {
    // Adding form for sending post to page.
    $page[] = ['form' => Drupal::formBuilder()->getForm('Drupal\yuraul0\Form\AddFeedback')];

    // Attaching style to the form.
    $page['form'][] = ['#attached' => ['library' => ['yuraul0/form']]];

    // Gettingg path to page template.
    $template = file_get_contents(__DIR__ . '/feedback.html.twig');
    $permission = \Drupal::currentUser()->hasPermission('administer site configuration');
    // Adding list of posts with the template to render.
    $page[] = [
      'feedbacks' => [
        '#type' => 'inline_template',
        '#template' => $template,
        '#context' => [
          'posts' => $this->getFeedback(),
          'can_edit' => $permission,
          'edit' => [
            '#type' => 'button',
            '#value' => 'Edit',
            '#ajax' => [
              'callback' => '::ajaxCallback',
              'event' => 'click',
              'progress' => [
                'type' => 'throbber',
                'message' => 'Editing...',
              ],
            ],
          ],
        ],
        '#attached' => [
          'library' => [
            'yuraul0/guestbook',
          ],
        ],
      ],
    ];
//    $query = \Drupal::entityQuery('file');
//    $storage = \Drupal::entityTypeManager()->getStorage('file');
//    $files = $storage->loadMultiple($query->execute());
//    foreach ($files as $f) {
//      if ($f->isPermanent()) {
//        $f->delete();
//      }
//    }

    return $page;
  }

  public function editPost($action, $id) {
    if (\Drupal::currentUser()->hasPermission('administer site configuration')) {
      switch ($action) {
        case 'edit':
          return [
            Drupal::formBuilder()->getForm('Drupal\yuraul0\Form\AddFeedback', $id),
            ['#attached' => ['library' => ['yuraul0/form']]],
          ];

        case 'delete':
          if (\Drupal::currentUser()->hasPermission('administer site configuration')) {
            $res = Drupal::database()
              ->delete('guestbook')
              ->condition('fid', "$id")
              ->execute();
            if ($res) {
              Drupal::messenger()->addMessage("Post $id successfully deleted!");
            }
            else {
              Drupal::messenger()->addError("Post #$id not found!");
            }
          }
      }
    }
    else {
      throw new AccessDeniedHttpException();
    }
    return $this->feedback();
  }

  public function admin() {
    return [
      '#type' => 'markup',
      '#markup' => t('<div style="color: red;">Admin!</div>'),
    ];
  }

}
