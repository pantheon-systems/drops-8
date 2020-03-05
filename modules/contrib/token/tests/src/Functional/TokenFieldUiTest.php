<?php

namespace Drupal\Tests\token\Functional;

use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\node\Entity\NodeType;
use Drupal\node\Entity\Node;
use Drupal\file\Entity\File;
use Drupal\image\Entity\ImageStyle;

/**
 * Tests field ui.
 *
 * @group token
 */
class TokenFieldUiTest extends TokenTestBase {

  /**
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $adminUser;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['field_ui', 'node', 'image'];

  /**
   * {@inheritdoc}
   */
  public function setUp($modules = []) {
    parent::setUp();
    $this->adminUser = $this->drupalCreateUser(['administer content types', 'administer node fields']);
    $this->drupalLogin($this->adminUser);

    $node_type = NodeType::create([
      'type' => 'article',
      'name' => 'Article',
      'description' => "Use <em>articles</em> for time-sensitive content like news, press releases or blog posts.",
    ]);
    $node_type->save();

    FieldStorageConfig::create([
      'field_name' => 'field_body',
      'entity_type' => 'node',
      'type' => 'text_with_summary',
    ])->save();
    FieldConfig::create([
      'field_name' => 'field_body',
      'label' => 'Body',
      'entity_type' => 'node',
      'bundle' => 'article',
    ])->save();
    FieldStorageConfig::create([
      'field_name' => 'field_image',
      'entity_type' => 'node',
      'type' => 'image',
    ])->save();
    FieldConfig::create([
      'field_name' => 'field_image',
      'label' => 'Image',
      'entity_type' => 'node',
      'bundle' => 'article',
    ])->save();
    FieldStorageConfig::create([
      'field_name' => 'field_image_2',
      'entity_type' => 'node',
      'type' => 'image',
    ])->save();
    FieldConfig::create([
      'field_name' => 'field_image_2',
      'label' => 'Image 2',
      'entity_type' => 'node',
      'bundle' => 'article',
    ])->save();
    FieldStorageConfig::create([
      'field_name' => 'multivalued_field_image',
      'entity_type' => 'node',
      'type' => 'image',
    ])->save();
    FieldConfig::create([
      'field_name' => 'multivalued_field_image',
      'label' => 'Multivalued field image',
      'entity_type' => 'node',
      'bundle' => 'article',
    ])->save();

    entity_get_form_display('node', 'article', 'default')
      ->setComponent('field_body', [
        'type' => 'text_textarea_with_summary',
        'settings' => [
          'rows' => '9',
          'summary_rows' => '3',
        ],
        'weight' => 5,
      ])
      ->save();
  }

  public function testFileFieldUi() {
    $this->drupalGet('admin/structure/types/manage/article/fields/node.article.field_image');

    // Ensure the 'Browse available tokens' link is present and correct.
    $this->assertLink('Browse available tokens.');
    $this->assertLinkByHref('token/tree');

    // Ensure that the default file directory value validates correctly.
    $this->drupalPostForm(NULL, [], t('Save settings'));
    $this->assertText(t('Saved Image configuration.'));
  }

  public function testFieldDescriptionTokens() {
    $edit = [
      'description' => 'The site is called [site:name].',
    ];
    $this->drupalPostForm('admin/structure/types/manage/article/fields/node.article.field_body', $edit, 'Save settings');

    $this->drupalGet('node/add/article');
    $this->assertText('The site is called Drupal.');
  }

  /**
   * Test that tokens are correctly provided and replaced for the image fields.
   */
  public function testImageFieldTokens() {
    // Generate 2 different test images.
    $file_system = \Drupal::service('file_system');
    $file_system->copy(\Drupal::root() . '/core/misc/druplicon.png', 'public://example1.png');
    $file_system->copy(\Drupal::root() . '/core/misc/loading.gif', 'public://example2.gif');

    // Resize the test images so that they will be scaled down during token
    // replacement.
    $image1 = \Drupal::service('image.factory')->get('public://example1.png');
    $image1->resize(500, 500);
    $image1->save();
    $image2 = \Drupal::service('image.factory')->get('public://example2.gif');
    $image2->resize(500, 500);
    $image2->save();

    /** @var \Drupal\file\Entity\File $image1 */
    $image1 = File::create(['uri' => 'public://example1.png']);
    $image1->save();
    /** @var \Drupal\file\Entity\File $image2 */
    $image2 = File::create(['uri' => 'public://example2.gif']);
    $image2->save();

    $node = Node::create([
      'title' => 'Test node title',
      'type' => 'article',
      'field_image' => [
        [
          'target_id' => $image1->id(),
        ],
      ],
      'field_image_2' => [
        [
          'target_id' => $image2->id(),
        ],
      ],
      'multivalued_field_image' => [
        ['target_id' => $image1->id()],
        ['target_id' => $image2->id()],
      ],
    ]);
    $node->save();

    // Obtain the file size and dimension of the images that will be scaled
    // down during token replacement by applying the styles here.
    $style_thumbnail = ImageStyle::load('thumbnail');
    $style_thumbnail->createDerivative('public://example1.png', 'public://styles/thumbnail/public/example1-test.png');
    $style_thumbnail->createDerivative('public://example2.gif', 'public://styles/thumbnail/public/example2-test.gif');
    $image_1_thumbnail = \Drupal::service('image.factory')->get('public://styles/thumbnail/public/example1-test.png');
    $image_2_thumbnail = \Drupal::service('image.factory')->get('public://styles/thumbnail/public/example2-test.gif');
    $style_medium = ImageStyle::load('medium');
    $style_medium->createDerivative('public://example1.png', 'public://styles/medium/public/example1-test.png');
    $style_medium->createDerivative('public://example2.gif', 'public://styles/medium/public/example2-test.gif');
    $image_1_medium = \Drupal::service('image.factory')->get('public://styles/medium/public/example1-test.png');
    $image_2_medium = \Drupal::service('image.factory')->get('public://styles/medium/public/example2-test.gif');
    $style_large = ImageStyle::load('large');
    $style_large->createDerivative('public://example1.png', 'public://styles/large/public/example1-test.png');
    $style_large->createDerivative('public://example2.gif', 'public://styles/large/public/example2-test.gif');
    $image_1_large = \Drupal::service('image.factory')->get('public://styles/large/public/example1-test.png');
    $image_2_large = \Drupal::service('image.factory')->get('public://styles/large/public/example2-test.gif');

    // Delete the image derivatives, to make sure they are re-created.
    unlink('public://styles/thumbnail/public/example1-test.png');
    unlink('public://styles/medium/public/example1-test.png');
    unlink('public://styles/large/public/example1-test.png');
    unlink('public://styles/thumbnail/public/example2-test.gif');
    unlink('public://styles/medium/public/example2-test.gif');
    unlink('public://styles/large/public/example2-test.gif');

    $tokens = [
      // field_image
      'field_image:thumbnail:mimetype' => 'image/png',
      'field_image:medium:mimetype' => 'image/png',
      'field_image:large:mimetype' => 'image/png',
      'field_image:thumbnail:filesize' => $image_1_thumbnail->getFileSize(),
      'field_image:medium:filesize' => $image_1_medium->getFileSize(),
      'field_image:large:filesize' => $image_1_large->getFileSize(),
      'field_image:thumbnail:height' => '100',
      'field_image:medium:height' => '220',
      'field_image:large:height' => '480',
      'field_image:thumbnail:width' => '100',
      'field_image:medium:width' => '220',
      'field_image:large:width' => '480',
      'field_image:thumbnail:uri' => 'public://styles/thumbnail/public/example1.png',
      'field_image:medium:uri' => 'public://styles/medium/public/example1.png',
      'field_image:large:uri' => 'public://styles/large/public/example1.png',
      'field_image:thumbnail:url' => $style_thumbnail->buildUrl('public://example1.png'),
      'field_image:medium:url' => $style_medium->buildUrl('public://example1.png'),
      'field_image:large:url' => $style_large->buildUrl('public://example1.png'),
      'field_image:thumbnail' => $style_thumbnail->buildUrl('public://example1.png'),
      'field_image:medium' => $style_medium->buildUrl('public://example1.png'),
      'field_image:large' => $style_large->buildUrl('public://example1.png'),
      // field_image_2
      'field_image_2:thumbnail:mimetype' => 'image/gif',
      'field_image_2:medium:mimetype' => 'image/gif',
      'field_image_2:large:mimetype' => 'image/gif',
      'field_image_2:thumbnail:filesize' => $image_2_thumbnail->getFileSize(),
      'field_image_2:medium:filesize' => $image_2_medium->getFileSize(),
      'field_image_2:large:filesize' => $image_2_large->getFileSize(),
      'field_image_2:thumbnail:height' => '100',
      'field_image_2:medium:height' => '220',
      'field_image_2:large:height' => '480',
      'field_image_2:thumbnail:width' => '100',
      'field_image_2:medium:width' => '220',
      'field_image_2:large:width' => '480',
      'field_image_2:thumbnail:uri' => 'public://styles/thumbnail/public/example2.gif',
      'field_image_2:medium:uri' => 'public://styles/medium/public/example2.gif',
      'field_image_2:large:uri' => 'public://styles/large/public/example2.gif',
      'field_image_2:thumbnail:url' => $style_thumbnail->buildUrl('public://example2.gif'),
      'field_image_2:medium:url' => $style_medium->buildUrl('public://example2.gif'),
      'field_image_2:large:url' => $style_large->buildUrl('public://example2.gif'),
      'field_image_2:thumbnail' => $style_thumbnail->buildUrl('public://example2.gif'),
      'field_image_2:medium' => $style_medium->buildUrl('public://example2.gif'),
      'field_image_2:large' => $style_large->buildUrl('public://example2.gif'),
      // multivalued_field_image:0, test for thumbnail image style only.
      'multivalued_field_image:0:thumbnail:mimetype' => 'image/png',
      'multivalued_field_image:0:thumbnail:filesize' => $image_1_thumbnail->getFileSize(),
      'multivalued_field_image:0:thumbnail:height' => '100',
      'multivalued_field_image:0:thumbnail:width' => '100',
      'multivalued_field_image:0:thumbnail:uri' => 'public://styles/thumbnail/public/example1.png',
      'multivalued_field_image:0:thumbnail:url' => $style_thumbnail->buildUrl('public://example1.png'),
      'multivalued_field_image:0:thumbnail' => $style_thumbnail->buildUrl('public://example1.png'),
      // multivalued_field_image:1, test for medium image style only.
      'multivalued_field_image:1:medium:mimetype' => 'image/gif',
      'multivalued_field_image:1:medium:filesize' => $image_2_medium->getFileSize(),
      'multivalued_field_image:1:medium:height' => '220',
      'multivalued_field_image:1:medium:width' => '220',
      'multivalued_field_image:1:medium:uri' => 'public://styles/medium/public/example2.gif',
      'multivalued_field_image:1:medium:url' => $style_medium->buildUrl('public://example2.gif'),
      'multivalued_field_image:1:medium' => $style_medium->buildUrl('public://example2.gif'),
    ];
    $this->assertTokens('node', ['node' => $node], $tokens);

    /** @var \Drupal\token\Token $token_service */
    $token_service = \Drupal::service('token');

    // Test one of the image style's token info for cardinality 1 image field.
    $token_info = $token_service->getTokenInfo('node-field_image', 'thumbnail');
    $this->assertEquals('Thumbnail (100×100)', $token_info['name']);
    $this->assertEquals('Represents the image in the given image style.', $token_info['description']);

    // Test one of the image style's token info for a multivalued image field.
    $token_info = $token_service->getTokenInfo('node-multivalued_field_image', 'medium');
    $this->assertEquals('Medium (220×220)', $token_info['name']);
    $this->assertEquals('Represents the image in the given image style.', $token_info['description']);

    // Test few of the image styles' properties token info.
    $token_info = $token_service->getTokenInfo('image_with_image_style', 'mimetype');
    $this->assertEquals('MIME type', $token_info['name']);
    $this->assertEquals('The MIME type (image/png, image/bmp, etc.) of the image.', $token_info['description']);

    $token_info = $token_service->getTokenInfo('image_with_image_style', 'filesize');
    $this->assertEquals('File size', $token_info['name']);
    $this->assertEquals('The file size of the image.', $token_info['description']);
  }

}
