<?php

namespace Drupal\metatag\Plugin\metatag\Tag;

use Drupal\Component\Plugin\PluginBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Each meta tag will extend this base.
 */
abstract class MetaNameBase extends PluginBase {

  use StringTranslationTrait;

  /**
   * Machine name of the meta tag plugin.
   *
   * @var string
   */
  protected $id;

  /**
   * Official metatag name.
   *
   * @var string
   */
  protected $name;

  /**
   * The title of the plugin.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  protected $label;

  /**
   * A longer explanation of what the field is for.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  protected $description;

  /**
   * The category this meta tag fits in.
   *
   * @var string
   */
  protected $group;

  /**
   * Type of the value being stored.
   *
   * @var string
   */
  protected $type;

  /**
   * True if URL must use HTTPS.
   *
   * @var bool
   */
  protected $secure;

  /**
   * True if more than one is allowed.
   *
   * @var bool
   */
  protected $multiple;

  /**
   * True if the tag should use a text area.
   *
   * @var bool
   */
  protected $long;

  /**
   * True if the URL value(s) must be absolute.
   *
   * @var bool
   */
  protected $absoluteUrl;

  /**
   * Retrieves the currently active request object.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected $request;

  /**
   * The value of the metatag in this instance.
   *
   * @var mixed
   */
  protected $value;

  /**
   * The attribute this tag uses for the name.
   *
   * @var string
   */
  protected $nameAttribute = 'name';

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, array $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    // Set the properties from the annotation.
    // @todo Should we have setProperty() methods for each of these?
    $this->id = $plugin_definition['id'];
    $this->name = $plugin_definition['name'];
    $this->label = $plugin_definition['label'];
    $this->description = $plugin_definition['description'];
    $this->group = $plugin_definition['group'];
    $this->weight = $plugin_definition['weight'];
    $this->type = $plugin_definition['type'];
    $this->secure = $plugin_definition['secure'];
    $this->multiple = $plugin_definition['multiple'];
    $this->long = !empty($plugin_definition['long']);
    $this->absoluteUrl = !empty($plugin_definition['absolute_url']);
    $this->request = \Drupal::request();
  }

  /**
   * Obtain the meta tag's internal ID.
   *
   * @return string
   *   This meta tag's internal ID.
   */
  public function id() {
    return $this->id;
  }

  /**
   * This meta tag's label.
   *
   * @return string
   *   The label.
   */
  public function label() {
    return $this->label;
  }

  /**
   * The meta tag's description.
   *
   * @return bool
   *   This meta tag's description.
   */
  public function description() {
    return $this->description;
  }

  /**
   * The meta tag's machine name.
   *
   * @return string
   *   This meta tag's machine name.
   */
  public function name() {
    return $this->name;
  }

  /**
   * The meta tag group this meta tag belongs to.
   *
   * @return string
   *   The meta tag's group name.
   */
  public function group() {
    return $this->group;
  }

  /**
   * This meta tag's form field's weight.
   *
   * @return int|float
   *   The form API weight for this. May be any number supported by Form API.
   */
  public function weight() {
    return $this->weight;
  }

  /**
   * Obtain this meta tag's type.
   *
   * @return string
   *   This meta tag's type.
   */
  public function type() {
    return $this->type;
  }

  /**
   * Whether or not this meta tag must output secure (HTTPS) URLs.
   *
   * @return bool
   *   Whether or not this meta tag must output secure (HTTPS) URLs.
   */
  public function secure() {
    return $this->secure;
  }

  /**
   * Whether or not this meta tag supports multiple values.
   *
   * @return bool
   *   Whether or not this meta tag supports multiple values.
   */
  public function multiple() {
    return $this->multiple;
  }

  /**
   * Whether or not this meta tag should use a text area.
   *
   * @return bool
   *   Whether or not this meta tag should use a text area.
   */
  public function isLong() {
    return $this->long;
  }

  /**
   * Whether or not this meta tag must output required absolute URLs.
   *
   * @return bool
   *   Whether or not this meta tag must output required absolute URLs.
   */
  public function requiresAbsoluteUrl() {
    return $this->absoluteUrl;
  }

  /**
   * Whether or not this meta tag is active.
   *
   * @return bool
   *   Whether this meta tag has been enabled.
   */
  public function isActive() {
    return TRUE;
  }

  /**
   * Generate a form element for this meta tag.
   *
   * @param array $element
   *   The existing form element to attach to.
   *
   * @return array
   *   The completed form element.
   */
  public function form(array $element = []) {
    $form = [
      '#type' => $this->isLong() ? 'textarea' : 'textfield',
      '#title' => $this->label(),
      '#default_value' => $this->value(),
      '#maxlength' => 255,
      '#required' => isset($element['#required']) ? $element['#required'] : FALSE,
      '#description' => $this->description(),
      '#element_validate' => [[get_class($this), 'validateTag']],
    ];

    // Optional handling for items that allow multiple values.
    if (!empty($this->multiple)) {
      $form['#description'] .= ' ' . $this->t('Multiple values may be used, separated by a comma. Note: Tokens that return multiple values will be handled automatically.');
    }

    // Optional handling for images.
    if ((!empty($this->type())) && ($this->type() === 'image')) {
      $form['#description'] .= ' ' . $this->t('This will be able to extract the URL from an image field.');
    }

    if (!empty($this->absolute_url)) {
      $form['#description'] .= ' ' . $this->t('Any relative or protocol-relative URLs will be converted to absolute URLs.');
    }

    // Optional handling for secure paths.
    if (!empty($this->secure)) {
      $form['#description'] .= ' ' . $this->t('Any URLs which start with "http://" will be converted to "https://".');
    }

    return $form;
  }

  /**
   * Obtain the current meta tag's raw value.
   *
   * @return string
   *   The current raw meta tag value.
   */
  public function value() {
    return $this->value;
  }

  /**
   * Assign the current meta tag a value.
   *
   * @param string $value
   *   The value to assign this meta tag.
   */
  public function setValue($value) {
    $this->value = $value;
  }

  /**
   * Make the string presentable.
   *
   * @param string $value
   *   The raw string to process.
   *
   * @return string
   *   The meta tag value after processing.
   */
  private function tidy($value) {
    return trim($value);
  }

  /**
   * Generate the HTML tag output for a meta tag.
   *
   * @return array|string
   *   A render array or an empty string.
   */
  public function output() {
    if (empty($this->value)) {
      // If there is no value, we don't want a tag output.
      return $this->multiple() ? [] : '';
    }

    // Parse out the image URL, if needed.
    $value = $this->parseImageUrl();
    $values = $this->multiple() ? explode(',', $value) : [$value];
    $elements = [];
    foreach ($values as $value) {
      $value = $this->tidy($value);
      if ($this->requiresAbsoluteUrl()) {
        // Relative URL.
        if (parse_url($value, PHP_URL_HOST) == NULL) {
          $value = $this->request->getSchemeAndHttpHost() . $value;
        }
        // Protocol-relative URL.
        elseif (substr($value, 0, 2) === '//') {
          $value = $this->request->getScheme() . ':' . $value;
        }
      }

      // If tag must be secure, convert all http:// to https://.
      if ($this->secure() && strpos($value, 'http://') !== FALSE) {
        $value = str_replace('http://', 'https://', $value);
      }

      $elements[] = [
        '#tag' => 'meta',
        '#attributes' => [
          $this->nameAttribute => $this->name,
          'content' => $value,
        ],
      ];
    }

    return $this->multiple() ? $elements : reset($elements);
  }

  /**
   * Validates the metatag data.
   *
   * @param array $element
   *   The form element to process.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  public static function validateTag(array &$element, FormStateInterface $form_state) {
    // @todo If there is some common validation, put it here. Otherwise, make
    // it abstract?
  }

  /**
   * Extract any image URLs that might be found in a meta tag.
   *
   * @return string
   *   A comma separated list of any image URLs found in the meta tag's value,
   *   or the original string if no images were identified.
   */
  protected function parseImageUrl() {
    $value = $this->value();

    // If this contains embedded image tags, extract the image URLs.
    if ($this->type() === 'image') {
      // If image tag src is relative (starts with /), convert to an absolute
      // link; ignore protocol-relative URLs.
      global $base_root;
      if (strpos($value, '<img src="/') !== FALSE && strpos($value, '<img src="//') === FALSE) {
        $value = str_replace('<img src="/', '<img src="' . $base_root . '/', $value);
      }

      if ($this->multiple()) {
        // Split the string into an array, remove empty items.
        $values = array_filter(explode(',', $value));
      }
      else {
        $values = [$value];
      }

      // Check through the value(s) to see if there are any image tags.
      foreach ($values as $key => $val) {
        $matches = [];
        preg_match('/src="([^"]*)"/', $val, $matches);
        if (!empty($matches[1])) {
          $values[$key] = $matches[1];
        }
      }
      $value = implode(',', $values);

      // Remove any HTML tags that might remain.
      $value = strip_tags($value);
    }

    return $value;
  }

}
