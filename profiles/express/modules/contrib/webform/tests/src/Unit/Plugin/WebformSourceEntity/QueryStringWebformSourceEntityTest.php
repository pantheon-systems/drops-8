<?php

namespace Drupal\Tests\webform\Unit\Plugin\WebformSourceEntity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Language\Language;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\webform\Plugin\WebformSourceEntity\QueryStringWebformSourceEntity;
use Drupal\Tests\UnitTestCase;
use Drupal\webform\WebformEntityReferenceManagerInterface;
use Drupal\webform\WebformInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Tests the "query_string" webform source entity plugin.
 *
 * @group webform
 *
 * @coversDefaultClass \Drupal\webform\Plugin\WebformSourceEntity\QueryStringWebformSourceEntity
 */
class QueryStringWebformSourceEntityTest extends UnitTestCase {

  /**
   * Tests detection of source entity via query string.
   *
   * @param array $options
   *   see ::providerGetCurrentSourceEntity.
   * @param bool $expect_source_entity
   *   Whether we expect the tested method to return the source entity.
   * @param string $assert_message
   *   Assert message to use.
   *
   * @see QueryStringWebformSourceEntity::getSourceEntity()
   *
   * @dataProvider providerGetCurrentSourceEntity
   */
  public function testGetCurrentSourceEntity(array $options, $expect_source_entity, $assert_message = '') {
    $options += [
      // Value for the setting 'form_prepopulate_source_entity' of the webform.
      'webform_settings_prepopulate_source_entity' => TRUE,

      // Source entity type.
      'source_entity_type' => 'node',
      // Source entity id.
      'source_entity_id' => 1,
       // Access result return by source entity 'view' operation.
      'source_entity_view_access_result' => TRUE,
      // Whether source entity has a populate webform field.
      'source_entity_has_webform_field' => TRUE,
       // Whether the source entity has translation.
      'source_entity_has_translation' => TRUE,

       // Source entity type return by request query string.
      'request_query_source_entity_type' => 'node',

       // Whether webform should be included in route object.
      'route_match_get_parameter_webform' => TRUE,

      // Array of entity types that may not be source.
      'ignored_types' => [],
    ];

    /**************************************************************************/

    $webform = $this->getMockWebform($options);
    list($source_entity, $source_entity_translation) = $this->getMockSourceEntity($options, $webform);

    // Mock source entity storage.
    $source_entity_storage = $this->getMockBuilder(EntityStorageInterface::class)
      ->getMock();
    $source_entity_storage->method('load')
      ->willReturnMap([
        [$options['source_entity_id'], $source_entity],
      ]);

    // Move entity type manager which returns the mock source entity storage.
    $entity_type_manager = $this->getMockBuilder(EntityTypeManagerInterface::class)
      ->disableOriginalConstructor()
      ->getMock();
    $entity_type_manager->method('hasDefinition')
      ->willReturnMap([
        [$options['source_entity_type'], TRUE],
      ]);
    $entity_type_manager->method('getStorage')
      ->willReturnMap([
        [$options['source_entity_type'], $source_entity_storage],
      ]);

    // Mock route match.
    $route_match = $this->getMockBuilder(RouteMatchInterface::class)
      ->disableOriginalConstructor()
      ->getMock();
    $route_match->method('getParameter')
      ->willReturnMap([
        ['webform', $options['route_match_get_parameter_webform'] ? $webform : NULL],
      ]);

    // Mock request stack.
    $request_stack = $this->getMockBuilder(RequestStack::class)
      ->disableOriginalConstructor()
      ->getMock();
    $request_stack->method('getCurrentRequest')
      ->will($this->returnValue(
        new Request([
          'source_entity_type' => $options['request_query_source_entity_type'],
          'source_entity_id' => $options['source_entity_id'],
        ])
      ));

    // Move entity reference manager.
    $webform_entity_reference_manager = $this->getMockBuilder(WebformEntityReferenceManagerInterface::class)
      ->disableOriginalConstructor()
      ->getMock();
    $webform_entity_reference_manager->method('getFieldNames')
      ->willReturnMap([
        [$source_entity, ['webform_field_name']],
        [$source_entity_translation, ['webform_field_name']],
      ]);

    // Mock language manager.
    $language_manager = $this->getMockBuilder(LanguageManagerInterface::class)
      ->disableOriginalConstructor()
      ->getMock();
    $language_manager->method('getCurrentLanguage')
      ->willReturn(new Language(['id' => 'es']));

    /**************************************************************************/

    // Create QueryStringWebformSourceEntity plugin instance.
    $plugin = new QueryStringWebformSourceEntity([], 'query_string', [], $entity_type_manager, $route_match, $request_stack, $language_manager, $webform_entity_reference_manager);

    $output = $plugin->getSourceEntity($options['ignored_types']);
    if ($expect_source_entity) {
      $this->assertSame($options['source_entity_has_translation'] ? $source_entity_translation : $source_entity, $output, $assert_message);
    }
    else {
      $this->assertNull($output, $assert_message);
    }
  }

  /**
   * Get mock webform entity.
   *
   * @param array $options
   *   Mock webform options.
   *
   * @return \PHPUnit\Framework\MockObject\MockObject
   *   A mocked webform entity.
   */
  protected function getMockWebform(array $options) {
    $webform = $this->getMockBuilder(WebformInterface::class)
      ->disableOriginalConstructor()
      ->getMock();
    $webform->method('getSetting')
      ->willReturnMap([
        ['form_prepopulate_source_entity', FALSE, $options['webform_settings_prepopulate_source_entity']],
      ]);
    $webform->method('id')
      ->willReturn('webform_id');
    return $webform;
  }

  /**
   * Get mocked source entity.
   *
   * @param array $options
   *   Mock source entity options.
   * @param \Drupal\webform\WebformInterface $webform
   *   A mocked webform.
   *
   * @return array
   *   An array containing a mocked source entity and its
   *   translated source entity.
   */
  protected function getMockSourceEntity(array $options, WebformInterface $webform) {
    // Mock source entity.
    $source_entity = $this->getMockBuilder(ContentEntityInterface::class)
      ->getMock();
    $source_entity->method('access')
      ->willReturnMap([
        ['view', NULL, FALSE, $options['source_entity_view_access_result']],
      ]);
    $source_entity->webform_field_name = [
      (object) ['target_id' => $options['source_entity_has_webform_field'] && !$options['source_entity_has_translation'] ? $webform->id() : 'other_webform'],
    ];

    // Mock source entity translation.
    $source_entity_translation = $this->getMockBuilder(ContentEntityInterface::class)
      ->getMock();
    $source_entity_translation->method('access')
      ->willReturnMap([
        ['view', NULL, FALSE, $options['source_entity_view_access_result']],
      ]);
    $source_entity_translation->webform_field_name = [
      (object) ['target_id' => $options['source_entity_has_webform_field'] && $options['source_entity_has_translation'] ? $webform->id() : 'other_webform'],
    ];

    // Add translation to source entity.
    $source_entity->method('hasTranslation')
      ->willReturnMap([
        ['es', $options['source_entity_has_translation']],
      ]);
    $source_entity->method('getTranslation')
      ->willReturnMap([
        ['es', $source_entity_translation],
      ]);

    return [$source_entity, $source_entity_translation];
  }

  /**
   * Data provider for testGetCurrentSourceEntity().
   *
   * @see testGetCurrentSourceEntity()
   */
  public function providerGetCurrentSourceEntity() {
    $tests[] = [
      [
        'source_entity_has_translation' => FALSE,
        'route_match_get_parameter_webform' => FALSE,
      ],
      FALSE,
      'No webform in route',
    ];
    $tests[] = [
      [
        'source_entity_has_translation' => FALSE,
        'request_query_source_entity_type' => 'user',
      ],
      FALSE,
      'Inexisting entity type in query string',
    ];
    $tests[] = [
      [
        'source_entity_view_access_result' => FALSE,
        'source_entity_has_translation' => FALSE,
      ],
      FALSE,
      'Source entity without "view" access',
    ];
    $tests[] = [
      [
        'source_entity_view_access_result' => FALSE,
      ],
      FALSE,
      'Source entity translated without "view" access',
    ];
    $tests[] = [
      [
        'source_entity_has_translation' => FALSE,
      ],
      TRUE,
      'Prepopulating of webform source entity is allowed',
    ];
    $tests[] = [
      [
        'source_entity_has_translation' => FALSE,
        'ignored_types' => ['node'],
      ],
      TRUE,
      'Ignored_types is not considered by query string plugin.',
    ];
    $tests[] = [
      [
        'webform_settings_prepopulate_source_entity' => FALSE,
        'source_entity_has_translation' => FALSE,
      ],
      TRUE,
      'Source entity references webform',
    ];
    $tests[] = [
      [
        'webform_settings_prepopulate_source_entity' => FALSE,
      ],
      TRUE,
      'Translation of source entity references webform',
    ];
    $tests[] = [
      [
        'webform_settings_prepopulate_source_entity' => FALSE,
        'source_entity_has_webform_field' => FALSE,
        'source_entity_has_translation' => FALSE,
      ],
      FALSE,
      'Source entity does not reference webform',
    ];
    $tests[] = [
      [
        'webform_settings_prepopulate_source_entity' => FALSE,
        'source_entity_has_webform_field' => FALSE,
      ],
      FALSE,
      'Translation of source entity does not reference webform',
    ];

    return $tests;
  }

}
