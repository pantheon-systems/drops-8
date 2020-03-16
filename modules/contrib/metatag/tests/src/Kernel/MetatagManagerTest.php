<?php

namespace Drupal\Tests\metatag\Kernel;

use Drupal\KernelTests\KernelTestBase;

/**
 * Test the Metatag Manager class.
 *
 * @group metatag
 */
class MetatagManagerTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['system', 'field', 'text', 'user', 'metatag', 'metatag_open_graph'];

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The metatag manager.
   *
   * @var \Drupal\metatag\MetatagManagerInterface
   */
  protected $metatagManager;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->entityTypeManager = $this->container->get('entity_type.manager');
    $this->metatagManager = $this->container->get('metatag.manager');

    $this->installConfig(['system', 'field', 'text', 'user', 'metatag', 'metatag_open_graph']);
    $this->installEntitySchema('user');
    $this->installSchema('user', ['users_data']);
  }

  /**
   * Tests default tags for user entity.
   */
  public function testDefaultTagsFromEntity() {
    /** @var \Drupal\user\UserInterface $user */
    $user = $this->entityTypeManager->getStorage('user')->create();

    $default_tags = $this->metatagManager->defaultTagsFromEntity($user);
    $expected_tags = [
      'canonical_url' => '[user:url]',
      'title' => '[user:display-name] | [site:name]',
      'description' => '[site:name]',
    ];

    $this->assertSame($expected_tags, $default_tags);
  }

  /**
   * Test the order of the meta tags as they are output.
   */
  public function testMetatagOrder() {
    $tags = $this->metatagManager->generateElements([
      'og_image_width' => 100,
      'og_image_height' => 100,
      'og_image_url' => 'http://www.example.com/example/foo.png',
    ]);

    $expected = [
      '#attached' => [
        'html_head' => [
          [
            [
              '#tag' => 'meta',
              '#attributes' => [
                'property' => 'og:image:url',
                'content' => 'http://www.example.com/example/foo.png',
              ],
            ],
            'og_image_url_0',
          ],
          [
            [
              '#tag' => 'meta',
              '#attributes' => [
                'property' => 'og:image:width',
                'content' => 100,
              ],
            ],
            'og_image_width',
          ],
          [
            [
              '#tag' => 'meta',
              '#attributes' => [
                'property' => 'og:image:height',
                'content' => 100,
              ],
            ],
            'og_image_height',
          ],
        ],
      ],
    ];
    $this->assertEquals($expected, $tags);
  }

  /**
   * Tests metatags with multiple values return multiple metatags.
   */
  public function testMetatagMultiple() {
    $tags = $this->metatagManager->generateElements([
      'og_image_width' => 100,
      'og_image_height' => 100,
      'og_image_url' => 'http://www.example.com/example/foo.png, http://www.example.com/example/foo2.png',
    ]);

    $expected = [
      '#attached' => [
        'html_head' => [
          [
            [
              '#tag' => 'meta',
              '#attributes' => [
                'property' => 'og:image:url',
                'content' => 'http://www.example.com/example/foo.png',
              ],
            ],
            'og_image_url_0',
          ],
          [
            [
              '#tag' => 'meta',
              '#attributes' => [
                'property' => 'og:image:url',
                'content' => 'http://www.example.com/example/foo2.png',
              ],
            ],
            'og_image_url_1',
          ],
          [
            [
              '#tag' => 'meta',
              '#attributes' => [
                'property' => 'og:image:width',
                'content' => 100,
              ],
            ],
            'og_image_width',
          ],
          [
            [
              '#tag' => 'meta',
              '#attributes' => [
                'property' => 'og:image:height',
                'content' => 100,
              ],
            ],
            'og_image_height',
          ],
        ],
      ],
    ];
    $this->assertEquals($expected, $tags);
  }

}
