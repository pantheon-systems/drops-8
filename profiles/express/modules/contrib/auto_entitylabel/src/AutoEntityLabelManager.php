<?php
/**
 * @file
 * Contains \Drupal\auto_entitylabel\AutoEntityLabelManager.
 */

namespace Drupal\auto_entitylabel;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Utility\Token;

class AutoEntityLabelManager implements AutoEntityLabelManagerInterface {
  use StringTranslationTrait;

  /**
   * Automatic label is disabled.
   */
  const DISABLED = 0;

  /**
   * Automatic label is enabled. Will always be generated.
   */
  const ENABLED = 1;

  /**
   * Automatic label is optional. Will only be generated if no label was given.
   */
  const OPTIONAL = 2;

  /**
   * The content entity.
   *
   * @var ContentEntityInterface
   */
  protected $entity;

  /**
   * The type of the entity.
   *
   * @var string
   */
  protected $entity_type;

  /**
   * The bundle of the entity.
   *
   * @var string
   */
  protected $entity_bundle;

  /**
   * Indicates if the automatic label has been applied.
   *
   * @var bool
   */
  protected $auto_label_applied = FALSE;

  /**
   * Config factory.
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
   * Token service.
   *
   * @var \Drupal\Core\Utility\Token
   */
  protected $token;

  /**
   * Automatic label configuration.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $config;

  /**
   * Constructs an AutoEntityLabelManager object.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity to add the automatic label to.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   Configuration factory.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   Entity type manager
   * @param \Drupal\Core\Utility\Token $token
   *   Token manager.
   */
  public function __construct(ContentEntityInterface $entity, ConfigFactoryInterface $config_factory, EntityTypeManagerInterface $entity_type_manager, Token $token) {
    $this->entity = $entity;
    $this->entity_type = $entity->getEntityType()->id();
    $this->entity_bundle = $entity->bundle();
    $this->bundle_entity_type = $entity_type_manager->getDefinition($this->entity_type)->getBundleEntityType();

    $this->configFactory = $config_factory;
    $this->entityTypeManager = $entity_type_manager;
    $this->token = $token;
  }

  /**
   * Checks if the entity has a label.
   *
   * @return bool
   *   True if the entity has a label property.
   */
  public function hasLabel() {
    /** @var \Drupal\Core\Entity\EntityTypeInterface $definition */
    $definition = $this->entityTypeManager->getDefinition($this->entity->getEntityTypeId());
    return $definition->hasKey('label');
  }

  /**
   * {@inheritdoc}
   */
  public function setLabel() {

    if (!$this->hasLabel()) {
      throw new \Exception('This entity has no label.');
    }

    $pattern = $this->getConfig('pattern') ?: '';
    $pattern = trim($pattern);

    if ($pattern) {
      $label = $this->generateLabel($pattern, $this->entity);
    }
    else {
      $label = $this->getAlternativeLabel();
    }

    $label = substr($label, 0, 255);
    $label_name = $this->getLabelName();
    $this->entity->$label_name->setValue($label);

    $this->auto_label_applied = TRUE;
    return $label;
  }

  /**
   * {@inheritdoc}
   */
  public function hasAutoLabel() {
    return $this->getConfig('status') == self::ENABLED;
  }

  /**
   * {@inheritdoc}
   */
  public function hasOptionalAutoLabel() {
    return $this->getConfig('status') == self::OPTIONAL;
  }

  /**
   * {@inheritdoc}
   */
  public function autoLabelNeeded() {
    $not_applied = empty($this->auto_label_applied);
    $required = $this->hasAutoLabel();
    $optional = $this->hasOptionalAutoLabel() && empty($this->entity->label());
    return $not_applied && ($required || $optional);
  }

  /**
   * Gets the field name of the entity label.
   *
   * @return string
   *   The entity label field name. Empty if the entity has no label.
   */
  public function getLabelName() {
    $label_field = '';

    if ($this->hasLabel()) {
      $definition = $this->entityTypeManager->getDefinition($this->entity->getEntityTypeId());
      $label_field = $definition->getKey('label');
    }

    return $label_field;
  }

  /**
   * Gets the entity bundle label or the entity label.
   *
   * @return string
   *   The bundle label.
   */
  protected function getBundleLabel() {
    $entity_type = $this->entity->getEntityTypeId();
    $bundle = $this->entity->bundle();

    // Use the the human readable name of the bundle type. If this entity has no
    // bundle, we use the name of the content entity type.
    if ($bundle != $entity_type) {
      $bundle_entity_type = $this->entityTypeManager
        ->getDefinition($entity_type)
        ->getBundleEntityType();
      $label = $this->entityTypeManager
        ->getStorage($bundle_entity_type)
        ->load($bundle)
        ->label();
    }
    else {
      $label = $this->entityTypeManager
        ->getDefinition($entity_type)
        ->getLabel();
    }

    return $label;
  }

  /**
   * Generates the label according to the settings.
   *
   * @param string $pattern
   *   Label pattern. May contain tokens.
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   Content entity.
   *
   * @return string
   *   A label string
   */
  protected function generateLabel($pattern, $entity) {
    $entity_type = $entity->getEntityType()->id();
    $output = $this->token
      ->replace($pattern, array($entity_type => $entity), array(
        'sanitize' => FALSE,
        'clear' => TRUE
      ));

    // Evaluate PHP.
    if ($this->getConfig('php')) {
      $output = $this->evalLabel($output, $this->entity);
    }
    // Strip tags.
    $output = preg_replace('/[\t\n\r\0\x0B]/', '', strip_tags($output));

    return $output;
  }

  /**
   * Returns automatic label configuration of the content entity bundle.
   *
   * @param string $value
   *   The configuration value to get.
   *
   * @return \Drupal\Core\Config\ImmutableConfig
   */
  protected function getConfig($value) {
    if (!isset($this->config)) {
      $this->config = $this->configFactory->get('auto_entitylabel.settings');
    }
    $key = $this->bundle_entity_type . '_' . $this->entity_bundle;
    return $this->config->get($key . '_' . $value);
  }

  /**
   * Gets an alternative entity label.
   *
   * @return string
   *   Translated label string.
   */
  protected function getAlternativeLabel() {
    $content_type = $this->getBundleLabel();

    if ($this->entity->id()) {
      $label = $this->t('@type @id', array(
        '@type' => $content_type,
        '@id' => $this->entity->id(),
      ));
    }
    else {
      $label = $content_type;
    }

    return $label;
  }

  /**
   * Evaluates php code and passes the entity to it.
   *
   * @param $code
   *   PHP code to evaluate.
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   Content entity to pa ss through to the PHP script.
   *
   * @return string
   *   String to use as label.
   */
  protected function evalLabel($code, $entity) {
    ob_start();
    print eval('?>' . $code);
    $output = ob_get_contents();
    ob_end_clean();

    return $output;
  }

  /**
   * Constructs the list of options for the given bundle.
   */
  public static function auto_entitylabel_options($entity_type, $bundle_name) {
    $options = array(
      'auto_entitylabel_disabled' => t('Disabled'),
    );
    if (self::auto_entitylabel_entity_label_visible($entity_type)) {
      $options += array(
        'auto_entitylabel_enabled' => t('Automatically generate the label and hide the label field'),
        'auto_entitylabel_optional' => t('Automatically generate the label if the label field is left empty'),
      );
    }
    else {
      $options += array(
        'auto_entitylabel_enabled' => t('Automatically generate the label'),
      );
    }
    return $options;
  }

  /**
   * Check if given entity bundle has a visible label on the entity form.
   *
   * @param $entity_type
   *   The entity type.
   * @param $bundle_name
   *   The name of the bundle.
   *
   * @return
   *   TRUE if the label is rendered in the entity form, FALSE otherwise.
   *
   * @todo
   *   Find a generic way of determining the result of this function. This
   *   will probably require access to more information about entity forms
   *   (entity api module?).
   */
  public static function auto_entitylabel_entity_label_visible($entity_type) {
    $hidden = array(
      'profile2' => TRUE,
    );

    return empty($hidden[$entity_type]);
  }
}