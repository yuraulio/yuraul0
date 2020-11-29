<?php

namespace Drupal\yuraul0\Controller;

use Drupal;
use Drupal\Core\Controller\ControllerBase;
use Drupal\file\Entity\File;
use Drupal\yuraul0\Utility\PostStorageTrait;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Constructs a guestbook page and admin panel.
 */
class Yuraul0Controller extends ControllerBase {

  use PostStorageTrait;

  protected function prepareForRender ($posts) {
    if ($posts) {
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
    }
    return $posts ?? FALSE;
  }

  /**
   * Builds the guestbook page.
   */
  public function feedback() {
    // Getting path to page template.
    $template = file_get_contents(__DIR__ . '/feedback.html.twig');

    $permission = Drupal::currentUser()->hasPermission('administer site configuration');
    // Adding list of posts with the template to render.
    $page['posts'] = [
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
    ];
    return $page;
  }

  public function show() {
    // Adding form for sending post to the page.
    $page[] = ['form' => Drupal::formBuilder()->getForm('Drupal\yuraul0\Form\AddFeedback')];

    // Adding feedback list to the page.
    $page[] = $this->feedback();

    return $page;
}
  public function edit($postID) {
    if (Drupal::currentUser()->hasPermission('administer site configuration')) {
      $post = $this->getPosts($postID);
      if ($post) {
        return [
          'form' => Drupal::formBuilder()->getForm(
            'Drupal\yuraul0\Form\AddFeedback',
            $post),
        ];
      }
      // Setting the error message if post with post ID was not found.
      else {
        Drupal::messenger()->addError("Post #$postID not found!"); // TODO: Wrap in t().
        return $this->show();
      }
    }
    else {
      throw new AccessDeniedHttpException();
    }
  }

  public function admin() {
    return [
      '#type' => 'markup',
      '#markup' => t('<div style="color: red;">Admin!</div>'),
    ];
  }

}
