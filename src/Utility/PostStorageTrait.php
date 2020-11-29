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
   *   Array of objects with the post data or FALSE
   *   if post with the postID does not exist.
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
      \Drupal::messenger()->addError('Can\'t write  to DB. Error: ' . $e->getMessage());
    }
    return !empty($posts) ? $posts : FALSE;
  }

  public function savePost($post, $postID = FALSE) {
    if ($postID) {
      $query = \Drupal::database()
        ->update('guestbook')
        ->fields($post)
        ->condition('fid', $postID);
      try {
        $query = $query->execute();
      }
      catch (\Exception $e) {
        \Drupal::messenger()->addError('Can\'t write  to DB. Error: ' . $e->getMessage());
      }
    }
    else {
      $query = \Drupal::database()
        ->insert('guestbook')
        ->fields($post);
      $this->saveFile($post['avatar']);
      $this->saveFile($post['picture']);
      try {
        $query = $query->execute();
      }
      catch (\Exception $e) {
        \Drupal::messenger()->addError('Can\'t write  to DB. Error: ' . $e->getMessage());
      }
    }
    return $query ?? FALSE;
  }

  public function deletePost($postID, $avatarFID, $pictureFID) {
    try {
      if ($avatarFID !== '') {
        $this->deleteFile($avatarFID);
      }
      if ($pictureFID !== '') {
        $this->deleteFile($pictureFID);
      }
    }
    catch (\Exception $e) {
      \Drupal::messenger()
        ->addError($this->t(
          "Can't delete file. Error message: @msg"),
          ['@msg' => $e->getMessage()]
        );
    }
    try {
      $result = \Drupal::database()
        ->delete('guestbook')
        ->condition('fid', $postID)
        ->execute();
      return !empty($result);
    }
    catch (\Exception $e) {
      \Drupal::messenger()
        ->addError($this->t(
          "Can't delete record in DB. Error message: @msg"),
          ['@msg' => $e->getMessage()]
        );
    }
  }

  public function saveFile($fid) {
    if (!empty($fid)) {
      $file = File::load(($fid));
      $file->setPermanent();
      $file->save();
      \Drupal::service('file.usage')->add($file, 'yuraul0', 'file', $fid); // TODO: Insert module name programmatically
      return $file->id();
    }
    else {
      return ''; // TODO: Change returned default type after changing field in DB.
    }
  }

  public function deleteFile($fid) {
    if (!empty($fid)) {
      try {
        File::load($fid)->delete();
      }
      catch (\Exception $e) {
        \Drupal::messenger()
          ->addError($this->t("Can't delete file. Error message: @msg"), [
            '@msg' => $e->getMessage(),
          ]);
      }
    }
    return '';
  }

}
