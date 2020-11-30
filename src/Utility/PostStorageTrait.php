<?php

namespace Drupal\yuraul0\Utility;


use Drupal\file\Entity\File;

/**
 * Trait to implement all job with storing data in DB and if filesystem.
 */
trait PostStorageTrait {

  /**
   * The name of database to work with.
   *
   * @var string
   */
  protected string $dbName = 'guestbook';

  /**
   * Fields of database record with feedback data.
   *
   * @var array
   */
  protected array $dbFields = [
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

  /**
   * Return module path.
   *
   * @return string
   *   A module path.
   */
  protected function getModulePath() {
    return drupal_get_path('module', $this->getModuleName());
  }

  /**
   * Save file entity permanently and set file usage by the module.
   *
   * @param string $fid
   *   The ID of the file entity to save.
   *
   * @return int
   *   Return ID of the saved entity or 0 if received ID was 0.
   */

  public function saveFile(string $fid) {
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

  /**
   * Delete file entity and usage by the mdole.
   *
   * @param string $fid
   *   The ID of the file entity to delete.
   */
  public function deleteFile(string $fid) {
    if (!empty($fid)) {
      try {
        $file = File::load($fid);
        \Drupal::service('file.usage')
          ->delete($file, $this->getModuleName(), 'file', $fid, 0);
        $file->delete();
      }
      catch (\Exception $e) {
        \Drupal::messenger()
          ->addError($this
            ->t("Can't delete file. Error message: @msg",
              ['@msg' => $e->getMessage()]));
      }
    }
  }

  /**
   * Return array of objects with the post data.
   *
   * If $postID not received, returns all records rom DB (newest first).
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

    // If $postID defined choose only one post with appropriate ID.
    if ($postID) {
      $query->condition('post_id', $postID);
    }

    // If requesting records from DB failed catching exception and
    // show its message to user.
    try {
      $posts = $query->execute()->fetchAll();
    }
    catch (\Exception $e) {
      \Drupal::messenger()
        ->addError($this
          ->t("Can't write  load from DB. Error: @msg",
            ['@msg' => $e->getMessage()]));
    }

    // Return FALSE if response from DB is empty,
    // so post with the $postID does not exists.
    return !empty($posts) ? $posts : FALSE;
  }

  /**
   * Save or update array with post data to DB and pictures in filesystem.
   *
   * If $postID received, update appropriate record in DB.
   * Either create a new record.
   *
   * @param array $post
   *   An array contains fields of DB record.
   * @param bool|string $postID
   *   ID of the post to update or FALSE if needs to create new record.
   *
   * @return bool|\Drupal\Core\Database\Query\Insert|\Drupal\Core\Database\Query\Update|\Drupal\Core\Database\StatementInterface|int|string
   *   A query result or FALSE if record needed to update not exist.
   */
  public function savePost(array $post, $postID = FALSE) {
    // Preparing query to update record with $postID.
    if ($postID) {
      $query = \Drupal::database()
        ->update('guestbook')
        ->fields($post)
        ->condition('post_id', $postID);
      try {
        $query = $query->execute();
      }
      catch (\Exception $e) {
        \Drupal::messenger()
          ->addError($this
            ->t("Can't write  to DB. Error: @msg"),
            ['@msg' => $e->getMessage()]);
      }
    }
    // Preparing query to create new record in DB.
    else {
      $query = \Drupal::database()
        ->insert('guestbook')
        ->fields($post);
      try {
        $query = $query->execute();
      }
      catch (\Exception $e) {
        \Drupal::messenger()
          ->addError($this
            ->t("Can't write  to DB. Error: @msg"),
              ['@msg' => $e->getMessage()]);
      }
    }
    // Save files in filesystem.
    $this->saveFile($post['avatar']);
    $this->saveFile($post['picture']);
    return $query ?? FALSE;
  }

  /**
   * Delete a record in DB and files if exist to post with $postID.
   *
   * @param string $postID
   *   The ID of the post.
   * @param string $avatarFID
   *   The ID of avatar file entity.
   * @param string $pictureFID
   *   The ID of post picture file entity.
   *
   * @return bool
   *   TRUE if deleting records from DB and files was successful or FALSE.
   */
  public function deletePost(string $postID, string $avatarFID, string $pictureFID) {
    // Delete user avatar and picture attached to the post
    // file entities if exist.
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
    // Delete the record from DB.
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

}
