<?php

namespace Drupal\Tests\webform\Functional\Element;

/**
 * Tests for element more.
 *
 * @group Webform
 */
class WebformElementMoreTest extends WebformElementBrowserTestBase {

  /**
   * Webforms to load.
   *
   * @var array
   */
  protected static $testWebforms = ['test_element_more'];

  /**
   * Test element more.
   */
  public function testMore() {
    $this->drupalGet('/webform/test_element_more');

    // Check more element.
    $this->assertRaw('<div data-drupal-selector="edit-more" id="webform-element-more" class="js-webform-element-more webform-element-more">');
    $this->assertRaw('<div class="webform-element-more--link"><a role="button" href="#webform-element-more--content">More</a></div>');
    $this->assertRaw('<div id="webform-element-more--content" class="webform-element-more--content">{This is an example of more}</div>');

    // Check textfield default more.
    $this->assertRaw('<div id="edit-more-textfield--more" class="js-webform-element-more webform-element-more">');
    $this->assertRaw('<div class="webform-element-more--link"><a role="button" href="#edit-more-textfield--more--content">More</a></div>');

    // Check textfield more with custom title.
    $this->assertRaw('<div id="edit-more-textfield-title--more" class="js-webform-element-more webform-element-more">');
    $this->assertRaw('<div class="webform-element-more--link"><a role="button" href="#edit-more-textfield-title--more--content">{Custom more title}</a></div>');

    // Check textfield more with HTML markup.
    $this->assertRaw('<div id="edit-more-textfield-html--more" class="js-webform-element-more webform-element-more">');
    $this->assertRaw('<div id="edit-more-textfield-html--more--content" class="webform-element-more--content">{This is an example of more with <b>HTML markup</b>}</div>');

    // Check textfield more with description.
    $this->assertRaw('<div id="edit-more-textfield-title-desc--description" class="webform-element-description">{This is an example of a description}</div>');
    $this->assertRaw('<div id="edit-more-textfield-title-desc--more" class="js-webform-element-more webform-element-more">');

    // Check more with hidden description.
    $this->assertRaw('<div id="edit-more-textfield-title-desc-hidden--description" class="webform-element-description visually-hidden">{This is an example of a hidden description}</div>');
    $this->assertRaw('<div id="edit-more-textfield-title-desc-hidden--more" class="js-webform-element-more webform-element-more">');

    // Check datetime more.
    $this->assertRaw('<div id="edit-more-datetime--more" class="js-webform-element-more webform-element-more">');

    // Check fieldset more.
    $this->assertRaw('<div id="edit-more-fieldset--description" class="webform-element-description">{This is a description}</div>');
    $this->assertRaw('<div id="edit-more-fieldset--more" class="js-webform-element-more webform-element-more">');

    // Check details more.
    $this->assertRaw('<div id="edit-more-details--more" class="js-webform-element-more webform-element-more">');
    $this->assertRaw('<div class="webform-element-more--link"><a role="button" href="#edit-more-details--more--content">More</a></div>');

    // Check tooltip ignored more.
    $this->assertRaw('<div id="edit-more-tooltip--description" class="webform-element-description visually-hidden">{This is a description}</div>');
    $this->assertRaw('<div id="edit-more-tooltip--more" class="js-webform-element-more webform-element-more">');
  }

}
