<?php

namespace Drupal\inline_entity_form\Tests;

use Drupal\node\Entity\Node;

/**
 * Tests translating inline entities.
 *
 * @group inline_entity_form
 */
class TranslationTest extends InlineEntityFormTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'content_translation', 'inline_entity_form_test', 'language',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->user = $this->createUser([
      'create ief_reference_type content',
      'edit any ief_reference_type content',
      'delete any ief_reference_type content',
      'create ief_test_complex content',
      'edit any ief_test_complex content',
      'delete any ief_test_complex content',
      'view own unpublished content',
      'administer content translation',
      'translate any entity',
      'create content translations',
      'administer languages',
    ]);
    $this->drupalLogin($this->user);

    // Enable translations for both entity types.
    $edit = [
      'entity_types[node]' => TRUE,
    ];
    foreach (['ief_test_complex', 'ief_reference_type'] as $node_type) {
      $edit['settings[node][' . $node_type . '][translatable]'] = TRUE;
      $edit['settings[node][' . $node_type . '][settings][language][language_alterable]'] = TRUE;
    }
    $this->drupalPostForm('admin/config/regional/content-language', $edit, t('Save configuration'));

    // Allow referencing existing entities.
    $form_display_storage = $this->container->get('entity_type.manager')->getStorage('entity_form_display');
    /** @var \Drupal\Core\Entity\Display\EntityFormDisplayInterface $display */
    $display = $form_display_storage->load('node.ief_test_complex.default');
    $component = $display->getComponent('multi');
    $component['settings']['allow_existing'] = TRUe;
    $display->setComponent('multi', $component)->save();
  }

  /**
   * Tests translating inline entities.
   */
  protected function testTranslation() {
    // Create a German node with a French translation.
    $first_inline_node = Node::create([
      'type' => 'ief_reference_type',
      'langcode' => 'de',
      'title' => 'An inline node',
      'first_name' => 'Dieter',
    ]);
    $translation = $first_inline_node->toArray();
    $translation['title'][0]['value'] = 'An inline node in French';
    $translation['first_name'][0]['value'] = 'Pierre';
    $first_inline_node->addTranslation('fr', $translation);
    $first_inline_node->save();

    $this->drupalGet('node/add/ief_test_complex');
    // Reference the German node.
    $this->drupalPostAjaxForm(NULL, [], $this->getButtonName('//input[@type="submit" and @value="Add existing node" and @data-drupal-selector="edit-multi-actions-ief-add-existing"]'));
    $edit = [
      'multi[form][entity_id]' => 'An inline node (' . $first_inline_node->id() . ')',
    ];
    $this->drupalPostAjaxForm(NULL, $edit, $this->getButtonName('//input[@type="submit" and @data-drupal-selector="edit-multi-form-actions-ief-reference-save"]'));
    $this->assertResponse(200, 'Adding a new referenced entity was successful.');

    // Add a new English inline node.
    $this->drupalPostAjaxForm(NULL, [], $this->getButtonName('//input[@type="submit" and @value="Add new node" and @data-drupal-selector="edit-multi-actions-ief-add"]'));
    $edit = [
      'multi[form][inline_entity_form][title][0][value]' => 'Another inline node',
      'multi[form][inline_entity_form][first_name][0][value]' => 'John',
      'multi[form][inline_entity_form][last_name][0][value]' => 'Smith',
    ];
    $this->drupalPostAjaxForm(NULL, $edit, $this->getButtonName('//input[@type="submit" and @value="Create node" and @data-drupal-selector="edit-multi-form-inline-entity-form-actions-ief-add-save"]'));
    $this->assertResponse(200, 'Creating a new inline entity was successful.');

    $edit = [
      'title[0][value]' => 'A node',
      'langcode[0][value]' => 'en',
    ];
    $this->drupalPostForm(NULL, $edit, t('Save'));
    $this->assertResponse(200, 'Saving the parent entity was successful.');

    // Both inline nodes should now be in English.
    $first_inline_node = $this->drupalGetNodeByTitle('An inline node');
    $second_inline_node = $this->drupalGetNodeByTitle('Another inline node');
    $this->assertEqual($first_inline_node->get('langcode')->value, 'en', 'The first inline entity has the correct langcode.');
    $this->assertEqual($second_inline_node->get('langcode')->value, 'en', 'The second inline entity has the correct langcode.');

    // Edit the node, change the source language to German.
    $node = $this->drupalGetNodeByTitle('A node');
    $this->drupalGet('node/' . $node->id() . '/edit');
    $edit = [
      'langcode[0][value]' => 'de',
    ];
    $this->drupalPostForm(NULL, $edit, t('Save'));
    $this->assertResponse(200, 'Saving the parent entity was successful.');

    // Both inline nodes should now be in German.
    $first_inline_node = $this->drupalGetNodeByTitle('An inline node', TRUE);
    $second_inline_node = $this->drupalGetNodeByTitle('Another inline node', TRUE);
    $this->assertEqual($first_inline_node->get('langcode')->value, 'de', 'The first inline entity has the correct langcode.');
    $this->assertEqual($second_inline_node->get('langcode')->value, 'de', 'The second inline entity has the correct langcode.');

    // Add a German -> French translation.
    $this->drupalGet('node/' . $node->id() . '/translations/add/de/fr');

    // Confirm that the translatability clue has been removed.
    $widget_title_element = $this->xpath('//fieldset[@id="edit-multi"]/legend/span');
    $this->assertEqual((string) $widget_title_element[0], 'Multiple nodes', 'The widget has the expected title.');
    // Confirm that the add and remove buttons are not present.
    $this->assertFalse((bool) $this->xpath('//input[@type="submit" and @value="Add new node" and @data-drupal-selector="edit-multi-actions-ief-add"]'), 'Add new node button does not appear in the table.');
    $this->assertFalse((bool) $this->xpath('//input[@type="submit" and @value="Remove"]'), 'Remove button does not appear in the table.');
    // Confirm the presence of the two node titles, in the expected languages.
    $this->assertTrue((bool) $this->xpath('//td[@class="inline-entity-form-node-label" and contains(.,"An inline node in French")]'), 'First inline node title appears in the table');
    $this->assertTrue((bool) $this->xpath('//td[@class="inline-entity-form-node-label" and contains(.,"Another inline node")]'), 'Second node title appears in the table');

    // Edit the translations of both inline entities.
    $this->drupalPostAjaxForm(NULL, [], $this->getButtonName('//input[@type="submit" and @value="Edit" and @data-drupal-selector="edit-multi-entities-0-actions-ief-entity-edit"]'));
    $this->assertNoText('Last name', 'The non-translatable last_name field is hidden.');
    $edit = [
      'multi[form][inline_entity_form][entities][0][form][title][0][value]' => 'An inline node in French!',
      'multi[form][inline_entity_form][entities][0][form][first_name][0][value]' => 'Damien',
    ];
    $this->drupalPostAjaxForm(NULL, $edit, $this->getButtonName('//input[@type="submit" and @value="Update node" and @data-drupal-selector="edit-multi-form-inline-entity-form-entities-0-form-actions-ief-edit-save"]'));

    $this->drupalPostAjaxForm(NULL, [], $this->getButtonName('//input[@type="submit" and @value="Edit" and @data-drupal-selector="edit-multi-entities-1-actions-ief-entity-edit"]'));
    $edit = [
      'multi[form][inline_entity_form][entities][1][form][title][0][value]' => 'Another inline node in French!',
      'multi[form][inline_entity_form][entities][1][form][first_name][0][value]' => 'Jacques',
    ];
    $this->drupalPostAjaxForm(NULL, $edit, $this->getButtonName('//input[@type="submit" and @value="Update node" and @data-drupal-selector="edit-multi-form-inline-entity-form-entities-1-form-actions-ief-edit-save"]'));

    $this->drupalPostForm(NULL, [], t('Save (this translation)'));
    $this->assertResponse(200, 'Saving the parent entity was successful.');

    // Load using the original titles, confirming they haven't changed.
    $first_inline_node = $this->drupalGetNodeByTitle('An inline node', TRUE);
    $second_inline_node = $this->drupalGetNodeByTitle('Another inline node', TRUE);
    // Confirm that the expected translated values are present.
    $this->assertTrue($first_inline_node->hasTranslation('fr'), 'The first inline entity has a FR translation');
    $this->assertTrue($second_inline_node->hasTranslation('fr'), 'The second inline entity has a FR translation');
    $first_translation = $first_inline_node->getTranslation('fr');
    $this->assertEqual($first_translation->title->value, 'An inline node in French!');
    $this->assertEqual($first_translation->first_name->value, 'Damien');
    $second_translation = $second_inline_node->getTranslation('fr');
    $this->assertEqual($second_translation->title->value, 'Another inline node in French!');
    $this->assertEqual($second_translation->first_name->value, 'Jacques');
  }

}
