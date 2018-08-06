<?php

namespace Drupal\entity_browser\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityWithPluginCollectionInterface;
use Drupal\Core\Plugin\DefaultSingleLazyPluginCollection;
use Drupal\entity_browser\EntityBrowserInterface;
use Drupal\entity_browser\WidgetInterface;
use Drupal\entity_browser\DisplayRouterInterface;
use Drupal\entity_browser\WidgetsCollection;
use Symfony\Component\Routing\Route;

/**
 * Defines an entity browser configuration entity.
 *
 * @ConfigEntityType(
 *   id = "entity_browser",
 *   label = @Translation("Entity browser"),
 *   handlers = {
 *     "form" = {
 *       "entity_browser" = "Drupal\entity_browser\Form\EntityBrowserForm",
 *       "delete" = "Drupal\entity_browser\Form\EntityBrowserDeleteForm",
 *     },
 *     "access" = "Drupal\Core\Entity\EntityAccessControlHandler",
 *     "list_builder" = "Drupal\entity_browser\Controllers\EntityBrowserListBuilder",
 *     "wizard" = {
 *       "add" = "Drupal\entity_browser\Wizard\EntityBrowserWizardAdd",
 *       "edit" = "Drupal\entity_browser\Wizard\EntityBrowserWizard",
 *     }
 *   },
 *   links = {
 *     "canonical" = "/admin/config/content/entity_browser/{machine_name}/{step}",
 *     "collection" = "/admin/config/content/entity_browser",
 *     "edit-form" = "/admin/config/content/entity_browser/{machine_name}/{step}",
 *     "delete-form" = "/admin/config/content/entity_browser/{entity_browser}/delete",
 *   },
 *   admin_permission = "administer entity browsers",
 *   config_prefix = "browser",
 *   entity_keys = {
 *     "id" = "name",
 *     "label" = "label"
 *   },
 *   config_export = {
 *     "name",
 *     "label",
 *     "display",
 *     "display_configuration",
 *     "selection_display",
 *     "selection_display_configuration",
 *     "widget_selector",
 *     "widget_selector_configuration",
 *     "widgets",
 *   },
 * )
 */
class EntityBrowser extends ConfigEntityBase implements EntityBrowserInterface, EntityWithPluginCollectionInterface {

  /**
   * The name of the entity browser.
   *
   * @var string
   */
  public $name;

  /**
   * The entity browser label.
   *
   * @var string
   */
  public $label;

  /**
   * The display plugin id.
   *
   * @var string
   */
  public $display;

  /**
   * The display plugin configuration.
   *
   * @var array
   */
  public $display_configuration = [];

  /**
   * Display lazy plugin collection.
   *
   * @var \Drupal\Core\Plugin\DefaultSingleLazyPluginCollection
   */
  protected $displayCollection;

  /**
   * The array of widgets for this entity browser.
   *
   * @var array
   */
  protected $widgets = [];

  /**
   * Holds the collection of widgets that are used by this entity browser.
   *
   * @var \Drupal\entity_browser\WidgetsCollection
   */
  protected $widgetsCollection;

  /**
   * The selection display plugin ID.
   *
   * @var string
   */
  public $selection_display;

  /**
   * The selection display plugin configuration.
   *
   * @var array
   */
  public $selection_display_configuration = [];

  /**
   * Selection display plugin collection.
   *
   * @var \Drupal\Core\Plugin\DefaultSingleLazyPluginCollection
   */
  protected $selectionDisplayCollection;

  /**
   * The widget selector plugin ID.
   *
   * @var string
   */
  public $widget_selector;

  /**
   * The widget selector plugin configuration.
   *
   * @var array
   */
  public $widget_selector_configuration = [];

  /**
   * Widget selector plugin collection.
   *
   * @var \Drupal\Core\Plugin\DefaultSingleLazyPluginCollection
   */
  protected $widgetSelectorCollection;

  /**
   * Additional widget parameters.
   *
   * @var array
   */
  protected $additional_widget_parameters = [];

  /**
   * Name of the form class.
   *
   * @var string
   */
  protected $form_class = '\Drupal\entity_browser\Form\EntityBrowserForm';

  /**
   * {@inheritdoc}
   */
  public function id() {
    return $this->name;
  }

  /**
   * {@inheritdoc}
   */
  public function getName() {
    return $this->get('name');
  }

  /**
   * {@inheritdoc}
   */
  public function setName($name) {
    $this->name = $name;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getDisplay() {
    return $this->displayPluginCollection()->get($this->display);
  }

  /**
   * {@inheritdoc}
   */
  public function setLabel($label) {
    $this->label = $label;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setDisplay($display) {
    $this->display = $display;
    $this->displayPluginCollection = NULL;
    $this->getDisplay();
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setWidgetSelector($widget_selector) {
    $this->widget_selector = $widget_selector;
    $this->widgetSelectorCollection = NULL;
    $this->getWidgetSelector();
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setSelectionDisplay($selection_display) {
    $this->selection_display = $selection_display;
    $this->selectionDisplayCollection = NULL;
    $this->getSelectionDisplay();
    return $this;
  }

  /**
   * Returns display plugin collection.
   *
   * @return \Drupal\Core\Plugin\DefaultSingleLazyPluginCollection
   *   The tag plugin collection.
   */
  protected function displayPluginCollection() {
    if (!$this->displayCollection) {
      $this->display_configuration['entity_browser_id'] = $this->id();
      $this->displayCollection = new DefaultSingleLazyPluginCollection(\Drupal::service('plugin.manager.entity_browser.display'), $this->display, $this->display_configuration);
    }
    return $this->displayCollection;
  }

  /**
   * Returns the plugin collections used by this entity.
   *
   * @return \Drupal\Component\Plugin\LazyPluginCollection[]
   *   An array of plugin collections, keyed by the property name they use to
   *   store their configuration.
   */
  public function getPluginCollections() {
    return [
      'widgets' => $this->getWidgets(),
      'widget_selector_configuration' => $this->widgetSelectorPluginCollection(),
      'display_configuration' => $this->displayPluginCollection(),
      'selection_display_configuration' => $this->selectionDisplayPluginCollection(),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getWidget($widget) {
    return $this->getWidgets()->get($widget);
  }

  /**
   * {@inheritdoc}
   */
  public function getWidgets() {
    if (!$this->widgetsCollection) {
      foreach ($this->widgets as &$widget) {
        $widget['settings']['entity_browser_id'] = $this->id();
      }
      $this->widgetsCollection = new WidgetsCollection(\Drupal::service('plugin.manager.entity_browser.widget'), $this->widgets);
      $this->widgetsCollection->sort();
    }
    return $this->widgetsCollection;
  }

  /**
   * {@inheritdoc}
   */
  public function addWidget(array $configuration) {
    $configuration['uuid'] = $this->uuidGenerator()->generate();
    $this->getWidgets()->addInstanceId($configuration['uuid'], $configuration);
    return $configuration['uuid'];
  }

  /**
   * {@inheritdoc}
   */
  public function deleteWidget(WidgetInterface $widget) {
    $this->getWidgets()->removeInstanceId($widget->uuid());
    $this->save();
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getFirstWidget() {
    $instance_ids = $this->getWidgets()->getInstanceIds();
    return reset($instance_ids);
  }

  /**
   * {@inheritdoc}
   */
  public function addAdditionalWidgetParameters(array $parameters) {
    // TODO - this doesn't make much sense. Refactor.
    $this->additional_widget_parameters += $parameters;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getAdditionalWidgetParameters() {
    // TODO - this doesn't make much sense. Refactor.
    return $this->get('additional_widget_parameters');
  }

  /**
   * Returns selection display plugin collection.
   *
   * @return \Drupal\Core\Plugin\DefaultSingleLazyPluginCollection
   *   The tag plugin collection.
   */
  protected function selectionDisplayPluginCollection() {
    if (!$this->selectionDisplayCollection) {
      $this->selection_display_configuration['entity_browser_id'] = $this->id();
      $this->selectionDisplayCollection = new DefaultSingleLazyPluginCollection(\Drupal::service('plugin.manager.entity_browser.selection_display'), $this->selection_display, $this->selection_display_configuration);
    }
    return $this->selectionDisplayCollection;
  }

  /**
   * {@inheritdoc}
   */
  public function getSelectionDisplay() {
    return $this->selectionDisplayPluginCollection()->get($this->selection_display);
  }

  /**
   * Returns widget selector plugin collection.
   *
   * @return \Drupal\Core\Plugin\DefaultSingleLazyPluginCollection
   *   The tag plugin collection.
   */
  protected function widgetSelectorPluginCollection() {
    if (!$this->widgetSelectorCollection) {
      $options = array();
      foreach ($this->getWidgets()->getInstanceIds() as $id) {
        $options[$id] = $this->getWidgets()->get($id)->label();
      }
      $this->widget_selector_configuration['widget_ids'] = $options;
      $this->widgetSelectorCollection = new DefaultSingleLazyPluginCollection(\Drupal::service('plugin.manager.entity_browser.widget_selector'), $this->widget_selector, $this->widget_selector_configuration);
    }
    return $this->widgetSelectorCollection;
  }

  /**
   * {@inheritdoc}
   */
  public function getWidgetSelector() {
    return $this->widgetSelectorPluginCollection()->get($this->widget_selector);
  }

  /**
   * {@inheritdoc}
   */
  public function route() {
    // TODO: Allow displays to define more than just path.
    // See: https://www.drupal.org/node/2364193
    $display = $this->getDisplay();
    if ($display instanceof DisplayRouterInterface) {
      $path = $display->path();
      return new Route(
        $path,
        [
          '_controller' => 'Drupal\entity_browser\Controllers\EntityBrowserFormController::getContentResult',
          '_title_callback' => 'Drupal\entity_browser\Controllers\EntityBrowserFormController::title',
          'entity_browser_id' => $this->id(),
        ],
        ['_permission' => 'access ' . $this->id() . ' entity browser pages'],
        ['_admin_route' => \Drupal::config('node.settings')->get('use_admin_theme')]
      );
    }

    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function preSave(EntityStorageInterface $storage) {
    parent::preSave($storage);

    // Entity browser ID was added when creating. No need to save that as it can
    // always be calculated.
    foreach ($this->widgets as &$widget) {
      unset($widget['settings']['entity_browser_id']);
    }
    unset($this->selection_display_configuration['entity_browser_id']);
    unset($this->display_configuration['entity_browser_id']);
    unset($this->widget_selector_configuration['widget_ids']);
  }

  /**
   * Prevent plugin collections from being serialized and correctly serialize
   * selected entities.
   */
  public function __sleep() {
    // Save configuration for all plugins.
    $this->widgets = $this->getWidgets()->getConfiguration();
    $this->widget_selector_configuration = $this->widgetSelectorPluginCollection()->getConfiguration();
    $this->display_configuration = $this->displayPluginCollection()->getConfiguration();
    $this->selection_display_configuration = $this->selectionDisplayPluginCollection()->getConfiguration();

    return array_diff(
      array_keys(get_object_vars($this)),
      [
        'widgetsCollection',
        'widgetSelectorCollection',
        'displayCollection',
        'selectionDisplayCollection',
        'selectedEntities',
      ]
    );
  }

  /**
   * {@inheritdoc}
   */
  public function postSave(EntityStorageInterface $storage, $update = TRUE) {
    parent::postSave($storage, $update);
    // Rebuild route information when browsers that register routes
    // are created/updated.
    \Drupal::service('router.builder')->setRebuildNeeded();
  }

  /**
   * {@inheritdoc}
   */
  public static function postDelete(EntityStorageInterface $storage, array $entities) {
    parent::postDelete($storage, $entities);
    // Rebuild route information when browsers that register routes
    // are deleted.
    \Drupal::service('router.builder')->setRebuildNeeded();
  }

  /**
   * {@inheritdoc}
   */
  public function getFormObject() {
    $form_class = \Drupal::service('class_resolver')->getInstanceFromDefinition($this->form_class);
    $form_class->setEntityBrowser($this);
    return $form_class;
  }

  /**
   * {@inheritdoc}
   */
  protected function urlRouteParameters($rel) {
    $uri_route_parameters = parent::urlRouteParameters($rel);

    if ($rel == 'config-translation-overview') {
      $uri_route_parameters['step'] = 'general';
    }

    return $uri_route_parameters;
  }

}
