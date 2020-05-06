<?php

namespace Drupal\webform\Plugin\WebformElement;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\webform\Plugin\WebformElementBase;
use Drupal\webform\Element\WebformMessage as WebformMessageElement;
use Drupal\webform\WebformInterface;
use Drupal\webform\WebformSubmissionInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Provides a 'webform_table_row' element.
 *
 * @WebformElement(
 *   id = "webform_table_row",
 *   label = @Translation("Table row"),
 *   description = @Translation("Provides an element to render a table row."),
 *   category = @Translation("Containers"),
 *   hidden = TRUE,
 * )
 */
class WebformTableRow extends WebformElementBase {

  /**
   * {@inheritdoc}
   */
  protected function defineDefaultProperties() {
    $properties = [
      'title' => '',
      'attributes' => [],
    ] + $this->defineDefaultBaseProperties();
    unset(
      $properties['prepopulate']
    );
    return $properties;
  }

  /****************************************************************************/

  /**
   * {@inheritdoc}
   */
  public function isInput(array $element) {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function isContainer(array $element) {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function getItemDefaultFormat() {
    return '';
  }

  /**
   * {@inheritdoc}
   */
  public function getItemFormats() {
    return [''];
  }

  /**
   * {@inheritdoc}
   */
  protected function build($format, array &$element, WebformSubmissionInterface $webform_submission, array $options = []) {
    $format_function = 'format' . ucfirst($format);
    return $this->$format_function($element, $webform_submission, $options);
  }

  /**
   * {@inheritdoc}
   */
  public function buildHtml(array $element, WebformSubmissionInterface $webform_submission, array $options = []) {
    $webform = $webform_submission->getWebform();
    $parent_key = $element['#webform_parent_key'];
    $parent_element = $webform->getElement($parent_key);
    $parent_format = (isset($parent_element['#format'])) ? $parent_element['#format'] : 'table';

    // Remove #states.
    unset($element['#states']);

    switch ($parent_format) {
      case 'details-closed':
      case 'details':
      case 'fieldset':
        $webform_plugin_id = ($parent_format === 'details-closed') ? 'details' : $parent_format;
        $element['#webform_plugin_id'] = $webform_plugin_id;
        $element['#type'] = $webform_plugin_id;
        $element['#format_item'] = $parent_format;
        $element_plugin = $this->elementManager->getElementInstance($element);
        return $element_plugin->buildHtml($element, $webform_submission, $options);

      case 'table':
      default:
        foreach ($element as $column_key => $column_element) {
          if (Element::property($column_key)) {
            continue;
          }

          $column_element_plugin = $this->elementManager->getElementInstance($column_element);
          if ($column_element_plugin->isContainer($column_element)) {
            $column_build = $column_element_plugin->buildHtml($column_element, $webform_submission, $options);
            $element[$column_key] = ['data' => $column_build];
          }
          elseif (!$column_element_plugin->isInput($column_element)) {
            $element[$column_key] = ['data' => $column_element];
          }
          else {
            $column_value = $column_element_plugin->format('html', $column_element, $webform_submission, $options);
            $element[$column_key] = (is_array($column_value))
              ? ['data' => $column_value]
              : ['data' => ['#markup' => $column_value]];
          }
        }
        return $element;
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function formatTextItem(array $element, WebformSubmissionInterface $webform_submission, array $options = []) {
    /** @var \Drupal\webform\WebformSubmissionViewBuilderInterface $view_builder */
    $view_builder = \Drupal::entityTypeManager()->getViewBuilder('webform_submission');
    $children = $view_builder->buildElements($element, $webform_submission, $options, 'text');
    if (empty($children)) {
      return [];
    }

    $build = [];
    if (!empty($element['#title'])) {
      $build['title'] = [
        '#markup' => $element['#title'],
        '#suffix' => PHP_EOL,
      ];
      $build['divider'] = [
        '#markup' => str_repeat('-', mb_strlen($element['#title'])),
        '#suffix' => PHP_EOL,
      ];
    }
    $build['children'] = $children;
    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function getTestValues(array $element, WebformInterface $webform, array $options = []) {
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    /** @var \Drupal\webform_ui\Form\WebformUiElementFormInterface $form_object */
    $form_object = $form_state->getFormObject();

    // Handle new row being added to a table.
    if ($form_object->isNew()) {
      $parent_key = $form_object->getParentKey();

      // Make sure the new row is being inserted into a table.
      $table_element = $form_object->getWebform()->getElement($parent_key);
      if (!$table_element || $table_element['#type'] !== 'webform_table') {
        throw new NotFoundHttpException();
      }

      // Make sure the table support prefixing.
      $prefix_children = (!isset($table_element['#prefix_children']) || $table_element['#prefix_children'] === TRUE);
      if (!$prefix_children) {
        return $form;
      }

      $form['element']['table_message'] = [
        '#type' => 'webform_message',
        '#message_message' => $this->t("Row keys are the tables's key with an incremented value."),
        '#message_type' => 'warning',
        '#message_close' => TRUE,
        '#message_storage' => WebformMessageElement::STORAGE_SESSION,
        '#weight' => -98,
        '#access' => TRUE,
      ];

      $element_properties = $form_state->get('element_properties');

      // Set duplicate and incremented title element properties.
      if ($table_element['#webform_children']) {
        $first_row_key = reset($table_element['#webform_children']);
        $first_row_element = $this->getWebform()->getElement($first_row_key);

        if ($this->hasIncrementalChildrenElements($first_row_key)) {
          $form['table_settings'] = [
            '#type' => 'fieldset',
            '#title' => $this->t('Table row settings'),
          ];
          $form['table_settings']['duplicate'] = [
            '#type' => 'checkbox',
            '#title' => $this->t("Duplicate the table's first row"),
            '#return_value' => TRUE,
          ];

          $element_properties['duplicate'] = TRUE;
        }
        $element_properties['title'] = preg_replace(
          '/\d+/',
          $this->getNextIncrement(),
          $first_row_element['#title']
        );
      }
      else {
        $element_properties['title'] = $table_element['#title'] . ' (1)';
      }
      $form_state->set('element_properties', $element_properties);
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultKey() {
    $webform = $this->getWebform();
    $parent_key = \Drupal::request()->query->get('parent');
    $table_element = $webform->getElement($parent_key);

    // Make sure prefixing elements is enabled for the table.
    $prefix_children = (!isset($table_element['#prefix_children']) || $table_element['#prefix_children'] === TRUE);
    if (!$prefix_children) {
      return NULL;
    }

    // Return the first rows keys based on the parent title.
    if (empty($table_element['#webform_children'])) {
      return $parent_key . '_01';
    }

    // Replace increment in first row.
    $first_row_key = reset($table_element['#webform_children']);
    $increment = $this->getNextIncrement();
    $increment = str_pad($increment, 2, '0', STR_PAD_LEFT);
    return preg_replace('/\d+/', $increment, $first_row_key);
  }

  /**
   * {@inheritdoc}
   */
  public function getConfigurationFormProperties(array &$form, FormStateInterface $form_state) {
    $properties = parent::getConfigurationFormProperties($form, $form_state);

    /** @var \Drupal\webform_ui\Form\WebformUiElementFormInterface $form_object */
    $form_object = $form_state->getFormObject();
    if (!$form_object->isNew()) {
      return $properties;
    }

    // Get and unset the #duplicate property.
    $duplicate = !empty($properties['#duplicate']);
    unset($properties['#duplicate']);

    // If $duplicate is FALSE don't duplicate the child elements.
    if (!$duplicate) {
      return $properties;
    }

    // This is the only way to get the row key for a new element.
    $key = $_POST['key'];
    $parent_key = \Drupal::request()->query->get('parent');
    if (!$form_object->isNew() || !$parent_key) {
      return $properties;
    }

    $row_index = (preg_match('/\d+/', $key, $match)) ? intval($match[0]) : NULL;
    $table_element = $this->getWebform()->getElement($parent_key);
    if (!$table_element['#webform_children']) {
      return $properties;
    }

    $row_key = reset($table_element['#webform_children']);
    $children_elements = $this->getChildrenElements($row_key, $row_index);
    if ($children_elements === FALSE) {
      $this->messenger()->addWarning("Unable to append child elements from @row because a child element's key do not include any index/number.", ['@row' => $row_key]);
    }
    else {
      $properties += $children_elements;
    }

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public function getElementSelectorOptions(array $element) {
    return [];
  }

  /****************************************************************************/
  // Helper function.
  /****************************************************************************/

  /**
   * Get the parent table's next row increment.
   *
   * @return int
   *   The parent table's next row increment.
   */
  protected function getNextIncrement() {
    $webform = $this->getWebform();
    $parent_key = \Drupal::request()->query->get('parent');
    $table_element = $webform->getElement($parent_key);

    if (!$table_element['#webform_children']) {
      return 1;
    }

    // Get next row increment.
    $indexes = [];
    foreach ($table_element['#webform_children'] as $child_key) {
      preg_match('/\d+/', $child_key, $match);
      $indexes[] = intval($match[0]);
    }
    return (max($indexes) + 1);
  }

  /**
   * Determine if an element and its use incremental keys.
   *
   * @param string $key
   *   An element key.
   *
   * @return bool
   *   TRUE if an element and its use incremental keys.
   */
  protected function hasIncrementalChildrenElements($key) {
    // Return FALSE if the key is not incremental.
    if (!preg_match('/\d+/', $key, $match)) {
      return FALSE;
    }

    $element = $this->getWebform()->getElement($key);
    foreach ($element['#webform_children'] as $child_key) {
      // Return FALSE if any child element key is not incremental.
      if (!$this->hasIncrementalChildrenElements($child_key)) {
        return FALSE;
      }
    }

    return TRUE;
  }

  /**
   * Get child elements incremented with a new index.
   *
   * @param string $element_key
   *   The element key.
   * @param int $index
   *   The index for all child elements.
   *
   * @return array|bool
   *   Child elements or FALSE is child element keys are not incremental.
   */
  protected function getChildrenElements($element_key, $index) {
    $webform = $this->getWebform();
    $element = $webform->getElement($element_key);

    $elements = [];
    foreach ($element['#webform_children'] as $child_key) {
      // Return FALSE if the child key is not incremental.
      if (!preg_match('/\d+/', $child_key, $match)) {
        return FALSE;
      }

      // Set incremented key.
      $increment = str_pad($index, 2, '0', STR_PAD_LEFT);
      $increment_key = preg_replace('/\d+/', $increment, $child_key);

      // Return FALSE if the sub element already exists.
      if ($webform->getElement($increment_key)) {
        return FALSE;
      }

      // Get the decoded element.
      $element = $webform->getElementDecoded($child_key);

      // Increment the element's #title and #admin_title.
      $increment_properties = ['#title', '#admin_title'];
      foreach ($increment_properties as $increment_property) {
        if (isset($element[$increment_property])) {
          $element[$increment_property] = preg_replace('/\d+/', $index, $element[$increment_property]);
        }
      }

      // Get child elements.
      $child_elements = $this->getChildrenElements($child_key, $index);

      // Return FALSE if any child element is not incremented.
      if ($child_elements === FALSE) {
        return FALSE;
      }

      // Set new incremented element with child elements.
      $elements[$increment_key] = $element + $child_elements;
    }

    return $elements;
  }

}
