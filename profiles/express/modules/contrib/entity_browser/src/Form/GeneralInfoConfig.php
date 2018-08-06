<?php

namespace Drupal\entity_browser\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\entity_browser\DisplayManager;
use Drupal\entity_browser\SelectionDisplayManager;
use Drupal\entity_browser\WidgetManager;
use Drupal\entity_browser\WidgetSelectorManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * General information configuration step in entity browser form wizard.
 */
class GeneralInfoConfig extends FormBase {

  /**
   * Entity browser display plugin manager.
   *
   * @var \Drupal\entity_browser\DisplayManager
   */
  protected $displayManager;

  /**
   * Entity browser widget selector plugin manager.
   *
   * @var \Drupal\entity_browser\WidgetSelectorManager
   */
  protected $widgetSelectorManager;

  /**
   * Entity browser selection display plugin manager.
   *
   * @var \Drupal\entity_browser\SelectionDisplayManager
   */
  protected $selectionDisplayManager;

  /**
   * Entity browser widget plugin manager.
   *
   * @var \Drupal\entity_browser\WidgetManager
   */
  protected $widgetManager;

  /**
   * Constructs GeneralInfoConfig form class.
   *
   * @param \Drupal\entity_browser\DisplayManager $display_manager
   *   Entity browser display plugin manager.
   * @param \Drupal\entity_browser\WidgetSelectorManager $widget_selector_manager
   *   Entity browser widget selector plugin manager.
   * @param \Drupal\entity_browser\SelectionDisplayManager $selection_display_manager
   *   Entity browser selection display plugin manager.
   * @param \Drupal\entity_browser\WidgetManager $widget_manager
   *   Entity browser widget plugin manager.
   */
  function __construct(DisplayManager $display_manager, WidgetSelectorManager $widget_selector_manager, SelectionDisplayManager $selection_display_manager, WidgetManager $widget_manager) {
    $this->displayManager = $display_manager;
    $this->selectionDisplayManager = $selection_display_manager;
    $this->widgetSelectorManager = $widget_selector_manager;
    $this->widgetManager = $widget_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.entity_browser.display'),
      $container->get('plugin.manager.entity_browser.widget_selector'),
      $container->get('plugin.manager.entity_browser.selection_display'),
      $container->get('plugin.manager.entity_browser.widget')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'entity_browser_general_info_config_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $cached_values = $form_state->getTemporaryValue('wizard');
    /** @var \Drupal\entity_browser\EntityBrowserInterface  $entity_browser */
    $entity_browser = $cached_values['entity_browser'];

    if (empty($entity_browser->id())) {
      $help_text = '<div class="clearfix eb-help-text"><h2>' . $this->t('Entity Browser creation instructions') . '</h2>';
      $help_text .= '<p>' . $this->t('This is a multi-step form. In this first step you need to define the main characteristics of the Entity Browser (in other words, which plugins will be used for each functionality). In the following steps of this wizard, each individual plugin can be configured, when necessary.') . '</p>';
      $help_text .= '<p>' . $this->t('You can find more detailed information about creating and configuring Entity Browsers at the <a href="@guide_href" target="_blank">official documentation</a>.', ['@guide_href' => 'https://drupal-media.gitbooks.io/drupal8-guide/content/modules/entity_browser/intro.html']) . '</p>';
      $help_text .= '</div>';
      $form['help_text'] = [
        '#markup' => $help_text,
      ];
    }

    $displays = [];
    $display_description = $this->t('Choose here how the browser(s) should be presented to the end user. The available plugins are:') . '<ul>';
    foreach ($this->displayManager->getDefinitions() as $plugin_id => $plugin_definition) {
      $displays[$plugin_id] = $plugin_definition['label'];
      $display_description .= '<li><b>' . $plugin_definition['label'] . ':</b> ' . $plugin_definition['description'] . '</li>';
    }
    $display_description .= '</ul>';
    $form['display'] = [
      '#type' => 'select',
      '#title' => $this->t('Display plugin'),
      '#description' => $display_description,
      '#default_value' => $entity_browser->get('display') ? $entity_browser->getDisplay()->getPluginId() : 'modal',
      '#options' => $displays,
      '#required' => TRUE,
    ];

    $widget_selectors = [];
    $widget_description = $this->t('In the last step of the entity browser configuration you can decide how the widgets will be available to the editor. The available plugins are:') . '<ul>';
    foreach ($this->widgetSelectorManager->getDefinitions() as $plugin_id => $plugin_definition) {
      $widget_selectors[$plugin_id] = $plugin_definition['label'];
      $widget_description .= '<li><b>' . $plugin_definition['label'] . ':</b> ' . $plugin_definition['description'] . '</li>';
    }
    $widget_description .= '</ul>';
    $form['widget_selector'] = [
      '#type' => 'select',
      '#title' => $this->t('Widget selector plugin'),
      '#description' => $widget_description,
      '#default_value' => $entity_browser->get('widget_selector') ? $entity_browser->getWidgetSelector()->getPluginId() : 'tabs',
      '#options' => $widget_selectors,
      '#required' => TRUE,
    ];

    $selection_display = [];
    $selection_description = $this->t('You can optionally allow a "work-in-progress selection zone" to be available to the editor, while still navigating, browsing and selecting the entities. The available plugins are:') . '<ul>';
    foreach ($this->selectionDisplayManager->getDefinitions() as $plugin_id => $plugin_definition) {
      $selection_display[$plugin_id] = $plugin_definition['label'];
      $selection_description .= '<li><b>' . $plugin_definition['label'] . ':</b> ' . $plugin_definition['description'] . '</li>';
    }
    $selection_description .= '</ul>';
    $form['selection_display'] = [
      '#type' => 'select',
      '#title' => $this->t('Selection display plugin'),
      '#description' => $selection_description,
      '#default_value' => $entity_browser->get('selection_display') ? $entity_browser->getSelectionDisplay()->getPluginId() : 'no_display',
      '#options' => $selection_display,
      '#required' => TRUE,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    /** @var \Drupal\entity_browser\EntityBrowserInterface $entity_browser */
    $entity_browser = $form_state->getTemporaryValue('wizard')['entity_browser'];
    $entity_browser->setName($form_state->getValue('id'))
      ->setLabel($form_state->getValue('label'))
      ->setDisplay($form_state->getValue('display'))
      ->setWidgetSelector($form_state->getValue('widget_selector'))
      ->setSelectionDisplay($form_state->getValue('selection_display'));
  }

}
