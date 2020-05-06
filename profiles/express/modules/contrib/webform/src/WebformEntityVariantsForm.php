<?php

namespace Drupal\webform;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\webform\Ajax\WebformRefreshCommand;
use Drupal\webform\Form\WebformEntityAjaxFormTrait;
use Drupal\webform\Plugin\WebformElementManagerInterface;
use Drupal\webform\Plugin\WebformVariantManagerInterface;
use Drupal\webform\Utility\WebformDialogHelper;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Provides a webform to manage submission variants.
 */
class WebformEntityVariantsForm extends EntityForm {

  use WebformEntityAjaxFormTrait;

  /**
   * The webform.
   *
   * @var \Drupal\webform\WebformInterface
   */
  protected $entity;

  /**
   * Webform element manager.
   *
   * @var \Drupal\webform\Plugin\WebformElementManagerInterface
   */
  protected $elementManager;

  /**
   * Webform variant manager.
   *
   * @var \Drupal\webform\Plugin\WebformVariantManagerInterface
   */
  protected $variantManager;

  /**
   * Constructs a WebformEntityVariantsForm.
   *
   * @param \Drupal\webform\Plugin\WebformElementManagerInterface $element_manager
   *   The webform element manager.
   * @param \Drupal\webform\Plugin\WebformVariantManagerInterface $variant_manager
   *   The webform variant manager.
   */
  public function __construct(WebformElementManagerInterface $element_manager, WebformVariantManagerInterface $variant_manager) {
    $this->elementManager = $element_manager;
    $this->variantManager = $variant_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.webform.element'),
      $container->get('plugin.manager.webform.variant')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $user_input = $form_state->getUserInput();

    // Hard code the form id.
    $form['#id'] = 'webform-variants-form';

    // Build table header.
    $header = [
      ['data' => $this->t('Title / Notes')],
      ['data' => $this->t('ID'), 'class' => [RESPONSIVE_PRIORITY_LOW]],
      ['data' => $this->t('Element'), 'class' => [RESPONSIVE_PRIORITY_LOW]],
      ['data' => $this->t('Summary'), 'class' => [RESPONSIVE_PRIORITY_LOW]],
      ['data' => $this->t('Status'), 'class' => [RESPONSIVE_PRIORITY_LOW]],
      ['data' => $this->t('Weight'), 'class' => ['webform-tabledrag-hide']],
      ['data' => $this->t('Operations')],
    ];

    /** @var \Drupal\webform\WebformInterface $webform */
    $webform = $this->getEntity();

    // Build table rows for variants.
    $variants = $webform->getVariants();
    $rows = [];
    foreach ($variants as $variant_id => $variant) {
      $row['#attributes']['class'][] = 'draggable';
      $row['#attributes']['data-webform-key'] = $variant_id;

      $row['#weight'] = (isset($user_input['variants']) && isset($user_input['variants'][$variant_id])) ? $user_input['variants'][$variant_id]['weight'] : NULL;

      $row['title'] = [
        '#tree' => FALSE,
        'data' => [
          'label' => [
            '#type' => 'link',
            '#title' => $variant->label(),
            '#url' => Url::fromRoute('entity.webform.variant.edit_form', [
              'webform' => $webform->id(),
              'webform_variant' => $variant_id,
            ]),
            '#attributes' => WebformDialogHelper::getOffCanvasDialogAttributes(),
          ],
          'notes' => [
            '#prefix' => '<br/>',
            '#markup' => $variant->getNotes(),
          ],
        ],
      ];

      $row['id'] = ['#markup' => $variant_id];

      $variant_element_key = $variant->getElementKey();
      $variant_element = $webform->getElement($variant_element_key);
      if ($variant_element) {
        $webform_element = $this->elementManager->getElementInstance($variant_element);
        $row['element'] = ['#markup' => $webform_element->getAdminLabel($variant_element)];
      }
      else {
        $row['element'] = [
          'data' => [
            '#markup' => $this->t("'@element_key' is missing.", ['@element_key' => $variant_element_key]),
            '#prefix' => '<b class="color-error">',
            '#suffix' => '</b>',
          ],
        ];
      }

      $row['summary'] = $variant->getSummary();

      if ($variant->isDisabled()) {
        $status = $this->t('Disabled');
      }
      else {
        $status = $this->t('Enabled');
      }
      $row['status'] = ['data' => ['#markup' => $status]];

      $row['weight'] = [
        '#type' => 'weight',
        '#title' => $this->t('Weight for @title', ['@title' => $variant->label()]),
        '#title_display' => 'invisible',
        '#delta' => 50,
        '#default_value' => $variant->getWeight(),
        '#attributes' => [
          'class' => ['webform-variant-order-weight'],
        ],
        '#wrapper_attributes' => ['class' => ['webform-tabledrag-hide']],
      ];

      $operations = [];
      // Edit.
      $operations['edit'] = [
        'title' => $this->t('Edit'),
        'url' => Url::fromRoute('entity.webform.variant.edit_form', [
          'webform' => $webform->id(),
          'webform_variant' => $variant_id,
        ]),
        'attributes' => WebformDialogHelper::getOffCanvasDialogAttributes(),
      ];
      // Duplicate.
      $operations['duplicate'] = [
        'title' => $this->t('Duplicate'),
        'url' => Url::fromRoute('entity.webform.variant.duplicate_form', [
          'webform' => $webform->id(),
          'webform_variant' => $variant_id,
        ]),
        'attributes' => WebformDialogHelper::getOffCanvasDialogAttributes(),
      ];
      if ($variant_element && $variant->isEnabled()) {
        // If #prepopulate is disabled use '_webform_variant'
        // querystring parameter for view and test operations.
        // @see \Drupal\webform\Entity\Webform::getSubmissionForm
        $query = [$variant_element_key => $variant_id];
        if (empty($variant_element['#prepopulate'])) {
          $query = ['_webform_variant' => $query];
        }
        // View.
        $operations['view'] = [
          'title' => $this->t('View'),
          'url' => Url::fromRoute(
            'entity.webform.canonical',
            ['webform' => $webform->id()],
            ['query' => $query]
          ),
        ];
        // Test.
        if ($webform->access('test')) {
          $operations['test'] = [
            'title' => $this->t('Test'),
            'url' => Url::fromRoute(
              'entity.webform.test_form',
              ['webform' => $webform->id()],
              ['query' => $query]
            ),
          ];
        }
      }
      // Apply.
      $operations['apply'] = [
        'title' => $this->t('Apply'),
        'url' => Url::fromRoute(
          'entity.webform.variant.apply_form',
          ['webform' => $webform->id()],
          ['query' => ['variant_id' => $variant_id]]
        ),
        'attributes' => WebformDialogHelper::getModalDialogAttributes(WebformDialogHelper::DIALOG_NARROW),
      ];
      // Add AJAX functionality to enable/disable operations.
      $operations['status'] = [
        'title' => $variant->isEnabled() ? $this->t('Disable') : $this->t('Enable'),
        'url' => Url::fromRoute('entity.webform.variant.' . ($variant->isEnabled() ? 'disable' : 'enable'), [
          'webform' => $webform->id(),
          'webform_variant' => $variant_id,
        ]),
        'attributes' => WebformDialogHelper::getModalDialogAttributes(WebformDialogHelper::DIALOG_NARROW, ['use-ajax']),
      ];
      // Delete.
      $operations['delete'] = [
        'title' => $this->t('Delete'),
        'url' => Url::fromRoute('entity.webform.variant.delete_form', [
          'webform' => $webform->id(),
          'webform_variant' => $variant_id,
        ]),
        'attributes' => WebformDialogHelper::getModalDialogAttributes(WebformDialogHelper::DIALOG_NARROW),
      ];

      $row['operations'] = [
        '#type' => 'operations',
        '#links' => $operations,
        '#prefix' => '<div class="webform-dropbutton">',
        '#suffix' => '</div>',
      ];

      $rows[$variant_id] = $row;
    }

    // Add test multiple variants.
    if ($webform->getVariants()->count() > 1 && count($webform->getElementsVariant()) > 1) {
      $row = [];
      $row['#attributes']['class'] = ['webform-variant-table-test-multiple'];
      $row[] = [
        '#wrapper_attributes' => ['colspan' => 6],
      ];
      $operations = [];
      // View variants.
      $operations['view'] = [
        'title' => $this->t('View variants'),
        'url' => Url::fromRoute(
          'entity.webform.variant.view_form',
          ['webform' => $webform->id()]
        ),
        'attributes' => WebformDialogHelper::getModalDialogAttributes(WebformDialogHelper::DIALOG_NARROW),
      ];
      // Test variants.
      if ($webform->access('test')) {
        $operations['test'] = [
          'title' => $this->t('Test variants'),
          'url' => Url::fromRoute(
            'entity.webform.variant.test_form',
            ['webform' => $webform->id()]
          ),
          'attributes' => WebformDialogHelper::getModalDialogAttributes(WebformDialogHelper::DIALOG_NARROW),
        ];
      }
      // Apply variants.
      $operations['apply'] = [
        'title' => $this->t('Apply variants'),
        'url' => Url::fromRoute(
          'entity.webform.variant.apply_form',
          ['webform' => $webform->id()]
        ),
        'attributes' => WebformDialogHelper::getModalDialogAttributes(WebformDialogHelper::DIALOG_NARROW),
      ];
      $row['operations'] = [
        '#type' => 'operations',
        '#prefix' => '<div class="webform-dropbutton">',
        '#suffix' => '</div>',
        '#links' => $operations,
      ];
      $rows[] = $row;
    }

    // Build the list of existing webform variants for this webform.
    $form['variants'] = [
      '#type' => 'table',
      '#header' => $header,
      '#tabledrag' => [
        [
          'action' => 'order',
          'relationship' => 'sibling',
          'group' => 'webform-variant-order-weight',
        ],
      ],
      '#attributes' => [
        'id' => 'webform-variants',
        'class' => ['webform-variants-table'],
      ],
      '#empty' => $this->t('There are currently no variants setup for this webform.'),
    ] + $rows;

    // Must preload libraries required by (modal) dialogs.
    WebformDialogHelper::attachLibraries($form);
    $form['#attached']['library'][] = 'webform/webform.admin.tabledrag';

    return parent::form($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  protected function actionsElement(array $form, FormStateInterface $form_state) {
    $form = parent::actionsElement($form, $form_state);
    $form['submit']['#value'] = $this->t('Save variants');
    unset($form['delete']);
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Update webform variant weights.
    if (!$form_state->isValueEmpty('variants')) {
      $this->updateVariantWeights($form_state->getValue('variants'));
    }

    parent::submitForm($form, $form_state);
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
      'link' => $webform->toLink($this->t('Edit'), 'variants')->toString(),
    ];
    $this->logger('webform')->notice('Webform @label variant saved.', $context);

    $this->messenger()->addStatus($this->t('Webform %label variant saved.', ['%label' => $webform->label()]));
  }

  /**
   * Updates webform variant weights.
   *
   * @param array $variants
   *   Associative array with variants having variant ids as keys and array
   *   with variant data as values.
   */
  protected function updateVariantWeights(array $variants) {
    foreach ($variants as $variant_id => $variant_data) {
      if ($this->entity->getVariants()->has($variant_id)) {
        $this->entity->getVariant($variant_id)->setWeight($variant_data['weight']);
      }
    }
  }

  /**
   * Calls a method on a webform variant and reloads the webform variants form.
   *
   * @param \Drupal\webform\WebformInterface $webform
   *   The webform being acted upon.
   * @param string $webform_variant
   *   THe webform variant id.
   * @param string $operation
   *   The operation to perform, e.g., 'enable' or 'disable'.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse|\Symfony\Component\HttpFoundation\RedirectResponse
   *   Either returns an AJAX response that refreshes the webform's variants
   *   page, or redirects back to the webform's variants page.
   */
  public static function ajaxOperation(WebformInterface $webform, $webform_variant, $operation, Request $request) {
    // Perform the variant disable/enable operation.
    $variant = $webform->getVariant($webform_variant);
    $variant->$operation();
    // Save the webform.
    $webform->save();

    // Display message.
    $t_args = [
      '@label' => $variant->label(),
      '@op' => ($operation === 'enable') ? t('enabled') : t('disabled'),
    ];
    \Drupal::messenger()->addStatus(t('This @label variant was @op.', $t_args));

    // Get the webform's variants form URL.
    $url = $webform->toUrl('variants', ['query' => ['update' => $webform_variant]])->toString();

    // If the request is via AJAX, return the webform variants form.
    if ($request->request->get('js')) {
      $response = new AjaxResponse();
      $response->addCommand(new WebformRefreshCommand($url));
      return $response;
    }

    // Otherwise, redirect back to the webform variants form.
    return new RedirectResponse($url);
  }

}
