<?php

namespace Drupal\webform\Tests\Element;

use Drupal\webform\Tests\WebformTestBase;

/**
 * Tests for webform CodeMirror element.
 *
 * @group Webform
 */
class WebformElementCodeMirrorTest extends WebformTestBase {

  /**
   * Webforms to load.
   *
   * @var array
   */
  protected static $testWebforms = ['test_element_codemirror'];

  /**
   * Tests CodeMirror element.
   */
  public function testWebformElementCodeMirror() {

    /**************************************************************************/
    // code:text
    /**************************************************************************/

    // Check Text.
    $this->drupalGet('webform/test_element_codemirror');
    $this->assertRaw('<label for="edit-text-basic">Text basic</label>');
    $this->assertRaw('<textarea data-drupal-selector="edit-text-basic" class="js-webform-codemirror webform-codemirror text form-textarea resize-vertical" data-webform-codemirror-mode="text/plain" id="edit-text-basic" name="text_basic" rows="5" cols="60"></textarea>');

    /**************************************************************************/
    // code:yaml
    /**************************************************************************/

    // Check YAML.
    $this->drupalGet('webform/test_element_codemirror');
    $this->assertRaw('<label for="edit-yaml-basic">YAML basic</label>');
    $this->assertRaw('<textarea data-drupal-selector="edit-yaml-basic" class="js-webform-codemirror webform-codemirror yaml form-textarea resize-vertical" data-webform-codemirror-mode="text/x-yaml" id="edit-yaml-basic" name="yaml_basic" rows="5" cols="60"></textarea>');

    // Check associative array as the #default_value.
    $this->drupalPostForm('webform/test_element_codemirror', [], t('Submit'));
    $this->assertRaw('yaml_array:
  one: One
  two: Two
  three: Three');

    // Check invalid YAML.
    $edit = [
      'yaml_basic' => "'not: valid",
    ];
    $this->drupalPostForm('webform/test_element_codemirror', $edit, t('Submit'));
    $this->assertRaw('<em class="placeholder">YAML basic</em> is not valid.');

    // Check valid YAML.
    $edit = [
      'yaml_basic' => 'is: valid',
    ];
    $this->drupalPostForm('webform/test_element_codemirror', $edit, t('Submit'));
    $this->assertNoRaw('<em class="placeholder">YAML basic</em> is not valid.');

    /**************************************************************************/
    // code:html
    /**************************************************************************/

    // Check HTML.
    $this->drupalGet('webform/test_element_codemirror');
    $this->assertRaw('<label for="edit-html-basic">HTML basic</label>');
    $this->assertRaw('<textarea data-drupal-selector="edit-html-basic" class="js-webform-codemirror webform-codemirror html form-textarea resize-vertical" data-webform-codemirror-mode="text/html" id="edit-html-basic" name="html_basic" rows="5" cols="60"></textarea>');

    // Check invalid HTML.
    $edit = [
      'html_basic' => "<b>bold</bold>",
    ];
    $this->drupalPostForm('webform/test_element_codemirror', $edit, t('Submit'));
    $this->assertRaw('<em class="placeholder">HTML basic</em> is not valid.');
    $this->assertRaw('expected &#039;&gt;&#039;');

    // Check valid HTML.
    $edit = [
      'html_basic' => '<b>bold</b>',
    ];
    $this->drupalPostForm('webform/test_element_codemirror', $edit, t('Submit'));
    $this->assertNoRaw('<em class="placeholder">HTML basic</em> is not valid.');
    $this->assertNoRaw('expected &#039;&gt;&#039;');
  }

}
