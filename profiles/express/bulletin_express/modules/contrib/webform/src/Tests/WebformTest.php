<?php

namespace Drupal\webform\Tests;

/**
 * Tests for webform entity.
 *
 * @group Webform
 */
class WebformTest extends WebformTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['node', 'webform'];

  /**
   * Webforms to load.
   *
   * @var array
   */
  protected static $testWebforms = ['test_results'];

  /**
   * Webform submission storage.
   *
   * @var \Drupal\webform\WebformSubmissionStorageInterface
   */
  protected $submissionStorage;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    // Storage.
    $this->submissionStorage = \Drupal::entityTypeManager()->getStorage('webform_submission');
  }

  /**
   * Tests webform entity.
   */
  public function testWebform() {
    /** @var \Drupal\webform\WebformInterface $webform */
    list($webform) = $this->createWebformWithSubmissions();

    // Check get elements.
    $elements = $webform->getElementsInitialized();
    $this->assert(is_array($elements));

    // Check getElements.
    $columns = $webform->getElementsInitializedFlattenedAndHasValue();
    $this->assertEqual(array_keys($columns), ['first_name', 'last_name', 'sex', 'dob', 'node', 'colors', 'likert', 'address']);

    // Set invalid elements.
    $webform->set('elements', "not\nvalid\nyaml")->save();

    // Check invalid elements.
    $this->assertFalse($webform->getElementsInitialized());

    // Check invalid element columns.
    $this->assertEqual($webform->getElementsInitializedFlattenedAndHasValue(), []);

    // Check for 3 submissions..
    $this->assertEqual($this->submissionStorage->getTotal($webform), 3);

    // Check delete.
    $webform->delete();

    // Check all 3 submissions deleted.
    $this->assertEqual($this->submissionStorage->getTotal($webform), 0);

    // Check that 'test' state was deleted with the webform.
    $this->assertEqual(\Drupal::state()->get('webform.webform.' . $webform->id()), NULL);
  }

}
