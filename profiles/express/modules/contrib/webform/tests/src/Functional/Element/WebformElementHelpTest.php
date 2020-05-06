<?php

namespace Drupal\Tests\webform\Functional\Element;

/**
 * Tests for element help.
 *
 * @group Webform
 */
class WebformElementHelpTest extends WebformElementBrowserTestBase {

  /**
   * Webforms to load.
   *
   * @var array
   */
  protected static $testWebforms = ['test_element_help'];

  /**
   * Test element help.
   */
  public function testHelp() {
    $this->drupalGet('/webform/test_element_help');

    // Check basic help.
    $this->assertRaw('<label for="edit-help">help<span class="webform-element-help" role="tooltip" tabindex="0" data-webform-help="&lt;div class=&quot;webform-element-help--title&quot;&gt;help&lt;/div&gt;&lt;div class=&quot;webform-element-help--content&quot;&gt;{This is an example of help}&lt;/div&gt;"><span aria-hidden="true">?</span></span>');

    // Check help with required.
    $this->assertRaw('<label for="edit-help-required" class="js-form-required form-required">help_required<span class="webform-element-help" role="tooltip" tabindex="0" data-webform-help="&lt;div class=&quot;webform-element-help--title&quot;&gt;help_required&lt;/div&gt;&lt;div class=&quot;webform-element-help--content&quot;&gt;{This is an example of help for a required element}&lt;/div&gt;"><span aria-hidden="true">?</span></span>');

    // Check help with custom title.
    $this->assertRaw('<label for="edit-help-title">help_title<span class="webform-element-help" role="tooltip" tabindex="0" data-webform-help="&lt;div class=&quot;webform-element-help--title&quot;&gt;{Help custom title}&lt;/div&gt;&lt;div class=&quot;webform-element-help--content&quot;&gt;{This is an example of help with a custom help title}&lt;/div&gt;"><span aria-hidden="true">?</span></span>');

    // Check help with HTML markup.
    $this->assertRaw('<label for="edit-help-html">help_html<span class="webform-element-help" role="tooltip" tabindex="0" data-webform-help="&lt;div class=&quot;webform-element-help--title&quot;&gt;help_html&lt;/div&gt;&lt;div class=&quot;webform-element-help--content&quot;&gt;{This is an example of help with &lt;b&gt;HTML markup&lt;/b&gt;}&lt;/div&gt;"><span aria-hidden="true">?</span></span>');

    // Check help with XSS.
    $this->assertRaw('<label for="edit-help-xss">help_xss<span class="webform-element-help" role="tooltip" tabindex="0" data-webform-help="&lt;div class=&quot;webform-element-help--title&quot;&gt;help_xss&lt;/div&gt;&lt;div class=&quot;webform-element-help--content&quot;&gt;{This is an example of help with &lt;b&gt;XSS alert(&quot;XSS&quot;)&lt;/b&gt;}&lt;/div&gt;"><span aria-hidden="true">?</span></span>');

    // Check help with inline title.
    $this->assertRaw('<label for="edit-help-checkbox" class="option">help_checkbox<span class="webform-element-help" role="tooltip" tabindex="0" data-webform-help="&lt;div class=&quot;webform-element-help--title&quot;&gt;help_checkbox&lt;/div&gt;&lt;div class=&quot;webform-element-help--content&quot;&gt;{This is an example of help}&lt;/div&gt;"><span aria-hidden="true">?</span></span>');
    $this->assertRaw('<label for="edit-help-inline"><span class="webform-element-help" role="tooltip" tabindex="0" data-webform-help="&lt;div class=&quot;webform-element-help--title&quot;&gt;help_inline&lt;/div&gt;&lt;div class=&quot;webform-element-help--content&quot;&gt;{This is an example of help with an inline title}&lt;/div&gt;"><span aria-hidden="true">?</span></span>');

    // Check radios (fieldset).
    $this->assertRaw('<span class="fieldset-legend">help_radios<span class="webform-element-help" role="tooltip" tabindex="0" data-webform-help="&lt;div class=&quot;webform-element-help--title&quot;&gt;help_radios&lt;/div&gt;&lt;div class=&quot;webform-element-help--content&quot;&gt;{This is an example of help for radio buttons}&lt;/div&gt;"><span aria-hidden="true">?</span></span>');

    // Check fieldset.
    $this->assertRaw('<span class="fieldset-legend">help_radios<span class="webform-element-help" role="tooltip" tabindex="0" data-webform-help="&lt;div class=&quot;webform-element-help--title&quot;&gt;help_radios&lt;/div&gt;&lt;div class=&quot;webform-element-help--content&quot;&gt;{This is an example of help for radio buttons}&lt;/div&gt;"><span aria-hidden="true">?</span></span>');

    // Check details.
    $this->assertRaw('<summary role="button" aria-controls="edit-help-details" aria-expanded="false" aria-pressed="false">help_details<span class="webform-element-help" role="tooltip" tabindex="0" data-webform-help="&lt;div class=&quot;webform-element-help--title&quot;&gt;help_details&lt;/div&gt;&lt;div class=&quot;webform-element-help--content&quot;&gt;{This is an example of help for a details element}&lt;/div&gt;"><span aria-hidden="true">?</span>');

    // Check section.
    $this->assertRaw('<h2 class="webform-section-title">help_section<span class="webform-element-help" role="tooltip" tabindex="0" data-webform-help="&lt;div class=&quot;webform-element-help--title&quot;&gt;help_section&lt;/div&gt;&lt;div class=&quot;webform-element-help--content&quot;&gt;{This is an example of help for a section element}&lt;/div&gt;"><span aria-hidden="true">?</span></span>');

    // Check help display title after.
    $this->assertRaw('<label for="edit-help-after-title">help_after_title<span class="webform-element-help" role="tooltip" tabindex="0" data-webform-help="&lt;div class=&quot;webform-element-help--title&quot;&gt;help_after_title&lt;/div&gt;&lt;div class=&quot;webform-element-help--content&quot;&gt;{This is an example of help}&lt;/div&gt;"><span aria-hidden="true">?</span></span></label>');

    // Check help display title before.
    $this->assertRaw('<label for="edit-help-before-title"><span class="webform-element-help" role="tooltip" tabindex="0" data-webform-help="&lt;div class=&quot;webform-element-help--title&quot;&gt;help_before_title&lt;/div&gt;&lt;div class=&quot;webform-element-help--content&quot;&gt;{This is an example of help}&lt;/div&gt;"><span aria-hidden="true">?</span></span>help_before_title</label>');

    // Check help display element after.
    $this->assertRaw('<span class="field-suffix"><span class="webform-element-help" role="tooltip" tabindex="0" data-webform-help="&lt;div class=&quot;webform-element-help--title&quot;&gt;help_after_element&lt;/div&gt;&lt;div class=&quot;webform-element-help--content&quot;&gt;{This is an example of help}&lt;/div&gt;"><span aria-hidden="true">?</span></span></span>');

    // Check help display element before.
    $this->assertRaw('<span class="field-prefix"><span class="webform-element-help" role="tooltip" tabindex="0" data-webform-help="&lt;div class=&quot;webform-element-help--title&quot;&gt;help_before_element&lt;/div&gt;&lt;div class=&quot;webform-element-help--content&quot;&gt;{This is an example of help}&lt;/div&gt;"><span aria-hidden="true">?</span></span></span>');
  }

}
