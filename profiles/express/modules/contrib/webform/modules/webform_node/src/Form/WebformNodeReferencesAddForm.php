<?php

namespace Drupal\webform_node\Form;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\webform\Utility\WebformElementHelper;
use Drupal\webform\WebformInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form for adding webform node variants.
 */
class WebformNodeReferencesAddForm extends FormBase {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'webform_node_references_add_form';
  }

  /**
   * Constructs a new WebformNodeReferencesAddForm object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager')
    );
  }

  /**
   * Form constructor.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param \Drupal\webform\WebformInterface $webform
   *   The webform.
   *
   * @return array
   *   The form structure.
   */
  public function buildForm(array $form, FormStateInterface $form_state, WebformInterface $webform = NULL) {
    $bundles = [];
    /** @var \Drupal\field\FieldConfigInterface[] $field_configs */
    $field_configs = $this->entityTypeManager->getStorage('field_config')->loadByProperties(['entity_type' => 'node']);
    foreach ($field_configs as $field_config) {
      if ($field_config->get('field_type') === 'webform') {
        $bundle = $field_config->get('bundle');
        $node_type = $this->entityTypeManager->getStorage('node_type')->load($bundle);
        $bundles[$bundle] = $node_type->label();
      }
    }

    $form['description'] = [
      '#type' => 'container',
      'text' => [
        '#markup' => $this->t('Enter webform information and then click submit, which will redirect you to the appropriate create content form.'),
        '#prefix' => '<p>',
        '#suffix' => '</p>',
      ],
    ];

    $form['webform_id'] = [
      '#type' => 'value',
      '#value' => $webform->id(),
    ];

    $form['webform_title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Title'),
      '#default_value' => $webform->label(),
      '#required' => TRUE,
    ];

    $form['bundle'] = [
      '#type' => 'select',
      '#title' => $this->t('Content type'),
      '#options' => $bundles,
      '#required' => TRUE,
    ];

    $element_keys = $webform->getElementsVariant();
    if (isset($element_keys)) {
      $form['webform_default_data'] = [
        '#tree' => TRUE,
      ];
      foreach ($element_keys as $element_key) {
        $element = $webform->getElement($element_key);
        $variants = $webform->getVariants(NULL, TRUE, $element_key);
        if (!$variants->count()) {
          continue;
        }

        $variant_options = [];
        foreach ($variants as $variant) {
          if ($variant->isEnabled()) {
            $variant_options[$variant->getVariantId()] = $variant->label();
          }
        }
        if ($variant_options) {
          $form['webform_default_data'][$element_key] = [
            '#type' => 'select',
            '#title' => WebformElementHelper::getAdminTitle($element),
            '#options' => $variant_options,
            '#empty_option' => $this->t('- None -'),
          ];
        }
      }
    }

    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Create content'),
      '#button_type' => 'primary',
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();

    // Build query string.
    $query = [];
    $query['webform_id'] = $values['webform_id'];
    $query['webform_title'] = $values['webform_title'];
    if (!empty($values['webform_default_data'])) {
      $query['webform_default_data'] = $values['webform_default_data'];
    }

    // Build route.
    $route_name = 'node.add';
    $route_parameters = ['node_type' => $values['bundle']];
    $route_options = ['query' => $query];

    // Redirect to node add form.
    $form_state->setRedirect($route_name, $route_parameters, $route_options);
  }

}
