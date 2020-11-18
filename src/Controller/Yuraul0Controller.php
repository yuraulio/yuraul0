<?php

namespace Drupal\yuraul0\Controller;

use Drupal\Core\File\FileSystemInterface;

/**
 * Constructs a guestbook page.
 */
class Yuraul0Controller {

  /**
   * {@inheritdoc}
   */
  public function feedback() {
    $query = \Drupal::database()->select('guestbook');
    $query->fields('guestbook', ['fid', 'username', 'avatar']);
    $query->orderBy('guestbook.fid', 'DESC');
    $result = $query->execute()->fetchAll();


//    <li>$user->message</li>
    foreach ($result as $user) {
      if ($user->avatar === '') {
        $av = '/sites/default/files/yuraul0/user/default.png';
      }
      else {
        $av = $user->avatar;
      }
      $users[] = [
        '#type' => 'markup',
        '#markup' => "
          <ul>
            <li>$user->username</li>
            <li><img src='$av' width='100' height='100'></li>
          </ul>",
      ];
    }
    $users[] = [
      '#type' => 'markup',
      '#markup' => t('<div style="color: deepskyblue;">This is my module!</div>'),
      'form' =>  \Drupal::formBuilder()->getForm('Drupal\yuraul0\Form\AddFeedback'),
    ];
    //Deleting records if it's too much
    if (count($result) > 3) {
      \Drupal::database()->delete('guestbook')->execute();
    }

//    $query = \Drupal::entityQuery('file');
//    $storage = \Drupal::entityTypeManager()->getStorage('file');
//    $files = $storage->loadMultiple($query->execute());
//    foreach ($files as $f) {
//      if ($f->isPermanent()) {
//        $f->delete();
//      }
//    }
    return $users;
  }

  public function admin() {
    return array(
      '#type' => 'markup',
      '#markup' => t('<div style="color: red;">Administrate it!</div>'),
    );
  }
}
