<?php

namespace Drupal\webform;

use Drupal\Core\Serialization\Yaml;
use Drupal\Core\Render\Element;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Form\FormState;
use Drupal\Core\Url;
use Drupal\webform\Utility\WebformArrayHelper;
use Drupal\webform\Utility\WebformElementHelper;

/**
 * Defines a class to validate webform elements.
 */
class WebformEntityElementsValidator {

  use StringTranslationTrait;

  /**
   * The webform being validated.
   *
   * @var \Drupal\webform\WebformInterface
   */
  protected $webform;

  /**
   * The raw elements value.
   *
   * @var string
   */
  protected $elementsRaw;

  /**
   * The raw original elements value.
   *
   * @var string
   */
  protected $originalElementsRaw;

  /**
   * The parsed elements array.
   *
   * @var array
   */
  protected $elements;

  /**
   * The parsed original elements array.
   *
   * @var array
   */
  protected $originalElements;

  /**
   * Validate webform elements.
   *
   * @param \Drupal\webform\WebformInterface $webform
   *   A webform.
   *
   * @return array|null
   *   An array of error messages or NULL is the elements are valid.
   */
  public function validate(WebformInterface $webform) {
    $this->webform = $webform;

    $this->elementsRaw = $webform->getElementsRaw();
    $this->originalElementsRaw = $webform->getElementsOriginalRaw();

    // Validate required.
    if ($message = $this->validateRequired()) {
      return [$message];
    }
    // Validate contain valid YAML.
    if ($message = $this->validateYaml()) {
      return [$message];
    }

    $this->elements = Yaml::decode($this->elementsRaw);
    $this->originalElements = Yaml::decode($this->originalElementsRaw);

    // Validate elements are an array.
    if ($message = $this->validateArray()) {
      return [$message];
    }

    // Validate duplicate element name.
    if ($messages = $this->validateDuplicateNames()) {
      return $messages;
    }

    // Validate ignored properties.
    if ($messages = $this->validateProperties()) {
      return $messages;
    }

    // Validate submission data.
    if ($messages = $this->validateSubmissions()) {
      return $messages;
    }

    // Validate hierarchy.
    if ($messages = $this->validateHierarchy()) {
      return $messages;
    }

    // Validate rendering.
    if ($message = $this->validateRendering()) {
      return [$message];
    }

    return NULL;
  }

  /**
   * Validate elements are required.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup|null
   *   If not valid an error message.
   */
  protected function validateRequired() {
    return (empty($this->elementsRaw)) ? $this->t('Elements are required') : NULL;
  }

  /**
   * Validate elements is validate YAML.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup|null
   *   If not valid an error message.
   */
  protected function validateYaml() {
    try {
      Yaml::decode($this->elementsRaw);
      return NULL;
    }
    catch (\Exception $exception) {
      return $this->t('Elements are not valid. @message', ['@message' => $exception->getMessage()]);
    }
  }

  /**
   * Validate elements are an array of elements.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup|null
   *   If not valid an error message.
   */
  protected function validateArray() {
    if (!is_array($this->elements)) {
      return $this->t('Elements are not valid. YAML must contain an associative array of elements.');
    }
    return NULL;
  }

  /**
   * Validate elements does not contain duplicate names.
   *
   * @return array|null
   *   If not valid, an array of error messages.
   */
  protected function validateDuplicateNames() {
    $duplicate_names = [];
    $this->getDuplicateNamesRecursive($this->elements, $duplicate_names);
    if ($duplicate_names = array_filter($duplicate_names)) {
      $messages = [];
      foreach ($duplicate_names as $duplicate_name => $duplicate_count) {
        $line_numbers = $this->getLineNumbers('/^\s*(["\']?)' . preg_quote($duplicate_name, '/') . '\1\s*:/');
        $t_args = [
          '%name' => $duplicate_name,
          '@lines' => $this->formatPlural(count($line_numbers), $this->t('line'), $this->t('lines')),
          '@line_numbers' => WebformArrayHelper::toString($line_numbers),
        ];
        $messages[] = $this->t('Elements contain a duplicate element name %name found on @lines @line_numbers.', $t_args);
      }
      return $messages;
    }
    return NULL;
  }

  /**
   * Recurse through elements and collect an associative array keyed by name and number of duplicate instances.
   *
   * @param array $elements
   *   An array of elements.
   * @param array $names
   *   An associative array keyed by name and number of duplicate instances.
   */
  protected function getDuplicateNamesRecursive(array $elements, array &$names) {
    foreach ($elements as $key => &$element) {
      if (Element::property($key) || !is_array($element)) {
        continue;
      }
      if (isset($element['#type'])) {
        if (!isset($names[$key])) {
          $names[$key] = 0;
        }
        else {
          ++$names[$key];
        }
      }
      $this->getDuplicateNamesRecursive($element, $names);
    }
  }

  /**
   * Validate that elements are not using ignored properties.
   *
   * @return array|null
   *   If not valid, an array of error messages.
   */
  protected function validateProperties() {
    $ignored_properties = WebformElementHelper::getIgnoredProperties($this->elements);
    if ($ignored_properties) {
      $messages = [];
      foreach ($ignored_properties as $ignored_property) {
        $line_numbers = $this->getLineNumbers('/^\s*(["\']?)' . preg_quote($ignored_property, '/') . '\1\s*:/');
        $t_args = [
          '%property' => $ignored_property,
          '@lines' => $this->formatPlural(count($line_numbers), $this->t('line'), $this->t('lines')),
          '@line_numbers' => WebformArrayHelper::toString($line_numbers),
        ];
        $messages[] = $this->t('Elements contain an unsupported %property property found on @lines @line_numbers.', $t_args);
      }
      return $messages;
    }
    return NULL;
  }

  /**
   * Validate that element are not deleted when the webform has submissions.
   *
   * @return array|null
   *   If not valid, an array of error messages.
   */
  protected function validateSubmissions() {
    if (!$this->webform->hasSubmissions()) {
      return NULL;
    }

    $element_keys = [];
    if ($this->elements) {
      $this->getElementKeysRecursive($this->elements, $element_keys);
    }
    $original_element_keys = [];
    if ($this->originalElements) {
      $this->getElementKeysRecursive($this->originalElements, $original_element_keys);
    }
    if ($missing_element_keys = array_diff_key($original_element_keys, $element_keys)) {
      $messages = [];
      foreach ($missing_element_keys as $missing_element_key) {
        // Display an error message with 3 possible approaches to safely
        // deleting or hiding an element.
        $items = [];
        $items[] = $this->t('<a href=":href">Delete all submissions</a> to this webform.', [':href' => $this->webform->toUrl('results-clear')->toString()]);
        if (\Drupal::moduleHandler()->moduleExists('webform_ui')) {
          $items[] = $this->t('<a href=":href">Delete this individual element</a> using the webform UI.', [':href' => Url::fromRoute('entity.webform_ui.element.delete_form', ['webform' => $this->webform->id(), 'key' => $missing_element_key])->toString()]);
        }
        else {
          $items[] = $this->t('<a href=":href">Enable the Webform UI module</a> and safely delete this element.', [':href' => Url::fromRoute('system.modules_list')->toString()]);
        }
        $items[] = $this->t("Hide this element by setting its <code>'#access'</code> property to <code>false</code>.");
        $build = [
          'message' => [
            '#markup' => $this->t('The %key element can not be removed because the %title webform has <a href=":href">results</a>.', ['%title' => $this->webform->label(), '%key' => $missing_element_key, ':href' => $this->webform->toUrl('results-submissions')->toString()]),
          ],
          'items' => [
            '#theme' => 'item_list',
            '#items' => $items,
          ],
        ];
        $messages[] = \Drupal::service('renderer')->renderPlain($build);
      }
      return $messages;
    }

    return NULL;
  }

  /**
   * Recurse through elements and collect an associative array of deleted element names.
   *
   * @param array $elements
   *   An array of elements.
   * @param array $names
   *   An array tracking deleted element names.
   */
  protected function getElementKeysRecursive(array $elements, array &$names) {
    foreach ($elements as $key => &$element) {
      if (Element::property($key) || !is_array($element)) {
        continue;
      }
      if (isset($element['#type'])) {
        $names[$key] = $key;
      }
      $this->getElementKeysRecursive($element, $names);
    }
  }

  /**
   * Validate element hierarchy.
   *
   * @return array|null
   *   If not valid, an array of error messages.
   */
  protected function validateHierarchy() {
    $elements = $this->webform->getElementsInitializedAndFlattened();
    $messages = [];
    foreach ($elements as $key => $element) {
      /** @var \Drupal\webform\WebformElementManagerInterface $element_manager */
      $element_manager = \Drupal::service('plugin.manager.webform.element');
      $plugin_id = $element_manager->getElementPluginId($element);
      /** @var \Drupal\webform\WebformElementInterface $webform_element */
      $webform_element = $element_manager->createInstance($plugin_id, $element);

      $t_args = [
        '%title' => (!empty($element['#title'])) ? $element['#title'] : $key,
        '@type' => $webform_element->getTypeName(),
      ];
      if ($webform_element->isRoot() && !empty($element['#webform_parent_key'])) {
        $messages[] = $this->t('The %title (@type) is a root element that can not be used as child to another element', $t_args);
      }
      elseif (!$webform_element->isContainer($element) && !empty($element['#webform_children'])) {
        $messages[] = $this->t('The %title (@type) is a webform element that can not have any child elements.', $t_args);
      }
    }
    return $messages;
  }

  /**
   * Validate that elements are a valid render array.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup|string|null
   *   If not valid an error message.
   *
   * @see \Drupal\Core\Entity\EntityFormBuilder
   * @see \Drupal\webform\Entity\Webform::getSubmissionForm()
   */
  protected function validateRendering() {
    set_error_handler('_webform_entity_element_validate_rendering_error_handler');

    try {
      /** @var \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager */
      $entity_type_manager = \Drupal::service('entity_type.manager');
      /** @var \Drupal\Core\Form\FormBuilderInterface $form_builder */
      $form_builder = \Drupal::service('form_builder');

      /** @var \Drupal\webform\WebformSubmissionInterface $webform_submission */
      $webform_submission = $entity_type_manager
        ->getStorage('webform_submission')
        ->create(['webform' => $this->webform]);

      $form_object = $entity_type_manager->getFormObject('webform_submission', 'default');
      $form_object->setEntity($webform_submission);
      $form_state = (new FormState())->setFormState([]);
      $form_builder->buildForm($form_object, $form_state);
      $message = NULL;
    }
    catch (\Exception $exception) {
      $message = $exception->getMessage();
    }

    set_error_handler('_drupal_error_handler');

    if ($message) {
      $build = [
        'title' => [
          '#markup' => $this->t('Unable to render elements, please view the below message(s) and the error log.'),
        ],
        'items' => [
          '#theme' => 'item_list',
          '#items' => [$message],
        ],
      ];
      return \Drupal::service('renderer')->renderPlain($build);
    }

    return $message;
  }

  /**
   * Get the line numbers for given pattern in the webform's elements string.
   *
   * @param string $pattern
   *   A regular expression.
   *
   * @return array
   *   An array of line numbers.
   */
  protected function getLineNumbers($pattern) {
    $lines = explode(PHP_EOL, $this->elementsRaw);
    $line_numbers = [];
    foreach ($lines as $index => $line) {
      if (preg_match($pattern, $line)) {
        $line_numbers[] = ($index + 1);
      }
    }
    return $line_numbers;
  }

}
