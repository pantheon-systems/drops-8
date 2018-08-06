<?php

namespace Drupal\Tests\field_group_migrate\Kernel\Migrate\d7;

use Drupal\Core\Entity\Display\EntityDisplayInterface;
use Drupal\Tests\migrate_drupal\Kernel\d7\MigrateDrupal7TestBase;

/**
 * Tests field group migration.
 *
 * @group field_group
 */
class MigrateFieldGroupTest extends MigrateDrupal7TestBase {

  static $modules = [
    'field_group',
    'field_group_migrate',
    'comment',
    'datetime',
    'image',
    'link',
    'node',
    'taxonomy',
    'telephone',
    'text',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->loadFixture(__DIR__ . '/../../../../fixtures/drupal7.php');

    $this->installConfig(static::$modules);

    $this->executeMigrations([
      'd7_node_type',
      'd7_comment_type',
      'd7_view_modes',
      'd7_field',
      'd7_field_instance',
      'd7_field_formatter_settings',
      'd7_field_group',
    ]);
  }

  /**
   * Asserts various aspects of a migrated field group.
   *
   * @param $id
   *   The id of the entity display to which the field group applies.
   * @param $type
   *   The destination type.
   * @param $group_name
   *   The name of the field group.
   * @param $expected_label
   *   The expected label.
   * @param int $expected_weight
   *   The expected label.
   * @param array $expected_format_settings
   *   The expected format settings.
   * @param string $expected_format_type
   *   The expected format type.
   * @param array $expected_children
   *   The expected children.
   * @param string $expected_parent_name
   *   The expected parent name.
   */
  protected function assertEntity($id, $type, $group_name, $expected_label, $expected_weight = 0, $expected_format_settings = [], $expected_format_type = 'tabs', $expected_children = [], $expected_parent_name = '') {
    /** @var EntityDisplayInterface $entity */
    $entity = \Drupal::entityTypeManager()
      ->getStorage($type)
      ->load($id);
    $field_group_settings = $entity->getThirdPartySettings('field_group');
    $this->assertNotEmpty($field_group_settings);
    $this->assertArrayHasKey($group_name, $field_group_settings);
    $field_group = $field_group_settings[$group_name];
    $this->assertEquals($expected_label, $field_group['label']);
    $this->assertEquals($expected_format_settings, $field_group['format_settings']);
    $this->assertEquals($expected_children, $field_group['children']);
    $this->assertEquals($expected_parent_name, $field_group['parent_name']);
    $this->assertEquals($expected_weight, $field_group['weight']);
    $this->assertEquals($expected_format_type, $field_group['format_type']);
  }

  /**
   * Test field group migration from Drupal 7 to 8.
   */
  public function testFieldGroup() {
    $this->assertEntity('node.page.default', 'entity_view_display', 'group_page', 'Node group', 0, ['direction' => 'horizontal']);
    $this->assertEntity('user.user.default', 'entity_view_display', 'group_user', 'User group parent', 1, ['element' => 'div'], 'html_element');
    $this->assertEntity('user.user.default', 'entity_view_display', 'group_user_child', 'User group child', 99, ['direction' => 'vertical', 'label' => 'User group child', 'classes' => 'user-group-child', 'id' => 'group_article_node_article_teaser'], 'tabs', ['user_picture'], 'group_user');
    $this->assertEntity('node.article.teaser', 'entity_view_display', 'group_article', 'htab group', 2, ['classes' => 'htab-group'], 'tab', ['field_image']);

    // Check an entity_view_display without a field group.
    /** @var EntityDisplayInterface $entity */
    $entity = \Drupal::entityTypeManager()
      ->getStorage('entity_view_display')
      ->load('node.page.teaser');
    $field_group_settings = $entity->getThirdPartySettings('field_group');
    $this->assertEmpty($field_group_settings);

    $this->assertEntity('node.page.default', 'entity_form_display', 'group_page', 'Node form group', 0, ['direction' => 'horizontal']);
    $this->assertEntity('node.article.default', 'entity_form_display', 'group_article', 'htab form group', 2, [], 'tab', ['field_image']);

    // Check an entity_form_display without a field group.
    $entity = \Drupal::entityTypeManager()
      ->getStorage('entity_form_display')
      ->load('node.blog.default');
    $field_group_settings = $entity->getThirdPartySettings('field_group');
    $this->assertEmpty($field_group_settings);
  }
}
