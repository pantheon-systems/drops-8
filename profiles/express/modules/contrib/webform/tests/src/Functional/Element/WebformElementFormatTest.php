<?php

namespace Drupal\Tests\webform\Functional\Element;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\file\Entity\File;
use Drupal\webform\Entity\Webform;
use Drupal\webform\Entity\WebformSubmission;
use Drupal\webform\WebformSubmissionInterface;

/**
 * Tests for webform submission webform element custom #format support.
 *
 * @group Webform
 */
class WebformElementFormatTest extends WebformElementBrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['node', 'taxonomy', 'file', 'webform', 'webform_image_select'];

  /**
   * Webforms to load.
   *
   * @var array
   */
  protected static $testWebforms = ['test_element_format', 'test_element_format_multiple', 'test_element_format_token'];

  /**
   * Tests element format.
   */
  public function testFormat() {
    $this->drupalLogin($this->rootUser);

    /**************************************************************************/
    /* Format (single) element as HTML and text */
    /**************************************************************************/

    /** @var \Drupal\webform\WebformInterface $webform */
    $webform = Webform::load('test_element_format');

    $sid = $this->postSubmission($webform);
    /** @var \Drupal\webform\WebformSubmissionInterface $submission */
    $submission = WebformSubmission::load($sid);

    // Check (single) elements item formatted as HTML.
    $body = $this->getMessageBody($submission, 'email_html');
    $elements = [
      'Checkbox (Value)' => 'Yes',
      'Radios (Description)' => 'This is the <strong>first</strong> description',
      'Color (Color swatch)' => '<font color="#ffffcc">█</font> #ffffcc',
      'Email (Link)' => '<a href="mailto:example@example.com">example@example.com</a>',
      'Email confirm (Link)' => '<a href="mailto:example@example.com">example@example.com</a>',
      'Email multiple (Link)' => '<a href="mailto:example@example.com">example@example.com</a>, <a href="mailto:test@test.com">test@test.com</a>, <a href="mailto:random@random.com">random@random.com</a>',
      'Signature (Status)' => '[signed]',
      'Telephone (Link)' => '<a href="tel:+1 212-333-4444">+1 212-333-4444</a>',
      'URL (Link)' => '<a href="http://example.com">http://example.com</a>',
      'Date (Raw value)' => '1942-06-18',
      'Date (Fallback date format)' => '1942-06-18',
      'Date (HTML Date)' => '1942-06-18',
      'Date (HTML Datetime)' => '1942-06-18T00:00:00+1000',
      'Date (HTML Month)' => '1942-06',
      'Date (HTML Time)' => '00:00:00',
      'Date (HTML Week)' => '1942-W25',
      'Date (HTML Year)' => '1942',
      'Date (HTML Yearless date)' => '06-18',
      'Date (Default long date)' => 'Thursday, June 18, 1942 - 00:00',
      'Date (Default medium date)' => 'Thu, 06/18/1942 - 00:00',
      'Date (Default short date)' => '06/18/1942 - 00:00',
      'Time (Value)' => '09:00',
      'Time (Raw value)' => '09:00:00',
//      'Entity autocomplete (Raw value)' => 'user:1',
//      'Entity autocomplete (Link)' => '<a href="http://localhost/webform/user/1" hreflang="en">admin</a>',
//      'Entity autocomplete (Entity ID)' => '1',
//      'Entity autocomplete (Label)' => 'admin',
//      'Entity autocomplete (Label (ID))' => 'admin (1)',
    ];
    foreach ($elements as $label => $value) {
      $this->assertContains('<b>' . $label . '</b><br />' . $value, $body, new FormattableMarkup('Found @label: @value', ['@label' => $label, '@value' => $value]));
    }

    // Check code format.
    $this->assertContains('<pre class="js-webform-codemirror-runmode webform-codemirror-runmode" data-webform-codemirror-mode="text/x-yaml">message: \'Hello World\'</pre>', $body);

    // Check elements formatted as text.
    $body = $this->getMessageBody($submission, 'email_text');
    $elements = [
      'Checkbox (Value): Yes',
      'Radios (Description)' => 'This is the *first* description',
      'Color (Color swatch): #ffffcc',
      'Email (Link): example@example.com',
      'Email multiple (Link): example@example.com, test@test.com, random@random.com',
      'URL (Link): http://example.com',
      'Date (Raw value): 1942-06-18',
      'Date (Fallback date format): 1942-06-18',
      'Date (HTML Date): 1942-06-18',
      'Date (HTML Datetime): 1942-06-18T00:00:00+1000',
      'Date (HTML Month): 1942-06',
      'Date (HTML Time): 00:00:00',
      'Date (HTML Week): 1942-W25',
      'Date (HTML Year): 1942',
      'Date (HTML Yearless date): 06-18',
      'Date (Default long date): Thursday, June 18, 1942 - 00:00',
      'Date (Default medium date): Thu, 06/18/1942 - 00:00',
      'Date (Default short date): 06/18/1942 - 00:00',
      'Time (Value): 09:00',
      'Time (Raw value): 09:00:00',
    ];
    foreach ($elements as $value) {
      $this->assertContains($value, $body, new FormattableMarkup('Found @value', ['@value' => $value]));
    }

    /**************************************************************************/
    /* Format managed file element as HTML and text */
    /**************************************************************************/

    $sid = $this->postSubmissionTest($webform);
    /** @var \Drupal\webform\WebformSubmissionInterface $submission */
    $submission = WebformSubmission::load($sid);

    // Check managed file element formatted as HTML.
    $body = $this->getMessageBody($submission, 'email_html');
    $elements = [
      'File (Value)' => $this->getSubmissionFileUrl($submission, 'managed_file_value'),
      'File (Raw value)' => $this->getSubmissionFileUrl($submission, 'managed_file_raw'),
      'File (File)' => '<div>
<span class="file file--mime-text-plain file--text"> <a href="' . $this->getSubmissionFileUrl($submission, 'managed_file_file') . '" type="text/plain; length=43">managed_file_file.txt</a></span>
</div>',
      'File (Link)' => '
<span class="file file--mime-text-plain file--text"> <a href="' . $this->getSubmissionFileUrl($submission, 'managed_file_link') . '" type="text/plain; length=43">managed_file_link.txt</a></span>',
      'File (File ID)' => $submission->getElementData('managed_file_id'),
      'File (File name)' => 'managed_file_name.txt',
      'File (File base name (no extension))' => 'managed_file_basename',
      'File (File extension)' => 'txt',
      'File (URL)' => $this->getSubmissionFileUrl($submission, 'managed_file_url'),
    ];
    foreach ($elements as $label => $value) {
      $this->assertContains('<b>' . $label . '</b><br />' . $value, $body, new FormattableMarkup('Found @label: @value', ['@label' => $label, '@value' => $value]));
    }

    // Check managed file element formatted as text.
    $body = $this->getMessageBody($submission, 'email_text');
    $elements = [
      'File (Value): ' . $this->getSubmissionFileUrl($submission, 'managed_file_value'),
      'File (Raw value): ' . $this->getSubmissionFileUrl($submission, 'managed_file_raw'),
      'File (File): ' . $this->getSubmissionFileUrl($submission, 'managed_file_file'),
      'File (Link): ' . $this->getSubmissionFileUrl($submission, 'managed_file_link'),
      'File (File ID): ' . $submission->getElementData('managed_file_id'),
      'File (File name): managed_file_name.txt',
      'File (URL): ' . $this->getSubmissionFileUrl($submission, 'managed_file_url'),
      'File (File mime type)' => 'text/plain',
      'File (File size (Bytes))' => '43',
      'File (File content (Base64))' => 'dGhpcyBpcyBhIHNhbXBsZSB0eHQgZmlsZQppdCBoYXMgdHdvIGxpbmVzCg==',
    ];
    foreach ($elements as $value) {
      $this->assertContains($value, $body, new FormattableMarkup('Found @value', ['@value' => $value]));
    }

    /**************************************************************************/
    /* Format multiple element as HTML and text */
    /**************************************************************************/

    /** @var \Drupal\webform\WebformInterface $webforms */
    $webforms = Webform::load('test_element_format_multiple');
    $sid = $this->postSubmission($webforms);
    $webforms_submission = WebformSubmission::load($sid);

    // Check elements (single) item formatted as HTML.
    $body = $this->getMessageBody($webforms_submission, 'email_html');
    $elements = [
      'Text field (Comma)' => 'Loremipsum, Oratione, Dixisset',
      'Text field (Semicolon)' => 'Loremipsum; Oratione; Dixisset',
      'Text field (And)' => 'Loremipsum, Oratione, and Dixisset',
      'Text field (Ordered list)' => '<div class="item-list"><ol><li>Loremipsum</li><li>Oratione</li><li>Dixisset</li></ol></div>',
      'Text field (Unordered list)' => '<div class="item-list"><ul><li>Loremipsum</li><li>Oratione</li><li>Dixisset</li></ul></div>',
      'Checkboxes (Comma)' => 'One, Two, Three',
      'Checkboxes (Semicolon)' => 'One; Two; Three',
      'Checkboxes (And)' => 'One, Two, and Three',
      'Checkboxes (Ordered list)' => '<div class="item-list"><ol><li>One</li><li>Two</li><li>Three</li></ol>',
      'Checkboxes (Unordered list)' => '<div class="item-list"><ul><li>One</li><li>Two</li><li>Three</li></ul>',
      'Checkboxes (Checklist)' => '<span style="font-size: 1.4em; line-height: 1em">☑</span> One<br /><span style="font-size: 1.4em; line-height: 1em">☑</span> Two<br /><span style="font-size: 1.4em; line-height: 1em">☑</span> Three<br />',
    ];
    foreach ($elements as $label => $value) {
      $this->assertContains('<b>' . $label . '</b><br />' . $value, $body, new FormattableMarkup('Found @label: @value', [
        '@label' => $label,
        '@value' => $value,
      ]));
    }

    // Check elements formatted as text.
    $body = $this->getMessageBody($webforms_submission, 'email_text');
    $elements = [
      'Text field (Comma): Loremipsum, Oratione, Dixisset',
      'Text field (Semicolon): Loremipsum; Oratione; Dixisset',
      'Text field (And): Loremipsum, Oratione, and Dixisset',
      'Text field (Ordered list):
1. Loremipsum
2. Oratione
3. Dixisset',
      'Text field (Unordered list):
- Loremipsum
- Oratione
- Dixisset',
      'Checkboxes (Comma): One, Two, Three',
      'Checkboxes (Semicolon): One; Two; Three',
      'Checkboxes (And): One, Two, and Three',
      'Checkboxes (Ordered list):
1. One
2. Two
3. Three',
      'Checkboxes (Unordered list):
- One
- Two
- Three',
      'Checkboxes (Checklist):
☑ One
☑ Two
☑ Three',
    ];
    foreach ($elements as $value) {
      $this->assertContains($value, $body, new FormattableMarkup('Found @value', ['@value' => $value]));
    }

    /**************************************************************************/
    /* Format element using tokens */
    /**************************************************************************/

    /** @var \Drupal\webform\WebformInterface $webform_format_token */
    $webform_format_token = Webform::load('test_element_format_token');
    $sid = $this->postSubmission($webform_format_token);
    $webform_format_token_submission = WebformSubmission::load($sid);

    // Check elements tokens formatted as HTML.
    $body = $this->getMessageBody($webform_format_token_submission, 'email_html');
    $elements = [
      'default:' => 'one, two, three',
      'comma:' => 'one, two, three',
      'semicolon:' => 'one; two; three',
      'and:' => 'one, two, and three',
      'ul:' => '<div class="item-list"><ul><li>one</li><li>two</li><li>three</li></ul></div>',
      'ol:' => '<div class="item-list"><ol><li>one</li><li>two</li><li>three</li></ol></div>',
      'raw:' => '1, 2, 3',
    ];
    foreach ($elements as $label => $value) {
      $this->assertContains('<h3>' . $label . '</h3>' . $value . '<hr />', $body, new FormattableMarkup('Found @label: @value', [
        '@label' => $label,
        '@value' => $value,
      ]));
    }

    // Check elements tokens formatted as text.
    $body = $this->getMessageBody($webform_format_token_submission, 'email_text');
    $elements = [
      "default:\none, two, three",
      "comma:\none, two, three",
      "semicolon:\none; two; three",
      "and:\none, two, and three",
      "ul:\n- one\n- two\n- three",
      "ol:\n1. one\n2. two\n3. three",
      "raw:\n1, 2, 3",
    ];
    foreach ($elements as $value) {
      $this->assertContains($value, $body, new FormattableMarkup('Found @value', ['@value' => $value]));
    }

    // Check element default format item global setting.
    \Drupal::configFactory()->getEditable('webform.settings')
      ->set('format.checkboxes.item', 'raw')
      ->save();
    $body = $this->getMessageBody($webform_format_token_submission, 'email_text');
    $this->assertContains("default:\n1, 2, 3", $body);

    // Check element default format items global setting.
    \Drupal::configFactory()->getEditable('webform.settings')
      ->set('format.checkboxes.items', 'and')
      ->save();
    $body = $this->getMessageBody($webform_format_token_submission, 'email_text');
    $this->assertContains("default:\n1, 2, and 3", $body);
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

  /**
   * Get submission element's file URL.
   *
   * @param \Drupal\webform\WebformSubmissionInterface $submission
   *   A webform submission.
   * @param string $element_key
   *   The element key.
   *
   * @return string
   *   A submission element's file URL.
   */
  protected function getSubmissionFileUrl(WebformSubmissionInterface $submission, $element_key) {
    $fid = $submission->getElementData($element_key);
    $file = File::load($fid);
    return file_create_url($file->getFileUri());
  }

}
