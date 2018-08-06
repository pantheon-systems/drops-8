<?php

namespace Drupal\webform\Plugin\Condition;

use Drupal\Core\Condition\ConditionPluginBase;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\webform\Plugin\Field\FieldType\WebformEntityReferenceItem;

/**
 * Provides a 'Webform' condition.
 *
 * @Condition(
 *   id = "webform",
 *   label = @Translation("Webforms"),
 *   context = {
 *     "webform" = @ContextDefinition("entity:webform", label = @Translation("Webform"), required = FALSE),
 *     "webform_submission" = @ContextDefinition("entity:webform_submission", label = @Translation("Webform submission"), required = FALSE),
 *     "node" = @ContextDefinition("entity:node", label = @Translation("Node"), required = FALSE),
 *   }
 * )
 */
class Webform extends ConditionPluginBase implements ContainerFactoryPluginInterface {

  /**
   * The entity storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $entityStorage;

  /**
   * Creates a new Webform instance.
   *
   * @param \Drupal\Core\Entity\EntityStorageInterface $entity_storage
   *   The entity storage.
   * @param array $configuration
   *   The plugin configuration, i.e. an array with configuration values keyed
   *   by configuration option name. The special key 'context' may be used to
   *   initialize the defined contexts by setting it to an array of context
   *   values keyed by context names.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   */
  public function __construct(EntityStorageInterface $entity_storage, array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityStorage = $entity_storage;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $container->get('entity_type.manager')->getStorage('webform'),
      $configuration,
      $plugin_id,
      $plugin_definition
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $options = [];
    $webforms = $this->entityStorage->loadMultiple();
    foreach ($webforms as $webform) {
      $options[$webform->id()] = $webform->label();
    }
    $form['webforms'] = [
      '#title' => $this->t('Webform'),
      '#description' => $this->t('Select which webforms this block should be displayed on.'),
      '#type' => 'select',
      '#options' => $options,
      '#multiple' => $options,
      '#default_value' => $this->configuration['webforms'],
      '#attached' => ['library' => ['webform/webform.element.select2']],
      '#attributes' => ['class' => ['js-webform-select2', 'webform-select2']],
    ];

    if (empty($this->configuration['context_mapping'])) {
      $form['message'] = [
        '#type' => 'webform_message',
        '#message_message' => $this->t('Please make sure to select which entities should be used to determine the current webform.'),
        '#message_type' => 'warning',
      ];
    }

    $form = parent::buildConfigurationForm($form, $form_state);

    // Add helpful descriptions to context mapping.
    $form['context_mapping']['webform']['#description'] = $this->t("Select 'Webform from URL' to display this block, when the current request's path contains the selected webform.");
    $form['context_mapping']['webform_submission']['#title'] = $this->t('Select a @context value:', ['@context' => $this->t('webform submission')]);
    $form['context_mapping']['webform_submission']['#description'] = $this->t("Select 'Webform submission from URL' to display this block, when the current request's path contains a webform submission that was created from the selected webform.");
    $form['context_mapping']['node']['#description'] = $this->t("Select 'Node from URL' to display this block, when the current request's path contains a node that references the selected webform using a dedicated webform field or node.");

    // Hide 'Negate the condition', which does not make sense.
    if (isset($form['negate'])) {
      $form['negate']['#access'] = FALSE;
    }

    // Attached library to summarize configuration settings.
    $form['#attached']['library'][] = 'webform/webform.block';

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    if (!empty($values['webforms']) && empty(array_filter($values['context_mapping']))) {
      $form_state->setErrorByName('webforms', $this->t('Please select which entity should be used to determine the current webform.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->configuration['webforms'] = array_filter($form_state->getValue('webforms'));
    parent::submitConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function summary() {
    if (count($this->configuration['webforms']) > 1) {
      $webforms = $this->configuration['webforms'];
      $last = array_pop($webforms);
      $webforms = implode(', ', $webforms);
      return $this->t('The webform is @webforms or @last', ['@webforms' => $webforms, '@last' => $last]);
    }
    $webform = reset($this->configuration['webforms']);
    return $this->t('The webform is @webform', ['@webform' => $webform]);
  }

  /**
   * {@inheritdoc}
   */
  public function evaluate() {
    if (empty($this->configuration['webforms']) && !$this->isNegated()) {
      return TRUE;
    }
    elseif ($webform = $this->getContextWebform()) {
      return !empty($this->configuration['webforms'][$webform->id()]);
    }
    else {
      return FALSE;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return ['webforms' => []] + parent::defaultConfiguration();
  }

  /**
   * Gets the webform for a defined context.
   *
   * @return null|\Drupal\webform\WebformInterface
   *   The current context's webform.
   */
  protected function getContextWebform() {
    if ($webform_submission = $this->getContextValue('webform_submission')) {
      return $webform_submission->getWebform();
    }
    if ($webform = $this->getContextValue('webform')) {
      return $webform;
    }
    if ($node = $this->getContextValue('node')) {
      $webform_field_name = WebformEntityReferenceItem::getEntityWebformFieldName($node);
      if ($webform_field_name && $node->$webform_field_name->entity) {
        return $node->$webform_field_name->entity;
      }
    }
    return NULL;
  }

}
