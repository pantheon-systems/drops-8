<?php

namespace Drupal\Tests\webform\Functional\Element;

use Drupal\Tests\TestFileCreationTrait;
use Drupal\webform\Entity\Webform;
use Drupal\webform\Entity\WebformSubmission;

/**
 * Tests for webform element prepopulate.
 *
 * @group Webform
 */
class WebformElementPrepopulateTest extends WebformElementBrowserTestBase {

  use TestFileCreationTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['file', 'webform'];

  /**
   * Webforms to load.
   *
   * @var array
   */
  protected static $testWebforms = ['test_element_prepopulate'];

  /**
   * Test element prepopulate.
   */
  public function testElementPrepopulate() {
    $webform = Webform::load('test_element_prepopulate');

    $files = $this->getTestFiles('text');

    // Check default value of elements on multiple_values.
    $this->drupalGet('/webform/test_element_prepopulate');
    $this->assertFieldByName('textfield_01', '');
    $this->assertFieldByName('textfield_prepopulate_01', '{default_value_01}');
    $this->assertFieldByName('files[managed_file_prepopulate_01]', '');
    $this->drupalPostForm('/webform/test_element_prepopulate', [], t('Next Page >'));
    $this->assertFieldByName('textfield_02', '');
    $this->assertFieldByName('textfield_prepopulate_02', '{default_value_02}');

    // Check 'textfield' can not be prepopulated.
    $this->drupalGet('/webform/test_element_prepopulate', ['query' => ['textfield_01' => 'value']]);
    $this->assertNoFieldByName('textfield_0', 'value');

    // Check prepopulating textfield on multiple pages.
    $options = [
      'query' => [
        'textfield_prepopulate_01' => 'value_01',
        'textfield_prepopulate_02' => 'value_02',
      ],
    ];
    $this->drupalGet('/webform/test_element_prepopulate', $options);
    $this->assertFieldByName('textfield_prepopulate_01', 'value_01');
    $this->drupalPostForm('/webform/test_element_prepopulate', [], t('Next Page >'), $options);
    $this->assertFieldByName('textfield_prepopulate_02', 'value_02');

    // Check prepopulating textfield on multiple pages and changing the value
    $options = [
      'query' => [
        'textfield_prepopulate_01' => 'value_01',
        'textfield_prepopulate_02' => 'value_02',
      ],
    ];
    $this->drupalGet('/webform/test_element_prepopulate', $options);
    $this->assertFieldByName('textfield_prepopulate_01', 'value_01');
    $this->drupalPostForm('/webform/test_element_prepopulate', ['textfield_prepopulate_01' => 'edit_01'], t('Next Page >'), $options);
    $this->assertFieldByName('textfield_prepopulate_02', 'value_02');
    $this->drupalPostForm(NULL, [], t('< Previous Page'), $options);
    $this->assertNoFieldByName('textfield_prepopulate_01', 'value_01');
    $this->assertFieldByName('textfield_prepopulate_01', 'edit_01');

    // Check 'managed_file_prepopulate' can not be prepopulated.
    // The #prepopulate property is not available to managed file elements.
    // @see \Drupal\webform\Plugin\WebformElement\WebformManagedFileBase::defaultProperties
    $edit = [
      'files[managed_file_prepopulate_01]' => \Drupal::service('file_system')->realpath($files[0]->uri),
    ];
    $this->drupalPostForm('/webform/test_element_prepopulate', $edit, t('Next Page >'));
    $this->drupalPostForm(NULL, [], t('Submit'));
    $sid = $this->getLastSubmissionId($webform);
    $webform_submission = WebformSubmission::load($sid);
    $fid = $webform_submission->getElementData('managed_file_prepopulate_01');
    $this->drupalGet('/webform/test_element_prepopulate', ['query' => ['managed_file_prepopulate_01' => $fid]]);
    $this->assertFieldByName('files[managed_file_prepopulate_01]', '');
  }

}
