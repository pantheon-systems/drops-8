<?php

namespace Drupal\Tests\webform\Functional;

use Drupal\Core\Test\AssertMailTrait;
use Drupal\webform\Entity\Webform;

/**
 * Tests for webform token submission value.
 *
 * @group Webform
 */
class WebformRenderingTest extends WebformBrowserTestBase {

  use AssertMailTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['filter', 'webform'];

  /**
   * Webforms to load.
   *
   * @var array
   */
  protected static $testWebforms = ['test_rendering'];

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    // Create filters.
    $this->createFilters();

    $this->drupalLogin($this->rootUser);
  }

  /**
   * Test text format element.
   */
  public function testRendering() {
    $webform = Webform::load('test_rendering');

    /**************************************************************************/
    // Preview.
    /**************************************************************************/

    $this->drupalPostForm('/webform/test_rendering', [], t('Preview'));

    // Check preview submission_label.
    $this->assertRaw('submission &lt;em&gt;label&lt;/em&gt; (&amp;&gt;&lt;#)');

    // Check preview textfield_plain_text.
    $this->assertRaw('{prefix}{default_value}{suffix}');

    // Check preview textfield_markup.
    $this->assertRaw('<label><em>textfield_markup</em></label>');
    $this->assertRaw('<em>{prefix}</em>{default_value}<em>{suffix}</em>');

    // Check preview textfield_special_characters.
    $this->assertRaw('<label>textfield_special_characters (&amp;&gt;&lt;#)</label>');
    $this->assertRaw('(&amp;&gt;&lt;#){default_value}(&amp;&gt;&lt;#)');

    // Check preview text_format_basic_html.
    $this->assertRaw('<p><em>{default_value}</em></p>');

    // Create a submission.
    $sid = $this->postSubmission($webform);

    /**************************************************************************/
    // Emails.
    /**************************************************************************/

    // Get sent emails.
    $sent_emails = $this->getMails();
    $html_email = $sent_emails[0];
    $text_email = $sent_emails[1];

    // Check HTML email.
    $this->assertEqual($html_email['subject'], 'submission label (&>');
    $this->assertEqual($html_email['params']['subject'], 'submission <em>label</em> (&><#)');
    $this->assertContains('<b>submission_label</b><br />submission &lt;em&gt;label&lt;/em&gt; (&amp;&gt;&lt;#)<br /><br />', $html_email['params']['body']);
    $this->assertContains('<b>textfield_plain_text</b><br />{prefix}{default_value}{suffix}<br /><br />', $html_email['params']['body']);
    $this->assertContains('<b><em>textfield_markup</em></b><br /><em>{prefix}</em>{default_value}<em>{suffix}</em><br /><br />', $html_email['params']['body']);
    $this->assertContains('<b>textfield_special_characters (&amp;&gt;&lt;#)</b><br />(&amp;&gt;&lt;#){default_value}(&amp;&gt;&lt;#)<br /><br />', $html_email['params']['body']);
    $this->assertContains('<b>text_format_basic_html</b><br /><p><em>{default_value}</em></p><br /><br />', $html_email['params']['body']);

    // Check plain text email.
    $this->assertEqual($text_email['subject'], 'submission label (&>');
    $this->assertEqual($text_email['params']['subject'], 'submission <em>label</em> (&><#)');
    $this->assertContains('submission_label: submission <em>label</em> (&><#)', $text_email['params']['body']);
    $this->assertContains('textfield_plain_text: {prefix}{default_value}{suffix}', $text_email['params']['body']);
    $this->assertContains('textfield_markup: {prefix}{default_value}{suffix}', $text_email['params']['body']);
    $this->assertContains('textfield_special_characters (&>: (&>{default_value}(&>', $text_email['params']['body']);
    $this->assertContains('text_format_basic_html:', $text_email['params']['body']);
    $this->assertContains('/{default_value}/', $text_email['params']['body']);

    /**************************************************************************/
    // Submission.
    /**************************************************************************/

    // Check view submission.
    $this->drupalGet("admin/structure/webform/manage/test_rendering/submission/$sid");

    // Check submission label token replacements.
    $this->assertRaw('<h1 class="page-title">submission &lt;em&gt;label&lt;/em&gt; (&amp;&gt;&lt;#)</h1>');
  }

}
