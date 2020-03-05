<?php

namespace Drupal\pathauto\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\pathauto\AliasTypeManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Edit form for pathauto patterns.
 */
class PatternEditForm extends EntityForm {

  /**
   * The alias type manager.
   *
   * @var \Drupal\pathauto\AliasTypeManager
   */
  protected $manager;

  /**
   * @var \Drupal\pathauto\PathautoPatternInterface
   */
  protected $entity;

  /**
   * The entity type bundle info service.
   *
   * @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface
   */
  protected $entityTypeBundleInfo;

  /**
   * The entity manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The language manager service.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.alias_type'),
      $container->get('entity_type.bundle.info'),
      $container->get('entity_type.manager'),
      $container->get('language_manager')
    );
  }

  /**
   * PatternEditForm constructor.
   *
   * @param \Drupal\pathauto\AliasTypeManager $manager
   *   The alias type manager.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entity_type_bundle_info
   *   The entity type bundle info service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity manager service.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager service.
   */
  function __construct(AliasTypeManager $manager, EntityTypeBundleInfoInterface $entity_type_bundle_info, EntityTypeManagerInterface $entity_type_manager, LanguageManagerInterface $language_manager) {
    $this->manager = $manager;
    $this->entityTypeBundleInfo = $entity_type_bundle_info;
    $this->entityTypeManager = $entity_type_manager;
    $this->languageManager = $language_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $options = [];
    foreach ($this->manager->getVisibleDefinitions() as $plugin_id => $plugin_definition) {
      $options[$plugin_id] = $plugin_definition['label'];
    }
    $form['type'] = [
      '#type' => 'select',
      '#title' => $this->t('Pattern type'),
      '#default_value' => $this->entity->getType(),
      '#options' => $options,
      '#required' => TRUE,
      '#limit_validation_errors' => [['type']],
      '#submit' => ['::submitSelectType'],
      '#executes_submit_callback' => TRUE,
      '#ajax' => [
        'callback' => '::ajaxReplacePatternForm',
        'wrapper' => 'pathauto-pattern',
        'method' => 'replace',
      ],
    ];

    $form['pattern_container'] = [
      '#type' => 'container',
      '#prefix' => '<div id="pathauto-pattern">',
      '#suffix' => '</div>',
    ];

    // if there is no type yet, stop here.
    if ($this->entity->getType()) {

      $alias_type = $this->entity->getAliasType();

      $form['pattern_container']['pattern'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Path pattern'),
        '#default_value' => $this->entity->getPattern(),
        '#size' => 65,
        '#maxlength' => 1280,
        '#element_validate' => ['token_element_validate', 'pathauto_pattern_validate'],
        '#after_build' => ['token_element_validate'],
        '#token_types' => $alias_type->getTokenTypes(),
        '#min_tokens' => 1,
        '#required' => TRUE,
      ];

      // Show the token help relevant to this pattern type.
      $form['pattern_container']['token_help'] = [
        '#theme' => 'token_tree_link',
        '#token_types' => $alias_type->getTokenTypes(),
      ];

      // Expose bundle and language conditions.
      if ($alias_type->getDerivativeId() && $entity_type = $this->entityTypeManager->getDefinition($alias_type->getDerivativeId())) {

        $default_bundles = [];
        $default_languages = [];
        foreach ($this->entity->getSelectionConditions() as $condition_id => $condition) {
          if (in_array($condition->getPluginId(), ['entity_bundle:' . $entity_type->id(), 'node_type'])) {
            $default_bundles = $condition->getConfiguration()['bundles'];
          }
          elseif ($condition->getPluginId() == 'language') {
            $default_languages = $condition->getConfiguration()['langcodes'];
          }
        }

        if ($entity_type->hasKey('bundle') && $bundles = $this->entityTypeBundleInfo->getBundleInfo($entity_type->id())) {
          $bundle_options = [];
          foreach ($bundles as $id => $info) {
            $bundle_options[$id] = $info['label'];
          }
          $form['pattern_container']['bundles'] = [
            '#title' => $entity_type->getBundleLabel(),
            '#type' => 'checkboxes',
            '#options' => $bundle_options,
            '#default_value' => $default_bundles,
            '#description' => $this->t('Check to which types this pattern should be applied. Leave empty to allow any.'),
          ];
        }

        if ($this->languageManager->isMultilingual() && $entity_type->isTranslatable()) {
          $language_options = [];
          foreach ($this->languageManager->getLanguages() as $id => $language) {
            $language_options[$id] = $language->getName();
          }
          $form['pattern_container']['languages'] = [
            '#title' => $this->t('Languages'),
            '#type' => 'checkboxes',
            '#options' => $language_options,
            '#default_value' => $default_languages,
            '#description' => $this->t('Check to which languages this pattern should be applied. Leave empty to allow any.'),
          ];
        }
      }
    }

    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $this->entity->label(),
      '#required' => TRUE,
      '#description' => $this->t('A short name to help you identify this pattern in the patterns list.'),
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#title' => $this->t('ID'),
      '#maxlength' => 255,
      '#default_value' => $this->entity->id(),
      '#required' => TRUE,
      '#disabled' => !$this->entity->isNew(),
      '#machine_name' => [
        'exists' => 'Drupal\pathauto\Entity\PathautoPattern::load',
      ],
    ];

    $form['status'] = [
      '#title' => $this->t('Enabled'),
      '#type' => 'checkbox',
      '#default_value' => $this->entity->status(),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function buildEntity(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\pathauto\PathautoPatternInterface $entity */
    $entity = parent::buildEntity($form, $form_state);

    // Will only be used for new patterns.
    $default_weight = 0;

    $alias_type = $entity->getAliasType();
    if ($alias_type->getDerivativeId() && $this->entityTypeManager->hasDefinition($alias_type->getDerivativeId())) {
      $entity_type = $alias_type->getDerivativeId();
      // First, remove bundle and language conditions.
      foreach ($entity->getSelectionConditions() as $condition_id => $condition) {
        if (in_array($condition->getPluginId(), ['entity_bundle:' . $entity_type, 'node_type', 'language'])) {
          $entity->removeSelectionCondition($condition_id);
        }
      }

      if ($bundles = array_filter((array) $form_state->getValue('bundles'))) {
        $default_weight -= 5;
        $plugin_id = $entity_type == 'node' ? 'node_type' : 'entity_bundle:' . $entity_type;
        $entity->addSelectionCondition(
          [
            'id' => $plugin_id,
            'bundles' => $bundles,
            'negate' => FALSE,
            'context_mapping' => [
              $entity_type => $entity_type,
            ]
          ]
        );
      }

      if ($languages = array_filter((array) $form_state->getValue('languages'))) {
        $default_weight -= 5;
        $language_mapping = $entity_type . ':' . $this->entityTypeManager->getDefinition($entity_type)->getKey('langcode') . ':language';
        $entity->addSelectionCondition(
          [
            'id' => 'language',
            'langcodes' => array_combine($languages, $languages),
            'negate' => FALSE,
            'context_mapping' => [
              'language' => $language_mapping,
            ]
          ]
        );
        $entity->addRelationship($language_mapping, t('Language'));
      }

    }

    if ($entity->isNew()) {
      $entity->setWeight($default_weight);
    }

    return $entity;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    parent::save($form, $form_state);
    $this->messenger()->addMessage($this->t('Pattern %label saved.', [
      '%label' => $this->entity->label(),
    ]));
    $form_state->setRedirectUrl($this->entity->toUrl('collection'));
  }

  /**
   * Handles switching the type selector.
   */
  public function ajaxReplacePatternForm($form, FormStateInterface $form_state) {
    return $form['pattern_container'];
  }

  /**
   * Handles submit call when alias type is selected.
   */
  public function submitSelectType(array $form, FormStateInterface $form_state) {
    $this->entity = $this->buildEntity($form, $form_state);
    $form_state->setRebuild();
  }

}
