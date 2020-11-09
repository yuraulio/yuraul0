<?php
/**
 * @file
 * Contains \Drupal\yuraul0\Controller\Yuraul0AdminController.
 */
namespace Drupal\yuraul0\Controller;

class Yuraul0AdminController {

  public function content() {
    return array(
      '#type' => 'markup',
      '#markup' => t('<div style="color: red;">Administrate it!</div>'),
    );
  }
}
