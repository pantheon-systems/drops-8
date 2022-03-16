<?php

namespace Drupal\Tests\views_ui\Functional;

/**
 * Tests the UI preview functionality.
 *
 * @group views_ui
 */
class PreviewTest extends UITestBase {

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = ['test_preview', 'test_preview_error', 'test_pager_full', 'test_mini_pager', 'test_click_sort'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'classy';

  /**
   * Tests contextual links in the preview form.
   */
  public function testPreviewContextual() {
    \Drupal::service('module_installer')->install(['contextual']);
    $this->resetAll();

    $this->drupalGet('admin/structure/views/view/test_preview/edit');
    $this->assertSession()->statusCodeEquals(200);
    $this->submitForm($edit = [], 'Update preview');

    // Verify that the contextual link to add a new field is shown.
    $this->assertSession()->elementsCount('xpath', '//div[@id="views-live-preview"]//ul[contains(@class, "contextual-links")]/li[contains(@class, "filter-add")]', 1);

    $this->submitForm($edit = ['view_args' => '100'], 'Update preview');

    // Test that area text and exposed filters are present and rendered.
    $this->assertSession()->fieldExists('id');
    $this->assertSession()->pageTextContains('Test header text');
    $this->assertSession()->pageTextContains('Test footer text');
    $this->assertSession()->pageTextContains('Test empty text');
  }

  /**
   * Tests arguments in the preview form.
   */
  public function testPreviewUI() {
    $this->drupalGet('admin/structure/views/view/test_preview/edit');
    $this->assertSession()->statusCodeEquals(200);

    $this->submitForm($edit = [], 'Update preview');

    $this->assertSession()->elementsCount('xpath', '//div[@class = "view-content"]/div[contains(@class, views-row)]', 5);

    // Filter just the first result.
    $this->submitForm($edit = ['view_args' => '1'], 'Update preview');
    $this->assertSession()->elementsCount('xpath', '//div[@class = "view-content"]/div[contains(@class, views-row)]', 1);

    // Filter for no results.
    $this->submitForm($edit = ['view_args' => '100'], 'Update preview');
    $this->assertSession()->elementNotExists('xpath', '//div[@class = "view-content"]/div[contains(@class, views-row)]');

    // Test that area text and exposed filters are present and rendered.
    $this->assertSession()->fieldExists('id');
    $this->assertSession()->pageTextContains('Test header text');
    $this->assertSession()->pageTextContains('Test footer text');
    $this->assertSession()->pageTextContains('Test empty text');

    // Test feed preview.
    $view = [];
    $view['label'] = $this->randomMachineName(16);
    $view['id'] = strtolower($this->randomMachineName(16));
    $view['page[create]'] = 1;
    $view['page[title]'] = $this->randomMachineName(16);
    $view['page[path]'] = $this->randomMachineName(16);
    $view['page[feed]'] = 1;
    $view['page[feed_properties][path]'] = $this->randomMachineName(16);
    $this->drupalGet('admin/structure/views/add');
    $this->submitForm($view, 'Save and edit');
    $this->clickLink('Feed');
    $this->submitForm([], 'Update preview');
    $this->assertSession()->elementTextContains('xpath', '//div[@id="views-live-preview"]/pre', '<title>' . $view['page[title]'] . '</title>');

    // Test the non-default UI display options.
    // Statistics only, no query.
    $settings = \Drupal::configFactory()->getEditable('views.settings');
    $settings->set('ui.show.performance_statistics', TRUE)->save();
    $this->drupalGet('admin/structure/views/view/test_preview/edit');
    $this->submitForm($edit = ['view_args' => '100'], 'Update preview');
    $this->assertSession()->pageTextContains('Query build time');
    $this->assertSession()->pageTextContains('Query execute time');
    $this->assertSession()->pageTextContains('View render time');
    $this->assertSession()->responseNotContains('<strong>Query</strong>');

    // Statistics and query.
    $settings->set('ui.show.sql_query.enabled', TRUE)->save();
    $this->submitForm($edit = ['view_args' => '100'], 'Update preview');
    $this->assertSession()->pageTextContains('Query build time');
    $this->assertSession()->pageTextContains('Query execute time');
    $this->assertSession()->pageTextContains('View render time');
    $this->assertSession()->responseContains('<strong>Query</strong>');
    $query_string = <<<SQL
SELECT "views_test_data"."name" AS "views_test_data_name"
FROM
{views_test_data} "views_test_data"
WHERE (views_test_data.id = '100')
SQL;
    $this->assertSession()->assertEscaped($query_string);

    // Test that the statistics and query are rendered above the preview.
    $this->assertLessThan(strpos($this->getSession()->getPage()->getContent(), 'view-test-preview'), strpos($this->getSession()->getPage()->getContent(), 'views-query-info'));

    // Test that statistics and query rendered below the preview.
    $settings->set('ui.show.sql_query.where', 'below')->save();
    $this->submitForm($edit = ['view_args' => '100'], 'Update preview');
    $this->assertLessThan(strpos($this->getSession()->getPage()->getContent(), 'views-query-info'), strpos($this->getSession()->getPage()->getContent(), 'view-test-preview'), 'Statistics shown below the preview.');

    // Test that the preview title isn't double escaped.
    $this->drupalGet("admin/structure/views/nojs/display/test_preview/default/title");
    $this->submitForm($edit = ['title' => 'Double & escaped'], 'Apply');
    $this->submitForm([], 'Update preview');
    $this->assertSession()->elementsCount('xpath', '//div[@id="views-live-preview"]/div[contains(@class, views-query-info)]//td[text()="Double & escaped"]', 1);
  }

  /**
   * Tests the additional information query info area.
   */
  public function testPreviewAdditionalInfo() {
    \Drupal::service('module_installer')->install(['views_ui_test']);
    $this->resetAll();

    $this->drupalGet('admin/structure/views/view/test_preview/edit');
    $this->assertSession()->statusCodeEquals(200);

    $this->submitForm($edit = [], 'Update preview');

    // Check for implementation of hook_views_preview_info_alter().
    // @see views_ui_test.module
    // Verify that Views Query Preview Info area was altered.
    $this->assertSession()->elementsCount('xpath', '//div[@id="views-live-preview"]/div[contains(@class, views-query-info)]//td[text()="Test row count"]', 1);
    // Check that additional assets are attached.
    $this->assertStringContainsString('views_ui_test/views_ui_test.test', $this->getDrupalSettings()['ajaxPageState']['libraries'], 'Attached library found.');
    $this->assertSession()->responseContains('css/views_ui_test.test.css');
  }

  /**
   * Tests view validation error messages in the preview.
   */
  public function testPreviewError() {
    $this->drupalGet('admin/structure/views/view/test_preview_error/edit');
    $this->assertSession()->statusCodeEquals(200);

    $this->submitForm($edit = [], 'Update preview');

    $this->assertSession()->pageTextContains('Unable to preview due to validation errors.');
  }

}
