<?php

namespace Drupal\yuraul0\Controller;

use Drupal;
use Drupal\Core\Controller\ControllerBase;
use Drupal\yuraul0\Utility\PostStorageTrait;

/**
 * Constructs a guestbook page and admin panel.
 */
class Yuraul0Controller extends ControllerBase {

  use PostStorageTrait;

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
          'posts' => $this->getPosts(),
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

  public function admin() {
    return [
      '#type' => 'markup',
      '#markup' => t('<div style="color: red;">Admin!</div>'),
    ];
  }

}
