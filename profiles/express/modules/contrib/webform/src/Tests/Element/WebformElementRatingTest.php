<?php

namespace Drupal\webform\Tests\Element;

use Drupal\webform\Tests\WebformTestBase;

/**
 * Tests for rating element.
 *
 * @group Webform
 */
class WebformElementRatingTest extends WebformTestBase {

  /**
   * Webforms to load.
   *
   * @var array
   */
  protected static $testWebforms = ['test_element_rating'];

  /**
   * Test rating element.
   */
  public function testRating() {
    $this->drupalGet('webform/test_element_rating');

    // Check basic rating display.
    $this->assertRaw('<label for="edit-rating-basic">Rating basic</label>');
    $this->assertRaw('<input data-drupal-selector="edit-rating-basic" type="range" id="edit-rating-basic" name="rating_basic" value="0" step="1" min="0" max="5" class="form-webform-rating" />');
    $this->assertRaw('<div class="rateit svg rateit-medium" data-rateit-min="0" data-rateit-max="5" data-rateit-step="1" data-rateit-resetable="false" data-rateit-readonly="false" data-rateit-backingfld="#edit-rating-basic" data-rateit-value="" data-rateit-starheight="24" data-rateit-starwidth="24">');

    // Check advanced rating display.
    $this->assertRaw('<label for="edit-rating-advanced">Rating advanced</label>');
    $this->assertRaw('<input data-drupal-selector="edit-rating-advanced" type="range" id="edit-rating-advanced" name="rating_advanced" value="0" step="0.1" min="0" max="10" class="form-webform-rating" />');
    $this->assertRaw('<div class="rateit svg rateit-large" data-rateit-min="0" data-rateit-max="10" data-rateit-step="0.1" data-rateit-resetable="true" data-rateit-readonly="false" data-rateit-backingfld="#edit-rating-advanced" data-rateit-value="" data-rateit-starheight="32" data-rateit-starwidth="32">');

    // Check processing.
    $edit = [
      'rating_basic' => '4',
    ];
    $this->drupalPostForm('webform/test_element_rating', $edit, t('Submit'));
    $this->assertRaw("rating_basic: '4'");
  }

}
