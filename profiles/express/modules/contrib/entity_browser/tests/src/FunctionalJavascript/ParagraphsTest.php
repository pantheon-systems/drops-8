<?php

namespace Drupal\Tests\entity_browser\FunctionalJavascript;

/**
 * Tests entity browser integration with paragraphs.
 *
 * @group entity_browser
 */
class ParagraphsTest extends EntityBrowserJavascriptTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'ctools',
    'views',
    'block',
    'node',
    'file',
    'image',
    'field_ui',
    'views_ui',
    'system',
    'entity_browser_test_paragraphs',
  ];

  /**
   * {@inheritdoc}
   */
  protected static $userPermissions = [
    'access test_nodes entity browser pages',
    'create paragraphs_test content',
    'delete own paragraphs_test content',
    'access content',
    'edit own article content',
    'edit any paragraphs_test content',
  ];

  /**
   * Tests a flow of adding/removing references with paragraphs.
   */
  public function testParagraphs() {
    // Create an article that we'll reference later.
    $node = $this->createNode(['type' => 'article', 'title' => 'Hello world']);
    $node_nested = $this->createNode(['type' => 'article', 'title' => 'Nested node']);
    $node_nested_second = $this->createNode(['type' => 'article', 'title' => 'Nested second node']);
    $this->drupalGet('node/add/paragraphs_test');

    // Fill in the title field.
    $title = $this->assertSession()->fieldExists('Title');
    $title->setValue('Paragraph test');

    // Create a new paragraph referencing our article.
    $this->getSession()->getPage()->pressButton('field_paragraph_content_embed_add_more');
    $this->waitForAjaxToFinish();
    $this->assertSession()->linkExists('Select entities');
    $this->getSession()->getPage()->clickLink('Select entities');
    $this->getSession()->switchToIFrame('entity_browser_iframe_test_nodes');
    $this->getSession()->getPage()->checkField('entity_browser_select[node:' . $node->id() . ']');
    $this->getSession()->getPage()->pressButton('Select entities');

    // Switch back to the main page.
    $this->getSession()->switchToIFrame();
    $this->waitForAjaxToFinish();

    // Create another paragraph referencing our article.
    $this->getSession()->getPage()->pressButton('field_paragraph_content_embed_add_more');
    $this->waitForAjaxToFinish();
    $this->assertSession()->linkExists('Select entities');
    $this->getSession()->getPage()->clickLink('Select entities');
    $this->getSession()->switchToIFrame('entity_browser_iframe_test_nodes');
    $this->getSession()->getPage()->checkField('entity_browser_select[node:' . $node->id() . ']');
    $this->getSession()->getPage()->pressButton('Select entities');

    // Switch back to the main page.
    $this->getSession()->switchToIFrame();
    $this->waitForAjaxToFinish();

    // Ensure that two paragraphs now exist.
    $selector_1 = '.field--name-field-paragraph tbody tr:nth-child(1) .paragraphs-subform';
    $selector_2 = str_replace('1', '2', $selector_1);
    $this->assertSession()->elementExists('css', $selector_1);
    $this->assertSession()->elementExists('css', $selector_2);

    // Click the remove button on the second paragraph's article reference,
    // and ensure that only that instance of our article is removed.
    $this->click('.field--name-field-paragraph tbody tr:nth-child(2) .paragraphs-subform [value="Remove"]');
    $this->waitForAjaxToFinish();
    $this->assertSession()->elementTextNotContains('css', $selector_2, 'Hello world');
    $this->assertSession()->elementTextContains('css', $selector_1, 'Hello world');

    // Testing nested paragraphs field.
    $this->getSession()->getPage()->pressButton('field_paragraph_0_subform_field_paragraphs_nested_nested_paragraph_add_more');
    $this->waitForAjaxToFinish();
    $this->assertSession()->linkExists('Select entities');
    $this->getSession()->getPage()->clickLink('Select entities');
    $this->getSession()->switchToIFrame('entity_browser_iframe_test_nodes');
    $this->getSession()->getPage()->checkField('entity_browser_select[node:' . $node_nested->id() . ']');
    $this->getSession()->getPage()->pressButton('Select entities');

    // Switch back to the main page.
    $this->getSession()->switchToIFrame();
    $this->waitForAjaxToFinish();

    $this->getSession()->getPage()->pressButton('field_paragraph_0_subform_field_paragraphs_nested_nested_paragraph_add_more');
    $this->waitForAjaxToFinish();
    $this->assertSession()->linkExists('Select entities');
    $this->getSession()->getPage()->clickLink('Select entities');
    $this->getSession()->switchToIFrame('entity_browser_iframe_test_nodes');
    $this->getSession()->getPage()->checkField('entity_browser_select[node:' . $node_nested_second->id() . ']');
    $this->getSession()->getPage()->pressButton('Select entities');

    // Switch back to the main page.
    $this->getSession()->switchToIFrame();
    $this->waitForAjaxToFinish();

    // Test editing node in nested paragraph.
    $this->assertSession()->elementTextContains('css', '.field--name-field-paragraphs-nested tbody tr:nth-child(1) .paragraphs-subform', 'Nested node');
    $this->click('.field--name-field-paragraphs-nested tbody tr:nth-child(1) .paragraphs-subform [value="Edit"]');
    $this->waitForAjaxToFinish();
    $this->assertSession()->elementExists('css', '.ui-dialog .node-article-edit-form .form-item-title-0-value [value="Nested node"]');
    $this->getSession()->getPage()->pressButton('Close');

    // Submit the form.
    $this->submitForm([], 'Save');
    $this->assertSession()->linkExists('Hello world');
    $this->assertSession()->elementTextContains('css', '.field--name-field-paragraphs-nested', 'Nested node');
    $this->assertSession()->elementTextContains('css', '.field--name-field-paragraphs-nested', 'Nested second node');

    $parent_node = $this->container->get('entity_type.manager')
      ->getStorage('node')
      ->loadByProperties(['title' => 'Paragraph test']);
    $this->drupalGet('node/' . current($parent_node)->id() . '/edit');

    // Test removing node in nested paragraph.
    $this->click('.field--name-field-paragraphs-nested tbody tr:nth-child(1) .paragraphs-subform [value="Remove"]');
    $this->waitForAjaxToFinish();
    $this->assertSession()->elementTextNotContains('css', '.field--name-field-paragraphs-nested tbody tr:nth-child(1) .paragraphs-subform', 'Nested node');
    $this->assertSession()->elementTextContains('css', '.field--name-field-paragraphs-nested tbody tr:nth-child(2) .paragraphs-subform', 'Nested second node');

    // Submit the form.
    $this->submitForm([], 'Save');

    // Make sure the form submitted and a link to the articles are present.
    $this->assertSession()->linkExists('Hello world');
    $this->assertSession()->linkNotExists('Nested node');
    $this->assertSession()->elementTextContains('css', '.field--name-field-paragraphs-nested', 'Nested second node');
  }

}
