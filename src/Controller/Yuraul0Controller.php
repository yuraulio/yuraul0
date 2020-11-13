<?php

/**
 * @file
 * Contains \Drupal\yuraul0\Controller\Yuraul0Controller.
 *
 *  Constructs a guestbook page
 */

namespace Drupal\yuraul0\Controller;

class Yuraul0Controller {

  /**
   * {@inheritdoc}
   */
  public function feedback() {

//    return [
//      '#markup' => 'Done',
//    ];

    return [
      '#type' => 'markup',
      '#markup' => t('<div style="color: deepskyblue;">This is my module!</div>'),
      'form' =>  \Drupal::formBuilder()->getForm('Drupal\yuraul0\Form\AddFeedback'),
    ];
  }

  public function admin() {
    return array(
      '#type' => 'markup',
      '#markup' => t('<div style="color: red;">Administrate it!</div>'),
    );
  }
}
