<?php

namespace Drupal\Tests\entity_browser\FunctionalJavascript;

use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;

/**
 * Tests entity browser pager form element.
 *
 * @group entity_browser
 */
class PagerElementTest extends EntityBrowserJavascriptTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'entity_browser_test',
    'node',
    'field_ui',
    'views_ui',
    'system',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->drupalCreateContentType(['type' => 'foo', 'name' => 'Foo']);

    FieldStorageConfig::create([
      'field_name' => 'field_reference_pager',
      'type' => 'entity_reference',
      'entity_type' => 'node',
      'cardinality' => FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED,
      'settings' => [
        'target_type' => 'node',
      ],
    ])->save();


    FieldConfig::create([
      'field_name' => 'field_reference_pager',
      'entity_type' => 'node',
      'bundle' => 'foo',
      'label' => 'Reference',
      'settings' => [],
    ])->save();

    /** @var \Drupal\Core\Entity\Display\EntityFormDisplayInterface $form_display */
    $form_display = $this->container->get('entity_type.manager')
      ->getStorage('entity_form_display')
      ->load('node.foo.default');

    $form_display->setComponent('field_reference_pager', [
      'type' => 'entity_browser_entity_reference',
      'settings' => [
        'entity_browser' => 'pager',
        'field_widget_display' => 'label',
        'open' => TRUE,
      ],
    ])->save();

    $account = $this->drupalCreateUser([
      'access pager entity browser pages',
      'create foo content',
      'access content',
    ]);
    $this->drupalLogin($account);
  }

  /**
   * Test entity browser pager form element.
   */
  public function testPagerElement() {
    $this->drupalGet('/entity-browser/iframe/pager');
    // Go through pager next and previous buttons and assert pages.
    $this->assertSession()->pageTextContains('Current page reported by the element is: 1.');
    $this->assertSession()->buttonExists('‹ Previous')->hasAttribute('disabled');
    $this->assertSession()->pageTextContains('Page 1');
    $this->assertSession()->buttonExists('Next ›');
    $this->getSession()->getPage()->pressButton('Next ›');
    $this->assertSession()->pageTextContains('Current page reported by the element is: 2.');
    $this->assertSession()->pageTextContains('Page 2');
    $this->getSession()->getPage()->pressButton('Next ›');
    $this->assertSession()->pageTextContains('Current page reported by the element is: 3.');
    $this->assertSession()->pageTextContains('Page 3');
    $this->getSession()->getPage()->pressButton('Next ›');
    $this->assertSession()->pageTextContains('Current page reported by the element is: 4.');
    $this->assertSession()->pageTextContains('Page 4');
    $this->assertSession()->buttonExists('Next ›')->hasAttribute('disabled');

    // Go back.
    $this->getSession()->getPage()->pressButton('‹ Previous');
    $this->assertSession()->pageTextContains('Current page reported by the element is: 3.');
    $this->assertSession()->pageTextContains('Page 3');
    $this->getSession()->getPage()->pressButton('‹ Previous');
    $this->assertSession()->pageTextContains('Current page reported by the element is: 2.');
    $this->assertSession()->pageTextContains('Page 2');
    $this->getSession()->getPage()->pressButton('‹ Previous');
    $this->assertSession()->pageTextContains('Current page reported by the element is: 1.');
    $this->assertSession()->pageTextContains('Page 1');
    $this->assertSession()->buttonExists('‹ Previous')->hasAttribute('disabled');

    // Test reset button.
    $this->getSession()->getPage()->pressButton('Last page');
    $this->assertSession()->pageTextContains('Current page reported by the element is: 4.');
    $this->assertSession()->pageTextContains('Page 4');
    $this->getSession()->getPage()->pressButton('First page');
    $this->assertSession()->pageTextContains('Current page reported by the element is: 1.');
    $this->assertSession()->pageTextContains('Page 1');
  }

}
