<?php

namespace Drupal\yuraul0\Controller;

use Drupal;
use Drupal\Core\Controller\ControllerBase;
use Drupal\file\Entity\File;
use Drupal\yuraul0\Utility\PostStorageTrait;

/**
 * Constructs a guestbook page and admin panel.
 */
class Yuraul0Controller extends ControllerBase {

  use PostStorageTrait;

  protected function prepareForRender ($posts) {
    // Setting default user profile picture of not exist.
    foreach ($posts as $post) {
      if ($post->avatar === '') { // TODO: Change type after changing avatar field in DB.
        $post->avatar = '/sites/default/files/yuraul0/user/default.png';
      }
      else {
        // Converting avatar file ID to URL.
        $post->avatar = File::load($post->avatar)->createFileUrl();
      }

      // Converting post picture file ID to URL.
      if ($post->picture !== '') {
        $post->picture = File::load($post->picture)->createFileUrl();
      }
      // And converting timestamp to human readable string.
      $post->timestamp = date('F/d/Y H:i:s', $post->timestamp);
    }
    return $posts;
  }

  /**
   * Builds the guestbook page.
   */
  public function feedback() {
    // Adding form for sending post to the page.
    $page[] = ['form' => Drupal::formBuilder()->getForm('Drupal\yuraul0\Form\AddFeedback')];

    // Attaching style to the form.
    $page['form'][] = ['#attached' => ['library' => ['yuraul0/form']]];

    // Getting path to page template.
    $template = file_get_contents(__DIR__ . '/feedback.html.twig');

    $permission = Drupal::currentUser()->hasPermission('administer site configuration');
    // Adding list of posts with the template to render.
    $page[] = [
      'feedbacks' => [
        '#type' => 'inline_template',
        '#template' => $template,
        '#context' => [
          'posts' => $this->prepareForRender($this->getPosts()),
          'can_edit' => $permission,
        ],
        '#attached' => [
          'library' => [
            'yuraul0/guestbook',
          ],
        ],
      ],
    ];
    return $page;
  }

  public function admin() {
    return [
      '#type' => 'markup',
      '#markup' => t('<div style="color: red;">Admin!</div>'),
    ];
  }

}
