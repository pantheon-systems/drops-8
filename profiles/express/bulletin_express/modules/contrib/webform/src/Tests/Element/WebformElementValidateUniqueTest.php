<?php

namespace Drupal\webform\Tests\Element;

use Drupal\webform\Entity\Webform;
use Drupal\webform\Tests\WebformTestBase;

/**
 * Tests for webform validate unique.
 *
 * @group Webform
 */
class WebformElementValidateUniqueTest extends WebformTestBase {

  /**
   * Webforms to load.
   *
   * @var array
   */
  protected static $testWebforms = ['test_element_validate_unique'];

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    // Create users.
    $this->createUsers();
  }

  /**
   * Tests element validate unique.
   */
  public function testElementValidateUnique() {
    $this->drupalLogin($this->adminWebformUser);

    $webform = Webform::load('test_element_validate_unique');

    // Check #unique validation only allows one unique 'value' to be submitted.
    // Check #unique_error property is displayed.
    $sid = $this->postSubmission($webform);
    $this->assertNoRaw('The value <em class="placeholder">value</em> has already been submitted once for the <em class="placeholder">unique_textfield</em> element. You may have already submitted this webform, or you need to use a different value.');
    $this->assertNoRaw('This is a custom unique error message');
    $this->drupalPostForm('webform/test_element_validate_unique', [], t('Submit'));
    $this->assertRaw('The value <em class="placeholder">value</em> has already been submitted once for the <em class="placeholder">unique_textfield</em> element. You may have already submitted this webform, or you need to use a different value.');
    $this->assertRaw('This is a custom unique error message');

    // Check #unique element can be updated.
    $this->drupalPostForm("admin/structure/webform/manage/test_element_validate_unique/submission/$sid/edit", [], t('Save'));
    $this->assertNoRaw('The value <em class="placeholder">value</em> has already been submitted once for the <em class="placeholder">unique_textfield</em> element. You may have already submitted this webform, or you need to use a different value.');
    // @todo Determine why during testing the below assertion random fails and
    // returns 'New submission added to Test: Element: Validate Unique.'
    // $this->assertRaw('Submission updated in <em class="placeholder">Test: Element: Validate Unique</em>.');
  }

}
