<?php

namespace Drupal\webform\Entity;

use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\Core\Serialization\Yaml;
use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\webform\Utility\WebformOptionsHelper;
use Drupal\webform\WebformOptionsInterface;

/**
 * Defines the webform options entity.
 *
 * @ConfigEntityType(
 *   id = "webform_options",
 *   label = @Translation("Webform options"),
 *   label_collection = @Translation("Webform options"),
 *   label_singular = @Translation("webform options"),
 *   label_plural = @Translation("webform options"),
 *   label_count = @PluralTranslation(
 *     singular = "@count webform options",
 *     plural = "@count webform options",
 *   ),
 *   handlers = {
 *     "storage" = "\Drupal\webform\WebformOptionsStorage",
 *     "access" = "Drupal\webform\WebformOptionsAccessControlHandler",
 *     "list_builder" = "Drupal\webform\WebformOptionsListBuilder",
 *     "form" = {
 *       "add" = "Drupal\webform\WebformOptionsForm",
 *       "edit" = "Drupal\webform\WebformOptionsForm",
 *       "duplicate" = "Drupal\webform\WebformOptionsForm",
 *       "delete" = "Drupal\webform\WebformOptionsDeleteForm",
 *     }
 *   },
 *   admin_permission = "administer webform",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *   },
 *   links = {
 *     "add-form" = "/admin/structure/webform/config/options/manage/add",
 *     "edit-form" = "/admin/structure/webform/config/options/manage/{webform_options}/edit",
 *     "duplicate-form" = "/admin/structure/webform/config/options/manage/{webform_options}/duplicate",
 *     "delete-form" = "/admin/structure/webform/config/options/manage/{webform_options}/delete",
 *     "collection" = "/admin/structure/webform/config/options/manage",
 *   },
 *   config_export = {
 *     "id",
 *     "uuid",
 *     "label",
 *     "category",
 *     "likert",
 *     "options",
 *   }
 * )
 */
class WebformOptions extends ConfigEntityBase implements WebformOptionsInterface {

  use StringTranslationTrait;

  /**
   * The webform options ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The webform options UUID.
   *
   * @var string
   */
  protected $uuid;

  /**
   * The webform options label.
   *
   * @var string
   */
  protected $label;

  /**
   * The webform options category.
   *
   * @var string
   */
  protected $category;

  /**
   * Flag to used options as likert answers.
   *
   * @var bool
   */
  protected $likert = FALSE;

  /**
   * The webform options options.
   *
   * @var string
   */
  protected $options;

  /**
   * The webform options decoded.
   *
   * @var string
   */
  protected $optionsDecoded;

  /**
   * {@inheritdoc}
   */
  public function isLikert() {
    return $this->likert;
  }

  /**
   * {@inheritdoc}
   */
  public function set($property_name, $value) {
    // Make sure to reset decoded options when options are updated.
    if ($property_name === 'options') {
      $this->optionsDecoded = NULL;
    }
    return parent::set($property_name, $value);
  }

  /**
   * {@inheritdoc}
   */
  public function getOptions() {
    if (!isset($this->optionsDecoded)) {
      try {
        $options = Yaml::decode($this->options);
        // Since YAML supports simple values.
        $options = (is_array($options)) ? $options : [];
      }
      catch (\Exception $exception) {
        $link = $this->toLink($this->t('Edit'), 'edit-form')->toString();
        \Drupal::logger('webform')->notice('%title options are not valid. @message', ['%title' => $this->label(), '@message' => $exception->getMessage(), 'link' => $link]);
        $options = FALSE;
      }
      $this->optionsDecoded = $options;
    }
    return $this->optionsDecoded;
  }

  /**
   * {@inheritdoc}
   */
  public function setOptions(array $options) {
    $this->options = Yaml::encode($options);
    $this->optionsDecoded = NULL;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function hasAlterHooks() {
    $hook_name = 'webform_options_' . $this->id() . '_alter';
    $alter_hooks = \Drupal::moduleHandler()->getImplementations($hook_name);
    return (count($alter_hooks)) ? TRUE : FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function preSave(EntityStorageInterface $storage) {
    parent::preSave($storage);

    // If the submitted options match the altered options clear the submission
    // options.
    $altered_options = [];
    $temp_element = [];
    \Drupal::moduleHandler()->alter('webform_options_' . $this->id(), $altered_options, $temp_element);
    $altered_options = WebformOptionsHelper::convertOptionsToString($altered_options);
    if ($altered_options === $this->getOptions()) {
      $this->options = '';
    }
  }

  /**
   * {@inheritdoc}
   */
  public function postSave(EntityStorageInterface $storage, $update = TRUE) {
    parent::postSave($storage, $update);

    // Clear cached properties.
    $this->optionsDecoded = NULL;
  }

  /**
   * {@inheritdoc}
   */
  public static function sort(ConfigEntityInterface $a, ConfigEntityInterface $b) {
    $a_label = $a->get('category') . $a->label();
    $b_label = $b->get('category') . $b->label();
    return strnatcasecmp($a_label, $b_label);
  }

  /**
   * {@inheritdoc}
   */
  public static function getElementOptions(array &$element, $property_name = '#options') {
    // If element already has #options array just call alter hook with
    // a NULL id.
    if (is_array($element[$property_name])) {
      $options = $element[$property_name];
      \Drupal::moduleHandler()->alter('webform_options', $options, $element);
      return $options;
    }

    // Return empty options if element does not define an options id.
    if (empty($element[$property_name]) || !is_string($element[$property_name])) {
      return [];
    }

    // If options have been set return them.
    // This allows dynamic options to be overridden.
    $id = $element[$property_name];
    if ($webform_options = WebformOptions::load($id)) {
      $options = $webform_options->getOptions() ?: [];
    }
    else {
      $options = [];
    }

    // Alter options using hook_webform_options_alter()
    // and/or hook_webform_options_WEBFORM_OPTIONS_ID_alter() hook.
    // @see webform.api.php
    \Drupal::moduleHandler()->alter('webform_options_' . $id, $options, $element);
    \Drupal::moduleHandler()->alter('webform_options', $options, $element, $id);

    // Log empty options.
    if (empty($options)) {
      \Drupal::logger('webform')->notice('Options %id do not exist.', ['%id' => $id]);
    }

    return $options;
  }

}
