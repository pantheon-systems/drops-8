<?php

namespace Drupal\entity_browser\Wizard;

use Drupal\Core\DependencyInjection\ClassResolverInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\ctools\Wizard\EntityFormWizardBase;
use Drupal\entity_browser\Form\DisplayConfig;
use Drupal\entity_browser\Form\GeneralInfoConfig;
use Drupal\entity_browser\Form\SelectionDisplayConfig;
use Drupal\entity_browser\Form\WidgetsConfig;
use Drupal\entity_browser\Form\WidgetSelectorConfig;
use Drupal\user\SharedTempStoreFactory;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Custom form wizard for entity browser configuration.
 */
class EntityBrowserWizard extends EntityFormWizardBase {

  /**
   * @param \Drupal\user\SharedTempStoreFactory $tempstore
   *   Tempstore Factory for keeping track of values in each step of the
   *   wizard.
   * @param \Drupal\Core\Form\FormBuilderInterface $builder
   *   The Form Builder.
   * @param \Drupal\Core\DependencyInjection\ClassResolverInterface $class_resolver
   *   The class resolver.
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $event_dispatcher
   *   The event dispatcher.
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager.
   * @param $tempstore_id
   *   The shared temp store factory collection name.
   * @param null $machine_name
   *   The SharedTempStore key for our current wizard values.
   * @param null $step
   *   The current active step of the wizard.
   */
  public function __construct(SharedTempStoreFactory $tempstore, FormBuilderInterface $builder, ClassResolverInterface $class_resolver, EventDispatcherInterface $event_dispatcher, EntityManagerInterface $entity_manager, RouteMatchInterface $route_match, $tempstore_id, $entity_browser = NULL, $step = 'general') {
    parent::__construct($tempstore, $builder, $class_resolver, $event_dispatcher, $entity_manager, $route_match, $tempstore_id, $entity_browser, $step);
  }

  /**
   * {@inheritdoc}
   */
  public function getNextParameters($cached_values) {
    $parameters = parent::getNextParameters($cached_values);
    $parameters['entity_browser'] = $parameters['machine_name'];
    unset($parameters['machine_name']);
    return $parameters;
  }

  /**
   * {@inheritdoc}
   */
  public function getPreviousParameters($cached_values) {
    $parameters = parent::getPreviousParameters($cached_values);
    $parameters['entity_browser'] = $parameters['machine_name'];
    unset($parameters['machine_name']);
    return $parameters;
  }

  /**
   * {@inheritdoc}
   */
  public function getWizardLabel() {
    return $this->t('Entity browser');
  }

  /**
   * {@inheritdoc}
   */
  public function getMachineLabel() {
    return $this->t('Label');
  }

  /**
   * {@inheritdoc}
   */
  public function getEntityType() {
    return 'entity_browser';
  }

  /**
   * {@inheritdoc}
   */
  public function exists() {
    return 'Drupal\entity_browser\Entity\EntityBrowser::load';
  }

  /**
   * {@inheritdoc}
   */
  public function getOperations($cached_values) {
    return [
      'general' => [
        'title' => $this->t('General information'),
        'form' => GeneralInfoConfig::class,
      ],
      'display' => [
        'title' => $this->t('Display'),
        'form' => DisplayConfig::class,
      ],
      'widget_selector' => [
        'title' => $this->t('Widget selector'),
        'form' => WidgetSelectorConfig::class,
      ],
      'selection_display' => [
        'title' => $this->t('Selection display'),
        'form' => SelectionDisplayConfig::class,
      ],
      'widgets' => [
        'title' => $this->t('Widgets'),
        'form' => WidgetsConfig::class,
      ],
    ];
  }

}
