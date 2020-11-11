<?php

/**
 * @file
 * Contains Drupal\yuraul0\Form\DefaultUserPicture.
 *
 * Implements a form for setting default user profile picture
 */

namespace Drupal\yuraul0\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

class DefaultUserPicture extends ConfigFormBase {

  /**
   * @inheritDoc
   */
  public function getFormId() {
    return 'set_user_pic';
  }

  /**
   * @inheritDoc
   */
  protected function getEditableConfigNames() {
    $pic = file_get_contents(preg_match('/^default_userpic\.()/s'));
  }

}
