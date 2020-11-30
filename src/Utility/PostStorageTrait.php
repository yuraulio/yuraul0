<?php

namespace Drupal\yuraul0\Utility;

use Drupal\file\Entity\File;

trait PostStorageTrait {
  protected $dbName = 'guestbook';
  protected $dbFields = [
    'post_id',
    'message',
    'picture',
    'timestamp',
    'username',
    'email',
    'phone',
    'avatar',
  ];

  /**
   * Name of our module.
   *
   * @return string
   *   A module name.
   */
  abstract protected function getModuleName();

  protected function getModulePath() {
    $path = drupal_get_path('module', $this->getModuleName());
    return $path;
  }

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
    $query->orderBy('guestbook.post_id', 'DESC');

    if ($postID) {
      $query->condition('post_id', $postID);
    }

    try {
      $posts = $query->execute()->fetchAll();
    }
    catch (\Exception $e) {
      \Drupal::messenger()->addError($this->t("Can't write  load from DB. Error: @msg", ['@msg' => $e->getMessage()]));
    }
    return !empty($posts) ? $posts : FALSE;
  }

  public function savePost($post, $postID = FALSE) {
    if ($postID) {
      $query = \Drupal::database()
        ->update('guestbook')
        ->fields($post)
        ->condition('post_id', $postID);
      $this->saveFile($post['avatar']);
      $this->saveFile($post['picture']);
      try {
        $query = $query->execute();
      }
      catch (\Exception $e) {
        \Drupal::messenger()->addError($this->t("Can't write  to DB. Error: @msg"). ['@msg' => $e->getMessage()]);
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
        \Drupal::messenger()->addError($this->t("Can't write  to DB. Error: @msg"). ['@msg' => $e->getMessage()]);
      }
    }
    return $query ?? FALSE;
  }

  public function deletePost($postID, $avatarFID, $pictureFID) {
    try {
      if ($avatarFID != '0') {
        $this->deleteFile($avatarFID);
      }
      if ($pictureFID != '0') {
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
        ->condition('post_id', $postID)
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
      \Drupal::service('file.usage')
        ->add($file, $this->getModuleName(), 'file', $fid);
      return $file->id();
    }
    else {
      return 0;
    }
  }

  public function deleteFile($fid) {
    if (!empty($fid)) {
      try {
        $file = File::load($fid);
        \Drupal::service('file.usage')
          ->delete($file, $this->getModuleName(), 'file', $fid, 0);
        $file->delete();
      }
      catch (\Exception $e) {
        \Drupal::messenger()
          ->addError($this->t("Can't delete file. Error message: @msg"), [
            '@msg' => $e->getMessage(),
          ]);
      }
    }
    return 0;
  }

}
