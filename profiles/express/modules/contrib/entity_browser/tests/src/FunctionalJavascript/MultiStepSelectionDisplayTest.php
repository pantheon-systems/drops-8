<?php

namespace Drupal\Tests\entity_browser\FunctionalJavascript;

use Drupal\Component\Utility\NestedArray;

/**
 * Test for multi_step_display selection display.
 *
 * @group entity_browser
 *
 * @package Drupal\Tests\entity_browser\FunctionalJavascript
 */
class MultiStepSelectionDisplayTest extends EntityBrowserJavascriptTestBase {

  /**
   * Open iframe entity browser and change scope to iframe.
   */
  protected function openEntityBrowser() {
    $this->getSession()->getPage()->clickLink('Select entities');
    $this->getSession()
      ->switchToIFrame('entity_browser_iframe_test_entity_browser_file');
    $this->waitForAjaxToFinish();
  }

  /**
   * Close iframe entity browser and change scope to base page.
   */
  protected function closeEntityBrowser() {
    $this->clickXpathSelector('//*[@data-drupal-selector="edit-use-selected"]');
    $this->getSession()->switchToIFrame();
    $this->waitForAjaxToFinish();
  }

  /**
   * Click on entity in view to be selected.
   *
   * @param string $entityId
   *   Entity ID that will be selected. Format: "file:1".
   */
  protected function clickViewEntity($entityId) {
    $xpathViewRow = '//*[./*[contains(@class, "views-field-entity-browser-select") and .//input[@name="entity_browser_select[' . $entityId . ']"]]]';

    $this->clickXpathSelector($xpathViewRow, FALSE);
  }

  /**
   * Wait for Ajax Commands to finish.
   *
   * Since commands are executed in batches, it can occur that one command is
   * still running and new one will be collected for next batch. To ensure all
   * of commands are executed, we have to add additional 200ms wait, before next
   * batch is triggered.
   *
   * It's related to: Drupal.entityBrowserCommandQueue.executeCommands
   */
  protected function waitSelectionDisplayAjaxCommands() {
    $this->waitForAjaxToFinish();
    $this->getSession()->wait(200);
    $this->waitForAjaxToFinish();
  }

  /**
   * Change selection mode for article reference field form display widget.
   *
   * @param array $configuration
   *   Configuration that will be used for field form display.
   */
  protected function changeFieldFormDisplayConfig(array $configuration) {
    /** @var \Drupal\Core\Entity\Display\EntityFormDisplayInterface $form_display */
    $form_display = $this->container->get('entity_type.manager')
      ->getStorage('entity_form_display')
      ->load('node.article.default');

    $form_display->setComponent(
      'field_reference',
      NestedArray::mergeDeep($form_display->getComponent('field_reference'), $configuration)
    )->save();
  }

  /**
   * Check that selection state in entity browser Inline Entity Form.
   */
  public function testAjaxCommands() {

    $this->createFile('test_file1');
    $this->createFile('test_file2');
    $this->createFile('test_file3');

    // Testing Action buttons (adding and removing) with usage of HTML View.
    $widget_configurations = [
      // View widget configuration.
      '774798f1-5ec5-4b63-84bd-124cd51ec07d' => [
        'settings' => [
          'view' => 'files_entity_browser_html',
          'auto_select' => TRUE,
        ],
      ],
    ];
    $this->getEntityBrowser('test_entity_browser_file', 'iframe', 'tabs', 'multi_step_display', [], [], [], $widget_configurations);
    $this->drupalGet('node/add/article');
    $this->openEntityBrowser();

    // Check that action buttons are not there.
    $this->assertSession()
      ->elementNotExists('xpath', '//*[@data-drupal-selector="edit-use-selected"]');
    $this->assertSession()
      ->elementNotExists('xpath', '//*[@data-drupal-selector="edit-show-selection"]');

    $this->clickViewEntity('file:1');
    $this->waitSelectionDisplayAjaxCommands();

    // Check that action buttons are there.
    $this->assertSession()
      ->elementExists('xpath', '//*[@data-drupal-selector="edit-use-selected"]');
    $this->assertSession()
      ->elementExists('xpath', '//*[@data-drupal-selector="edit-show-selection"]');

    // Click on first entity Remove button.
    $this->clickXpathSelector('//input[@data-row-id="0"]');
    $this->waitSelectionDisplayAjaxCommands();

    // Check that action buttons are not there.
    $this->assertSession()
      ->elementNotExists('xpath', '//*[@data-drupal-selector="edit-use-selected"]');
    $this->assertSession()
      ->elementNotExists('xpath', '//*[@data-drupal-selector="edit-show-selection"]');

    $this->clickViewEntity('file:1');
    $this->waitSelectionDisplayAjaxCommands();
    $this->closeEntityBrowser();

    // Testing quick adding and removing of entities with usage of Table
    // (default) view.
    $widget_configurations = [
      // View widget configuration.
      '774798f1-5ec5-4b63-84bd-124cd51ec07d' => [
        'settings' => [
          'view' => 'files_entity_browser',
          'auto_select' => TRUE,
        ],
      ],
    ];
    $this->getEntityBrowser('test_entity_browser_file', 'iframe', 'tabs', 'multi_step_display', [], [], [], $widget_configurations);
    $this->drupalGet('node/add/article');
    $this->openEntityBrowser();

    // Quickly add 5 entities.
    $entitiesToAdd = ['file:1', 'file:2', 'file:3', 'file:1', 'file:2'];
    foreach ($entitiesToAdd as $entityId) {
      $this->clickViewEntity($entityId);
    }
    $this->waitSelectionDisplayAjaxCommands();

    // Check that there are 5 entities in selection display list.
    $this->assertSession()
      ->elementsCount('xpath', '//div[contains(@class, "entities-list")]/*', 5);

    // Quickly remove all 5 entities.
    foreach (array_keys($entitiesToAdd) as $entityIndex) {
      $this->clickXpathSelector('//input[@data-row-id="' . $entityIndex . '"]');
    }
    $this->waitSelectionDisplayAjaxCommands();

    // Check that action buttons are not there.
    $this->assertSession()
      ->elementNotExists('xpath', '//*[@data-drupal-selector="edit-use-selected"]');
    $this->assertSession()
      ->elementNotExists('xpath', '//*[@data-drupal-selector="edit-show-selection"]');

    $this->clickViewEntity('file:1');
    $this->waitSelectionDisplayAjaxCommands();
    $this->closeEntityBrowser();

    // Testing adding with preselection with usage of Grid view.
    $widget_configurations = [
      // View widget configuration.
      '774798f1-5ec5-4b63-84bd-124cd51ec07d' => [
        'settings' => [
          'view' => 'files_entity_browser_grid',
          'auto_select' => TRUE,
        ],
      ],
    ];
    $this->getEntityBrowser('test_entity_browser_file', 'iframe', 'tabs', 'multi_step_display', [], [], [], $widget_configurations);

    // Change selection mode to 'Edit', to test adding/removing inside EB.
    $this->changeFieldFormDisplayConfig([
      'settings' => [
        'selection_mode' => 'selection_edit',
      ],
    ]);

    $this->drupalGet('node/add/article');
    $this->openEntityBrowser();

    $this->clickViewEntity('file:1');
    $this->waitSelectionDisplayAjaxCommands();
    $this->closeEntityBrowser();

    $this->openEntityBrowser();

    $this->clickViewEntity('file:2');
    $this->waitSelectionDisplayAjaxCommands();
    $this->closeEntityBrowser();

    $this->assertSession()
      ->elementsCount('xpath', '//div[contains(@class, "entities-list")]/*', 2);

    // Testing removing with preselection with usage of Unformatted view.
    $widget_configurations = [
      // View widget configuration.
      '774798f1-5ec5-4b63-84bd-124cd51ec07d' => [
        'settings' => [
          'view' => 'files_entity_browser_unformatted',
          'auto_select' => TRUE,
        ],
      ],
    ];
    $this->getEntityBrowser('test_entity_browser_file', 'iframe', 'tabs', 'multi_step_display', [], [], [], $widget_configurations);

    $this->drupalGet('node/add/article');
    $this->openEntityBrowser();

    // Select 3 entities.
    $entitiesToAdd = ['file:1', 'file:2', 'file:3'];
    foreach ($entitiesToAdd as $entityId) {
      $this->clickViewEntity($entityId);

      // For some reason PhantomJS crashes here on quick clicking. That's why
      // waiting is added. Selenium works fine.
      $this->waitSelectionDisplayAjaxCommands();
    }
    $this->closeEntityBrowser();

    // Check that there are 3 entities in selection list after closing of EB.
    $this->assertSession()
      ->elementsCount('xpath', '//div[contains(@class, "entities-list")]/*', 3);

    $this->openEntityBrowser();

    // Click on first entity Remove button.
    $this->clickXpathSelector('//input[@data-row-id="0"]');
    $this->waitSelectionDisplayAjaxCommands();

    $this->closeEntityBrowser();

    // Check that there are 2 entities in selection list after closing of EB.
    $this->assertSession()
      ->elementsCount('xpath', '//div[contains(@class, "entities-list")]/*', 2);
  }

}
