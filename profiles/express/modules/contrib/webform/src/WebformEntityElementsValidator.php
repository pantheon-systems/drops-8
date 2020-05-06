<?php

namespace Drupal\webform;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Serialization\Yaml;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Form\FormState;
use Drupal\Core\Url;
use Drupal\webform\Plugin\WebformElementManagerInterface;
use Drupal\webform\Utility\WebformArrayHelper;
use Drupal\webform\Utility\WebformElementHelper;

/**
 * Webform elements validator.
 */
class WebformEntityElementsValidator implements WebformEntityElementsValidatorInterface {

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
   * An array of element keys.
   *
   * @var array
   */
  protected $elementKeys;

  /**
   * The 'renderer' service.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * The 'plugin.manager.webform.element' service.
   *
   * @var \Drupal\webform\Plugin\WebformElementManagerInterface
   */
  protected $elementManager;

  /**
   * The 'entity_type.manager' service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The 'form_builder' service.
   *
   * @var \Drupal\Core\Form\FormBuilderInterface
   */
  protected $formBuilder;

  /**
   * The configuration object factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Element keys/names that are reserved.
   *
   * @var array
   */
  public static $reservedNames = [
    'add',
    'form_build_id',
    'form_id',
    'form_token',
    'op',
  ];

  /**
   * Constructs a WebformEntityElementsValidator object.
   *
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The 'renderer' service.
   * @param \Drupal\webform\Plugin\WebformElementManagerInterface $element_manager
   *   The 'plugin.manager.webform.element' service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The 'entity_type.manager' service.
   * @param \Drupal\Core\Form\FormBuilderInterface $form_builder
   *   The 'form_builder' service.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration object factory.
   *
   * @todo Webform 8.x-6.x: Move $config_factory before $renderer.
   */
  public function __construct(RendererInterface $renderer, WebformElementManagerInterface $element_manager, EntityTypeManagerInterface $entity_type_manager, FormBuilderInterface $form_builder, ConfigFactoryInterface $config_factory = NULL) {
    $this->renderer = $renderer;
    $this->elementManager = $element_manager;
    $this->entityTypeManager = $entity_type_manager;
    $this->formBuilder = $form_builder;
    $this->configFactory = $config_factory ?: \Drupal::configFactory();
  }

  /**
   * {@inheritdoc}
   */
  public function validate(WebformInterface $webform, array $options = []) {
    $options += [
      'required' => TRUE,
      'yaml' => TRUE,
      'array' => TRUE,
      'names' => TRUE,
      'properties' => TRUE,
      'submissions' => TRUE,
      'variants' => TRUE,
      'hierarchy' => TRUE,
      'rendering' => TRUE,
    ];

    $this->webform = $webform;

    $this->elementsRaw = $webform->getElementsRaw();
    $this->originalElementsRaw = $webform->getElementsOriginalRaw();

    // Validate required.
    if ($options['required'] && ($message = $this->validateRequired())) {
      return [$message];
    }

    // Validate contain valid YAML.
    if ($options['yaml'] && ($message = $this->validateYaml())) {
      return [$message];
    }

    $this->elements = Yaml::decode($this->elementsRaw);
    $this->originalElements = Yaml::decode($this->originalElementsRaw);

    $this->elementKeys = [];
    if (is_array($this->elements)) {
      $this->getElementKeysRecursive($this->elements, $this->elementKeys);
    }

    // Validate elements are an array.
    if ($options['array'] && ($message = $this->validateArray())) {
      return [$message];
    }

    // Validate duplicate element name.
    if ($options['names']) {
      if ($messages = $this->validateNames()) {
        return $messages;
      }
      elseif ($messages = $this->validateDuplicateNames()) {
        return $messages;
      }
    }

    // Validate ignored properties.
    if ($options['properties'] && ($messages = $this->validateProperties())) {
      return $messages;
    }

    // Validate submission data.
    if ($options['submissions'] && ($messages = $this->validateSubmissions())) {
      return $messages;
    }

    // Validate variants data.
    if ($options['variants'] && ($messages = $this->validateVariants())) {
      return $messages;
    }

    // Validate hierarchy.
    if ($options['hierarchy'] && ($messages = $this->validateHierarchy())) {
      return $messages;
    }

    // Validate rendering.
    if ($options['rendering'] && ($message = $this->validateRendering())) {
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
   * Validate elements names.
   *
   * @return array|null
   *   If not valid, an array of error messages.
   */
  protected function validateNames() {
    // @see \Drupal\webform_ui\Form\WebformUiElementFormBase::buildForm
    $machine_name_pattern = $this->configFactory->get('webform.settings')->get('element.machine_name_pattern') ?: 'a-z0-9_';
    switch ($machine_name_pattern) {
      case 'a-z0-9_':
        $machine_name_requirement = $this->t('lowercase letters, numbers, and underscores');
        break;

      case 'a-zA-Z0-9_':
        $machine_name_requirement = $this->t('letters, numbers, and underscores');
        break;

      case 'a-z0-9_-':
        $machine_name_requirement = $this->t('lowercase letters, numbers, underscores, and dashes');
        break;

      case 'a-zA-Z0-9_-':
        $machine_name_requirement = $this->t('letters, numbers, underscores, and dashes');
        break;
    }

    $messages = [];
    foreach ($this->elementKeys as $name) {
      if (!preg_match('/^[' . $machine_name_pattern . ']+$/', $name)) {
        $line_numbers = $this->getLineNumbers('/^\s*(["\']?)' . preg_quote($name, '/') . '\1\s*:/');
        $t_args = [
          '%name' => $name,
          '@line_number' => WebformArrayHelper::toString($line_numbers),
          '@requirement' => $machine_name_requirement,
        ];
        $messages[] = $this->t('The element key %name on line @line_number must contain only @requirement.', $t_args);
      }
      elseif (in_array($name, static::$reservedNames)) {
        $line_numbers = $this->getLineNumbers('/^\s*(["\']?)' . preg_quote($name, '/') . '\1\s*:/');
        $t_args = [
          '%name' => $name,
          '@line_number' => WebformArrayHelper::toString($line_numbers),
        ];
        $messages[] = $this->t('The element key %name on line @line_number is a reserved key.', $t_args);
      }
    }
    return $messages;
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
          '@line_numbers' => WebformArrayHelper::toString($line_numbers),
        ];
        $messages[] = $this->formatPlural(
          count($line_numbers),
          'Elements contain a duplicate element key %name found on line @line_numbers.',
          'Elements contain a duplicate element key %name found on lines @line_numbers.',
          $t_args
        );
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
      if (!WebformElementHelper::isElement($element, $key)) {
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
      foreach ($ignored_properties as $ignored_property => $ignored_message) {
        if ($ignored_property != $ignored_message) {
          $messages[] = $ignored_message;
        }
        else {
          $line_numbers = $this->getLineNumbers('/^\s*(["\']?)' . preg_quote($ignored_property, '/') . '\1\s*:/');
          $t_args = [
            '%property' => $ignored_property,
            '@line_number' => WebformArrayHelper::toString($line_numbers),
          ];
          $messages[] = $this->formatPlural(
            count($line_numbers),
            'Elements contain an unsupported %property property found on line @line_number.',
            'Elements contain an unsupported %property property found on lines @line_number.',
            $t_args
          );
        }
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
        $messages[] = $this->renderer->renderPlain($build);
      }
      return $messages;
    }

    return NULL;
  }

  /**
   * Validate that element are not deleted when the webform has related variants.
   *
   * @return array|null
   *   If not valid, an array of error messages.
   */
  protected function validateVariants() {
    if (!$this->webform->hasVariants()) {
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
        if ($this->webform->getVariants(NULL, NULL, $missing_element_key)->count()) {
          $t_args = [
            '%title' => $this->webform->label(),
            '%key' => $missing_element_key,
            ':href' => $this->webform->toUrl('variants')->toString(),
          ];
          $messages[] = $this->t('The %key element can not be removed because the %title webform has related <a href=":href">variants</a>.', $t_args);
        }
      }
      return $messages;
    }

    return NULL;
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
      $plugin_id = $this->elementManager->getElementPluginId($element);
      /** @var \Drupal\webform\Plugin\WebformElementInterface $webform_element */
      $webform_element = $this->elementManager->createInstance($plugin_id, $element);

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
      elseif ($plugin_id === 'webform_table_row') {
        $parent_element = ($element['#webform_parent_key']) ? $elements[$element['#webform_parent_key']] : NULL;
        if (!$parent_element || !isset($parent_element['#type']) || $parent_element['#type'] !== 'webform_table') {
          $t_args += [
            '%parent_title' => $this->t('Table'),
            '@parent_type' => 'webform_table',
          ];
          $messages[] = $this->t('The %title (@type) must be with in a %parent_title (@parent_type) element.', $t_args);
        }
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
    // Override Drupal's error and exception handler so that we can capture
    // all rendering exceptions and display the captured error/exception
    // message to the user.
    // @see _webform_entity_element_validate_rendering_error_handler()
    // @see _webform_entity_element_validate_rendering_exception_handler()
    set_error_handler('_webform_entity_element_validate_rendering_error_handler');
    set_exception_handler('_webform_entity_element_validate_rendering_exception_handler');
    try {
      /** @var \Drupal\webform\WebformSubmissionInterface $webform_submission */
      $webform_submission = $this->entityTypeManager
        ->getStorage('webform_submission')
        ->create(['webform' => $this->webform]);

      $form_object = $this->entityTypeManager->getFormObject('webform_submission', 'add');
      $form_object->setEntity($webform_submission);
      $form_state = (new FormState())->setFormState([]);
      $this->formBuilder->buildForm($form_object, $form_state);
      $message = NULL;
    }
    // PHP 7 introduces Throwable, which covers both Error and
    // Exception throwables.
    // @see _drupal_exception_handler
    catch (\Throwable $error) {
      $message = $error->getMessage();
    }
    catch (\Exception $exception) {
      $message = $exception->getMessage();
    }
    // Restore Drupal's error and exception handler.
    restore_error_handler();
    restore_exception_handler();

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
      return $this->renderer->renderPlain($build);
    }

    return $message;
  }

  /****************************************************************************/
  // Helper methods.
  /****************************************************************************/

  /**
   * Recurse through elements and collect an associative array of deleted element keys.
   *
   * @param array $elements
   *   An array of elements.
   * @param array $names
   *   An array tracking deleted element keys.
   */
  protected function getElementKeysRecursive(array $elements, array &$names) {
    foreach ($elements as $key => &$element) {
      if (!WebformElementHelper::isElement($element, $key)) {
        continue;
      }
      if (isset($element['#type'])) {
        $names[$key] = $key;
      }
      $this->getElementKeysRecursive($element, $names);
    }
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
