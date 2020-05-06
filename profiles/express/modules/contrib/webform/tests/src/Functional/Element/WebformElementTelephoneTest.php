<?php

namespace Drupal\Tests\webform\Functional\Element;

/**
 * Tests for telephone element.
 *
 * @group Webform
 */
class WebformElementTelephoneTest extends WebformElementBrowserTestBase {


  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['webform', 'telephone_validation'];

  /**
   * Webforms to load.
   *
   * @var array
   */
  protected static $testWebforms = ['test_element_telephone'];

  /**
   * Test telephone element.
   */
  public function testRating() {
    $this->drupalGet('/webform/test_element_telephone');

    // Check basic tel.
    $this->assertRaw('<input data-drupal-selector="edit-tel-default" type="tel" id="edit-tel-default" name="tel_default" value="" size="30" maxlength="128" class="form-tel" />');

    // Check international tel.
    $this->assertRaw('<input class="js-webform-telephone-international webform-webform-telephone-international form-tel" data-drupal-selector="edit-tel-international" type="tel" id="edit-tel-international" name="tel_international" value="" size="30" maxlength="128" />');

    // Check international telephone valddation.
    $this->assertRaw('<input data-drupal-selector="edit-tel-validation-e164" type="tel" id="edit-tel-validation-e164" name="tel_validation_e164" value="" size="30" maxlength="128" class="form-tel" />');

    // Check USE telephone validation.
    $this->assertRaw('<input data-drupal-selector="edit-tel-validation-national" aria-describedby="edit-tel-validation-national--description" type="tel" id="edit-tel-validation-national" name="tel_validation_national" value="" size="30" maxlength="128" class="form-tel" />');

    // Check telephone validation missing plus sign.
    $edit = [
      'tel_validation_e164' => '12024561111',
      'tel_validation_national' => '12024561111',
    ];
    $this->drupalPostForm('/webform/test_element_telephone', $edit, t('Submit'));
    $this->assertRaw('The phone number <em class="placeholder">12024561111</em> is not valid.');

    // Check telephone validation with plus sign.
    $edit = [
      'tel_validation_e164' => '+12024561111',
      'tel_validation_national' => '+12024561111',
    ];
    $this->drupalPostForm('/webform/test_element_telephone', $edit, t('Submit'));
    $this->assertNoRaw('The phone number <em class="placeholder">12024561111</em> is not valid.');

    // Check telephone validation with non US number.
    $edit = [
      'tel_validation_national' => '+74956970349',
    ];
    $this->drupalPostForm('/webform/test_element_telephone', $edit, t('Submit'));
    $this->assertRaw('The phone number <em class="placeholder">+74956970349</em> is not valid.');
  }

}
