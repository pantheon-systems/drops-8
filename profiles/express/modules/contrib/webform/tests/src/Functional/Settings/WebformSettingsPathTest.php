<?php

namespace Drupal\Tests\webform\Functional\Settings;

use Drupal\Core\Serialization\Yaml;
use Drupal\webform\Entity\Webform;
use Drupal\Tests\webform\Functional\WebformBrowserTestBase;
use Drupal\webform\WebformInterface;

/**
 * Tests for webform path and page.
 *
 * @group Webform
 */
class WebformSettingsPathTest extends WebformBrowserTestBase {

  public static $modules = ['path', 'webform', 'node'];

  /**
   * Tests YAML page and title.
   */
  public function testPaths() {
    /** @var \Drupal\Core\Path\AliasStorageInterface $alias_storage */
    $alias_storage = $this->container->get('path.alias_storage');

    $node = $this->drupalCreateNode();

    /**************************************************************************/
    // With paths.
    /**************************************************************************/

    $webform = Webform::create([
      'langcode' => 'en',
      'status' => WebformInterface::STATUS_OPEN,
      'id' => 'test_paths',
      'title' => 'test_paths',
      'elements' => Yaml::encode([
        'test' => ['#markup' => 'test'],
      ]),
    ]);
    $webform->setSetting('draft', WebformInterface::DRAFT_ALL);
    $webform->save();
    $webform_path = '/webform/' . $webform->id();
    $form_path = '/form/' . str_replace('_', '-', $webform->id());

    // Check paths.
    $this->drupalLogin($this->rootUser);

    // Check that aliases exist.
    $this->assert(is_array($alias_storage->load(['alias' => $form_path])));
    $this->assert(is_array($alias_storage->load(['alias' => "$form_path/confirmation"])));
    $this->assert(is_array($alias_storage->load(['alias' => "$form_path/drafts"])));
    $this->assert(is_array($alias_storage->load(['alias' => "$form_path/submissions"])));

    // Check default system submit path.
    $this->drupalGet($webform_path);
    $this->assertResponse(200, 'Submit system path exists');

    // Check default alias submit path.
    $this->drupalGet($form_path);
    $this->assertResponse(200, 'Submit URL alias exists');

    // Check default alias confirm path.
    $this->drupalGet("$form_path/confirmation");
    $this->assertResponse(200, 'Confirm URL alias exists');

    // Check default alias drafts path.
    $this->drupalGet("$form_path/drafts");
    $this->assertResponse(200, 'Drafts URL alias exists');

    // Check default alias submissions path.
    $this->drupalGet("$form_path/submissions");
    $this->assertResponse(200, 'Submissions URL alias exists');

    $this->drupalLogout();

    // Disable paths for the webform.
    $webform->setSettings(['page' => FALSE])->save();

    // Check that aliases do not exist.
    $this->assertFalse($alias_storage->load(['alias' => $form_path]));
    $this->assertFalse($alias_storage->load(['alias' => "$form_path/confirmation"]));
    $this->assertFalse($alias_storage->load(['alias' => "$form_path/drafts"]));
    $this->assertFalse($alias_storage->load(['alias' => "$form_path/submissions"]));

    // Check page hidden (i.e. access denied).
    $this->drupalGet($webform_path);
    $this->assertResponse(403, 'Submit system path access denied');
    $this->assertNoRaw('Only webform administrators are allowed to access this page and create new submissions.');
    $this->drupalGet($form_path);
    $this->assertResponse(404, 'Submit URL alias does not exist');

    // Check page hidden with source entity.
    $this->drupalGet($webform_path, ['query' => ['source_entity_type' => 'node', 'source_entity_id' => $node->id()]]);
    $this->assertResponse(403, 'Submit system path access denied');

    // Check page visible with source entity.
    $webform->setSettings(['form_prepopulate_source_entity' => TRUE])->save();
    $this->drupalGet($webform_path, ['query' => ['source_entity_type' => 'node', 'source_entity_id' => $node->id()]]);
    $this->assertResponse(200, 'Submit system path exists');

    // Check hidden page visible to admin.
    $this->drupalLogin($this->rootUser);
    $this->drupalGet($webform_path);
    $this->assertResponse(200, 'Submit system path access permitted');
    $this->assertRaw('Only webform administrators are allowed to access this page and create new submissions.');
    $this->drupalLogout();

    // Check custom submit and confirm path.
    $webform->setSettings(['page' => TRUE, 'page_submit_path' => 'page_submit_path', 'page_confirm_path' => 'page_confirm_path'])->save();
    $this->drupalGet('/page_submit_path');
    $this->assertResponse(200, 'Submit system path access permitted');
    $this->drupalGet('/page_confirm_path');
    $this->assertResponse(200, 'Submit URL alias access permitted');

    // Check custom base path.
    $webform->setSettings(['page_submit_path' => '', 'page_confirm_path' => ''])->save();
    $this->drupalLogin($this->rootUser);
    $this->drupalPostForm('/admin/structure/webform/config', ['page_settings[default_page_base_path]' => 'base/path'], t('Save configuration'));
    $this->drupalGet('/base/path/' . str_replace('_', '-', $webform->id()));
    $this->assertResponse(200, 'Submit URL alias with custom base path exists');
    $this->drupalGet('/base/path/' . str_replace('_', '-', $webform->id()) . '/confirmation');
    $this->assertResponse(200, 'Confirm URL alias with custom base path exists');

    // Check custom base path delete if accessing webform as page is disabled.
    $webform->setSettings(['page' => FALSE])->save();
    $this->drupalGet('/base/path/' . str_replace('_', '-', $webform->id()));
    $this->assertResponse(404, 'Submit URL alias does not exist.');
    $this->drupalGet('/base/path/' . str_replace('_', '-', $webform->id()) . '/confirmation');
    $this->assertResponse(404, 'Confirm URL alias does not exist.');

    // Disable automatic generation of paths.
    \Drupal::configFactory()->getEditable('webform.settings')
      ->set('settings.default_page_base_path', '')
      ->save();

    /**************************************************************************/
    // Without paths.
    /**************************************************************************/

    $webform = Webform::create([
      'langcode' => 'en',
      'status' => WebformInterface::STATUS_OPEN,
      'id' => 'test_no_paths',
      'title' => 'test_no_paths',
      'elements' => Yaml::encode([
        'test' => ['#markup' => 'test'],
      ]),
    ]);
    $webform->save();
    $webform_path = '/webform/' . $webform->id();
    $form_path = '/form/' . str_replace('_', '-', $webform->id());

    // Check default system submit path.
    $this->drupalGet($webform_path);
    $this->assertResponse(200, 'Submit system path exists');

    // Check no default alias submit path.
    $this->drupalGet($form_path);
    $this->assertResponse(404, 'Submit URL alias does not exist');

    /**************************************************************************/
    // Admin theme.
    /**************************************************************************/

    $this->drupalLogin($this->rootUser);

    $webform = Webform::create([
      'langcode' => 'en',
      'status' => WebformInterface::STATUS_OPEN,
      'id' => 'test_admin_theme',
      'title' => 'test_admin_theme',
      'elements' => Yaml::encode([
        'test' => ['#markup' => 'test'],
      ]),
    ]);
    $webform->save();

    // Check that admin theme is not applied.
    $this->drupalGet('/webform/test_admin_theme');
    $this->assertNoRaw('seven');

    // Install Seven and set it as the default admin theme.
    \Drupal::service('theme_handler')->install(['seven']);

    $edit = [
      'admin_theme' => 'seven',
      'use_admin_theme' => TRUE,
    ];
    $this->drupalPostForm('/admin/appearance', $edit, t('Save configuration'));
    $webform->setSetting('page_admin_theme', TRUE)->save();

    // Check that admin theme is applied.
    $this->drupalGet('/webform/test_admin_theme');
    $this->assertRaw('seven');
  }

}
