<?php

namespace Drupal\field_group\Tests;

use Drupal\Core\Url;
use Drupal\simpletest\WebTestBase;

/**
 * Test field_group without field_ui.
 *
 * @group field_group
 */
class FieldGroupWithoutFieldUiTest extends WebTestBase {

  protected static $modules = ['field_group', 'block'];

  public function testLocalActions() {
    // Local actions of field_group should not depend on field_ui
    // @see https://www.drupal.org/node/2719569
    $this->placeBlock('local_actions_block', ['id' => 'local_actions_block']);
    $this->drupalGet(Url::fromRoute('user.login'));
  }

}
