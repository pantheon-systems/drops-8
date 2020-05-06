<?php

namespace Drupal\Tests\webform\Functional\Composite;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\webform\Entity\Webform;
use Drupal\webform\Entity\WebformSubmission;
use Drupal\Tests\webform\Functional\WebformBrowserTestBase;
use Drupal\webform\WebformSubmissionInterface;

/**
 * Tests for webform submission webform element custom #format support.
 *
 * @group Webform
 */
class WebformCompositeFormatTest extends WebformBrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['filter', 'address', 'webform'];

  /**
   * Webforms to load.
   *
   * @var array
   */
  protected static $testWebforms = ['test_composite_format', 'test_composite_format_multiple'];

  /**
   * Tests element format.
   */
  public function testFormat() {

    /**************************************************************************/
    /* Format composite element as HTML and text */
    /**************************************************************************/

    /** @var \Drupal\webform\WebformInterface $webform */
    $webform = Webform::load('test_composite_format');
    $sid = $this->postSubmission($webform);
    $submission = WebformSubmission::load($sid);

    // Check composite elements item formatted as HTML.
    $body = $this->getMessageBody($submission, 'email_html');
    $elements = [
      'Text format (Plain text)' => '<p>&lt;p&gt;Lorem ipsum dolor sit amet, consectetur adipiscing elit. Negat esse eam, inquit, propter se expetendam. Primum Theophrasti, Strato, physicum se voluit; Id mihi magnum videtur. Itaque mihi non satis videmini considerare quod iter sit naturae quaeque progressio. Quare hoc videndum est, possitne nobis hoc ratio philosophorum dare. Est enim tanti philosophi tamque nobilis audacter sua decreta defendere.&lt;/p&gt;</p>',
      'Likert (Value)' => '<div class="item-list"><ul><li><b>Please answer question 1?:</b> 1</li><li><b>How about now answering question 2?:</b> 1</li><li><b>Finally, here is question 3?:</b> 1</li></ul></div>',
      'Likert (Raw value)' => '<div class="item-list"><ul><li><b>q1:</b> 1</li><li><b>q2:</b> 1</li><li><b>q3:</b> 1</li></ul></div>',
      'Likert (List)' => '<div class="item-list"><ul><li><b>Please answer question 1?:</b> 1</li><li><b>How about now answering question 2?:</b> 1</li><li><b>Finally, here is question 3?:</b> 1</li></ul></div>',
      'Basic address (Value)' => '10 Main Street<br />10 Main Street<br />Springfield, Alabama. 11111<br />Afghanistan<br />',
      'Basic address (Raw value)' => '<div class="item-list"><ul><li><b>address:</b> 10 Main Street</li><li><b>address_2:</b> 10 Main Street</li><li><b>city:</b> Springfield</li><li><b>state_province:</b> Alabama</li><li><b>postal_code:</b> 11111</li><li><b>country:</b> Afghanistan</li></ul></div><br /><br />',
      'Basic address (List)' => '<div class="item-list"><ul><li><b>Address:</b> 10 Main Street</li><li><b>Address 2:</b> 10 Main Street</li><li><b>City/Town:</b> Springfield</li><li><b>State/Province:</b> Alabama</li><li><b>ZIP/Postal Code:</b> 11111</li><li><b>Country:</b> Afghanistan</li></ul></div><br /><br />',
      'Advanced address (Value)' => '<div class="address" translate="no"><span class="given-name">John</span> <span class="family-name">Smith</span><br>
<span class="organization">Google Inc.</span><br>
<span class="address-line1">1098 Alta Ave</span><br>
<span class="locality">Mountain View</span>, <span class="administrative-area">CA</span> <span class="postal-code">94043</span><br>
<span class="country">United States</span></div>',
      'Advanced address (Raw value)' => '<div class="item-list"><ul><li><b>given_name:</b> John</li><li><b>family_name:</b> Smith</li><li><b>organization:</b> Google Inc.</li><li><b>address_line1:</b> 1098 Alta Ave</li><li><b>postal_code:</b> 94043</li><li><b>locality:</b> Mountain View</li><li><b>administrative_area:</b> CA</li><li><b>country_code:</b> US</li><li><b>langcode:</b> en</li></ul>',
      'Advanced address (List)' => '<div class="item-list"><ul><li><b>Given name:</b> John</li><li><b>Family name:</b> Smith</li><li><b>Organization:</b> Google Inc.</li><li><b>Address line 1:</b> 1098 Alta Ave</li><li><b>Postal code:</b> 94043</li><li><b>Locality:</b> Mountain View</li><li><b>Administrative area:</b> CA</li><li><b>Country code:</b> US</li><li><b>Language code:</b> en</li></ul>',
      'Link (Value)' => '<a href="http://example.com">Loremipsum</a>',
    ];
    foreach ($elements as $label => $value) {
      $this->assertContains('<b>' . $label . '</b><br />' . $value, $body, new FormattableMarkup('Found @label: @value', ['@label' => $label, '@value' => $value]));
    }

    // Check composite elements formatted as text.
    $body = $this->getMessageBody($submission, 'email_text');
    $elements = [
      'Link (Value): Loremipsum (http://example.com)',
      'Likert (Value):
Please answer question 1?: 1
How about now answering question 2?: 1
Finally, here is question 3?: 1',
      'Likert (Raw value):
q1: 1
q2: 1
q3: 1',
      'Likert (List):
Please answer question 1?: 1
How about now answering question 2?: 1
Finally, here is question 3?: 1',
      'Likert (Table):
Please answer question 1?: 1
How about now answering question 2?: 1
Finally, here is question 3?: 1',
      'Basic address (Value):
10 Main Street
10 Main Street
Springfield, Alabama. 11111
Afghanistan',
      'Basic address (Raw value):
address: 10 Main Street
address_2: 10 Main Street
city: Springfield
state_province: Alabama
postal_code: 11111
country: Afghanistan',
      'Basic address (List):
Address: 10 Main Street
Address 2: 10 Main Street
City/Town: Springfield
State/Province: Alabama
ZIP/Postal Code: 11111
Country: Afghanistan',
      'Advanced address (Value):
John Smith
Google Inc.
1098 Alta Ave
Mountain View, CA 94043
United States',
      'Advanced address (Raw value):
given_name: John
family_name: Smith
organization: Google Inc.
address_line1: 1098 Alta Ave
postal_code: 94043
locality: Mountain View
administrative_area: CA
country_code: US
langcode: en',
      'Advanced address (List):
Given name: John
Family name: Smith
Organization: Google Inc.
Address line 1: 1098 Alta Ave
Postal code: 94043
Locality: Mountain View
Administrative area: CA
Country code: US
Language code: en',
    ];
    foreach ($elements as $value) {
      $this->assertContains($value, $body, new FormattableMarkup('Found @value', ['@value' => $value]));
    }

    /**************************************************************************/
    /* Format composite multiple element as HTML and text */
    /**************************************************************************/

    /** @var \Drupal\webform\WebformInterface $webform */
    $webform = Webform::load('test_composite_format_multiple');
    $sid = $this->postSubmission($webform);
    $submission = WebformSubmission::load($sid);

    // Check composite elements item formatted as HTML.
    $body = $this->getMessageBody($submission, 'email_html');

    // Remove all spaces between tags to that we can easily check the output.
    $body = preg_replace('/>\s+</ims', '><', $body);
    $body = str_replace('<b>', PHP_EOL . '<b>', $body);
    $this->debug($body);

    $elements = [
      'Basic address (Ordered list)' => '<div class="item-list"><ol><li>10 Main Street<br />10 Main Street<br />Springfield, Alabama. 11111<br />Afghanistan</li><li>10 Main Street<br />10 Main Street<br />Springfield, Alabama. 11111<br />Afghanistan</li><li>10 Main Street<br />10 Main Street<br />Springfield, Alabama. 11111<br />Afghanistan</li></ol></div>',
      'Basic address (Unordered list)' => '<div class="item-list"><ul><li>10 Main Street<br />10 Main Street<br />Springfield, Alabama. 11111<br />Afghanistan</li><li>10 Main Street<br />10 Main Street<br />Springfield, Alabama. 11111<br />Afghanistan</li><li>10 Main Street<br />10 Main Street<br />Springfield, Alabama. 11111<br />Afghanistan</li></ul></div>',
      'Basic address (Horizontal rule)' => '10 Main Street<br />10 Main Street<br />Springfield, Alabama. 11111<br />Afghanistan<hr class="webform-horizontal-rule" />10 Main Street<br />10 Main Street<br />Springfield, Alabama. 11111<br />Afghanistan<hr class="webform-horizontal-rule" />10 Main Street<br />10 Main Street<br />Springfield, Alabama. 11111<br />Afghanistan',
      'Basic address (Table)' => '<table width="100%" cellspacing="0" cellpadding="5" border="1" class="responsive-enabled" data-striping="1"><thead><tr><th bgcolor="#eee">Address</th><th bgcolor="#eee">Address 2</th><th bgcolor="#eee">City/Town</th><th bgcolor="#eee">State/Province</th><th bgcolor="#eee">ZIP/Postal Code</th><th bgcolor="#eee">Country</th></tr></thead><tbody><tr class="odd"><td>10 Main Street</td><td>10 Main Street</td><td>Springfield</td><td>Alabama</td><td>11111</td><td>Afghanistan</td></tr><tr class="even"><td>10 Main Street</td><td>10 Main Street</td><td>Springfield</td><td>Alabama</td><td>11111</td><td>Afghanistan</td></tr><tr class="odd"><td>10 Main Street</td><td>10 Main Street</td><td>Springfield</td><td>Alabama</td><td>11111</td><td>Afghanistan</td></tr></tbody></table>',
    ];
    foreach ($elements as $label => $value) {
      $this->assertContains('<b>' . $label . '</b><br />' . $value, $body, new FormattableMarkup('Found @label: @value', ['@label' => $label, '@value' => $value]));
    }

    // Check composite elements formatted as text.
    $body = $this->getMessageBody($submission, 'email_text');
    $elements = [
      'Basic address (Ordered list):
1. 10 Main Street
   10 Main Street
   Springfield, Alabama. 11111
   Afghanistan
2. 10 Main Street
   10 Main Street
   Springfield, Alabama. 11111
   Afghanistan
3. 10 Main Street
   10 Main Street
   Springfield, Alabama. 11111
   Afghanistan',
      'Basic address (Unordered list):
- 10 Main Street
  10 Main Street
  Springfield, Alabama. 11111
  Afghanistan
- 10 Main Street
  10 Main Street
  Springfield, Alabama. 11111
  Afghanistan
- 10 Main Street
  10 Main Street
  Springfield, Alabama. 11111
  Afghanistan',
      'Basic address (Horizontal rule):
10 Main Street
10 Main Street
Springfield, Alabama. 11111
Afghanistan
---
10 Main Street
10 Main Street
Springfield, Alabama. 11111
Afghanistan
---
10 Main Street
10 Main Street
Springfield, Alabama. 11111
Afghanistan',
    ];
    foreach ($elements as $value) {
      $this->assertContains($value, $body, new FormattableMarkup('Found @value', ['@value' => $value]));
    }
  }

  /**
   * Get webform email message body for a webform submission.
   *
   * @param \Drupal\webform\WebformSubmissionInterface $submission
   *   A webform submission.
   * @param string $handler_id
   *   The webform email handler id.
   *
   * @return string
   *   The webform email message body for a webform submission.
   */
  protected function getMessageBody(WebformSubmissionInterface $submission, $handler_id = 'email_html') {
    /** @var \Drupal\webform\Plugin\WebformHandlerMessageInterface $message_handler */
    $message_handler = $submission->getWebform()->getHandler($handler_id);
    $message = $message_handler->getMessage($submission);
    $body = (string) $message['body'];
    $this->verbose($body);
    return $body;
  }

}
