<?php

namespace Drupal\webform\Tests\Settings;

use Drupal\Core\Serialization\Yaml;
use Drupal\webform\Entity\Webform;
use Drupal\webform\Tests\WebformTestBase;
use Drupal\webform\WebformInterface;

/**
 * Tests for webform path and page.
 *
 * @group Webform
 */
class WebformSettingsPathTest extends WebformTestBase {

  public static $modules = ['path', 'webform'];

  /**
   * Tests YAML page and title.
   */
  public function testPaths() {
    $webform = Webform::create([
      'langcode' => 'en',
      'status' => WebformInterface::STATUS_OPEN,
      'id' => 'test_paths',
      'title' => 'test_paths',
      'elements' => Yaml::encode([
        'test' => ['#markup' => 'test'],
      ]),
    ]);
    $webform->save();

    // Check default system submit path.
    $this->drupalGet('webform/' . $webform->id());
    $this->assertResponse(200, 'Submit system path exists');

    // Check default alias submit path.
    $this->drupalGet('form/' . str_replace('_', '-', $webform->id()));
    $this->assertResponse(200, 'Submit URL alias exists');

    // Check default alias confirm path.
    $this->drupalGet('form/' . str_replace('_', '-', $webform->id()) . '/confirmation');
    $this->assertResponse(200, 'Confirm URL alias exists');

    // Check page hidden (i.e. access denied).
    $webform->setSettings(['page' => FALSE])->save();
    $this->drupalGet('webform/' . $webform->id());
    $this->assertResponse(403, 'Submit system path access denied');
    $this->drupalGet('form/' . str_replace('_', '-', $webform->id()));
    $this->assertResponse(404, 'Submit URL alias does not exist');

    // Check hidden page visible to admin.
    $this->drupalLogin($this->rootUser);
    $this->drupalGet('webform/' . $webform->id());
    $this->assertResponse(200, 'Submit system path access permitted');
    $this->drupalLogout();

    // Check custom submit and confirm path.
    $webform->setSettings(['page' => TRUE, 'page_submit_path' => 'page_submit_path', 'page_confirm_path' => 'page_confirm_path'])->save();
    $this->drupalGet('page_submit_path');
    $this->assertResponse(200, 'Submit system path access permitted');
    $this->drupalGet('page_confirm_path');
    $this->assertResponse(200, 'Submit URL alias access permitted');

    // Check custom base path.
    $webform->setSettings(['page_submit_path' => '', 'page_confirm_path' => ''])->save();
    $this->drupalLogin($this->rootUser);
    $this->drupalPostForm('admin/structure/webform/config', ['page_settings[default_page_base_path]' => 'base/path'], t('Save configuration'));
    $this->drupalGet('base/path/' . str_replace('_', '-', $webform->id()));
    $this->assertResponse(200, 'Submit URL alias with custom base path exists');
    $this->drupalGet('base/path/' . str_replace('_', '-', $webform->id()) . '/confirmation');
    $this->assertResponse(200, 'Confirm URL alias with custom base path exists');

    // Check custom base path delete if accessing webform as page is disabled.
    $webform->setSettings(['page' => FALSE])->save();
    $this->drupalGet('base/path/' . str_replace('_', '-', $webform->id()));
    $this->assertResponse(404, 'Submit URL alias does not exist.');
    $this->drupalGet('base/path/' . str_replace('_', '-', $webform->id()) . '/confirmation');
    $this->assertResponse(404, 'Confirm URL alias does not exist.');
  }

}
