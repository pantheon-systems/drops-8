<?php

namespace Drupal\webform\Tests\Element;

use Drupal\webform\Tests\WebformTestBase;
use Drupal\webform\Entity\Webform;

/**
 * Tests for webform date element.
 *
 * @group Webform
 */
class WebformElementDateTest extends WebformTestBase {

  /**
   * Webforms to load.
   *
   * @var array
   */
  protected static $testWebforms = ['test_element_date'];

  /**
   * Test date element.
   */
  public function testDateElement() {
    $webform = Webform::load('test_element_date');

    $this->drupalGet('webform/test_element_date');

    // Check '#format' values.
    $this->assertFieldByName('date_default', '2009-08-18');

    // Check dynamic date picker.
    $min = date('D, m/d/Y', strtotime('-1 year'));
    $min_year = date('Y', strtotime('-1 year'));
    $max = date('D, m/d/Y', strtotime('+1 year'));
    $max_year = date('Y', strtotime('+1 year'));
    $default_value = date('D, m/d/Y', strtotime('now'));
    $this->assertRaw('<input min="' . $min . '" data-min-year="' . $min_year . '" max="' . $max . '" data-max-year="' . $max_year . '" type="text" data-drupal-date-format="D, m/d/Y" data-drupal-selector="edit-date-datepicker-min-max-dynamic" aria-describedby="edit-date-datepicker-min-max-dynamic--description" id="edit-date-datepicker-min-max-dynamic" name="date_datepicker_min_max_dynamic" value="' . $default_value . '" class="form-text" />');

    // Check 'datelist' and 'datetime' #default_value.
    $form = $webform->getSubmissionForm();
    $this->assert(is_string($form['elements']['date_default']['#default_value']), 'date_default #default_value is a string.');

    // Check date #max validation.
    $edit = ['date_min_max' => '2010-08-18'];
    $this->drupalPostForm('webform/test_element_date', $edit, t('Submit'));
    $this->assertRaw('<em class="placeholder">date_min_max</em> must be on or before <em class="placeholder">2009-12-31</em>.');

    // Check date #min validation.
    $edit = ['date_min_max' => '2006-08-18'];
    $this->drupalPostForm('webform/test_element_date', $edit, t('Submit'));
    $this->assertRaw('<em class="placeholder">date_min_max</em> must be on or after <em class="placeholder">2009-01-01</em>.');

    // Check dynamic date.
    $this->drupalGet('webform/test_element_date');
    $min = \Drupal::service('date.formatter')->format(strtotime('-1 year'), 'html_date');
    $min_year = date('Y', strtotime('-1 year'));
    $max = \Drupal::service('date.formatter')->format(strtotime('+1 year'), 'html_date');
    $max_year = date('Y', strtotime('+1 year'));
    $default_value = \Drupal::service('date.formatter')->format(strtotime('now'), 'html_date');
    $this->assertRaw('<input min="' . $min . '" data-min-year="' . $min_year . '" max="' . $max . '" data-max-year="' . $max_year . '" type="date" data-drupal-selector="edit-date-min-max-dynamic" aria-describedby="edit-date-min-max-dynamic--description" data-drupal-date-format="Y-m-d" id="edit-date-min-max-dynamic" name="date_min_max_dynamic" value="' . $default_value . '" class="form-date" />');
  }

}
