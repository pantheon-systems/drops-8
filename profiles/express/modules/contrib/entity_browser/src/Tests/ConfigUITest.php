<?php

namespace Drupal\entity_browser\Tests;

use Drupal\entity_browser\Plugin\EntityBrowser\Display\IFrame;
use Drupal\entity_browser\Plugin\EntityBrowser\SelectionDisplay\NoDisplay;
use Drupal\entity_browser\Plugin\EntityBrowser\WidgetSelector\Tabs;
use Drupal\simpletest\WebTestBase;

/**
 * Tests the entity browser config UI.
 *
 * @group entity_browser
 */
class ConfigUITest extends WebTestBase {

  /**
   * The test user.
   *
   * @var \Drupal\User\UserInterface
   */
  protected $adminUser;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['entity_browser', 'ctools', 'block', 'views', 'entity_browser_entity_form'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->drupalPlaceBlock('local_actions_block');
    $this->adminUser = $this->drupalCreateUser([
      'administer entity browsers',
    ]);
  }

  /**
   * Tests the entity browser config UI.
   */
  public function testConfigUI() {
    // We need token module to test upload widget settings.
    $this->container->get('module_installer')->install(['token']);

    $this->drupalGet('/admin/config/content/entity_browser');
    $this->assertResponse(403, "Anonymous user can't access entity browser listing page.");
    $this->drupalGet('/admin/config/content/entity_browser/add');
    $this->assertResponse(403, "Anonymous user can't access entity browser add form.");

    // Listing is empty.
    $this->drupalLogin($this->adminUser);
    $this->drupalGet('/admin/config/content/entity_browser');
    $this->assertResponse(200, 'Admin user is able to navigate to the entity browser listing page.');
    $this->assertText('There is no Entity browser yet.', 'Entity browsers table is empty.');

    // Add page.
    $this->clickLink('Add Entity browser');
    $this->assertUrl('/admin/config/content/entity_browser/add');
    $edit = [
      'label' => 'Test entity browser',
      'id' => 'test_entity_browser',
      'display' => 'iframe',
      'widget_selector' => 'tabs',
      'selection_display' => 'no_display',
    ];
    $this->drupalPostForm(NULL, $edit, 'Next');

    // Display configuration step.
    $this->assertUrl('/admin/config/content/entity_browser/test_entity_browser/display', ['query' => ['js' => 'nojs']]);
    $edit = [
      'width' => 100,
      'height' => 100,
      'link_text' => 'All animals are created equal',
      'auto_open' => TRUE,
    ];
    $this->drupalPostForm(NULL, $edit, 'Next');

    // Widget selector step.
    $this->assertUrl('/admin/config/content/entity_browser/test_entity_browser/widget_selector', ['query' => ['js' => 'nojs']]);
    $this->assertText('This plugin has no configuration options.');
    $this->drupalPostForm(NULL, [], 'Next');

    // Selection display step.
    $this->assertUrl('/admin/config/content/entity_browser/test_entity_browser/selection_display', ['query' => ['js' => 'nojs']]);
    $this->assertText('This plugin has no configuration options.');
    $this->drupalPostForm(NULL, [], 'Previous');

    // Widget selector step again.
    $this->assertUrl('/admin/config/content/entity_browser/test_entity_browser/widget_selector', ['query' => ['js' => 'nojs']]);
    $this->assertText('This plugin has no configuration options.');
    $this->drupalPostForm(NULL, [], 'Next');

    // Selection display step.
    $this->assertUrl('/admin/config/content/entity_browser/test_entity_browser/selection_display', ['query' => ['js' => 'nojs']]);
    $this->assertText('This plugin has no configuration options.');
    $this->drupalPostForm(NULL, [], 'Next');

    // Widgets step.
    $this->assertUrl('/admin/config/content/entity_browser/test_entity_browser/widgets', ['query' => ['js' => 'nojs']]);
    $this->assertText('The available plugins are:');
    $this->assertText("Upload: Adds an upload field browser's widget.");
    $this->assertText("View: Uses a view to provide entity listing in a browser's widget.");
    $this->assertText("Entity form: Provides entity form widget.");
    $this->drupalPostAjaxForm(NULL, ['widget' => 'upload'], 'widget');
    $this->assertText('Label (Upload)');
    $this->assertText('You can use tokens in the upload location.');
    $this->assertLink('Browse available tokens.');

    // Make sure that removing of widgets works.
    $this->drupalPostAjaxForm(NULL, ['widget' => 'view'], 'widget');
    $this->assertText('Label (View)');
    $this->assertText('View : View display', 'View selection dropdown label found.');
    $this->assertRaw('- Select a view -', 'Empty option appears in the view selection dropdown.');
    $this->assertText('Submit button text', 'Widget submit button text element found.');
    $this->assertFieldByXPath('//*[starts-with(@data-drupal-selector, "edit-table-") and contains(@data-drupal-selector, "-form-submit-text")]', 'Select entities', 'Widget submit button text element found.');
    $delete_buttons = $this->xpath("//input[@value='Delete']");
    $delete_button_name = (string) $delete_buttons[1]->attributes()['name'];
    $this->drupalPostAjaxForm(NULL, [], [$delete_button_name => 'Delete']);
    $this->assertNoText('View : View display', 'View widget was removed.');
    $this->assertNoRaw('- Select a view -', 'View widget was removed.');
    $this->assertEqual(count($this->xpath("//input[@value='Delete']")), 1, 'Only one delete button appears on the page.');

    // Make sure the "Entity form" widget has all available config elements.
    $this->drupalPostAjaxForm(NULL, ['widget' => 'entity_form'], 'widget');
    $this->assertText('Label (Entity form)');
    $this->assertText('Entity type', 'Entity type select found on IEF widget.');
    $this->assertText('Bundle', 'Bundle select found on IEF widget.');
    $this->assertText('Form mode', 'Form mode select found on IEF widget.');
    $this->assertFieldByXPath('//*[starts-with(@data-drupal-selector, "edit-table-") and contains(@data-drupal-selector, "-form-submit-text")]', 'Save entity', 'Widget submit button text element found.');
    $entity_type_element = $this->xpath('//*[starts-with(@data-drupal-selector, "edit-table-") and contains(@data-drupal-selector, "-form-entity-type")]');
    $entity_type_name = (string) $entity_type_element[0]['name'];
    $edit = [
      $entity_type_name => 'user',
    ];
    $commands = $this->drupalPostAjaxForm(NULL, $edit, $entity_type_name);
    // WebTestBase::drupalProcessAjaxResponse() won't correctly execute our ajax
    // commands so we have to do it manually. Code below is based on the logic
    // in that function.
    $content = $this->content;
    $dom = new \DOMDocument();
    @$dom->loadHTML($content);
    $xpath = new \DOMXPath($dom);
    foreach ($commands as $command) {
      if ($command['command'] == 'insert' && $command['method'] == 'replaceWith') {
        $wrapperNode = $xpath->query('//*[@id="' . ltrim($command['selector'], '#') . '"]')->item(0);
        $newDom = new \DOMDocument();
        @$newDom->loadHTML('<div>' . $command['data'] . '</div>');
        $newNode = @$dom->importNode($newDom->documentElement->firstChild->firstChild, TRUE);
        $wrapperNode->parentNode->replaceChild($newNode, $wrapperNode);
        $content = $dom->saveHTML();
        $this->setRawContent($content);
      }
    }
    $this->verbose($content);
    // Assure the form_mode "Register" is one of the available options.
    $form_mode_element = $this->xpath('//*[starts-with(@data-drupal-selector, "edit-table-") and contains(@data-drupal-selector, "-form-form-mode-form-select")]');
    $form_mode_id = (string) $form_mode_element[0]['id'];
    $form_mode_name = (string) $form_mode_element[0]['name'];
    $this->assertOption($form_mode_id, 'register', 'A non-default form mode is correctly available to be chosen.');
    $bundle_element = $this->xpath('//*[starts-with(@data-drupal-selector, "edit-table-") and contains(@data-drupal-selector, "-form-bundle-select")]');
    $bundle_name = (string) $bundle_element[0]['name'];
    $submit_text_element = $this->xpath('//*[starts-with(@data-drupal-selector, "edit-table-") and contains(@data-drupal-selector, "-form-submit-text")]');
    $submit_text_name = (string) $submit_text_element[1]['name'];
    $edit = [
      $entity_type_name => 'user',
      $bundle_name => 'user',
      $form_mode_name => 'register',
      $submit_text_name => 'But some are more equal than others',
    ];
    $this->drupalPostForm(NULL, $edit, 'Finish');

    // Back on listing page.
    $this->assertUrl('/admin/config/content/entity_browser');
    $this->assertText('Test entity browser', 'Entity browser label found on the listing page');
    $this->assertText('test_entity_browser', 'Entity browser ID found on the listing page.');

    // Check structure of entity browser object.
    /** @var \Drupal\entity_browser\EntityBrowserInterface $loaded_entity_browser */
    $loaded_entity_browser = $this->container->get('entity_type.manager')
      ->getStorage('entity_browser')
      ->load('test_entity_browser');
    $this->assertEqual('test_entity_browser', $loaded_entity_browser->id(), 'Entity browser ID was correctly saved.');
    $this->assertEqual('Test entity browser', $loaded_entity_browser->label(), 'Entity browser label was correctly saved.');
    $this->assertTrue($loaded_entity_browser->getDisplay() instanceof IFrame, 'Entity browser display was correctly saved.');
    $expected = [
      'width' => '100',
      'height' => '100',
      'link_text' => 'All animals are created equal',
      'auto_open' => TRUE,
    ];
    $this->assertEqual($expected, $loaded_entity_browser->getDisplay()->getConfiguration(), 'Entity browser display configuration was correctly saved.');
    $this->assertTrue($loaded_entity_browser->getSelectionDisplay() instanceof NoDisplay, 'Entity browser selection display was correctly saved.');
    $this->assertEqual([], $loaded_entity_browser->getSelectionDisplay()->getConfiguration(), 'Entity browser selection display configuration was correctly saved.');
    $this->assertEqual($loaded_entity_browser->getWidgetSelector() instanceof Tabs, 'Entity browser widget selector was correctly saved.');
    $this->assertEqual([], $loaded_entity_browser->getWidgetSelector()->getConfiguration(), 'Entity browser widget selector configuration was correctly saved.');

    $widgets = $loaded_entity_browser->getWidgets();
    $instance_ids = $widgets->getInstanceIds();
    $first_uuid = current($instance_ids);
    $second_uuid = next($instance_ids);
    /** @var \Drupal\entity_browser\WidgetInterface $widget */
    $widget = $widgets->get($first_uuid);
    $this->assertEqual('upload', $widget->id(), 'Entity browser widget was correctly saved.');
    $this->assertEqual($first_uuid, $widget->uuid(), 'Entity browser widget uuid was correctly saved.');
    $configuration = $widget->getConfiguration()['settings'];
    $this->assertEqual([
      'upload_location' => 'public://',
      'multiple' => TRUE,
      'submit_text' => 'Select files',
      'extensions' => 'jpg jpeg gif png txt doc xls pdf ppt pps odt ods odp',
    ], $configuration, 'Entity browser widget configuration was correctly saved.');
    $this->assertEqual(1, $widget->getWeight(), 'Entity browser widget weight was correctly saved.');
    $widget = $widgets->get($second_uuid);
    $this->assertEqual('entity_form', $widget->id(), 'Entity browser widget was correctly saved.');
    $this->assertEqual($second_uuid, $widget->uuid(), 'Entity browser widget uuid was correctly saved.');
    $configuration = $widget->getConfiguration()['settings'];
    $this->assertEqual([
      'entity_type' => 'user',
      'bundle' => 'user',
      'form_mode' => 'register',
      'submit_text' => 'But some are more equal than others',
    ], $configuration, 'Entity browser widget configuration was correctly saved.');
    $this->assertEqual(2, $widget->getWeight(), 'Entity browser widget weight was correctly saved.');

    // Navigate to edit.
    $this->clickLink('Edit');
    $this->assertUrl('/admin/config/content/entity_browser/test_entity_browser');
    $this->assertFieldById('edit-label', 'Test entity browser', 'Correct label found.');
    $this->assertText('test_entity_browser', 'Correct id found.');
    $this->assertOptionSelected('edit-display', 'iframe', 'Correct display selected.');
    $this->assertOptionSelected('edit-widget-selector', 'tabs', 'Correct widget selector selected.');
    $this->assertOptionSelected('edit-selection-display', 'no_display', 'Correct selection display selected.');

    $this->drupalPostForm(NULL, [], 'Next');
    $this->assertUrl('/admin/config/content/entity_browser/test_entity_browser/display', ['query' => ['js' => 'nojs']]);
    $this->assertFieldById('edit-width', '100', 'Correct value for width found.');
    $this->assertFieldById('edit-height', '100', 'Correct value for height found.');
    $this->assertFieldById('edit-link-text', 'All animals are created equal', 'Correct value for link text found.');
    $this->assertFieldChecked('edit-auto-open', 'Auto open is enabled.');

    $this->drupalPostForm(NULL, [], 'Next');
    $this->assertUrl('/admin/config/content/entity_browser/test_entity_browser/widget_selector', ['query' => ['js' => 'nojs']]);

    $this->drupalPostForm(NULL, [], 'Next');
    $this->assertUrl('/admin/config/content/entity_browser/test_entity_browser/selection_display', ['query' => ['js' => 'nojs']]);

    $this->drupalPostForm(NULL, [], 'Next');
    $this->assertFieldById('edit-table-' . $first_uuid . '-label', 'upload', 'Correct value for widget label found.');
    $this->assertFieldChecked('edit-table-' . $first_uuid . '-form-multiple', 'Accept multiple files option is enabled by default.');
    $this->assertText('Multiple uploads will only be accepted if the source field allows more than one value.');
    $this->assertFieldById('edit-table-' . $first_uuid . '-form-upload-location', 'public://', 'Correct value for upload location found.');
    $this->assertFieldByXPath("//input[@data-drupal-selector='edit-table-" . $first_uuid . "-form-submit-text']", 'Select files', 'Correct value for submit text found.');
    $this->assertFieldById('edit-table-' . $second_uuid . '-label', 'entity_form', 'Correct value for widget label found.');
    $this->assertOptionSelectedWithDrupalSelector('edit-table-' . $second_uuid . '-form-entity-type', 'user', 'Correct value for entity type found.');
    $this->assertOptionSelectedWithDrupalSelector('edit-table-' . $second_uuid . '-form-bundle-select', 'user', 'Correct value for bundle found.');
    $this->assertOptionSelectedWithDrupalSelector('edit-table-' . $second_uuid . '-form-form-mode-form-select', 'register', 'Correct value for form modes found.');
    $this->assertFieldByXPath("//input[@data-drupal-selector='edit-table-" . $second_uuid . "-form-submit-text']", 'But some are more equal than others', 'Correct value for submit text found.');

    $this->drupalPostForm(NULL, ['table[' . $first_uuid . '][form][multiple]' => FALSE], 'Finish');
    $this->drupalGet('/admin/config/content/entity_browser/test_entity_browser/widgets');
    $this->assertNoFieldChecked('edit-table-' . $first_uuid . '-form-multiple', 'Accept multiple files option is disabled.');

    $this->drupalLogout();
    $this->drupalGet('/admin/config/content/entity_browser/test_entity_browser');
    $this->assertResponse(403, "Anonymous user can't access entity browser edit form.");

    $this->drupalLogin($this->adminUser);
    $this->drupalGet('/admin/config/content/entity_browser');
    $this->clickLink('Delete');
    $this->assertText('This action cannot be undone.', 'Delete question found.');
    $this->drupalPostForm(NULL, [], 'Delete Entity Browser');

    $this->assertText('Entity browser Test entity browser was deleted.', 'Confirmation message found.');
    $this->assertText('There is no Entity browser yet.', 'Entity browsers table is empty.');
    $this->drupalLogout();
  }

}
