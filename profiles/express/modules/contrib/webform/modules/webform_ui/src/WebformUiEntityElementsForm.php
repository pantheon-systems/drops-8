<?php

namespace Drupal\webform_ui;

use Drupal\Core\Entity\BundleEntityFormBase;
use Drupal\Core\Serialization\Yaml;
use Drupal\Component\Utility\Unicode;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\Render\ElementInfoManagerInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Url;
use Drupal\webform\Element\WebformElementStates;
use Drupal\webform\Form\WebformEntityAjaxFormTrait;
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
   * @var \Drupal\webform\WebformEntityElementsValidator
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

    // Build table rows for elements.
    $rows = [];
    $elements = $this->getOrderableElements();
    $delta = count($elements);
    foreach ($elements as $element) {
      $rows[$element['#webform_key']] = $this->getElementRow($element, $delta);
    }

    // Must manually add local actions to the webform because we can't alter local
    // actions and add the needed dialog attributes.
    // @see https://www.drupal.org/node/2585169
    $local_actions = [];
    $local_actions['add_element'] = [
      '#theme' => 'menu_local_action',
      '#link' => [
        'title' => $this->t('Add element'),
        'url' => new Url('entity.webform_ui.element', ['webform' => $webform->id()]),
        'attributes' => WebformDialogHelper::getModalDialogAttributes(800),
      ]
    ];
    if ($this->elementManager->createInstance('webform_wizard_page')->isEnabled()) {
      $local_actions['add_page'] = [
        '#theme' => 'menu_local_action',
        '#link' => [
          'title' => $this->t('Add page'),
          'url' => new Url('entity.webform_ui.element.add_form', ['webform' => $webform->id(), 'type' => 'webform_wizard_page']),
          'attributes' => WebformDialogHelper::getModalDialogAttributes(800),
        ]
      ];
    }
    if ($webform->hasFlexboxLayout()) {
      $local_actions['add_layout'] = [
        '#theme' => 'menu_local_action',
        '#link' => [
          'title' => $this->t('Add layout'),
          'url' => new Url('entity.webform_ui.element.add_form', ['webform' => $webform->id(), 'type' => 'webform_flexbox']),
          'attributes' => WebformDialogHelper::getModalDialogAttributes(800),
        ]
      ];
    }
    $form['local_actions'] = [
      '#prefix' => '<ul class="action-links">',
      '#suffix' => '</ul>',
    ] + $local_actions;

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
      $parent_key = $table_element['parent_key'];

      // Validate the parent key.
      if ($parent_key && !isset($elements_flattened[$parent_key])) {
        $form_state->setError($form['webform_ui_elements'], $this->t('Parent %parent_key does not exist.', ['%parent_key' => $parent_key]));
        return;
      }

      // Set #required or remove the property.
      if (isset($webform_ui_elements[$key]['required'])) {
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
      'link' => $webform->toLink($this->t('Edit'), 'edit-form')->toString()
    ];
    $t_args = ['%label' => $webform->label()];
    $this->logger('webform')->notice('Webform @label elements saved.', $context);
    drupal_set_message($this->t('Webform %label elements saved.', $t_args));
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
          $element['#title'] = Unicode::truncate(strip_tags($element['#markup']), 100, TRUE, TRUE);
        }
        else {
          $element['#title'] = '[' .  $element_key . ']';
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
        'class' => [RESPONSIVE_PRIORITY_MEDIUM, 'webform-ui-element-operations'],
      ];
    }
    if (!$this->isQuickEdit()) {
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
    }
    $header['weight'] = $this->t('Weight');
    $header['parent'] = $this->t('Parent');
    if (!$this->isQuickEdit()) {
      $header['operations'] = [
        'data' => $this->t('Operations'),
        'class' => ['webform-ui-element-operations'],
      ];
    }
    return $header;
  }

  /**
   * Gets an row for a single element.
   *
   * @param array $element
   *   Webform element.
   * @param int $delta
   *   The number of elements. @todo is this correct?
   *
   * @return array
   *   The row for the element.
   */
  protected function getElementRow(array $element, $delta) {
    /** @var \Drupal\webform\WebformInterface $webform */
    $webform = $this->getEntity();

    $row = [];

    $element_dialog_attributes = WebformDialogHelper::getModalDialogAttributes(800);
    $key = $element['#webform_key'];

    $plugin_id = $this->elementManager->getElementPluginId($element);

    /** @var \Drupal\webform\Plugin\WebformElementInterface $webform_element */
    $webform_element = $this->elementManager->createInstance($plugin_id);

    $is_container = $webform_element->isContainer($element);
    $is_root = $webform_element->isRoot();

    // If disabled, display warning.
    if ($webform_element->isDisabled()) {
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
        $row['add'] = [
          '#type' => 'link',
          '#title' => $this->t('Add element'),
          '#url' => new Url('entity.webform_ui.element', $route_parameters, $route_options),
          '#attributes' => WebformDialogHelper::getModalDialogAttributes(800, ['button', 'button-action', 'button--primary', 'button--small']),
        ];
      }
      else {
        $row['add'] = ['#markup' => ''];
      }
    }
    if (!$this->isQuickEdit()) {
      $row['name'] = [
        '#markup' => $element['#webform_key'],
      ];

      $row['type'] = [
        '#markup' => $webform_element->getPluginLabel(),
      ];

      if ($webform->hasFlexboxLayout()) {
        $row['flex'] = [
          '#markup' => (empty($element['#flex'])) ? 1 : $element['#flex'],
        ];
      }

      if ($webform->hasConditions()) {
        $states = [];
        if (!empty($element['#states'])) {
          $states = array_intersect_key(WebformElementStates::getStateOptions(), $element['#states']);
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
          ],
        ];
      }

      if ($webform_element->hasProperty('required')) {
        $row['required'] = [
          '#type' => 'checkbox',
          '#default_value' => (empty($element['#required'])) ? FALSE : TRUE,
        ];
      }
      else {
        $row['required'] = ['#markup' => ''];
      }
    }

    $row['weight'] = [
      '#type' => 'weight',
      '#title' => $this->t('Weight for ID @id', ['@id' => $key]),
      '#title_display' => 'invisible',
      '#default_value' => $element['#weight'],
      '#attributes' => [
        'class' => ['row-weight'],
      ],
      '#delta' => $delta,
    ];

    $row['parent']['key'] = [
      '#parents' => ['webform_ui_elements', $key, 'key'],
      '#type' => 'hidden',
      '#value' => $key,
      '#attributes' => [
        'class' => ['row-key'],
      ],
    ];
    $row['parent']['parent_key'] = [
      '#parents' => ['webform_ui_elements', $key, 'parent_key'],
      '#type' => 'textfield',
      '#size' => 20,
      '#title' => $this->t('Parent'),
      '#title_display' => 'invisible',
      '#default_value' => $element['#webform_parent_key'],
      '#attributes' => [
        'class' => ['row-parent-key'],
        'readonly' => 'readonly',
      ],
    ];

    if (!$this->isQuickEdit()) {
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
      if ($webform_element->getPluginId() == 'processed_text') {
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
        'attributes' => WebformDialogHelper::getModalDialogAttributes(700),
      ];
    }
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
      '#attributes' => WebformDialogHelper::getModalDialogAttributes(800),
    ];
    if ($webform->hasContainer()) {
      $row['add'] = ['#markup' => ''];
    }
    if (!$this->isQuickEdit()) {
      $row['name'] = ['#markup' => 'actions'];
      $row['type'] = [
        '#markup' => $this->t('Submit button(s)'),
      ];
      if ($webform->hasFlexboxLayout()) {
        $row['flex'] = ['#markup' => 1];
      }
      if ($webform->hasConditions()) {
        $row['flex'] = ['#markup' => ''];
      }
      $row['required'] = ['#markup' => ''];
    }
    $row['weight'] = ['#markup' => ''];
    $row['parent'] = ['#markup' => ''];
    if (!$this->isQuickEdit()) {
      $row['operations'] = [
        '#type' => 'operations',
        '#prefix' => '<div class="webform-dropbutton">',
        '#suffix' => '</div>',
      ];
      $row['operations']['#links']['customize'] = [
        'title' => $this->t('Customize'),
        'url' => new Url('entity.webform_ui.element.add_form', ['webform' => $webform->id(), 'type' => 'webform_actions']),
        'attributes' => WebformDialogHelper::getModalDialogAttributes(800),
      ];
    }
    return $row;
  }

}
