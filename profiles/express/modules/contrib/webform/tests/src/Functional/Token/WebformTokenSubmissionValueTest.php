<?php

namespace Drupal\Tests\webform\Functional\Token;

use Drupal\webform\Entity\Webform;
use Drupal\Tests\webform\Functional\WebformBrowserTestBase;

/**
 * Tests for webform token submission value.
 *
 * @group Webform
 */
class WebformTokenSubmissionValueTest extends WebformBrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['taxonomy'];

  /**
   * Webforms to load.
   *
   * @var array
   */
  protected static $testWebforms = ['test_token_submission_value'];

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    // Create 'tags' vocabulary.
    $this->createTags();
  }

  /**
   * Test webform token submission value.
   */
  public function testWebformTokenSubmissionValue() {
    $webform = Webform::load('test_token_submission_value');

    // Check anonymous token handling.
    $this->postSubmission($webform);
    $tokens = [
      // Element.
      'webform:element:email' => 'email',
      'webform:element:email:title' => 'email',
      'webform:element:email:description' => '<em>This is an email address</em>',
      'webform:element:not_element:description' => '[webform:element:not_element:description]',
      'webform:element:email:not_property' => '[webform:element:email:not_property]',

      // Checkboxes.
      'webform:element:checkboxes' => 'One, Three',
      'webform:element:checkboxes:checked:one' => '1',
      'webform:element:checkboxes:selected:two' => '0',
      'webform:element:checkboxes:selected:three' => '1',

      // Emails.
      'webform_submission:values:email' => 'example@example.com',
      'webform_submission:values:emails:0' => 'one@example.com',
      'webform_submission:values:emails:1' => 'two@example.com',
      'webform_submission:values:emails:2' => 'three@example.com',
      'webform_submission:values:emails:value:comma' => 'one@example.com, two@example.com, three@example.com',
      'webform_submission:values:emails:html' => '<div class="item-list"><ul><li><a href="mailto:one@example.com">one@example.com</a></li><li><a href="mailto:two@example.com">two@example.com</a></li><li><a href="mailto:three@example.com">three@example.com</a></li></ul></div>',
      'webform_submission:values:emails:0:html' => '<a href="mailto:one@example.com">one@example.com</a>',
      'webform_submission:values:emails:1:html' => '<a href="mailto:two@example.com">two@example.com</a>',
      'webform_submission:values:emails:2:html' => '<a href="mailto:three@example.com">three@example.com</a>',
      'webform_submission:values:emails:99:html' => '',

      // Users.
      'webform_submission:values:user' => 'admin (1)',
      'webform_submission:values:users' => 'admin (1)',
      'webform_submission:values:user:entity:mail' => 'admin@example.com',
      'webform_submission:values:users:0:entity:account-name' => 'admin',
      'webform_submission:values:users:99:entity:account-name' => '',

      // Current users.
      'current-user:display-name' => '',
      'current-user:missing' => '',

      // Terms.
      'webform_submission:values:term' => 'Parent 1 (1)',
      'webform_submission:values:terms' => 'Parent 1 (1), Parent 1: Child 1 (2)',
      'webform_submission:values:term:entity:name' => 'Parent 1',
      'webform_submission:values:terms:entity:name' => 'Parent 1',
      'webform_submission:values:terms:1:entity:name' => 'Parent 1: Child 1',

      // Names.
      'webform_submission:values:name' => 'John Smith',
      'webform_submission:values:names' => '- John Smith
- Jane Doe',
      'webform_submission:values:names:0' => 'John Smith',
      'webform_submission:values:names:1' => 'Jane Doe',
      'webform_submission:values:names:99' => '',

      // Contacts.
      'webform_submission:values:contact' => 'John Smith
10 Main Street
Springfield, Alabama. 12345
United States
john@example.com',
      'webform_submission:values:contacts' => '- John Smith
  10 Main Street
  Springfield, Alabama. 12345
  United States
  john@example.com
- Jane Doe
  10 Main Street
  Springfield, Alabama. 12345
  United States
  jane@example.com',
      'webform_submission:values:contacts:html' => '<div class="item-list"><ul><li>John Smith<br />10 Main Street<br />Springfield, Alabama. 12345<br />United States<br /><a href="mailto:john@example.com">john@example.com</a></li><li>Jane Doe<br />10 Main Street<br />Springfield, Alabama. 12345<br />United States<br /><a href="mailto:jane@example.com">jane@example.com</a></li></ul></div>',
      'webform_submission:values:contacts:0:html' => 'John Smith<br />10 Main Street<br />Springfield, Alabama. 12345<br />United States<br /><a href="mailto:john@example.com">john@example.com</a>',
      'webform_submission:values:contacts:0:name' => 'John Smith',
      'webform_submission:values:contacts:1:name' => 'Jane Doe',
      'webform_submission:values:contacts:0:email:html' => '<a href="mailto:john@example.com">john@example.com</a>',
      'webform_submission:values:contacts:1:email:raw:html' => 'jane@example.com',

      // Containers.
      'webform_submission:values:fieldset' => '<pre>fieldset
--------
first_name: John
last_name: Smith
</pre>',

      // Markup.
      'webform_submission:values:webform_markup' => '*This is some basic HTML.*
',
      'webform_submission:values:webform_markup:html' => '<strong>This is some basic HTML.</strong>',

      // Submission limits.
      'webform_submission:limit:webform' => '100',
      'webform_submission:total:webform' => '1',
      'webform_submission:limit:user' => '10',
      'webform_submission:total:user' => '1',
      'webform_submission:limit:webform:source_entity' => '50',
      'webform_submission:total:webform:source_entity' => '',
      'webform_submission:limit:user:source_entity' => '5',
      'webform_submission:total:user:source_entity' => '',

      // Clear.
      'webform_submission:values:missing' => '[webform_submission:values:missing]',
      'webform_submission:values:missing:clear' => '',
      'webform:random:missing' => '[webform:random:missing]',
      'webform:random:missing:clear' => '',

      // HTML decode.
      'webform_submission:values:markup' => '&lt;b&gt;Bold&lt;/b&gt; &amp;amp; UPPERCASE',
      'webform_submission:values:markup:htmldecode' => '<b>Bold</b> &amp; UPPERCASE',
      'webform_submission:values:markup:htmldecode:striptags' => 'Bold &amp; UPPERCASE',
      'webform_submission:values:script' => '&lt;script&gt;alert(&#039;hi&#039;);&lt;/script&gt;',
      'webform_submission:values:script:htmldecode' => 'alert(\'hi\');',

      // URL encode.
      'webform_submission:values:url' => 'http://example.com?query=param',
      'webform_submission:values:url:urlencode' => 'http%3A%2F%2Fexample.com%3Fquery%3Dparam',
    ];
    foreach ($tokens as $token => $value) {
      $this->assertRaw("<tr><th width=\"50%\">$token</th><td width=\"50%\">$value</td></tr>");
    }

    // Check containers.
    $this->assertRaw('<tr><th width="50%">webform_submission:values:fieldset</th><td width="50%"><pre>fieldset');
    $this->assertRaw('<tr><th width="50%">webform_submission:values:fieldset:html</th><td width="50%"><fieldset class="webform-container webform-container-type-fieldset js-form-item form-item js-form-wrapper form-wrapper" id="test_token_submission_value--fieldset">');
    $this->assertRaw('<tr><th width="50%">webform_submission:values:fieldset:header:html</th><td width="50%"><section class="js-form-item form-item js-form-wrapper form-wrapper webform-section" id="test_token_submission_value--fieldset">');
    $this->assertRaw('<tr><th width="50%">webform_submission:values:fieldset:details:html</th><td width="50%"><details class="webform-container webform-container-type-details js-form-wrapper form-wrapper" data-webform-element-id="test_token_submission_value--fieldset" id="test_token_submission_value--fieldset" open="open">');
    $this->assertRaw('<tr><th width="50%">webform_submission:values:fieldset:fieldset:html</th><td width="50%"><fieldset class="webform-container webform-container-type-fieldset js-form-item form-item js-form-wrapper form-wrapper" id="test_token_submission_value--fieldset">');

    // Check authenticated token handling.
    $this->drupalLogin($this->rootUser);
    $this->postSubmission($webform);
    $tokens = [
      // Current users.
      'current-user:display-name' => 'admin',
      'current-user:missing' => '',
    ];
    foreach ($tokens as $token => $value) {
      $this->assertRaw("<tr><th width=\"50%\">$token</th><td width=\"50%\">$value</td></tr>");
    }
  }

}
