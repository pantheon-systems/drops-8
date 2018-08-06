<?php

namespace Drupal\webform\Tests\Settings;

use Drupal\webform\Entity\Webform;
use Drupal\webform\Entity\WebformSubmission;
use Drupal\webform\Tests\WebformTestBase;

/**
 * Tests for webform prepopulate settings.
 *
 * @group Webform
 */
class WebformSettingsPrepopulateTest extends WebformTestBase {

  /**
   * Webforms to load.
   *
   * @var array
   */
  protected static $testWebforms = ['test_form_prepopulate'];

  /**
   * Tests webform setting including confirmation.
   */
  public function testPrepopulate() {

    /**************************************************************************/
    /* Test webform prepopulate (form_prepopulate) */
    /**************************************************************************/

    $webform_prepopulate = Webform::load('test_form_prepopulate');

    // Check prepopulation of an element.
    $this->drupalGet('webform/test_form_prepopulate', ['query' => ['name' => 'John', 'colors' => ['red', 'white']]]);
    $this->assertFieldByName('name', 'John');
    $this->assertFieldChecked('edit-colors-red');
    $this->assertFieldChecked('edit-colors-white');
    $this->assertNoFieldChecked('edit-colors-blue');

    $this->drupalGet('webform/test_form_prepopulate', ['query' => ['name' => 'John', 'colors' => 'red']]);
    $this->assertFieldByName('name', 'John');
    $this->assertFieldChecked('edit-colors-red');
    $this->assertNoFieldChecked('edit-colors-white');
    $this->assertNoFieldChecked('edit-colors-blue');

    // Check disabling prepopulation of an element.
    $webform_prepopulate->setSetting('form_prepopulate', FALSE);
    $webform_prepopulate->save();
    $this->drupalGet('webform/test_form_prepopulate', ['query' => ['name' => 'John']]);
    $this->assertFieldByName('name', '');

    /**************************************************************************/
    /* Test webform prepopulate source entity (form_prepopulate_source_entity) */
    /**************************************************************************/

    // Check prepopulating source entity.
    $this->drupalPostForm('webform/test_form_prepopulate', [], t('Submit'), ['query' => ['source_entity_type' => 'webform', 'source_entity_id' => 'contact']]);
    $sid = $this->getLastSubmissionId($webform_prepopulate);
    $webform_submission = WebformSubmission::load($sid);
    $this->assertNotNull($webform_submission->getSourceEntity());
    if ($webform_submission->getSourceEntity()) {
      $this->assertEqual($webform_submission->getSourceEntity()->getEntityTypeId(), 'webform');
      $this->assertEqual($webform_submission->getSourceEntity()->id(), 'contact');
    }

    // Check disabling prepopulation source entity.
    $webform_prepopulate->setSetting('form_prepopulate_source_entity', FALSE);
    $webform_prepopulate->save();
    $this->drupalPostForm('webform/test_form_prepopulate', [], t('Submit'), ['query' => ['source_entity_type' => 'webform', 'source_entity_id' => 'contact']]);
    $sid = $this->getLastSubmissionId($webform_prepopulate);
    $webform_submission = WebformSubmission::load($sid);
    $this->assert(!$webform_submission->getSourceEntity());

    // Set prepopulated source entity required.
    $webform_prepopulate->setSetting('form_prepopulate_source_entity', TRUE);
    $webform_prepopulate->setSetting('form_prepopulate_source_entity_required', TRUE);
    $webform_prepopulate->save();

    // Check required prepopulated source entity displays error when no source
    // entity is defined.
    $this->drupalGet('webform/test_form_prepopulate');
    $this->assertRaw('This webform is not available. Please contact the site administrator.');

    // Check required prepopulated source entity displays error when invalid
    // source entity is defined.
    $this->drupalGet('webform/test_form_prepopulate', ['query' => ['source_entity_type' => 'webform', 'source_entity_id' => 'DOES_NOT_EXIST']]);
    $this->assertRaw('This webform is not available. Please contact the site administrator.');

    // Check required prepopulated source entity loads when source entity is
    // valid.
    $this->drupalGet('webform/test_form_prepopulate', ['query' => ['source_entity_type' => 'webform', 'source_entity_id' => 'contact']]);
    $this->assertNoRaw('This webform is not available. Please contact the site administrator.');

    // Set prepopulated source entity type to user.
    $webform_prepopulate->setSetting('form_prepopulate_source_entity_type', 'user');
    $webform_prepopulate->save();

    // Check invalid source entity type displays error.
    $this->drupalGet('webform/test_form_prepopulate', ['query' => ['source_entity_type' => 'webform', 'source_entity_id' => 'contact']]);
    $this->assertRaw('This webform is not available. Please contact the site administrator.');

    // Set prepopulated source entity type to webform.
    $webform_prepopulate->setSetting('form_prepopulate_source_entity_type', 'webform');
    $webform_prepopulate->save();

    // Check invalid source entity type displays error.
    $this->drupalGet('webform/test_form_prepopulate', ['query' => ['source_entity_type' => 'webform', 'source_entity_id' => 'contact']]);
    $this->assertNoRaw('This webform is not available. Please contact the site administrator.');
  }

}
