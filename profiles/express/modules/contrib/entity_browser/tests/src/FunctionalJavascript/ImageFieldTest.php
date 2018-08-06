<?php

namespace Drupal\Tests\entity_browser\FunctionalJavascript;

use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\entity_browser\Element\EntityBrowserElement;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\file\Entity\File;
use Drupal\node\Entity\Node;

/**
 * Tests the image field widget.
 *
 * @group entity_browser
 */
class ImageFieldTest extends EntityBrowserJavascriptTestBase {

  /**
   * Created file entity.
   *
   * @var \Drupal\file\Entity\File
   */
  protected $image;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    FieldStorageConfig::create([
      'field_name' => 'field_image',
      'type' => 'image',
      'entity_type' => 'node',
      'cardinality' => FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED,
    ])->save();

    FieldConfig::create([
      'field_name' => 'field_image',
      'entity_type' => 'node',
      'bundle' => 'article',
      'label' => 'Images',
      'settings' => [
        'file_extensions' => 'jpg',
        'file_directory' => 'entity-browser-test',
        'max_resolution' => '40x40',
        'title_field' => TRUE,
      ],
    ])->save();

    file_unmanaged_copy(\Drupal::root() . '/core/modules/simpletest/files/image-test.jpg', 'public://example.jpg');
    $this->image = File::create([
      'uri' => 'public://example.jpg',
    ]);
    $this->image->save();
    // Register usage for this file to avoid validation erros when referencing
    // this file on node save.
    \Drupal::service('file.usage')->add($this->image, 'entity_browser', 'test', '1');

    /** @var \Drupal\Core\Entity\Display\EntityFormDisplayInterface $form_display */
    $form_display = $this->container->get('entity_type.manager')
      ->getStorage('entity_form_display')
      ->load('node.article.default');

    $form_display->setComponent('field_image', [
      'type' => 'entity_browser_file',
      'settings' => [
        'entity_browser' => 'test_entity_browser_iframe_view',
        'open' => TRUE,
        'field_widget_edit' => FALSE,
        'field_widget_remove' => TRUE,
        'selection_mode' => EntityBrowserElement::SELECTION_MODE_APPEND,
        'view_mode' => 'default',
        'preview_image_style' => 'thumbnail',
      ],
    ])->save();

    $display_config = [
      'width' => '650',
      'height' => '500',
      'link_text' => 'Select images',
    ];
    /** @var \Drupal\entity_browser\EntityBrowserInterface $browser */
    $browser = $this->container->get('entity_type.manager')
      ->getStorage('entity_browser')
      ->load('test_entity_browser_iframe_view');
    $browser->setDisplay('iframe');
    $browser->getDisplay()->setConfiguration($display_config);
    $browser->addWidget([
      // These settings should get overridden by our field settings.
      'settings' => [
        'upload_location' => 'public://',
        'extensions' => 'png',
      ],
      'weight' => 1,
      'label' => 'Upload images',
      'id' => 'upload',
    ]);
    $browser->setWidgetSelector('tabs');
    $browser->save();

    $account = $this->drupalCreateUser([
      'access test_entity_browser_iframe_view entity browser pages',
      'create article content',
      'edit own article content',
      'access content',
    ]);
    $this->drupalLogin($account);
  }

  /**
   * Tests basic usage for an image field.
   */
  public function testImageFieldUsage() {
    $this->drupalGet('node/add/article');
    $this->assertSession()->linkExists('Select images');
    $this->getSession()->getPage()->clickLink('Select images');
    $this->getSession()->switchToIFrame('entity_browser_iframe_test_entity_browser_iframe_view');
    $this->getSession()->getPage()->checkField('entity_browser_select[file:' . $this->image->id() . ']');
    $this->getSession()->getPage()->pressButton('Select entities');
    $this->getSession()->getPage()->pressButton('Use selected');
    $this->assertSession()->pageTextContains('example.jpg');
    // Switch back to the main page.
    $this->getSession()->switchToIFrame();
    $this->waitForAjaxToFinish();
    // Check if the image thumbnail exists.
    $this->assertSession()->elementExists('xpath', '//*[@data-drupal-selector="edit-field-image-current-' . $this->image->id() . '-display"]');
    // Test if the image filename is present.
    $this->assertSession()->pageTextContains('example.jpg');
    // Test specifying Alt and Title texts and saving the node.
    $alt_text = 'Test alt text.';
    $title_text = 'Test title text.';
    $this->getSession()->getPage()->fillField('field_image[current][1][meta][alt]', $alt_text);
    $this->getSession()->getPage()->fillField('field_image[current][1][meta][title]', $title_text);
    $this->getSession()->getPage()->fillField('title[0][value]', 'Node 1');
    $this->getSession()->getPage()->pressButton('Save');
    $this->assertSession()->pageTextContains('Article Node 1 has been created.');
    $node = Node::load(1);
    $saved_alt = $node->get('field_image')[0]->alt;
    $this->assertEquals($saved_alt, $alt_text);
    $saved_title = $node->get('field_image')[0]->title;
    $this->assertEquals($saved_title, $title_text);
    // Test the Delete functionality.
    $this->drupalGet('node/1/edit');
    $this->assertSession()->buttonExists('Remove');
    $this->getSession()->getPage()->pressButton('Remove');
    $this->waitForAjaxToFinish();
    // Image filename should not be present.
    $this->assertSession()->pageTextNotContains('example.jpg');
    $this->assertSession()->linkExists('Select entities');
  }

  /**
   * Tests that settings are passed from the image field to the upload widget.
   */
  public function testImageFieldSettings() {
    $root = \Drupal::root();
    $file_wrong_type = $root . '/core/misc/druplicon.png';
    $file_too_big = $root . '/core/modules/simpletest/files/image-2.jpg';
    $file_just_right = $root . '/core/modules/simpletest/files/image-test.jpg';
    $this->drupalGet('node/add/article');
    $this->assertSession()->linkExists('Select images');
    $this->getSession()->getPage()->clickLink('Select images');
    $this->getSession()->switchToIFrame('entity_browser_iframe_test_entity_browser_iframe_view');
    // Switch to the image tab.
    $this->clickLink('Upload images');
    // Attempt to upload an invalid image type. The upload widget is configured
    // to allow png but the field widget is configured to allow jpg, so we
    // expect the field to override the widget.
    $this->getSession()->getPage()->attachFileToField('files[upload][]', $file_wrong_type);
    $this->waitForAjaxToFinish();
    $this->assertSession()->pageTextContains('Only files with the following extensions are allowed: jpg');
    $this->assertSession()->pageTextContains('The specified file druplicon.png could not be uploaded');
    // Upload an image bigger than the field widget's configured max size.
    $this->getSession()->getPage()->attachFileToField('files[upload][]', $file_too_big);
    $this->waitForAjaxToFinish();
    $this->assertSession()->pageTextContains('The image was resized to fit within the maximum allowed dimensions of 40x40 pixels.');
    // Upload an image that passes validation and finish the upload.
    $this->getSession()->getPage()->attachFileToField('files[upload][]', $file_just_right);
    $this->waitForAjaxToFinish();
    $this->getSession()->getPage()->pressButton('Select files');
    $this->getSession()->getPage()->pressButton('Use selected');
    $this->assertSession()->pageTextContains('image-test.jpg');
    // Check that the file has uploaded to the correct sub-directory.
    $this->getSession()->switchToIFrame();
    $this->waitForAjaxToFinish();
    $entity_id = $this->getSession()->evaluateScript('jQuery("#edit-field-image-wrapper [data-entity-id]").data("entity-id")');
    $this->assertStringStartsWith('file:', $entity_id);
    /** @var \Drupal\file\Entity\File $file */
    $fid = explode(':', $entity_id)[1];
    $file = File::load($fid);
    $this->assertContains('entity-browser-test', $file->getFileUri());
  }

}
