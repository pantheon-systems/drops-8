<?php

namespace Drupal\features_ui\Form;

use Drupal\features\FeaturesManagerInterface;
use Drupal\features\FeaturesAssignerInterface;
use Drupal\Core\Entity\ContentEntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configures the selected configuration assignment method for this site.
 */
abstract class AssignmentFormBase extends FormBase {

  /**
   * The features manager.
   *
   * @var \Drupal\features\FeaturesManagerInterface
   */
  protected $featuresManager;

  /**
   * The package assigner.
   *
   * @var \Drupal\features\FeaturesAssignerInterface
   */
  protected $assigner;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The current bundle.
   *
   * @var \Drupal\features\FeaturesBundleInterface
   */
  protected $currentBundle;

  /**
   * Constructs a AssignmentBaseForm object.
   *
   * @param \Drupal\features\FeaturesManagerInterface $features_manager
   *   The features manager.
   * @param \Drupal\features\FeaturesAssignerInterface $assigner
   *   The assigner.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(FeaturesManagerInterface $features_manager, FeaturesAssignerInterface $assigner, EntityTypeManagerInterface $entity_type_manager) {
    $this->featuresManager = $features_manager;
    $this->assigner = $assigner;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('features.manager'),
      $container->get('features_assigner'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * Adds configuration types checkboxes.
   */
  protected function setConfigTypeSelect(&$form, $defaults, $type, $bundles_only = FALSE, $description = '') {
    $options = $this->featuresManager->listConfigTypes($bundles_only);

    if (!isset($form['types'])) {
      $form['types'] = array(
        '#type' => 'container',
        '#tree' => TRUE,
      );
    }

    $form['types']['config'] = array(
      '#type' => 'checkboxes',
      '#title' => $this->t('Configuration types'),
      '#description' => !empty($description) ? $description : $this->t('Select types of configuration that should be considered @type types.', array('@type' => $type)),
      '#options' => $options,
      '#default_value' => $defaults,
    );
  }

  /**
   * Adds content entity types checkboxes.
   */
  protected function setContentTypeSelect(&$form, $defaults, $type, $exclude_has_config_bundles = TRUE) {
    $entity_types = $this->entityTypeManager->getDefinitions();

    $has_config_bundle = array();
    foreach ($entity_types as $definition) {
      if ($entity_type_id = $definition->getBundleOf()) {
        $has_config_bundle[] = $entity_type_id;
      }
    }
    $options = array();

    foreach ($entity_types as $entity_type_id => $entity_type) {
      if (!$entity_type instanceof ContentEntityTypeInterface) {
        continue;
      }
      if ($exclude_has_config_bundles && in_array($entity_type_id, $has_config_bundle)) {
        continue;
      }
      $options[$entity_type_id] = $entity_type->getLabel() ?: $entity_type_id;
    }

    // Sort the entity types by label.
    uasort($options, 'strnatcasecmp');

    if (!isset($form['types'])) {
      $form['types'] = array(
        '#type' => 'container',
        '#tree' => TRUE,
      );
    }

    $form['types']['content'] = array(
      '#type' => 'checkboxes',
      '#title' => $this->t('Content entity types'),
      '#description' => $this->t('Select content entity types that should be considered @type types.', array('@type' => $type)),
      '#options' => $options,
      '#default_value' => $defaults,
    );
  }

  /**
   * Adds a "Save settings" submit action.
   */
  protected function setActions(&$form, $method_id = NULL) {
    $assignment_info = $this->assigner->getAssignmentMethods();
    if (isset($method_id) && isset($assignment_info[$method_id])) {
      $method = $assignment_info[$method_id];
      $form['help_text'] = array(
        '#markup' => $method['description'],
        '#prefix' => '<p class="messages messages--status">',
        '#suffix' => '</p>',
        '#weight' => -99,
      );
    }

    $form['actions'] = array('#type' => 'actions');
    $form['actions']['submit'] = array(
      '#type' => 'submit',
      '#button_type' => 'primary',
      '#value' => $this->t('Save settings'),
    );
    $form['#attributes']['class'][] = 'features-assignment-settings-form';
    $form['#attached'] = array(
      'library' => array(
        'features_ui/drupal.features_ui.admin',
    ));
  }

  /**
   * Redirects back to the Bundle config form.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  protected function setRedirect(FormStateInterface $form_state) {
    $form_state->setRedirect('features.assignment', array('bundle_name' => $this->currentBundle->getMachineName()));
  }

}
