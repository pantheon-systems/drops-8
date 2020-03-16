<?php

namespace Drupal\Tests\metatag\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Verify that node translation form works.
 *
 * @group metatag
 */
class NodeTranslation extends BrowserTestBase {

  // Contains helper methods.
  use MetatagHelperTrait;

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    // Modules for core functionality.
    'language',
    'node',
    'field_ui',
    'user',

    // Contrib dependencies.
    'token',

    // This module.
    'metatag',

    // The extra module(s) to test.
    'content_translation',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Login.
    $this->loginUser1();

    // Add language.
    $this->drupalGet('/admin/config/regional/language/add');
    $this->assertResponse(200);
    $edit = [
      'predefined_langcode' => 'hu',
    ];
    $this->drupalPostForm(NULL, $edit, 'Add language');

    // Set up a content type.
    $this->drupalCreateContentType(['type' => 'article']);
    $this->drupalGet('/admin/structure/types/manage/article');
    $this->assertResponse(200);
    $edit = [
      'language_configuration[content_translation]' => TRUE,
    ];
    $this->drupalPostForm(NULL, $edit, 'Save content type');
  }

  /**
   * Load the custom route, make sure something is output.
   */
  public function testContentTranslationForm() {
    $this->drupalGet('/admin/config/regional/content-language');
    $this->assertResponse(200);
    $this->assertText('Content language');
    $this->drupalPostForm(NULL, [], 'Save configuration');
    $this->assertResponse(200);
    $this->assertText('Settings successfully updated.');
  }

}
