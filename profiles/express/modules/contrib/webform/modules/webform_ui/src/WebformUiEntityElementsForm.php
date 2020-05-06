<?php

namespace Drupal\webform_ui;

use Drupal\Core\Entity\BundleEntityFormBase;
use Drupal\Core\Form\OptGroup;
use Drupal\Core\Render\Markup;
use Drupal\Core\Serialization\Yaml;
use Drupal\Component\Utility\Unicode;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\Render\ElementInfoManagerInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Url;
use Drupal\webform\Element\WebformElementStates;
use Drupal\webform\Form\WebformEntityAjaxFormTrait;
use Drupal\webform\Plugin\WebformElement\WebformElement;
use Drupal\webform\Plugin\WebformElement\WebformTable;
use Drupal\webform\Utility\WebformDialogHelper;
use Drupal\webform\Plugin\WebformElementManagerInterface;
use Drupal\webform\WebformEntityElementsValidatorInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Webform manage elements UI form.
 */
class WebformUiEntityElementsForm extends BundleEntityFormBase {

  use WebformEntityAjaxFormTrait;

  /**
   * Array of required states.
   *
   * @var array
   */
  protected $requiredStates = [
    'required' => 'required',
    '!required' => '!required',
    'optional' => 'optional',
    '!optional' => '!optional',
  ];

  /**
   * The renderer.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * Element info manager.
   *
   * @var \Drupal\Core\Render\ElementInfoManagerInterface
   */
  protected $elementInfo;

  /**
   * Webform element manager.
   *
   * @var \Drupal\webform\Plugin\WebformElementManagerInterface
   */
  protected $elementManager;

  /**
   * Webform element validator.
   *
   * @var \Drupal\webform\WebformEntityElementsValidatorInterface
   */
  protected $elementsValidator;

  /**
   * The webform token manager.
   *
   * @var \Drupal\webform\WebformTokenManagerInterface
   */
  protected $tokenManager;

  /**
   * Constructs a WebformUiEntityElementsForm.
   *
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer.
   * @param \Drupal\Core\Render\ElementInfoManagerInterface $element_info
   *   The element manager.
   * @param \Drupal\webform\Plugin\WebformElementManagerInterface $element_manager
   *   The webform element manager.
   * @param \Drupal\webform\WebformEntityElementsValidatorInterface $elements_validator
   *   Webform element validator.
   */
  public function __construct(RendererInterface $renderer, ElementInfoManagerInterface $element_info, WebformElementManagerInterface $element_manager, WebformEntityElementsValidatorInterface $elements_validator) {
    $this->renderer = $renderer;
    $this->elementInfo = $element_info;
    $this->elementManager = $element_manager;
    $this->elementsValidator = $elements_validator;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('renderer'),
      $container->get('plugin.manager.element_info'),
      $container->get('plugin.manager.webform.element'),
      $container->get('webform.elements_validator')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\webform\WebformInterface $webform */
    $webform = $this->getEntity();

    $header = $this->getTableHeader();

    $elements = $this->getOrderableElements();

    // Get (weight) delta parent options.
    $delta = count($elements);
    $parent_options = $this->getParentOptions($elements);

    // Build table rows for elements.
    $rows = [];
    foreach ($elements as $element) {
      $rows[$element['#webform_key']] = $this->getElementRow($element, $delta, $parent_options);
    }

    $form['webform_ui_elements'] = [
        '#type' => 'table',
        '#header' => $header,
        '#empty' => $this->t('Please add elements to this webform.'),
        '#attributes' => [
          'class' => ['webform-ui-elements-table'],
        ],
        '#tabledrag' => [
          [
            'action' => 'match',
            'relationship' => 'parent',
            'group' => 'row-parent-key',
            'source' => 'row-key',
            'hidden' => TRUE, /* hides the WEIGHT & PARENT tree columns below */
            'limit' => FALSE,
          ],
          [
            'action' => 'order',
            'relationship' => 'sibling',
            'group' => 'row-weight',
          ],
        ],
      ] + $rows;

    if ($rows && !$webform->hasActions()) {
      $form['webform_ui_elements'] += ['webform_actions_default' => $this->getCustomizeActionsRow()];
    }

    // Must preload libraries required by (modal) dialogs.
    WebformDialogHelper::attachLibraries($form);
    $form['#attached']['library'][] = 'webform/webform.admin.tabledrag';
    $form['#attached']['library'][] = 'webform_ui/webform_ui';

    $form = parent::buildForm($form, $form_state);

    return $this->buildAjaxForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    /** @var \Drupal\webform\WebformInterface $webform */
    $webform = $this->getEntity();

    // Get raw flattened elements that will be used to rebuild element's YAML
    // hierarchy.
    $elements_flattened = $webform->getElementsDecodedAndFlattened();

    // Get the reordered elements and sort them by weight.
    $webform_ui_elements = $form_state->getValue('webform_ui_elements') ?: [];
    uasort($webform_ui_elements, ['Drupal\Component\Utility\SortArray', 'sortByWeightElement']);

    // Make sure the reordered element keys and match the existing element keys.
    if (array_diff_key($webform_ui_elements, $elements_flattened)) {
      $form_state->setError($form['webform_ui_elements'], $this->t('The elements have been unexpectedly altered. Please try again'));
    }

    // Validate parent key and add children to ordered elements.
    foreach ($webform_ui_elements as $key => $table_element) {

      // Validate parent key.
      if ($parent_key = $table_element['parent_key']) {
        // Validate missing parent key.
        if (!isset($elements_flattened[$parent_key])) {
          $form_state->setError($form['webform_ui_elements'][$key]['parent']['parent_key'], $this->t('Parent %parent_key does not exist.', ['%parent_key' => $parent_key]));
          continue;
        }

        // Validate the parent keys and make sure there
        // are no recursive parents.
        $parent_keys = [$key];
        $current_parent_key = $parent_key;
        while ($current_parent_key) {
          if (in_array($current_parent_key, $parent_keys)) {
            $form_state->setError($form['webform_ui_elements'][$key]['parent']['parent_key'], $this->t('Parent %parent_key key is not valid.', ['%parent_key' => $parent_key]));
            break;
          }

          $parent_keys[] = $current_parent_key;
          $current_parent_key = (isset($webform_ui_elements[$current_parent_key]['parent_key'])) ? $webform_ui_elements[$current_parent_key]['parent_key'] : NULL;
        }
      }

      // Set #required or remove the property.
      $is_conditionally_required = isset($elements_flattened[$key]['#states']) && array_intersect_key($this->requiredStates, $elements_flattened[$key]['#states']);
      if ($is_conditionally_required) {
        // Always unset conditionally required elements.
        unset($elements_flattened[$key]['#required']);
      }
      elseif (isset($webform_ui_elements[$key]['required'])) {
        if (empty($webform_ui_elements[$key]['required'])) {
          unset($elements_flattened[$key]['#required']);
        }
        else {
          $elements_flattened[$key]['#required'] = TRUE;
        }
      }

      // Add this key to the parent's children.
      $webform_ui_elements[$parent_key]['children'][$key] = $key;
    }

    if ($form_state->hasAnyErrors()) {
      return;
    }

    // Rebuild elements to reflect new hierarchy.
    $elements_updated = [];
    // Preserve the original elements root properties.
    $elements_original = Yaml::decode($webform->get('elements')) ?: [];
    foreach ($elements_original as $key => $value) {
      if (Element::property($key)) {
        $elements_updated[$key] = $value;
      }
    }

    $this->buildUpdatedElementsRecursive($elements_updated, '', $webform_ui_elements, $elements_flattened);

    // Update the webform's elements.
    $webform->setElements($elements_updated);

    // Validate only elements required, hierarchy, and rendering.
    $validate_options = [
      'required' => TRUE,
      'yaml' => FALSE,
      'array' => FALSE,
      'names' => FALSE,
      'properties' => FALSE,
      'submissions' => FALSE,
      'hierarchy' => TRUE,
      'rendering' => TRUE,
    ];
    if ($this->elementsValidator->validate($webform, $validate_options)) {
      $form_state->setErrorByName(NULL, $this->t('There has been error validating the elements.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\webform\WebformInterface $webform */
    $webform = $this->getEntity();

    $webform->save();

    $context = [
      '@label' => $webform->label(),
      'link' => $webform->toLink($this->t('Edit'), 'edit-form')->toString(),
    ];
    $t_args = ['%label' => $webform->label()];
    $this->logger('webform')->notice('Webform @label elements saved.', $context);
    $this->messenger()->addStatus($this->t('Webform %label elements saved.', $t_args));
  }

  /**
   * {@inheritdoc}
   */
  protected function actionsElement(array $form, FormStateInterface $form_state) {
    $form = parent::actionsElement($form, $form_state);
    $form['submit']['#value'] = $this->t('Save elements');
    unset($form['delete']);
    return $form;
  }

  /**
   * Build updated elements using the new parent child relationship.
   *
   * @param array $elements
   *   An associative array that will be populated with updated elements
   *   hierarchy.
   * @param string $key
   *   The current element key. The blank empty key represents the elements
   *   root.
   * @param array $webform_ui_elements
   *   An associative array contain the reordered elements parent child
   *   relationship.
   * @param array $elements_flattened
   *   An associative array containing the raw flattened elements that will
   *   copied into the updated elements hierarchy.
   */
  protected function buildUpdatedElementsRecursive(array &$elements, $key, array $webform_ui_elements, array $elements_flattened) {
    if (!isset($webform_ui_elements[$key]['children'])) {
      return;
    }

    foreach ($webform_ui_elements[$key]['children'] as $key) {
      $elements[$key] = $elements_flattened[$key];
      $this->buildUpdatedElementsRecursive($elements[$key], $key, $webform_ui_elements, $elements_flattened);
    }
  }

  /**
   * Get webform's elements as an associative array of orderable elements.
   *
   * @return array
   *   An associative array of orderable elements.
   */
  protected function getOrderableElements() {
    /** @var \Drupal\webform\WebformInterface $webform */
    $webform = $this->getEntity();

    $elements = $webform->getElementsInitializedAndFlattened();
    $weights = [];
    foreach ($elements as $element_key => &$element) {
      $parent_key = $element['#webform_parent_key'];
      if (!isset($weights[$parent_key])) {
        $element['#weight'] = $weights[$parent_key] = 0;
      }
      else {
        $element['#weight'] = ++$weights[$parent_key];
      }

      if (empty($element['#type'])) {
        if (isset($element['#theme'])) {
          $element['#type'] = $element['#theme'];
        }
        else {
          $element['#type'] = '';
        }
      }

      if (empty($element['#title'])) {
        if (!empty($element['#markup'])) {
          $element['#title'] = Markup::create(Unicode::truncate(strip_tags($element['#markup']), 100, TRUE, TRUE));
        }
        else {
          $element['#title'] = '[' . $element_key . ']';
        }
      }
    }
    return $elements;
  }

  /**
   * Gets the elements table header.
   *
   * @return array
   *   The header elements.
   */
  protected function getTableHeader() {
    /** @var \Drupal\webform\WebformInterface $webform */
    $webform = $this->getEntity();
    $header = [];
    $header['title'] = $this->t('Title');
    if ($webform->hasContainer()) {
      $header['add'] = [
        'data' => '',
        'class' => ['webform-ui-element-operations'],
      ];
    }
    $header['key'] = [
      'data' => $this->t('Key'),
      'class' => [RESPONSIVE_PRIORITY_LOW],
    ];
    $header['type'] = [
      'data' => $this->t('Type'),
      'class' => [RESPONSIVE_PRIORITY_LOW],
    ];
    if ($webform->hasFlexboxLayout()) {
      $header['flex'] = [
        'data' => $this->t('Flex'),
        'class' => [RESPONSIVE_PRIORITY_LOW],
      ];
    }
    if ($webform->hasConditions()) {
      $header['conditions'] = [
        'data' => $this->t('Conditional'),
        'class' => [RESPONSIVE_PRIORITY_LOW],
      ];
    }
    $header['required'] = [
      'data' => $this->t('Required'),
      'class' => ['webform-ui-element-required', RESPONSIVE_PRIORITY_LOW],
    ];
    $header['weight'] = [
      'data' => $this->t('Weight'),
      'class' => ['webform-tabledrag-hide'],
    ];
    $header['parent'] = [
      'data' => $this->t('Parent'),
      'class' => ['webform-tabledrag-hide'],
    ];
    $header['operations'] = [
      'data' => $this->t('Operations'),
      'class' => ['webform-ui-element-operations'],
    ];
    return $header;
  }

  /**
   * Get parent (container) elements as options.
   *
   * @param array $elements
   *   A flattened array of elements.
   *
   * @return array
   *   Parent (container) elements as options.
   */
  protected function getParentOptions(array $elements) {
    $options = [];
    foreach ($elements as $key => $element) {
      $plugin_id = $this->elementManager->getElementPluginId($element);
      $webform_element = $this->elementManager->createInstance($plugin_id);
      if ($webform_element->isContainer($element)) {
        $options[$key] = $element['#admin_title'] ?: $element['#title'];
      }
    }
    return $options;
  }

  /**
   * Gets an row for a single element.
   *
   * @param array $element
   *   Webform element.
   * @param int $delta
   *   The number of elements.
   * @param array $parent_options
   *   An associative array of parent (container) options.
   *
   * @return array
   *   The row for the element.
   */
  protected function getElementRow(array $element, $delta, array $parent_options) {
    /** @var \Drupal\webform\WebformInterface $webform */
    $webform = $this->getEntity();

    $row = [];

    $element_state_options = OptGroup::flattenOptions(WebformElementStates::getStateOptions());
    $element_dialog_attributes = WebformDialogHelper::getOffCanvasDialogAttributes();
    $key = $element['#webform_key'];
    $title = $element['#admin_title'] ?: $element['#title'];
    $title = (is_array($title)) ? $this->renderer->render($title) : $title;
    $plugin_id = $this->elementManager->getElementPluginId($element);

    /** @var \Drupal\webform\Plugin\WebformElementInterface $webform_element */
    $webform_element = $this->elementManager->createInstance($plugin_id);

    $is_container = $webform_element->isContainer($element);
    $is_root = $webform_element->isRoot();
    $is_element_disabled = $webform_element->isDisabled();
    $is_access_disabled = (isset($element['#access']) && $element['#access'] === FALSE);

    // If disabled, display warning.
    if ($is_element_disabled) {
      $webform_element->displayDisabledWarning($element);
    }

    // Get row class names.
    $row_class = ['draggable'];
    if ($is_root) {
      $row_class[] = 'tabledrag-root';
      $row_class[] = 'webform-ui-element-root';
    }
    if (!$is_container) {
      $row_class[] = 'tabledrag-leaf';
    }
    if ($is_container) {
      $row_class[] = 'webform-ui-element-container';
    }
    if (!empty($element['#type'])) {
      $row_class[] = 'webform-ui-element-type-' . $element['#type'];
    }
    else {
      $row_class[] = 'webform-ui-element-container';
    }
    if ($is_element_disabled || $is_access_disabled) {
      $row_class[] = 'webform-ui-element-disabled';
    }

    // Add element key.
    $row['#attributes']['data-webform-key'] = $element['#webform_key'];

    $row['#attributes']['class'] = $row_class;

    $indentation = NULL;
    if ($element['#webform_depth']) {
      $indentation = [
        '#theme' => 'indentation',
        '#size' => $element['#webform_depth'],
      ];
    }

    $row['title'] = [
      '#type' => 'link',
      '#title' => $element['#admin_title'] ?: $element['#title'],
      '#url' => new Url('entity.webform_ui.element.edit_form', [
        'webform' => $webform->id(),
        'key' => $key,
      ]),
      '#attributes' => $element_dialog_attributes,
      '#prefix' => !empty($indentation) ? $this->renderer->renderPlain($indentation) : '',
    ];

    if ($webform->hasContainer()) {
      if ($is_container) {
        $route_parameters = [
          'webform' => $webform->id(),
        ];
        $route_options = ['query' => ['parent' => $key]];
        if ($webform_element instanceof WebformTable) {
          $route_parameters['type'] = 'webform_table_row';
          $row['add'] = [
            '#type' => 'link',
            '#title' => $this->t('Add <span>row</span>'),
            '#url' => new Url('entity.webform_ui.element.add_form', $route_parameters, $route_options),
            '#attributes' => WebformDialogHelper::getOffCanvasDialogAttributes(WebformDialogHelper::DIALOG_NORMAL, ['button', 'button-action', 'button--primary', 'button--small']),
          ];
        }
        else {
          $row['add'] = [
            '#type' => 'link',
            '#title' => $this->t('Add <span>element</span>'),
            '#url' => new Url('entity.webform_ui.element', $route_parameters, $route_options),
            '#attributes' => WebformDialogHelper::getModalDialogAttributes(WebformDialogHelper::DIALOG_NORMAL, ['button', 'button-action', 'button--primary', 'button--small']),
          ];
        }
      }
      else {
        $row['add'] = ['#markup' => ''];
      }
    }
    $row['name'] = [
      '#markup' => $element['#webform_key'],
    ];

    $type = $webform_element->getPluginLabel();
    if ($webform_element instanceof WebformElement) {
      if (!empty($element['#type'])) {
        $type = '[' . $element['#type'] . ']';
      }
      elseif (isset($element['#theme'])) {
        $type = '[' . $element['#theme'] . ']';
      }
    }
    $row['type'] = ['#markup' => $type];

    if ($webform->hasFlexboxLayout()) {
      $row['flex'] = [
        '#markup' => (empty($element['#flex'])) ? 1 : $element['#flex'],
      ];
    }

    $is_conditionally_required = FALSE;
    if ($webform->hasConditions()) {
      $states = [];
      if (!empty($element['#states'])) {
        $states = array_intersect_key($element_state_options, $element['#states']);
        $is_conditionally_required = array_intersect_key($this->requiredStates, $element['#states']);
      }
      $row['conditional'] = [
        '#type' => 'link',
        '#title' => implode('; ', $states),
        '#url' => new Url(
          'entity.webform_ui.element.edit_form',
          ['webform' => $webform->id(), 'key' => $key]
        ),
        '#attributes' => $element_dialog_attributes + [
          // Add custom hash to current page's location.
          // @see Drupal.behaviors.webformAjaxLink
          'data-hash' => 'webform-tab--conditions',
          'title' => $this->t('Edit @states conditional', ['@states' => implode('; ', $states)]),
          'aria-label' => $this->t('Edit @states conditional', ['@states' => implode('; ', $states)]),
        ],
      ];
    }

    if ($webform_element->hasProperty('required')) {
      $row['required'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Required for @title', ['@title' => $title]),
        '#title_display' => 'invisible',
        '#default_value' => (empty($element['#required'])) ? FALSE : TRUE,
      ];
      if ($is_conditionally_required) {
        $row['required']['#default_value'] = TRUE;
        $row['required']['#disabled'] = TRUE;
      }
    }
    else {
      $row['required'] = ['#markup' => ''];
    }

    $row['weight'] = [
      '#type' => 'weight',
      '#title' => $this->t('Weight for @title', ['@title' => $title]),
      '#title_display' => 'invisible',
      '#default_value' => $element['#weight'],
      '#wrapper_attributes' => ['class' => ['webform-tabledrag-hide']],
      '#attributes' => [
        'class' => ['row-weight'],
      ],
      '#delta' => $delta,
    ];

    $row['parent'] = [
      '#wrapper_attributes' => ['class' => ['webform-tabledrag-hide']],
    ];
    $row['parent']['key'] = [
      '#parents' => ['webform_ui_elements', $key, 'key'],
      '#type' => 'hidden',
      '#value' => $key,
      '#attributes' => [
        'class' => ['row-key'],
      ],
    ];
    if ($parent_options) {
      $row['parent']['parent_key'] = [
        '#parents' => ['webform_ui_elements', $key, 'parent_key'],
        '#type' => 'select',
        '#options' => $parent_options,
        '#empty_option' => '',
        '#title' => $this->t('Parent element @title', ['@title' => $title]),
        '#title_display' => 'invisible',
        '#default_value' => $element['#webform_parent_key'],
        '#attributes' => [
          'class' => ['row-parent-key'],
        ],
      ];
    }
    else {
      $row['parent']['parent_key'] = [
        '#parents' => ['webform_ui_elements', $key, 'parent_key'],
        '#type' => 'hidden',
        '#default_value' => '',
        '#attributes' => [
          'class' => ['row-parent-key'],
        ],
      ];
    }

    $row['operations'] = [
      '#type' => 'operations',
      '#prefix' => '<div class="webform-dropbutton">',
      '#suffix' => '</div>',
    ];
    $row['operations']['#links']['edit'] = [
      'title' => $this->t('Edit'),
      'url' => new Url(
        'entity.webform_ui.element.edit_form',
        [
          'webform' => $webform->id(),
          'key' => $key,
        ]
      ),
      'attributes' => $element_dialog_attributes,
    ];
    // Issue #2741877 Nested modals don't work: when using CKEditor in a
    // modal, then clicking the image button opens another modal,
    // which closes the original modal.
    // @todo Remove the below workaround once this issue is resolved.
    if ($webform_element->getPluginId() == 'processed_text' && !WebformDialogHelper::useOffCanvas()) {
      unset($row['operations']['#links']['edit']['attributes']);
    }
    if (!$is_container) {
      $row['operations']['#links']['duplicate'] = [
        'title' => $this->t('Duplicate'),
        'url' => new Url(
          'entity.webform_ui.element.duplicate_form',
          [
            'webform' => $webform->id(),
            'key' => $key,
          ]
        ),
        'attributes' => $element_dialog_attributes,
      ];
    }
    $row['operations']['#links']['delete'] = [
      'title' => $this->t('Delete'),
      'url' => new Url(
        'entity.webform_ui.element.delete_form',
        [
          'webform' => $webform->id(),
          'key' => $key,
        ]
      ),
      'attributes' => WebformDialogHelper::getModalDialogAttributes(WebformDialogHelper::DIALOG_NARROW),
    ];
    return $row;
  }

  /**
   * Get customize actions row.
   *
   * @return array
   *   The customize actions row.
   */
  protected function getCustomizeActionsRow() {
    /** @var \Drupal\webform\WebformInterface $webform */
    $webform = $this->getEntity();

    $row = [];
    $row['#attributes']['class'] = ['webform-ui-element-type-webform_actions'];
    $row['title'] = [
      '#type' => 'link',
      '#title' => $this->t('Submit button(s)'),
      '#url' => new Url('entity.webform_ui.element.add_form', ['webform' => $webform->id(), 'type' => 'webform_actions'], ['query' => ['key' => 'actions']]),
      '#attributes' => WebformDialogHelper::getOffCanvasDialogAttributes(),
    ];
    if ($webform->hasContainer()) {
      $row['add'] = ['#markup' => ''];
    }
    $row['name'] = ['#markup' => 'actions'];
    $row['type'] = [
      '#markup' => $this->t('Submit button(s)'),
    ];
    if ($webform->hasFlexboxLayout()) {
      $row['flex'] = ['#markup' => 1];
    }
    if ($webform->hasConditions()) {
      $row['conditions'] = ['#markup' => ''];
    }
    $row['required'] = ['#markup' => ''];
    $row['weight'] = ['#markup' => '', '#wrapper_attributes' => ['class' => ['webform-tabledrag-hide']]];
    $row['parent'] = ['#markup' => '', '#wrapper_attributes' => ['class' => ['webform-tabledrag-hide']]];
    if ($this->elementManager->isExcluded('webform_actions')) {
      $row['operations'] = ['#markup' => ''];
    }
    else {
      $row['operations'] = [
        '#type' => 'operations',
        '#prefix' => '<div class="webform-dropbutton">',
        '#suffix' => '</div>',
      ];
      $row['operations']['#links']['customize'] = [
        'title' => $this->t('Customize'),
        'url' => new Url('entity.webform_ui.element.add_form', ['webform' => $webform->id(), 'type' => 'webform_actions']),
        'attributes' => WebformDialogHelper::getOffCanvasDialogAttributes(),
      ];
    }
    return $row;
  }

}
