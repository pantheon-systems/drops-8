<?php

namespace Drupal\ctools\Wizard;

use Drupal\Core\DependencyInjection\ClassResolverInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\ctools\Event\WizardEvent;
use Drupal\Core\TempStore\SharedTempStoreFactory;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * The base class for all entity form wizards.
 */
abstract class EntityFormWizardBase extends FormWizardBase implements EntityFormWizardInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * @param \Drupal\Core\TempStore\SharedTempStoreFactory $tempstore
   *   Tempstore Factory for keeping track of values in each step of the
   *   wizard.
   * @param \Drupal\Core\Form\FormBuilderInterface $builder
   *   The Form Builder.
   * @param \Drupal\Core\DependencyInjection\ClassResolverInterface $class_resolver
   *   The class resolver.
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $event_dispatcher
   *   The event dispatcher.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param $tempstore_id
   *   The shared temp store factory collection name.
   * @param null $machine_name
   *   The SharedTempStore key for our current wizard values.
   * @param null $step
   *   The current active step of the wizard.
   */
  public function __construct(SharedTempStoreFactory $tempstore, FormBuilderInterface $builder, ClassResolverInterface $class_resolver, EventDispatcherInterface $event_dispatcher, EntityTypeManagerInterface $entity_type_manager, RouteMatchInterface $route_match, $tempstore_id, $machine_name = NULL, $step = NULL) {
    $this->entityTypeManager = $entity_type_manager;
    parent::__construct($tempstore, $builder, $class_resolver, $event_dispatcher, $route_match, $tempstore_id, $machine_name, $step);
  }

  /**
   * {@inheritdoc}
   */
  public static function getParameters() {
    return [
      'tempstore' => \Drupal::service('tempstore.shared'),
      'builder' => \Drupal::service('form_builder'),
      'class_resolver' => \Drupal::service('class_resolver'),
      'event_dispatcher' => \Drupal::service('event_dispatcher'),
      // Keep the deprecated entity manager service as a parameter as well for
      // BC, so that subclasses still work.
      'entity_manager' => \Drupal::service('entity.manager'),
      'entity_type_manager' => \Drupal::service('entity_type.manager'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function initValues() {
    $storage = $this->entityTypeManager->getStorage($this->getEntityType());
    if ($this->getMachineName()) {
      $values = $this->getTempstore()->get($this->getMachineName());
      if (!$values) {
        $entity = $storage->load($this->getMachineName());
        $values[$this->getEntityType()] = $entity;
        $values['id'] = $entity->id();
        $values['label'] = $entity->label();
      }
    }
    else {
      $entity = $storage->create([]);
      $values[$this->getEntityType()] = $entity;
    }
    $event = new WizardEvent($this, $values);
    $this->dispatcher->dispatch(FormWizardInterface::LOAD_VALUES, $event);
    return $event->getValues();
  }

  /**
   * {@inheritdoc}
   */
  public function finish(array &$form, FormStateInterface $form_state) {
    $cached_values = $form_state->getTemporaryValue('wizard');
    /** @var \Drupal\Core\Entity\EntityInterface $entity */
    $entity = $cached_values[$this->getEntityType()];
    $entity->set('id', $cached_values['id']);
    $entity->set('label', $cached_values['label']);
    $status = $entity->save();

    $arguments = [
      '@entity-type' => $entity->getEntityType()->getLowercaseLabel(),
      '%label' => $entity->label(),
    ];
    if ($status === SAVED_UPDATED) {
      $this->messenger()->addMessage($this->t('The @entity-type %label has been updated.', $arguments));
      $this->logger($entity->getEntityType()->getProvider())->notice('Updated @entity-type %label.', $arguments);
    }
    elseif ($status === SAVED_NEW) {
      $this->messenger()->addMessage($this->t('The @entity-type %label has been added.', $arguments));
      $this->logger($entity->getEntityType()->getProvider())->notice('Added @entity-type %label.', $arguments);
    }

    $form_state->setRedirectUrl($entity->toUrl('collection'));
    parent::finish($form, $form_state);
  }

  /**
   * Helper function for generating label and id form elements.
   *
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *
   * @return array
   */
  protected function customizeForm(array $form, FormStateInterface $form_state) {
    $form = parent::customizeForm($form, $form_state);
    if ($this->machine_name) {
      $entity = $this->entityTypeManager->getStorage($this->getEntityType())
        ->load($this->machine_name);
    }
    else {
      $entity = NULL;
    }
    $cached_values = $form_state->getTemporaryValue('wizard');
    // If the entity already exists, allow for non-linear step interaction.
    if ($entity) {
      // Setup the step rendering theme element.
      $prefix = [
        '#theme' => ['ctools_wizard_trail_links'],
        '#wizard' => $this,
        '#cached_values' => $cached_values,
      ];
      $form['#prefix'] = \Drupal::service('renderer')->render($prefix);
    }
    // Get the current form operation.
    $operation = $this->getOperation($cached_values);
    $operations = $this->getOperations($cached_values);
    $default_operation = reset($operations);
    if ($operation['form'] == $default_operation['form']) {
      // Get the plugin definition of this entity.
      $definition = $this->entityTypeManager->getDefinition($this->getEntityType());
      // Create id and label form elements.
      $form['name'] = array(
        '#type' => 'fieldset',
        '#attributes' => array('class' => array('fieldset-no-legend')),
        '#title' => $this->getWizardLabel(),
      );
      $form['name']['label'] = array(
        '#type' => 'textfield',
        '#title' => $this->getMachineLabel(),
        '#required' => TRUE,
        '#size' => 32,
        '#default_value' => !empty($cached_values['label']) ? $cached_values['label'] : '',
        '#maxlength' => 255,
        '#disabled' => !empty($cached_values['label']),
      );
      $form['name']['id'] = array(
        '#type' => 'machine_name',
        '#maxlength' => 128,
        '#machine_name' => array(
          'source' => array('name', 'label'),
          'exists' => $this->exists(),
        ),
        '#description' => $this->t('A unique machine-readable name for this @entity_type. It must only contain lowercase letters, numbers, and underscores.', ['@entity_type' => $definition->getLabel()]),
        '#default_value' => !empty($cached_values['id']) ? $cached_values['id'] : '',
        '#disabled' => !empty($cached_values['id']),
      );
    }

    return $form;
  }

}
