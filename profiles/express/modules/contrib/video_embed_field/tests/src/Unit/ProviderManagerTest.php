<?php

namespace Drupal\Tests\video_embed_field\Unit;

use Drupal\Tests\UnitTestCase;

/**
 * Tests the provider manager is working.
 *
 * @group video_embed_field
 */
class ProviderManagerTest extends UnitTestCase {

  /**
   * Mock providers to use for the test.
   *
   * @var array
   */
  protected $mockProviders = [
    'provider_a' => [
      'id' => 'provider_a',
      'title' => 'Provider A',
    ],
    'provider_b' => [
      'id' => 'provider_b',
      'title' => 'Provider B',
    ],
    'provider_c' => [
      'id' => 'provider_c',
      'title' => 'Provider C',
    ],
  ];

  /**
   * Test URL parsing works as expected.
   */
  public function testOptionsList() {
    $options = $this->getManagerMock()->getProvidersOptionList();
    $this->assertEquals($options, [
      'provider_a' => 'Provider A',
      'provider_b' => 'Provider B',
      'provider_c' => 'Provider C',
    ]);
  }

  /**
   * Test filtering the definition list from user input via checkboxes.
   *
   * @dataProvider optionsWithExpectedProviders
   */
  public function testDefinitionListFromOptionsList($user_input, $expected_providers) {
    $this->assertEquals($expected_providers, $this->getManagerMock()
      ->loadDefinitionsFromOptionList($user_input));
  }

  /**
   * A data provider for user input with expected filtered providers.
   *
   * @return array
   *   An array of test cases.
   */
  public function optionsWithExpectedProviders() {
    return [
      'Empty input: all providers' => [
        [],
        $this->mockProviders,
      ],
      'Empty checkbox input: all providers' => [
        [
          'provider_a' => '0',
          'provider_b' => '0',
          'provider_c' => '0',
        ],
        $this->mockProviders,
      ],
      'Some providers' => [
        [
          'provider_a' => '0',
          'provider_b' => 'provider_b',
          'provider_c' => 'provider_c',
        ],
        [
          'provider_b' => $this->mockProviders['provider_b'],
          'provider_c' => $this->mockProviders['provider_c'],
        ],
      ],
      'One provider' => [
        [
          'provider_a' => 'provider_a',
          'provider_b' => '0',
          'provider_c' => '0',
        ],
        [
          'provider_a' => $this->mockProviders['provider_a'],
        ],
      ],
    ];
  }

  /**
   * Get a mock provider manager.
   */
  protected function getManagerMock() {
    $definitions = $this->mockProviders;
    $manager = $this->getMockBuilder('Drupal\video_embed_field\ProviderManager')
      ->disableOriginalConstructor()
      ->setMethods(['getDefinitions', 'getDefinition', 'createInstance'])
      ->getMock();
    $manager
      ->method('getDefinitions')
      ->willReturn($definitions);
    $manager
      ->method('getDefinition')
      ->willReturnCallback(function ($value) use ($definitions) {
        return $definitions[$value];
      });
    $manager
      ->method('createInstance')
      ->willReturnCallback(function ($name) {
        return (object) ['id' => $name];
      });
    return $manager;
  }

}
