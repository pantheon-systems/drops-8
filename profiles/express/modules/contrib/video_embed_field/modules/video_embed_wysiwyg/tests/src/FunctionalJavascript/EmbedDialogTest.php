<?php

namespace Drupal\Tests\video_embed_wysiwyg\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\JavascriptTestBase;
use Drupal\simpletest\ContentTypeCreationTrait;

/**
 * Test the dialog form.
 *
 * @group video_embed_wysiwyg
 */
class EmbedDialogTest extends JavascriptTestBase {

  use ContentTypeCreationTrait;

  /**
   * Modules to install.
   *
   * @var array
   */
  public static $modules = [
    'video_embed_field',
    'video_embed_wysiwyg',
    'editor',
    'ckeditor',
    'field_ui',
    'node',
    'image',
  ];

  /**
   * An admin account for testing.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $adminUser;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    $this->adminUser = $this->drupalCreateUser(array_keys($this->container->get('user.permissions')->getPermissions()));
    $this->drupalLogin($this->adminUser);
    $this->createContentType(['type' => 'page']);
    \Drupal::configFactory()->getEditable('image.settings')->set('suppress_itok_output', TRUE)->save();

    // Assert access is denied without enabling the filter.
    $this->drupalGet('video-embed-wysiwyg/dialog/plain_text');
    $this->assertEquals(403, $this->getSession()->getStatusCode());

    // Enable the filter.
    $this->drupalGet('admin/config/content/formats/manage/plain_text');
    $this->find('[name="editor[editor]"]')->setValue('ckeditor');
    $this->assertSession()->assertWaitOnAjaxRequest();
    $this->submitForm([
      'filters[video_embed_wysiwyg][status]' => TRUE,
      'filters[filter_html_escape][status]' => FALSE,
      'editor[settings][toolbar][button_groups]' => '[[{"name":"Group","items":["video_embed","Source"]}]]',
    ], t('Save configuration'));

    // Visit the modal again.
    $this->drupalGet('video-embed-wysiwyg/dialog/plain_text');
    $this->assertEquals(200, $this->getSession()->getStatusCode());
  }

  /**
   * Test the WYSIWYG embed modal.
   */
  public function testEmbedDialog() {
    // Use the modal to embed into a page.
    $this->drupalGet('node/add/page');
    $this->find('.cke_button__video_embed')->click();
    $this->assertSession()->assertWaitOnAjaxRequest();

    // Assert all the form fields appear on the modal.
    $this->assertSession()->pageTextContains('Autoplay');
    $this->assertSession()->pageTextContains('Responsive Video');
    $this->assertSession()->pageTextContains('Width');
    $this->assertSession()->pageTextContains('Height');
    $this->assertSession()->pageTextContains('Video URL');

    // Attempt to submit the modal with no values.
    $this->find('input[name="video_url"]')->setValue('');
    $this->find('button.form-submit')->click();
    $this->assertSession()->assertWaitOnAjaxRequest();
    $this->assertSession()->pageTextContains('Video URL field is required.');

    // Submit the form with an invalid video URL.
    $this->find('input[name="video_url"]')->setValue('http://example.com/');
    $this->find('button.form-submit')->click();
    $this->assertSession()->assertWaitOnAjaxRequest();
    $this->assertSession()->pageTextContains('Could not find a video provider to handle the given URL.');
    $this->assertContains('http://example.com/', $this->getSession()->getPage()->getHtml());

    // Submit a valid URL.
    $this->find('input[name="video_url"]')->setValue('https://www.youtube.com/watch?v=iaf3Sl2r3jE&t=1553s');
    $this->find('button.form-submit')->click();
    $this->assertSession()->assertWaitOnAjaxRequest();
    // View the source of the ckeditor and find the output.
    $this->find('.cke_button__source_label')->click();
    $base_path = \Drupal::request()->getBasePath();
    $this->assertEquals('<p>{"preview_thumbnail":"' . rtrim($base_path, '/') . '/' . $this->publicFilesDirectory . '/styles/video_embed_wysiwyg_preview/public/video_thumbnails/iaf3Sl2r3jE.jpg","video_url":"https://www.youtube.com/watch?v=iaf3Sl2r3jE&amp;t=1553s","settings":{"responsive":1,"width":"854","height":"480","autoplay":1},"settings_summary":["Embedded Video (Responsive, autoplaying)."]}</p>', trim($this->getSession()->getPage()->find('css', '.cke_source')->getValue()));
  }

  /**
   * Test the WYSIWYG integration works with nested markup.
   */
  public function testNestedMarkup() {
    $nested_content = '<div class="nested-content">
<p>{"preview_thumbnail":"/thumb.jpg","video_url":"https://www.youtube.com/watch?v=iaf3Sl2r3jE","settings":{"responsive":1,"width":"854","height":"480","autoplay":1},"settings_summary":["Embedded Video (Responsive, autoplaying)."]}</p>
</div>';
    $node = $this->createNode([
      'type' => 'page',
      'body' => ['value' => $nested_content],
    ]);
    $this->drupalGet('node/' . $node->id() . '/edit');
    $this->find('.cke_button__source_label')->click();
    $this->assertEquals($nested_content, trim($this->getSession()->getPage()->find('css', '.cke_source')->getValue()));
  }

  /**
   * Find an element based on a CSS selector.
   *
   * @param string $css_selector
   *   A css selector to find an element for.
   *
   * @return \Behat\Mink\Element\NodeElement|null
   *   The found element or null.
   */
  protected function find($css_selector) {
    return $this->getSession()->getPage()->find('css', $css_selector);
  }

}
