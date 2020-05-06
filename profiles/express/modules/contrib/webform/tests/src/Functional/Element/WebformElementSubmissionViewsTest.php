<?php

namespace Drupal\Tests\webform\Functional\Element;

/**
 * Tests for webform submission views element.
 *
 * @group Webform
 */
class WebformElementSubmissionViewsTest extends WebformElementBrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['views', 'node', 'webform', 'webform_node'];

  /**
   * Webforms to load.
   *
   * @var array
   */
  protected static $testWebforms = ['test_element_submission_views'];

  /**
   * Test webform submission views element.
   */
  public function testSubmissionViews() {
    // Check global and webform rendering.
    $this->drupalGet('/webform/test_element_submission_views');
    $this->assertRaw('<th class="webform_submission_views_global-table--name_title_view webform-multiple-table--name_title_view">');
    $this->assertRaw('<th class="webform_submission_views_global-table--global_routes webform-multiple-table--global_routes">');
    $this->assertRaw('<th class="webform_submission_views_global-table--webform_routes webform-multiple-table--webform_routes">');
    $this->assertRaw('<th class="webform_submission_views_global-table--node_routes webform-multiple-table--node_routes">');
    $this->assertRaw('<th class="webform_submission_views-table--name_title_view webform-multiple-table--name_title_view">');
    $this->assertNoRaw('<th class="webform_submission_views-table--global_routes webform-multiple-table--global_routes">');
    $this->assertRaw('<th class="webform_submission_views-table--webform_routes webform-multiple-table--webform_routes">');
    $this->assertRaw('<th class="webform_submission_views-table--node_routes webform-multiple-table--node_routes">');

    // Check name validation.
    $edit = ['webform_submission_views_global[items][0][name]' => ''];
    $this->drupalPostForm('/webform/test_element_submission_views', $edit, t('Submit'));
    $this->assertRaw('Name is required');

    // Check view validation.
    $edit = ['webform_submission_views_global[items][0][view]' => ''];
    $this->drupalPostForm('/webform/test_element_submission_views', $edit, t('Submit'));
    $this->assertRaw('View name/display id is required.');

    // Check title validation.
    $edit = ['webform_submission_views_global[items][0][title]' => ''];
    $this->drupalPostForm('/webform/test_element_submission_views', $edit, t('Submit'));
    $this->assertRaw('Title is required.');

    // Check processing.
    $this->drupalPostForm('/webform/test_element_submission_views', [], t('Submit'));
    $this->assertRaw("webform_submission_views_global:
  admin:
    view: 'webform_submissions:embed_administer'
    title: Admin
    global_routes:
      - entity.webform_submission.collection
    webform_routes:
      - entity.webform.results_submissions
    node_routes:
      - entity.node.webform.results_submissions
webform_submission_views:
  admin:
    view: 'webform_submissions:embed_administer'
    title: Admin
    webform_routes:
      - entity.webform.results_submissions
    node_routes:
      - entity.node.webform.results_submissions");

    // Check processing empty record.
    $edit = [
      'webform_submission_views_global[items][0][name]' => '',
      'webform_submission_views_global[items][0][view]' => '',
      'webform_submission_views_global[items][0][title]' => '',
      'webform_submission_views_global[items][0][global_routes][entity.webform_submission.collection]' => FALSE,
      'webform_submission_views_global[items][0][webform_routes][entity.webform.results_submissions]' => FALSE,
      'webform_submission_views_global[items][0][node_routes][entity.node.webform.results_submissions]' => FALSE,
    ];
    $this->drupalPostForm('/webform/test_element_submission_views', $edit, t('Submit'));
    $this->assertNoRaw('Name is required');
    $this->assertNoRaw('View name/display id is required.');
    $this->assertNoRaw('Title is required.');
    $this->assertRaw("webform_submission_views_global: {  }
webform_submission_views:
  admin:
    view: 'webform_submissions:embed_administer'
    title: Admin
    webform_routes:
      - entity.webform.results_submissions
    node_routes:
      - entity.node.webform.results_submissions");

    // Uninstall the webform node module.
    $this->container->get('module_installer')->uninstall(['webform_node']);

    // Check global and webform rendering without node settings.
    $this->drupalGet('/webform/test_element_submission_views');
    $this->assertNoRaw('<th class="webform_submission_views_global-table--node_routes webform-multiple-table--node_routes">');
    $this->assertNoRaw('<th class="webform_submission_views-table--node_routes webform-multiple-table--node_routes">');

    // Check processing removes node settings.
    $this->drupalPostForm('/webform/test_element_submission_views', [], t('Submit'));
    $this->assertRaw("webform_submission_views_global:
  admin:
    view: 'webform_submissions:embed_administer'
    title: Admin
    global_routes:
      - entity.webform_submission.collection
    webform_routes:
      - entity.webform.results_submissions
webform_submission_views:
  admin:
    view: 'webform_submissions:embed_administer'
    title: Admin
    webform_routes:
      - entity.webform.results_submissions");

    // Uninstall the views module.
    $this->container->get('module_installer')->uninstall(['views']);

    // Check that element is completely hidden.
    $this->drupalGet('/webform/test_element_submission_views');
    $this->assertNoRaw('<th class="webform_submission_views_global-table--name_title_view webform-multiple-table--name_title_view">');
    $this->assertNoRaw('<th class="webform_submission_views-table--name_title_view webform-multiple-table--name_title_view">');

    // Check that value is preserved.
    $this->drupalPostForm('/webform/test_element_submission_views', [], t('Submit'));
    $this->assertRaw("webform_submission_views_global: {  }
webform_submission_views: {  }");
  }

}
