<?php

namespace Drupal\yuraul0\Controller;

use Drupal;

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
    if (count($feedbacks) > 3) {
      Drupal::database()->delete('guestbook')->execute();
    }
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

    // Adding list of posts with the template to render.
    $page[] = [
      'feedbacks' => [
        '#type' => 'inline_template',
        '#template' => $template,
        '#context' => [
          'posts' => $this->getFeedback(),
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

  public function admin() {
    return array(
      '#type' => 'markup',
      '#markup' => t('<div style="color: red;">Administrate it!</div>'),
    );
  }
}
