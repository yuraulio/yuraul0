<?php

namespace Drupal\yuraul0\Utility;

use Drupal\file\Entity\File;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

trait PostStorageTrait {
  protected $dbName = 'guestbook';
  protected $dbFields = [
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
   * Return array of objects with the post data ready to render.
   *
   * Get one (if postID received) or all entries from DB and prepare some fields
   * to rendering.
   *
   * @param bool $postID
   *   The ID of the needed post.
   *
   * @return bool
   *   If post with the postID does not exist.
   */
  public function getPosts($postID = FALSE) {
    $query = \Drupal::database()->select($this->dbName);
    $query->fields('guestbook', $this->dbFields);
    $query->orderBy('guestbook.fid', 'DESC');

    if ($postID) {
      $query->condition('fid', $postID);
    }

    try {
      $posts = $query->execute()->fetchAll();
    }
    catch (\Exception $e) {
      \Drupal::messenger()->addError('Shit happens!');
    }

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
    return $posts ?? FALSE;
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
          // If record for this post ID exists delete uploaded pictures and
          // the record from DB.
          if ($record) {
            // Delete avatar file if exists.
            if ($record[0]->avatar !== '') {
              File::load($record[0]->avatar)->delete();
            }
            // Delete post picture file if exists.
            if ($record[0]->picture !== '') {
              File::load($record[0]->picture)->delete();
            }
            // Delete record from DB.
            $res = Drupal::database() // TODO: Do something with it or delete.
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

}
