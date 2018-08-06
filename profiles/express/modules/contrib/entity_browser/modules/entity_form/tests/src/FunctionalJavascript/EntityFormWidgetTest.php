<?php

namespace Drupal\Tests\entity_browser_entity_form\FunctionalJavascript;

use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\FunctionalJavascriptTests\JavascriptTestBase;

/**
 * Class for Entity browser entity form Javascript functional tests.
 *
 * @group entity_browser_entity_form
 */
class EntityFormWidgetTest extends JavascriptTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'entity_browser_entity_form_test',
    'ctools',
    'views',
    'block',
    'node',
    'file',
    'image',
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
      'field_name' => 'field_reference',
      'type' => 'entity_reference',
      'entity_type' => 'node',
      'cardinality' => FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED,
      'settings' => [
        'target_type' => 'node',
      ],
    ])->save();

    FieldConfig::create([
      'field_name' => 'field_reference',
      'entity_type' => 'node',
      'bundle' => 'foo',
      'label' => 'Reference',
      'settings' => [],
    ])->save();

    /** @var \Drupal\Core\Entity\Display\EntityFormDisplayInterface $form_display */
    $form_display = $this->container->get('entity_type.manager')
      ->getStorage('entity_form_display')
      ->load('node.foo.default');

    $form_display->setComponent('field_reference', [
      'type' => 'entity_browser_entity_reference',
      'settings' => [
        'entity_browser' => 'entity_browser_test_entity_form',
        'field_widget_display' => 'label',
        'open' => TRUE,
      ],
    ])->save();

    $account = $this->drupalCreateUser([
      'access entity_browser_test_entity_form entity browser pages',
      'create foo content',
      'access content',
    ]);
    $this->drupalLogin($account);
  }

  /**
   * Test if save button is appears on form.
   */
  public function testEntityForm() {
    /** @var \Drupal\entity_browser\EntityBrowserInterface $browser */
    $browser = $this->container->get('entity_type.manager')
      ->getStorage('entity_browser')
      ->load('entity_browser_test_entity_form');

    // Make sure that the "Save entities" button exists.
    $this->drupalGet('entity-browser/iframe/entity_browser_test_entity_form');
    $this->assertSession()->buttonExists('Save entity');

    // Change save button's text and make sure that the change was respected.
    $config = $browser->getWidget('9c6ee4c0-4642-4203-b4bd-ec0bad068ad3')->getConfiguration();
    $config['settings']['submit_text'] = 'Save node';
    $browser->getWidget('9c6ee4c0-4642-4203-b4bd-ec0bad068ad3')->setConfiguration($config);
    $browser->save();
    $this->drupalGet('entity-browser/iframe/entity_browser_test_entity_form');
    $this->assertSession()->buttonNotExists('Save entity');
    $this->assertSession()->buttonExists('Save node');

    // Make sure that the widget works correctly with the field widget
    $this->drupalGet('node/add/foo');
    $this->getSession()->getPage()->clickLink('Select entities');
    $this->getSession()->switchToIFrame('entity_browser_iframe_entity_browser_test_entity_form');
    $this->getSession()->getPage()->fillField('inline_entity_form[title][0][value]', 'War is peace');
    $this->getSession()->getPage()->pressButton('Save node');

    // Switch back to the main page.
    $this->getSession()->switchToIFrame();
    $this->assertSession()->assertWaitOnAjaxRequest();

    $this->assertSession()->pageTextContains('War is peace');
    $this->getSession()->getPage()->fillField('title[0][value]', 'Freedom is slavery');
    $this->getSession()->getPage()->pressButton('Save');

    $parent_node = $this->container->get('entity_type.manager')
      ->getStorage('node')
      ->loadByProperties(['title' => 'Freedom is slavery']);
    $parent_node = current($parent_node);
    $this->assertEquals(1, $parent_node->get('field_reference')->count(), 'There is one child node.');
    $this->assertEquals('War is peace', $parent_node->field_reference->entity->label(), 'Child node has correct title.');

    // Now try using Multi value selection display and make sure there is only
    // one node created by the Entity browser.
    $browser->setSelectionDisplay('multi_step_display')->save();
    $this->drupalGet('node/add/foo');
    $this->getSession()->getPage()->clickLink('Select entities');
    $this->getSession()->switchToIFrame('entity_browser_iframe_entity_browser_test_entity_form');
    $this->getSession()->getPage()->fillField('inline_entity_form[title][0][value]', 'War is peace');
    $this->getSession()->getPage()->pressButton('Save node');
    $this->getSession()->getPage()->pressButton('Use selected');

    // Switch back to the main page.
    $this->getSession()->switchToIFrame();
    $this->assertSession()->assertWaitOnAjaxRequest();

    $this->assertSession()->pageTextContains('War is peace');
    $this->getSession()->getPage()->fillField('title[0][value]', 'Ignorance is strength');
    $this->getSession()->getPage()->pressButton('Save');

    $parent_node = $this->container->get('entity_type.manager')
      ->getStorage('node')
      ->loadByProperties(['title' => 'Ignorance is strength']);
    $parent_node = current($parent_node);
    $this->assertEquals(1, $parent_node->get('field_reference')->count(), 'There is one child node.');
    $this->assertEquals('War is peace', $parent_node->field_reference->entity->label(), 'Child node has correct title.');
  }

}
