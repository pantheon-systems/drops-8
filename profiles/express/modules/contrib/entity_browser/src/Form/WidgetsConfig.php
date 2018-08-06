<?php

namespace Drupal\entity_browser\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\entity_browser\WidgetManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Widget configuration step in entity browser form wizard.
 */
class WidgetsConfig extends FormBase {

  /**
   * Entity browser widget plugin manager.
   *
   * @var \Drupal\entity_browser\WidgetManager
   */
  protected $widgetManager;

  /**
   * WidgetsConfig constructor.
   *
   * @param \Drupal\entity_browser\WidgetManager $widget_manager
   *   Entity browser widget plugin manager.
   */
  function __construct(WidgetManager $widget_manager) {
    $this->widgetManager = $widget_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.entity_browser.widget')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'entity_browser_widgets_config_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\entity_browser\EntityBrowserInterface $entity_browser */
    $entity_browser = $form_state->getTemporaryValue('wizard')['entity_browser'];

    $widgets = [];
    $description = $this->t('The available plugins are:') . '<ul>';
    foreach ($this->widgetManager->getDefinitions() as $plugin_id => $plugin_definition) {
      $widgets[$plugin_id] = $plugin_definition['label'];
      $description .= '<li><b>' . $plugin_definition['label'] . ':</b> ' . $plugin_definition['description'] . '</li>';
    }
    $description .= '</ul>';
    $default_widgets = [];
    foreach ($entity_browser->getWidgets() as $widget) {
      /** @var \Drupal\entity_browser\WidgetInterface $widget */
      $default_widgets[] = $widget->id();
    }
    $form['widget'] = [
      '#type' => 'select',
      '#title' => $this->t('Add widget plugin'),
      '#options' => ['_none_' => '- ' . $this->t('Select a widget to add it') . ' -'] + $widgets,
      '#description' => $description,
      '#ajax' => [
        'callback' => [get_class($this), 'tableUpdatedAjaxCallback'],
        'wrapper' => 'widgets',
        'event' => 'change',
      ],
      '#executes_submit_callback' => TRUE,
      '#submit' => [[get_class($this), 'submitAddWidget']],
      '#limit_validation_errors' => [['widget']],
    ];
    $form_state->unsetValue('widget');

    $form['widgets'] = [
      '#type' => 'container',
      '#attributes' => ['id' => 'widgets'],
    ];

    $form['widgets']['table'] = [
      '#type' => 'table',
      '#header' => [
        $this->t('Form'),
        $this->t('Operations'),
        $this->t('Actions'),
        $this->t('Weight'),
      ],
      '#empty' => $this->t('There are no widgets.'),
      '#tabledrag' => [[
        'action' => 'order',
        'relationship' => 'sibling',
        'group' => 'variant-weight',
      ],
      ],
    ];

    /** @var \Drupal\entity_browser\WidgetInterface $widget */
    foreach ($entity_browser->getWidgets() as $uuid => $widget) {
      $row = [
        '#attributes' => [
          'class' => ['draggable'],
        ],
      ];
      $row['label'] = [
        '#type' => 'textfield',
        '#default_value' => $widget->label(),
        '#title' => $this->t('Label (%label)', [
          '%label' => $widget->getPluginDefinition()['label'],
        ]),
      ];
      $row['form'] = [];
      $row['form'] = $widget->buildConfigurationForm($row['form'], $form_state);
      $row['remove'] = [
        '#type' => 'submit',
        '#value' => $this->t('Delete'),
        '#name' => 'remove' . $uuid,
        '#ajax' => [
          'callback' => [get_class($this), 'tableUpdatedAjaxCallback'],
          'wrapper' => 'widgets',
          'event' => 'click',
        ],
        '#executes_submit_callback' => TRUE,
        '#submit' => [[get_class($this), 'submitDeleteWidget']],
        '#arguments' => $uuid,
        '#limit_validation_errors' => [],
      ];
      $row['weight'] = [
        '#type' => 'weight',
        '#default_value' => $widget->getWeight(),
        '#title' => $this->t('Weight for @widget widget', ['@widget' => $widget->label()]),
        '#title_display' => 'invisible',
        '#attributes' => [
          'class' => ['variant-weight'],
        ],
      ];
      $form['widgets']['table'][$uuid] = $row;
    }
    return $form;
  }

  /**
   * AJAX submit callback for adding widgets to the entity browser.
   */
  public static function submitAddWidget($form, FormStateInterface $form_state) {
    $cached_values = $form_state->getTemporaryValue('wizard');
    /** @var \Drupal\entity_browser\EntityBrowserInterface $entity_browser */
    $entity_browser = $cached_values['entity_browser'];
    $widgets_num = count($entity_browser->getWidgets());
    $widget = $form_state->getValue('widget');
    $weight = $widgets_num + 1;
    $entity_browser->addWidget([
      'id' => $widget,
      'label' => $widget,
      'weight' => $weight,
      // Configuration will be set on the widgets page.
      'settings' => [],
    ]);
    \Drupal::service('user.shared_tempstore')
      ->get('entity_browser.config')
      ->set($entity_browser->id(), $cached_values);
    $form_state->setRebuild();
  }

  /**
   * AJAX submit callback for removing widgets from the entity browser.
   */
  public static function submitDeleteWidget($form, FormStateInterface $form_state) {
    $cached_values = $form_state->getTemporaryValue('wizard');
    /** @var \Drupal\entity_browser\EntityBrowserInterface $entity_browser */
    $entity_browser = $cached_values['entity_browser'];
    $entity_browser->deleteWidget($entity_browser->getWidget($form_state->getTriggeringElement()['#arguments']));
    \Drupal::service('user.shared_tempstore')
      ->get('entity_browser.config')
      ->set($entity_browser->id(), $cached_values);
    $form_state->setRebuild();
  }

  /**
   * AJAX callback for all operations that update widgets table.
   */
  public static function tableUpdatedAjaxCallback($form, $form_state) {
    return $form['widgets'];
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    /** @var \Drupal\entity_browser\EntityBrowserInterface $entity_browser */
    $entity_browser = $form_state->getTemporaryValue('wizard')['entity_browser'];
    /** @var \Drupal\entity_browser\WidgetInterface $widget */
    foreach ($entity_browser->getWidgets() as $widget) {
      $widget->validateConfigurationForm($form, $form_state);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    /** @var \Drupal\entity_browser\EntityBrowserInterface $entity_browser */
    $entity_browser = $form_state->getTemporaryValue('wizard')['entity_browser'];
    $table = $form_state->getValue('table');
    /** @var \Drupal\entity_browser\WidgetInterface $widget */
    foreach ($entity_browser->getWidgets() as $uuid => $widget) {
      $widget->submitConfigurationForm($form, $form_state);
      $widget->setWeight($table[$uuid]['weight']);
      $widget->setLabel($table[$uuid]['label']);
    }
  }

}
