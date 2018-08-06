<?php

namespace Drupal\diff;

use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Plugin\PluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Base class for field diff builder plugins.
 */
abstract class FieldDiffBuilderBase extends PluginBase implements FieldDiffBuilderInterface, ContainerFactoryPluginInterface {

  /**
   * Contains the configuration object factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The entity parser.
   *
   * @var \Drupal\diff\DiffEntityParser
   */
  protected $entityParser;

  /**
   * Constructs a FieldDiffBuilderBase object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\diff\DiffEntityParser $entity_parser
   *   The entity parser.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager, DiffEntityParser $entity_parser) {
    $this->entityTypeManager = $entity_type_manager;
    $this->entityParser = $entity_parser;
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->configuration += $this->defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('diff.entity_parser')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form['show_header'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Show field title'),
      '#weight' => -5,
      '#default_value' => $this->configuration['show_header'],
    );
    $form['markdown'] = array(
      '#type' => 'select',
      '#title' => $this->t('Markdown callback'),
      '#default_value' => $this->configuration['markdown'],
      '#options' => array(
        'drupal_html_to_text' => $this->t('Drupal HTML to Text'),
        'filter_xss' => $this->t('Filter XSS (some tags)'),
        'filter_xss_all' => $this->t('Filter XSS (all tags)'),
      ),
      '#description' => $this->t('These provide ways to clean markup tags to make comparisons easier to read.'),
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    // By default an empty validation function is provided.
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->configuration['show_header'] = $form_state->getValue('show_header');
    $this->configuration['markdown'] = $form_state->getValue('markdown');
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return array(
      'show_header' => 1,
      'markdown' => 'drupal_html_to_text',
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getConfiguration() {
    return $this->configuration;
  }

  /**
   * {@inheritdoc}
   */
  public function setConfiguration(array $configuration) {
    $this->configuration = $configuration + $this->defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    return array();
  }

  /**
   * {@inheritdoc}
   */
  public static function isApplicable(FieldStorageDefinitionInterface $field_definition) {
    return TRUE;
  }

}
