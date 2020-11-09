<?php
/**
 * @file
 * Contains \Drupal\yuraul0\Controller\Yuraul0Controller.
 */
namespace Drupal\yuraul0\Controller;

class Yuraul0Controller {

  public function content() {
    return array(
      '#type' => 'markup',
      '#markup' => t('<div style="color: deepskyblue;">This is my module!</div>'),
    );
  }
}
