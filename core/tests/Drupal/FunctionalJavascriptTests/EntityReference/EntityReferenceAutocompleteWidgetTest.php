<?php

namespace Drupal\FunctionalJavascriptTests\EntityReference;

use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use Drupal\Tests\field\Traits\EntityReferenceTestTrait;
use Drupal\Tests\node\Traits\ContentTypeCreationTrait;
use Drupal\Tests\node\Traits\NodeCreationTrait;

/**
 * Tests the output of entity reference autocomplete widgets.
 *
 * @group entity_reference
 */
class EntityReferenceAutocompleteWidgetTest extends WebDriverTestBase {

  use ContentTypeCreationTrait;
  use EntityReferenceTestTrait;
  use NodeCreationTrait;

  /**
   * {@inheritdoc}
   */
  public static $modules = ['node'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Create a Content type and two test nodes.
    $this->createContentType(['type' => 'page']);
    $this->createNode(['title' => 'Test page']);
    $this->createNode(['title' => 'Page test']);

    $user = $this->drupalCreateUser([
      'access content',
      'create page content',
    ]);
    $this->drupalLogin($user);
  }

  /**
   * Tests that the default autocomplete widget return the correct results.
   */
  public function testEntityReferenceAutocompleteWidget() {
    // Create an entity reference field and use the default 'CONTAINS' match
    // operator.
    $field_name = 'field_test';
    $this->createEntityReferenceField('node', 'page', $field_name, $field_name, 'node', 'default', ['target_bundles' => ['page']]);
    $form_display = entity_get_form_display('node', 'page', 'default');
    $form_display->setComponent($field_name, [
        'type' => 'entity_reference_autocomplete',
        'settings' => [
          'match_operator' => 'CONTAINS',
        ],
      ]);
    // To satisfy config schema, the size setting must be an integer, not just
    // a numeric value. See https://www.drupal.org/node/2885441.
    $this->assertInternalType('integer', $form_display->getComponent($field_name)['settings']['size']);
    $form_display->save();
    $this->assertInternalType('integer', $form_display->getComponent($field_name)['settings']['size']);

    // Visit the node add page.
    $this->drupalGet('node/add/page');
    $page = $this->getSession()->getPage();
    $assert_session = $this->assertSession();

    $autocomplete_field = $assert_session->waitForElement('css', '[name="' . $field_name . '[0][target_id]"].ui-autocomplete-input');
    $autocomplete_field->setValue('Test');
    $this->getSession()->getDriver()->keyDown($autocomplete_field->getXpath(), ' ');
    $assert_session->waitOnAutocomplete();

    $results = $page->findAll('css', '.ui-autocomplete li');

    $this->assertCount(2, $results);
    $assert_session->pageTextContains('Test page');
    $assert_session->pageTextContains('Page test');

    // Now switch the autocomplete widget to the 'STARTS_WITH' match operator.
    entity_get_form_display('node', 'page', 'default')
      ->setComponent($field_name, [
        'type' => 'entity_reference_autocomplete',
        'settings' => [
          'match_operator' => 'STARTS_WITH',
        ],
      ])
      ->save();

    $this->drupalGet('node/add/page');
    $page = $this->getSession()->getPage();

    $autocomplete_field = $assert_session->waitForElement('css', '[name="' . $field_name . '[0][target_id]"].ui-autocomplete-input');
    $autocomplete_field->setValue('Test');
    $this->getSession()->getDriver()->keyDown($autocomplete_field->getXpath(), ' ');
    $assert_session->waitOnAutocomplete();

    $results = $page->findAll('css', '.ui-autocomplete li');

    $this->assertCount(1, $results);
    $assert_session->pageTextContains('Test page');
    $assert_session->pageTextNotContains('Page test');
  }

}
