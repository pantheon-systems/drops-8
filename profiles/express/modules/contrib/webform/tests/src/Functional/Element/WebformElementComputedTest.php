<?php

namespace Drupal\Tests\webform\Functional\Element;

use Drupal\webform\Entity\Webform;
use Drupal\webform\Entity\WebformSubmission;

/**
 * Tests for computed elements.
 *
 * @group Webform
 */
class WebformElementComputedTest extends WebformElementBrowserTestBase {

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
  protected static $testWebforms = [
    'test_element_computed_token',
    'test_element_computed_twig',
    'test_element_computed_ajax',
  ];

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    // Create filters.
    $this->createFilters();
  }

  /**
   * Test computed elements.
   */
  public function testComputedElement() {
    $this->drupalLogin($this->rootUser);

    /* Token */

    $token_webform = Webform::load('test_element_computed_token');

    // Check computed tokens are processed on form load.
    $this->drupalGet('/webform/test_element_computed_token');
    $this->assertRaw('<b class="webform_computed_token_auto">simple string:</b> This is a string<br />');

    // Get computed token preview.
    $this->drupalPostForm('/webform/test_element_computed_token', [], t('Preview'));

    // Check token auto detection.
    $this->assertRaw('<b class="webform_computed_token_auto">simple string:</b> This is a string<br />');
    $this->assertRaw('<b class="webform_computed_token_auto">complex string :</b> This is a &lt;strong&gt;complex&lt;/strong&gt; string, which contains &quot;double&quot; and &#039;single&#039; quotes with special characters like &gt;, &lt;, &gt;&lt;, and &lt;&gt;.<br />');
    $this->assertRaw('<b class="webform_computed_token_auto">text_format:</b> <p>This is a <strong>text format</strong> string.</p>');
    $this->assertRaw('<p>It contains "double" and \'single\' quotes with special characters like &lt;, &gt;, &lt;&gt;, and &gt;&lt;.</p><br />');
    $this->assertRaw('<b class="webform_computed_token_auto">xss:</b> &lt;script&gt;alert(&quot;XSS&quot;);&lt;/script&gt;<br />');

    // Check token html rendering.
    $this->assertRaw('<b class="webform_computed_token_html">simple string:</b> This is a string<br />');
    $this->assertRaw('<b class="webform_computed_token_html">complex string :</b> This is a &lt;strong&gt;complex&lt;/strong&gt; string, which contains &quot;double&quot; and &#039;single&#039; quotes with special characters like &gt;, &lt;, &gt;&lt;, and &lt;&gt;.<br />');
    $this->assertRaw('<b class="webform_computed_token_html">text_format:</b> <p>This is a <strong>text format</strong> string.</p>');
    $this->assertRaw('<p>It contains "double" and \'single\' quotes with special characters like &lt;, &gt;, &lt;&gt;, and &gt;&lt;.</p><br />');
    $this->assertRaw('<b class="webform_computed_token_html">xss:</b> &lt;script&gt;alert(&quot;XSS&quot;);&lt;/script&gt;<br />');

    // Check token plain text rendering.
    $this->assertRaw('<div class="webform-element webform-element-type-webform-computed-token js-form-item form-item js-form-type-item form-type-item js-form-item-webform-computed-token-text form-item-webform-computed-token-text" id="test_element_computed_token--webform_computed_token_text">');
    $this->assertRaw('<label>webform_computed_token_text</label>');
    $this->assertRaw('simple string: This is a string<br />');
    $this->assertRaw('complex string : This is a &lt;strong&gt;complex&lt;/strong&gt; string, which contains &quot;double&quot; and &#039;single&#039; quotes with special characters like &gt;, &lt;, &gt;&lt;, and &lt;&gt;.<br />');
    $this->assertRaw('xss: &lt;script&gt;alert(&quot;XSS&quot;);&lt;/script&gt;<br />');

    // Submit the computed token.
    $sid = $this->postSubmission($token_webform);
    $webform_submission = WebformSubmission::load($sid);
    $data = $webform_submission->getData();

    // Check value stored in the database.
    $this->debug($data['webform_computed_token_store']);
    $this->assertEqual($data['webform_computed_token_store'], "sid: $sid");

    // Check values not stored in the database.
    $result = \Drupal::database()->select('webform_submission_data')
      ->fields('webform_submission_data', ['value'])
      ->condition('webform_id', 'test_element_computed_token')
      ->condition('name', ['webform_computed_token_auto', 'webform_computed_token_html', 'webform_computed_token_text'], 'IN')
      ->execute()
      ->fetchAll();
    $this->assert(empty($result));

    /* Twig */

    // Get computed Twig form.
    $this->drupalGet('/webform/test_element_computed_twig');

    // Check computed Twig is processed on form load.
    $this->assertRaw('<b class="webform_computed_twig_auto">number:</b> 2 * 2 = 4<br />');

    // Check Twig trim.
    $this->assertFieldByName('webform_computed_twig_trim', '<em>This is trimmed</em>  <br/>');

    // Check Twig spaceless.
    $this->assertFieldByName('webform_computed_twig_spaceless', '<em>This is spaceless</em><br/>');

    // Get computed Twig preview.
    $this->drupalPostForm('/webform/test_element_computed_twig', [], t('Preview'));

    // Check Twig auto detection.
    $this->assertRaw('<b class="webform_computed_twig_auto">number:</b> 2 * 2 = 4<br />');
    $this->assertRaw('<b class="webform_computed_twig_auto">simple string:</b> This is a string<br />');
    $this->assertRaw('<b class="webform_computed_twig_auto">complex string:</b> This is a &lt;strong&gt;complex&lt;/strong&gt; string, which contains &quot;double&quot; and &#039;single&#039; quotes with special characters like &gt;, &lt;, &gt;&lt;, and &lt;&gt;.<br />');
    $this->assertRaw('<b class="webform_computed_twig_auto">text_format:</b> <p>This is a <strong>text format</strong> string.</p>');
    $this->assertRaw('<p>It contains "double" and \'single\' quotes with special characters like &lt;, &gt;, &lt;&gt;, and &gt;&lt;.</p><br />');
    $this->assertRaw('<b class="webform_computed_twig_auto">xss:</b> &lt;script&gt;alert(&quot;XSS&quot;);&lt;/script&gt;<br />');

    // Check Twig html rendering.
    $this->assertRaw('<b class="webform_computed_twig_html">number:</b> 2 * 2 = 4<br />');
    $this->assertRaw('<b class="webform_computed_twig_html">simple string:</b> This is a string<br />');
    $this->assertRaw('<b class="webform_computed_twig_html">complex string:</b> This is a &lt;strong&gt;complex&lt;/strong&gt; string, which contains &quot;double&quot; and &#039;single&#039; quotes with special characters like &gt;, &lt;, &gt;&lt;, and &lt;&gt;.<br />');
    $this->assertRaw('<b class="webform_computed_twig_html">xss:</b> &lt;script&gt;alert(&quot;XSS&quot;);&lt;/script&gt;<br />');

    // Check Twig plain text rendering.
    $this->assertRaw('number: 2 * 2 = 4<br />');
    $this->assertRaw('simple string: This is a string<br />');
    $this->assertRaw('complex string: This is a &lt;strong&gt;complex&lt;/strong&gt; string, which contains &quot;double&quot; and &#039;single&#039; quotes with special characters like &gt;, &lt;, &gt;&lt;, and &lt;&gt;.<br />');
    $this->assertRaw('text_format: This is a *text format* string.<br />');

    // Check Twig data rendering.
    $this->assertRaw('<b class="webform_computed_twig_data">number:</b> 2 * 2 = 4<br />');
    $this->assertRaw('<b class="webform_computed_twig_data">simple string:</b> This is a string<br />');
    $this->assertRaw('<b class="webform_computed_twig_data">complex string:</b> This is a &lt;strong&gt;complex&lt;/strong&gt; string, which contains &quot;double&quot; and &#039;single&#039; quotes with special characters like &gt;, &lt;, &gt;&lt;, and &lt;&gt;.<br />');
    $this->assertRaw('<b class="webform_computed_twig_data">text_format:</b> &lt;p&gt;This is a &lt;strong&gt;text format&lt;/strong&gt; string.&lt;/p&gt;');
    $this->assertRaw('<b class="webform_computed_twig_data">xss:</b> &lt;script&gt;alert(&quot;XSS&quot;);&lt;/script&gt;<br />');

  }

}
