<?php

namespace Drupal\entity_browser\Tests;

use Drupal\file\Entity\File;
use Drupal\simpletest\WebTestBase;

/**
 * Tests the entity browser UI.
 *
 * @group entity_browser
 */
class EntityBrowserUITest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'entity_browser_test',
    'ctools',
    'views',
    'block',
  ];

  /**
   * Tests entity browser UI.
   */
  public function testEntityBrowserUI() {
    $account = $this->drupalCreateUser([
      'administer entity browsers',
      'access test_entity_browser_iframe entity browser pages',
    ]);
    $this->drupalLogin($account);
    // Go to the entity browser iframe link.
    $this->drupalGet('/entity-browser/iframe/test_entity_browser_iframe');
    $this->assertRaw('Select');
    $this->drupalGet('/admin/config/content/entity_browser/test_entity_browser_iframe/widgets');
    $edit = [
      'table[871dbf77-012e-41cb-b32a-ada353d2de35][form][submit_text]' => 'Different',
    ];
    $this->drupalPostForm(NULL, $edit, 'Finish');
    $this->drupalGet('/entity-browser/iframe/test_entity_browser_iframe');
    $this->assertRaw('Different');
  }

  /**
   * Tests entity browser token support for upload widget.
   */
  public function testEntityBrowserToken() {
    $this->container->get('module_installer')->install(['token', 'file']);
    $account = $this->drupalCreateUser([
      'access test_entity_browser_token entity browser pages',
    ]);
    $this->drupalLogin($account);
    // Go to the entity browser iframe link.
    $this->drupalGet('/entity-browser/iframe/test_entity_browser_token');
    $image = current($this->drupalGetTestFiles('image'));
    $edit = [
      'files[upload][]' => $this->container->get('file_system')->realpath($image->uri),
    ];
    $this->drupalPostForm(NULL, $edit, 'Select files');

    $file = File::load(1);
    // Test entity browser token that has upload location configured to
    // public://[current-user:account-name]/.
    $this->assertEqual($file->getFileUri(), 'public://' . $account->getUsername() . '/' . $file->getFilename(), 'Image has the correct uri.');
  }

}
