<?php

namespace Drupal\Tests\entity_browser\Kernel\Extension;

use Drupal\Component\FileCache\FileCacheFactory;
use Drupal\Component\Plugin\Exception\PluginException;
use Drupal\Core\Config\Entity\ConfigEntityStorage;
use Drupal\Core\Entity\EntityMalformedException;
use Drupal\Core\Form\FormState;
use Drupal\entity_browser\DisplayInterface;
use Drupal\entity_browser\EntityBrowserInterface;
use Drupal\entity_browser\WidgetInterface;
use Drupal\entity_browser\WidgetSelectorInterface;
use Drupal\entity_browser\SelectionDisplayInterface;
use Drupal\KernelTests\KernelTestBase;
use Drupal\views\Entity\View;

/**
 * Tests the entity_browser config entity.
 *
 * @group entity_browser
 */
class EntityBrowserTest extends KernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'system',
    'user',
    'views',
    'file',
    'node',
    'entity_browser',
    'entity_browser_test',
  ];

  /**
   * The entity browser storage.
   *
   * @var \Drupal\Core\Config\Entity\ConfigEntityStorageInterface
   */
  protected $controller;

  /**
   * Pre-generated UUID.
   *
   * @var string
   */
  protected $widgetUUID;

  /**
   * Route provider service.
   *
   * @var \Drupal\Core\Routing\RouteProviderInterface
   */
  protected $routeProvider;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    FileCacheFactory::setPrefix($this->randomString(4));
    parent::setUp();

    $this->controller = $this->container->get('entity.manager')->getStorage('entity_browser');
    $this->widgetUUID = $this->container->get('uuid')->generate();
    $this->routeProvider = $this->container->get('router.route_provider');

    $this->installSchema('system', ['router', 'key_value_expire', 'sequences']);
    View::create(['id' => 'test_view'])->save();
  }

  /**
   * Tests CRUD operations.
   */
  public function testEntityBrowserCrud() {
    $this->assertTrue($this->controller instanceof ConfigEntityStorage, 'The entity_browser storage is loaded.');

    // Run each test method in the same installation.
    $this->createTests();
    $this->loadTests();
    $this->deleteTests();
  }

  /**
   * Tests the creation of entity_browser.
   */
  protected function createTests() {
    $plugin = [
      'name' => 'test_browser',
      'label' => 'Testing entity browser instance',
      'display' => 'standalone',
      'display_configuration' => ['path' => 'test-browser-test'],
      'selection_display' => 'no_display',
      'selection_display_configuration' => [],
      'widget_selector' => 'single',
      'widget_selector_configuration' => [],
      'widgets' => [
        $this->widgetUUID => [
          'id' => 'view',
          'label' => 'View widget',
          'uuid' => $this->widgetUUID,
          'weight' => 0,
          'settings' => [
            'view' => 'test_view',
            'view_display' => 'test_display',
          ],
        ],
      ],
    ];

    foreach ([
      'display' => 'getDisplay',
      'selection_display' => 'getSelectionDisplay',
      'widget_selector' => 'getWidgetSelector',
    ] as $plugin_type => $function_name) {
      $current_plugin = $plugin;
      unset($current_plugin[$plugin_type]);

      // Attempt to create an entity_browser without required plugin.
      try {
        $entity = $this->controller->create($current_plugin);
        $entity->{$function_name}();
        $this->fail('An entity browser without required ' . $plugin_type . ' created with no exception thrown.');
      }
      catch (PluginException $e) {
        $this->assertEquals('The "" plugin does not exist.', $e->getMessage(), 'An exception was thrown when an entity_browser was created without a ' . $plugin_type . ' plugin.');
      }
    }

    // Try to create an entity browser w/o the ID.
    $current_plugin = $plugin;
    unset($current_plugin['name']);
    try {
      $entity = $this->controller->create($current_plugin);
      $entity->save();
      $this->fail('An entity browser without required name created with no exception thrown.');
    }
    catch (EntityMalformedException $e) {
      $this->assertEquals('The entity does not have an ID.', $e->getMessage(), 'An exception was thrown when an entity_browser was created without a name.');
    }

    // Create an entity_browser with required values.
    $entity = $this->controller->create($plugin);
    $entity->save();

    $this->assertTrue($entity instanceof EntityBrowserInterface, 'The newly created entity is an Entity browser.');

    // Verify all of the properties.
    $actual_properties = $this->container->get('config.factory')
      ->get('entity_browser.browser.test_browser')
      ->get();

    $this->assertTrue(!empty($actual_properties['uuid']), 'The entity browser UUID is set.');
    unset($actual_properties['uuid']);

    // Ensure that default values are filled in.
    $expected_properties = [
      'langcode' => $this->container->get('language_manager')->getDefaultLanguage()->getId(),
      'status' => TRUE,
      'dependencies' => [
        'config' => ['views.view.test_view'],
        'module' => ['views'],
      ],
      'name' => 'test_browser',
      'label' => 'Testing entity browser instance',
      'display' => 'standalone',
      'display_configuration' => ['path' => 'test-browser-test'],
      'selection_display' => 'no_display',
      'selection_display_configuration' => [],
      'widget_selector' => 'single',
      'widget_selector_configuration' => [],
      'widgets' => [
        $this->widgetUUID => [
          'id' => 'view',
          'label' => 'View widget',
          'uuid' => $this->widgetUUID,
          'weight' => 0,
          'settings' => [
            'view' => 'test_view',
            'view_display' => 'test_display',
            'submit_text' => 'Select entities',
            'auto_select' => FALSE,
          ],
        ],
      ],
    ];

    $this->assertEquals($actual_properties, $expected_properties, 'Actual config properties are structured as expected.');

    // Ensure that rebuilding routes works.
    $route = $this->routeProvider->getRoutesByPattern('/test-browser-test');
    $this->assertTrue($route, 'Route exists.');
  }

  /**
   * Tests the loading of entity browser.
   */
  protected function loadTests() {
    /** @var \Drupal\entity_browser\EntityBrowserInterface $entity */
    $entity = $this->controller->load('test_browser');

    $this->assertTrue($entity instanceof EntityBrowserInterface, 'The loaded entity is an entity browser.');

    // Verify several properties of the entity browser.
    $this->assertEquals($entity->label(), 'Testing entity browser instance');
    $this->assertTrue($entity->uuid());
    $plugin = $entity->getDisplay();
    $this->assertTrue($plugin instanceof DisplayInterface, 'Testing display plugin.');
    $this->assertEquals($plugin->getPluginId(), 'standalone');
    $plugin = $entity->getSelectionDisplay();
    $this->assertTrue($plugin instanceof SelectionDisplayInterface, 'Testing selection display plugin.');
    $this->assertEquals($plugin->getPluginId(), 'no_display');
    $plugin = $entity->getWidgetSelector();
    $this->assertTrue($plugin instanceof WidgetSelectorInterface, 'Testing widget selector plugin.');
    $this->assertEquals($plugin->getPluginId(), 'single');
    $plugin = $entity->getWidget($this->widgetUUID);
    $this->assertTrue($plugin instanceof WidgetInterface, 'Testing widget plugin.');
    $this->assertEquals($plugin->getPluginId(), 'view');
  }

  /**
   * Tests the deleting of entity browser.
   */
  protected function deleteTests() {
    $entity = $this->controller->load('test_browser');

    // Ensure that the storage isn't currently empty.
    $config_storage = $this->container->get('config.storage');
    $config = $config_storage->listAll('entity_browser.browser.');
    $this->assertFalse(empty($config), 'There are entity browsers in config storage.');

    // Delete the entity browser.
    $entity->delete();

    // Ensure that the storage is now empty.
    $config = $config_storage->listAll('entity_browser.browser.');
    $this->assertTrue(empty($config), 'There are no entity browsers in config storage.');
  }

  /**
   * Tests dynamic routes.
   */
  public function testDynamicRoutes() {
    $this->installConfig(['entity_browser_test']);
    $this->container->get('router.builder')->rebuild();

    /** @var \Drupal\entity_browser\EntityBrowserInterface $entity */
    $entity = $this->controller->load('test');
    $route = $entity->route();

    $this->assertEquals($route->getPath(), '/entity-browser/test', 'Dynamic path matches.');
    $this->assertEquals($route->getDefault('entity_browser_id'), $entity->id(), 'Entity browser ID matches.');
    $this->assertEquals($route->getDefault('_controller'), 'Drupal\entity_browser\Controllers\EntityBrowserFormController::getContentResult', 'Controller matches.');
    $this->assertEquals($route->getDefault('_title_callback'), 'Drupal\entity_browser\Controllers\EntityBrowserFormController::title', 'Title callback matches.');
    $this->assertEquals($route->getRequirement('_permission'), 'access ' . $entity->id() . ' entity browser pages', 'Permission matches.');

    try {
      $registered_route = $this->routeProvider->getRouteByName('entity_browser.' . $entity->id());
    }
    catch (\Exception $e) {
      $this->fail(t('Expected route not found: @message', array('@message' => $e->getMessage())));
      return;
    }

    $this->assertEquals($registered_route->getPath(), '/entity-browser/test', 'Dynamic path matches.');
    $this->assertEquals($registered_route->getDefault('entity_browser_id'), $entity->id(), 'Entity browser ID matches.');
    $this->assertEquals($registered_route->getDefault('_controller'), 'Drupal\entity_browser\Controllers\EntityBrowserFormController::getContentResult', 'Controller matches.');
    $this->assertEquals($registered_route->getDefault('_title_callback'), 'Drupal\entity_browser\Controllers\EntityBrowserFormController::title', 'Title callback matches.');
    $this->assertEquals($registered_route->getRequirement('_permission'), 'access ' . $entity->id() . ' entity browser pages', 'Permission matches.');
  }

  /**
   * Tests dynamically generated permissions.
   */
  public function testDynamicPermissions() {
    $this->installConfig(['entity_browser_test']);
    $permissions = $this->container->get('user.permissions')->getPermissions();

    /** @var \Drupal\entity_browser\EntityBrowserInterface $entity */
    $entity = $this->controller->load('test');

    $expected_permission_name = 'access ' . $entity->id() . ' entity browser pages';
    $expected_permission = [
      'title' => $this->container->get('string_translation')
        ->translate('Access @name pages', ['@name' => $entity->label()])
        ->render(),
      'description' => $this->container->get('string_translation')
        ->translate('Access pages that %browser uses to operate.', ['%browser' => $entity->label()])
        ->render(),
      'provider' => 'entity_browser',
    ];

    $this->assertSame($permissions[$expected_permission_name]['title']->render(), $expected_permission['title'], 'Dynamically generated permission title found.');
    $this->assertSame($permissions[$expected_permission_name]['description']->render(), $expected_permission['description'], 'Dynamically generated permission description found.');
    $this->assertSame($permissions[$expected_permission_name]['provider'], $expected_permission['provider'], 'Dynamically generated permission provider found.');
  }

  /**
   * Tests default widget selector.
   */
  public function testDefaultWidget() {
    $this->installConfig(['entity_browser_test']);

    /** @var \Drupal\entity_browser\EntityBrowserInterface $entity */
    $entity = $this->controller->load('test');

    /** @var \Drupal\entity_browser\EntityBrowserFormInterface $form_object */
    $form_object = $entity->getFormObject();
    $form_object->setEntityBrowser($entity);
    $form_state = new FormState();

    $form = [];
    $form = $form_object->buildForm($form, $form_state);
    $this->assertEquals($form['widget']['#markup'], 'Number one', 'First widget is active.');

    // Change weight and expect second widget to become first.
    $entity->getWidget($entity->getFirstWidget())->setWeight(3);
    $form_state->set('entity_browser_current_widget', NULL);
    $entity->getWidgets()->sort();

    $form = [];
    $form = $form_object->buildForm($form, $form_state);
    $this->assertEquals($form['widget']['#markup'], 'Number two', 'Second widget is active after changing widgets.');
  }

  /**
   * Test selected event dispatch.
   */
  public function testSelectedEvent() {
    $this->installConfig(['entity_browser_test']);

    /** @var \Drupal\entity_browser\EntityBrowserInterface $entity */
    $entity = $this->controller->load('dummy_widget');

    /** @var \Drupal\entity_browser\EntityBrowserFormInterface $form_object */
    $form_object = $entity->getFormObject();
    $form_object->setEntityBrowser($entity);

    $form_state = new FormState();
    $entity->getWidgets()->get($entity->getFirstWidget())->entity = $entity;

    $this->container->get('form_builder')->buildForm($form_object, $form_state);
    $this->assertEquals(0, count($form_state->get([
      'entity_browser',
      'selected_entities',
    ])), 'Correct number of entities was propagated.');

    $this->container->get('form_builder')->submitForm($form_object, $form_state);

    // Event should be dispatched from widget and added to list of selected
    // entities.
    $selected_entities = $form_state->get([
      'entity_browser',
      'selected_entities',
    ]);
    $this->assertEquals($selected_entities, [$entity], 'Expected selected entities detected.');
  }

  /**
   * Tests propagation of existing selection.
   */
  public function testExistingSelection() {
    $this->installConfig(['entity_browser_test']);
    $this->installEntitySchema('user');

    /** @var \Drupal\entity_browser\EntityBrowserInterface $entity */
    $entity = $this->controller->load('test');

    /** @var \Drupal\user\UserInterface $user */
    $user = $this->container->get('entity_type.manager')
      ->getStorage('user')
      ->create([
        'name' => $this->randomString(),
        'mail' => 'info@example.com',
      ]);
    $user->save();

    /** @var \Symfony\Component\HttpFoundation\Request $request */
    $uuid = $this->container->get('uuid')->generate();
    $this->container->get('request_stack')
      ->getCurrentRequest()
      ->query
      ->set('uuid', $uuid);
    $this->container->get('entity_browser.selection_storage')->setWithExpire($uuid, ['selected_entities' => [$user]], 21600);

    /** @var \Drupal\entity_browser\EntityBrowserFormInterface $form_object */
    $form_object = $entity->getFormObject();
    $form_object->setEntityBrowser($entity);
    $form_state = new FormState();

    $form = [];
    $form_object->buildForm($form, $form_state);
    $propagated_entities = $form_state->get([
      'entity_browser',
      'selected_entities',
    ]);
    $this->assertEquals(1, count($propagated_entities), 'Correct number of entities was propagated.');
    $this->assertEquals($user->id(), $propagated_entities[0]->id(), 'Propagated entity ID is correct.');
    $this->assertEquals($user->getAccountName(), $propagated_entities[0]->getAccountName(), 'Propagated entity name is correct.');
    $this->assertEquals($user->getEmail(), $propagated_entities[0]->getEmail(), 'Propagated entity name is correct.');
  }

  /**
   * Tests validators.
   */
  public function testValidators() {
    $this->installConfig(['entity_browser_test']);
    $this->installEntitySchema('user');

    /** @var \Drupal\entity_browser\EntityBrowserInterface $entity */
    $entity = $this->controller->load('test');

    /** @var \Drupal\user\UserInterface $user */
    $user = $this->container->get('entity_type.manager')
      ->getStorage('user')
      ->create([
        'name' => $this->randomString(),
        'mail' => 'info@example.com',
      ]);
    $user->save();

    /** @var \Symfony\Component\HttpFoundation\Request $request */
    $uuid = $this->container->get('uuid')->generate();
    $this->container->get('request_stack')
      ->getCurrentRequest()
      ->query
      ->set('uuid', $uuid);

    $storage = [
      'validators' => [
        'entity_type' => ['type' => 'user'],
      ],
    ];
    $this->container->get('entity_browser.selection_storage')->setWithExpire($uuid, $storage, 21600);

    /** @var \Drupal\entity_browser\EntityBrowserFormInterface $form_object */
    $form_object = $entity->getFormObject();
    $form_object->setEntityBrowser($entity);
    $form_state = new FormState();

    $form = $form_object->buildForm([], $form_state);
    $validators = $form_state->get(['entity_browser', 'validators']);
    $this->assertSame($validators, $storage['validators'], 'Correct validators were passed to form');

    // Set a valid triggering element
    // (see \Drupal\entity_browser\WidgetBase::validate())
    $element = [
      '#array_parents' => ['submit'],
    ];
    $form_state->setTriggeringElement($element);

    // Use an entity that we know will fail validation.
    $form_state->setValue('dummy_entities', [$entity]);
    $form_object->validateForm($form, $form_state);

    $this->assertNotEmpty($form_state->getErrors(), t('Validation failed where expected'));

    // Use an entity that we know will pass validation.
    $form_state->clearErrors();
    $form_state->setValue('dummy_entities', [$user]);
    $form_object->validateForm($form, $form_state);

    $this->assertEmpty($form_state->getErrors(), t('Validation succeeded where expected'));
  }

}
