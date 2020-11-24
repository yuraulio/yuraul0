<?php

namespace Drupal\yuraul0\Controller;

use Drupal;
use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Constructs a guestbook page and admin panel.
 */
class Yuraul0Controller extends ControllerBase {
  // Fields of post DB record.
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
   * Returns post data from database prepared to rendering.
   */
  protected function getFeedback() {
    // Get posts from database.
    $query = Drupal::database()->select('guestbook');
    $query->fields('guestbook', self::FIELDS);

    // Sort it newest first.
    $query->orderBy('guestbook.fid', 'DESC');
    $feedbacks = $query->execute()->fetchAll();

    // Setting default user profile picture of not exist.
    foreach ($feedbacks as $post) {
      if ($post->avatar === '') { // TODO: Change type after changing avatar field in DB.
        $post->avatar = '/sites/default/files/yuraul0/user/default.png';
      }
      else {
        // Converting avatar file ID to URL.
        $post->avatar = Drupal::entityTypeManager()
          ->getStorage('file')
          ->load($post->avatar)
          ->createFileUrl();
      }

      // Converting post picture file ID to URL.
      if ($post->picture !== '') {
        $post->picture = Drupal::entityTypeManager()
          ->getStorage('file')
          ->load($post->picture)
          ->createFileUrl();
      }
      // And converting timestamp to human readable string.
      $post->timestamp = date('F/d/Y H:i:s', $post->timestamp);
    }

    return $feedbacks;
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
          'posts' => $this->getFeedback(),
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

  public function editPost($action, $postID = FALSE) {
    if (Drupal::currentUser()->hasPermission('administer site configuration')) {
      switch ($action) {
        case 'edit':
          return [
            Drupal::formBuilder()->getForm('Drupal\yuraul0\Form\AddFeedback', $postID),
            [
              '#attached' => [
                'library' => [
                  'yuraul0/form',
                ],
              ],
            ],
          ];

        case 'delete':
          // Trying to get the post record from DB.
          $record = Drupal::database()
            ->select('guestbook')
            ->fields('guestbook', self::FIELDS)
            ->condition('fid', $postID)
            ->execute()->fetchAll();
          // If record for this post ID exists delete uploaded pictures and
          // the record from DB.
          if ($record) {
            // Delete avatar file if exists.
            if ($record[0]->avatar !== '') {
              Drupal::entityTypeManager()
                ->getStorage('file')
                ->load($record[0]->avatar)
                ->delete();
            }
            // Delete post picture file if exists.
            if ($record[0]->picture !== '') {
              Drupal::entityTypeManager()
                ->getStorage('file')
                ->load($record[0]->picture)
                ->delete();
            }
            // Delete record from DB.
            $res = Drupal::database()
              ->delete('guestbook')
              ->condition('fid', "$postID")
              ->execute();
            // Setting the message about successful deleting of the post.
            Drupal::messenger()->addMessage("Post $postID successfully deleted!");
          }
          // Setting the error message if post with post ID was not found.
          else {
            Drupal::messenger()->addError("Post #$postID not found!");
          }
      }
    }
    // Throwing exception if user has no permissions to edit.
    else {
      throw new AccessDeniedHttpException();
    }
    return $this->redirect('yuraul0.feedback');
  }

  public function admin() {
    return [
      '#type' => 'markup',
      '#markup' => t('<div style="color: red;">Admin!</div>'),
    ];
  }

}
