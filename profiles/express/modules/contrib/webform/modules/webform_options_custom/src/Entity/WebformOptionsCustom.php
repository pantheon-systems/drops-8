<?php

namespace Drupal\webform_options_custom\Entity;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\Core\Render\Markup;
use Drupal\Core\Serialization\Yaml;
use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\webform_options_custom\Element\WebformOptionsCustom as WebformOptionsCustomElement;
use Drupal\webform_options_custom\WebformOptionsCustomInterface;

/**
 * Defines the webform options custom entity.
 *
 * @ConfigEntityType(
 *   id = "webform_options_custom",
 *   label = @Translation("Webform options custom"),
 *   label_collection = @Translation("Custom options"),
 *   label_singular = @Translation("custom options"),
 *   label_plural = @Translation("custom options"),
 *   label_count = @PluralTranslation(
 *     singular = "@count custom options",
 *     plural = "@count custom options",
 *   ),
 *   handlers = {
 *     "storage" = "\Drupal\webform_options_custom\WebformOptionsCustomStorage",
 *     "access" = "Drupal\webform_options_custom\WebformOptionsCustomAccessControlHandler",
 *     "list_builder" = "Drupal\webform_options_custom\WebformOptionsCustomListBuilder",
 *     "form" = {
 *       "add" = "Drupal\webform_options_custom\WebformOptionsCustomForm",
 *       "edit" = "Drupal\webform_options_custom\WebformOptionsCustomForm",
 *       "source" = "Drupal\webform_options_custom\WebformOptionsCustomForm",
 *       "preview" = "Drupal\webform_options_custom\WebformOptionsCustomForm",
 *       "duplicate" = "Drupal\webform_options_custom\WebformOptionsCustomForm",
 *       "delete" = "Drupal\webform_options_custom\WebformOptionsCustomDeleteForm",
 *     }
 *   },
 *   admin_permission = "administer webform",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *   },
 *   links = {
 *     "add-form" = "/admin/structure/webform/config/options_custom/manage/add",
 *     "edit-form" = "/admin/structure/webform/config/options_custom/manage/{webform_options_custom}/edit",
 *     "source-form" = "/admin/structure/webform/config/options_custom/manage/{webform_options_custom}/source",
 *     "duplicate-form" = "/admin/structure/webform/config/options_custom/manage/{webform_options_custom}/duplicate",
 *     "delete-form" = "/admin/structure/webform/config/options_custom/manage/{webform_options_custom}/delete",
 *     "collection" = "/admin/structure/webform/config/options_custom/manage",
 *   },
 *   config_export = {
 *     "id",
 *     "uuid",
 *     "label",
 *     "description",
 *     "help",
 *     "category",
 *     "type",
 *     "template",
 *     "url",
 *     "css",
 *     "javascript",
 *     "options",
 *     "value_attributes",
 *     "text_attributes",
 *     "fill",
 *     "zoom",
 *     "tooltip",
 *     "show_select",
 *     "element",
 *     "entity_reference",
 *   }
 * )
 */
class WebformOptionsCustom extends ConfigEntityBase implements WebformOptionsCustomInterface {

  use StringTranslationTrait;

  /**
   * The custom options ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The custom options UUID.
   *
   * @var string
   */
  protected $uuid;

  /**
   * The custom options label.
   *
   * @var string
   */
  protected $label;

  /**
   * The custom options description.
   *
   * @var string
   */
  protected $description;

  /**
   * The custom options help.
   *
   * @var string
   */
  protected $help;

  /**
   * The custom options category.
   *
   * @var string
   */
  protected $category;

  /**
   * The type of custom options.
   *
   * @var string
   */
  protected $type = WebformOptionsCustomInterface::TYPE_URL;

  /**
   * The custom HTML/SVG markup.
   *
   * @var string
   */
  protected $template;

  /**
   * The custom HTML/SVG URL.
   *
   * @var string
   */
  protected $url;

  /**
   * The option value attribute names.
   *
   * @var string
   */
  protected $value_attributes = 'data-option-value,data-value,data-id,id';

  /**
   * The option text attribute names.
   *
   * @var string
   */
  protected $text_attributes = 'data-option-text,data-text,data-name,name,title';

  /**
   * Allow SVG to be filled using CSS.
   *
   * @var bool
   */
  protected $fill = TRUE;

  /**
   * Enable SVG pan and zoom.
   *
   * @var bool
   */
  protected $zoom = TRUE;

  /**
   * Display text and description in a tooltip.
   *
   * @var bool
   */
  protected $tooltip = TRUE;

  /**
   * Hide select menu.
   *
   * @var bool
   */
  protected $show_select = TRUE;

  /**
   * Use custom options as a webform select element.
   *
   * @var bool
   */
  protected $element = TRUE;

  /**
   * Use custom options as a webform entity reference element.
   *
   * @var bool
   */
  protected $entity_reference = FALSE;

  /**
   * The CSS style sheet.
   *
   * @var string
   */
  protected $css = '';

  /**
   * The JavaScript.
   *
   * @var string
   */
  protected $javascript = '';

  /**
   * The custom options.
   *
   * @var string
   */
  protected $options;

  /**
   * The custom options decoded.
   *
   * @var string
   */
  protected $optionsDecoded;

  /**
   * {@inheritdoc}
   */
  public function getOptions() {
    if (!isset($this->optionsDecoded)) {
      try {
        $options = $this->options ? Yaml::decode($this->options) : [];
        // Since YAML supports simple values.
        $options = (is_array($options)) ? $options : [];
      }
      catch (\Exception $exception) {
        $link = $this->toLink($this->t('Edit'), 'edit-form')->toString();
        \Drupal::logger('webform_options_custom')->notice('%title custom options are not valid. @message', ['%title' => $this->label(), '@message' => $exception->getMessage(), 'link' => $link]);
        $options = [];
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
  }

  /**
   * Set a custom options element HTML/SVG template.
   *
   * @return string
   *   A custom options element HTML/SVG template.
   */
  public function getTemplate() {
    switch ($this->type) {
      case static::TYPE_URL:
        $url = $this->getUrl();
        return ($url) ? file_get_contents($url) : '';

      default:
      case static::TYPE_TEMPLATE:
        return $this->template;
    }
  }

  /**
   * Set a custom options element template URL.
   *
   * @return string
   *   A custom options element template URL.
   */
  public function getUrl() {
    global $base_url;

    $url = $this->url;
    if (empty($url)) {
      return NULL;
    }

    if (strpos($url, '/') === 0) {
      // Map root-relative path.
      $url = $base_url . preg_replace('/^' . preg_quote(base_path(), '/') . '/', '/', $url);
    }
    elseif (strpos($url, 'http') !== 0) {
      // Map webform_option_custom/images path.
      $path = drupal_get_path('module', 'webform_options_custom') . '/images/' . $url;
      if (file_exists($path)) {
        $url = $base_url . '/' . $path;
      }
    }

    if (strpos($url, 'http') === 0) {
      return $url;
    }

    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getElement() {
    return [
      '#type' => 'webform_options_custom',
      '#options_custom' => $this->id(),
      '#template' => $this->getTemplate(),
      '#options' => $this->getOptions(),
      '#fill' => $this->get('fill'),
      '#zoom' => $this->get('zoom'),
      '#tooltip' => $this->get('tooltip'),
      '#show_select' => $this->get('show_select'),
      '#value_attributes' => $this->get('value_attributes'),
      '#text_attributes' => $this->get('text_attributes'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getPreview() {
    $element = [
      '#title' => $this->label(),
    ] + $this->getElement();

    // Set default #options.
    if (empty($element['#options'])) {
      $element['#options'] = [
        'one' => t('One -- This is the number 1.'),
        'two' => t('Two -- This is the number 2.'),
        'three' => t('Three -- This is the number 3.'),
      ];
    }

    // Set assets (CSS and JavaScript).
    $assets = '';
    if ($this->css) {
      $assets .= '<style>' . $this->css . '</style>';
    }
    if ($this->javascript) {
      $assets .= '<script>' . $this->javascript . '</script>';
    }
    if ($assets) {
      $element['#prefix'] = Markup::create($assets);
    }

    return $element;
  }

  /**
   * Get template custom options.
   *
   * @return array
   *   A templates custom options.
   */
  public function getTemplateOptions() {
    $element = $this->getElement();
    WebformOptionsCustomElement::setTemplateOptions($element);
    return $element['#options'];
  }

  /**
   * {@inheritdoc}
   */
  public function postSave(EntityStorageInterface $storage, $update = TRUE) {
    parent::postSave($storage, $update);

    /** @var \Drupal\webform\Plugin\WebformElementManagerInterface $element_manager */
    $element_manager = \Drupal::service('plugin.manager.webform.element');
    $element_manager->clearCachedDefinitions();

    // Clear cached properties.
    $this->optionsDecoded = NULL;

    // Invalidate library_info cache tag if any element
    // declares CSS or JavaScript.
    // @see webform_library_info_build()
    if ($this->css || $this->javascript) {
      Cache::invalidateTags(['library_info']);
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function sort(ConfigEntityInterface $a, ConfigEntityInterface $b) {
    $a_label = $a->get('category') . $a->label();
    $b_label = $b->get('category') . $b->label();
    return strnatcasecmp($a_label, $b_label);
  }

}
