<?php

namespace Drupal\webform;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\webform\Ajax\WebformRefreshCommand;
use Drupal\webform\Form\WebformEntityAjaxFormTrait;
use Drupal\webform\Plugin\WebformHandlerInterface;
use Drupal\webform\Plugin\WebformHandlerManagerInterface;
use Drupal\webform\Utility\WebformDialogHelper;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Provides a webform to manage submission handlers.
 */
class WebformEntityHandlersForm extends EntityForm {

  use WebformEntityAjaxFormTrait;

  /**
   * The webform.
   *
   * @var \Drupal\webform\WebformInterface
   */
  protected $entity;

  /**
   * Webform handler manager.
   *
   * @var \Drupal\webform\Plugin\WebformHandlerManagerInterface
   */
  protected $handlerManager;

  /**
   * Constructs a WebformEntityHandlersForm.
   *
   * @param \Drupal\webform\Plugin\WebformHandlerManagerInterface $handler_manager
   *   The webform handler manager.
   */
  public function __construct(WebformHandlerManagerInterface $handler_manager) {
    $this->handlerManager = $handler_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.webform.handler')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $user_input = $form_state->getUserInput();

    // Hard code the form id.
    $form['#id'] = 'webform-handlers-form';

    // Build table header.
    $header = [
      ['data' => $this->t('Title / Description')],
      ['data' => $this->t('ID'), 'class' => [RESPONSIVE_PRIORITY_LOW]],
      ['data' => $this->t('Summary'), 'class' => [RESPONSIVE_PRIORITY_LOW]],
      ['data' => $this->t('Status'), 'class' => [RESPONSIVE_PRIORITY_LOW]],
      ['data' => $this->t('Weight'), 'class' => ['webform-tabledrag-hide']],
      ['data' => $this->t('Operations')],
    ];

    // Build table rows for handlers.
    $handlers = $this->entity->getHandlers();
    $rows = [];
    foreach ($handlers as $handler_id => $handler) {
      $row['#attributes']['class'][] = 'draggable';
      $row['#attributes']['data-webform-key'] = $handler_id;

      $row['#weight'] = (isset($user_input['handlers']) && isset($user_input['handlers'][$handler_id])) ? $user_input['handlers'][$handler_id]['weight'] : NULL;

      $row['handler'] = [
        '#tree' => FALSE,
        'data' => [
          'label' => [
            '#type' => 'link',
            '#title' => $handler->label(),
            '#url' => Url::fromRoute('entity.webform.handler.edit_form', [
              'webform' => $this->entity->id(),
              'webform_handler' => $handler_id,
            ]),
            '#attributes' => WebformDialogHelper::getOffCanvasDialogAttributes(),
          ],
          'description' => [
            '#prefix' => '<br/>',
            '#markup' => $handler->description(),
          ],
        ],
      ];

      $row['id'] = [
        'data' => ['#markup' => $handler->getHandlerId()],
      ];

      $row['summary'] = $handler->getSummary();

      if ($handler->isDisabled()) {
        $status = $this->t('Disabled');
      }
      else {
        $status = ($handler->supportsConditions() && $handler->getConditions()) ? $this->t('Conditional') : $this->t('Enabled');
      }
      $row['status'] = ['data' => ['#markup' => $status]];

      $row['weight'] = [
        '#type' => 'weight',
        '#title' => $this->t('Weight for @title', ['@title' => $handler->label()]),
        '#title_display' => 'invisible',
        '#delta' => 50,
        '#default_value' => $handler->getWeight(),
        '#attributes' => [
          'class' => ['webform-handler-order-weight'],
        ],
        '#wrapper_attributes' => ['class' => ['webform-tabledrag-hide']],
      ];

      $operations = [];
      // Edit.
      $operations['edit'] = [
        'title' => $this->t('Edit'),
        'url' => Url::fromRoute('entity.webform.handler.edit_form', [
          'webform' => $this->entity->id(),
          'webform_handler' => $handler_id,
        ]),
        'attributes' => WebformDialogHelper::getOffCanvasDialogAttributes(),
      ];
      // Duplicate.
      if ($handler->cardinality() === WebformHandlerInterface::CARDINALITY_UNLIMITED) {
        $operations['duplicate'] = [
          'title' => $this->t('Duplicate'),
          'url' => Url::fromRoute('entity.webform.handler.duplicate_form', [
            'webform' => $this->entity->id(),
            'webform_handler' => $handler_id,
          ]),
          'attributes' => WebformDialogHelper::getOffCanvasDialogAttributes(),
        ];
      }
      // Test individual handler.
      if ($this->entity->access('test')) {
        $operations['test'] = [
          'title' => $this->t('Test'),
          'url' => Url::fromRoute(
            'entity.webform.test_form',
            ['webform' => $this->entity->id()],
            ['query' => ['_webform_handler' => $handler_id]]
          ),
        ];
      }
      // Add AJAX functionality to enable/disable operations.
      $operations['status'] = [
        'title' => $handler->isEnabled() ? $this->t('Disable') : $this->t('Enable'),
        'url' => Url::fromRoute('entity.webform.handler.' . ($handler->isEnabled() ? 'disable' : 'enable'), [
          'webform' => $this->entity->id(),
          'webform_handler' => $handler_id,
        ]),
        'attributes' => WebformDialogHelper::getModalDialogAttributes(WebformDialogHelper::DIALOG_NARROW, ['use-ajax']),
      ];
      // Delete.
      $operations['delete'] = [
        'title' => $this->t('Delete'),
        'url' => Url::fromRoute('entity.webform.handler.delete_form', [
          'webform' => $this->entity->id(),
          'webform_handler' => $handler_id,
        ]),
        'attributes' => WebformDialogHelper::getModalDialogAttributes(WebformDialogHelper::DIALOG_NARROW),
      ];

      $row['operations'] = [
        '#type' => 'operations',
        '#links' => $operations,
        '#prefix' => '<div class="webform-dropbutton">',
        '#suffix' => '</div>',
      ];

      $rows[$handler_id] = $row;
    }

    // Build the list of existing webform handlers for this webform.
    $form['handlers'] = [
      '#type' => 'table',
      '#header' => $header,
      '#tabledrag' => [
        [
          'action' => 'order',
          'relationship' => 'sibling',
          'group' => 'webform-handler-order-weight',
        ],
      ],
      '#attributes' => [
        'id' => 'webform-handlers',
        'class' => ['webform-handlers-table'],
      ],
      '#empty' => $this->t('There are currently no handlers setup for this webform.'),
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
    $form['submit']['#value'] = $this->t('Save handlers');
    unset($form['delete']);
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Update webform handler weights.
    if (!$form_state->isValueEmpty('handlers')) {
      $this->updateHandlerWeights($form_state->getValue('handlers'));
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
      'link' => $webform->toLink($this->t('Edit'), 'handlers')->toString(),
    ];
    $this->logger('webform')->notice('Webform @label handler saved.', $context);

    $this->messenger()->addStatus($this->t('Webform %label handler saved.', ['%label' => $webform->label()]));
  }

  /**
   * Updates webform handler weights.
   *
   * @param array $handlers
   *   Associative array with handlers having handler ids as keys and array
   *   with handler data as values.
   */
  protected function updateHandlerWeights(array $handlers) {
    foreach ($handlers as $handler_id => $handler_data) {
      if ($this->entity->getHandlers()->has($handler_id)) {
        $this->entity->getHandler($handler_id)->setWeight($handler_data['weight']);
      }
    }
  }

  /**
   * Calls a method on a webform handler and reloads the webform handlers form.
   *
   * @param \Drupal\webform\WebformInterface $webform
   *   The webform being acted upon.
   * @param string $webform_handler
   *   THe webform handler id.
   * @param string $operation
   *   The operation to perform, e.g., 'enable' or 'disable'.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse|\Symfony\Component\HttpFoundation\RedirectResponse
   *   Either returns an AJAX response that refreshes the webform's handlers
   *   page, or redirects back to the webform's handlers page.
   */
  public static function ajaxOperation(WebformInterface $webform, $webform_handler, $operation, Request $request) {
    // Perform the handler disable/enable operation.
    $handler = $webform->getHandler($webform_handler);
    $handler->$operation();
    // Save the webform.
    $webform->save();

    // Display message.
    $t_args = [
      '@label' => $handler->label(),
      '@op' => ($operation === 'enable') ? t('enabled') : t('disabled'),
    ];
    \Drupal::messenger()->addStatus(t('This @label handler was @op.', $t_args));

    // Get the webform's handlers form URL.
    $url = $webform->toUrl('handlers', ['query' => ['update' => $webform_handler]])->toString();

    // If the request is via AJAX, return the webform handlers form.
    if ($request->request->get('js')) {
      $response = new AjaxResponse();
      $response->addCommand(new WebformRefreshCommand($url));
      return $response;
    }

    // Otherwise, redirect back to the webform handlers form.
    return new RedirectResponse($url);
  }

}
