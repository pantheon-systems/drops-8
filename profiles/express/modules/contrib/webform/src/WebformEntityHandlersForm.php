<?php

namespace Drupal\webform;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\webform\Form\WebformEntityAjaxFormTrait;
use Drupal\webform\Plugin\WebformHandlerInterface;
use Drupal\webform\Plugin\WebformHandlerManagerInterface;
use Drupal\webform\Utility\WebformDialogHelper;
use Symfony\Component\DependencyInjection\ContainerInterface;

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
    /** @var \Drupal\webform\WebformInterface $webform */
    $webform = $this->getEntity();

    $user_input = $form_state->getUserInput();

    // Build table header.
    $header = [
      ['data' => $this->t('Title / Description')],
      ['data' => $this->t('ID'), 'class' => [RESPONSIVE_PRIORITY_LOW]],
      ['data' => $this->t('Summary'), 'class' => [RESPONSIVE_PRIORITY_LOW]],
      ['data' => $this->t('Status'), 'class' => [RESPONSIVE_PRIORITY_LOW]],
      ['data' => $this->t('Weight'), 'class' => [RESPONSIVE_PRIORITY_LOW]],
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
            '#markup' => '<b>' . $handler->label() . '</b>: ' . $handler->description(),
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
      ];

      $operations = [];
      $operations['edit'] = [
        'title' => $this->t('Edit'),
        'url' => Url::fromRoute('entity.webform.handler.edit_form', [
          'webform' => $this->entity->id(),
          'webform_handler' => $handler_id,
        ]),
        'attributes' => WebformDialogHelper::getModalDialogAttributes(800),
      ];
      if ($handler->cardinality() === WebformHandlerInterface::CARDINALITY_UNLIMITED) {
        $operations['duplicate'] = [
          'title' => $this->t('Duplicate'),
          'url' => Url::fromRoute('entity.webform.handler.duplicate_form', [
            'webform' => $this->entity->id(),
            'webform_handler' => $handler_id,
          ]),
          'attributes' => WebformDialogHelper::getModalDialogAttributes(800),
        ];
      }
      $operations['delete'] = [
        'title' => $this->t('Delete'),
        'url' => Url::fromRoute('entity.webform.handler.delete_form', [
          'webform' => $this->entity->id(),
          'webform_handler' => $handler_id,
        ]),
        'attributes' => WebformDialogHelper::getModalDialogAttributes(700),
      ];
      $row['operations'] = [
        '#type' => 'operations',
        '#links' => $operations,
        '#prefix' => '<div class="webform-dropbutton">',
        '#suffix' => '</div>',
      ];

      $rows[$handler_id] = $row;
    }

    // Filter add handler by excluded_handlers.
    $handler_definitions = $this->handlerManager->getDefinitions();
    $handler_definitions = $this->handlerManager->removeExcludeDefinitions($handler_definitions);
    unset($handler_definitions['broken']);


    // Must manually add local actions to the webform because we can't alter local
    // actions and add the needed dialog attributes.
    // @see https://www.drupal.org/node/2585169
    $local_actions = [];
    if (isset($handler_definitions['email'])) {
      $local_actions['add_email'] = [
        '#theme' => 'menu_local_action',
        '#link' => [
          'title' => $this->t('Add email'),
          'url' => new Url('entity.webform.handler.add_form', ['webform' => $webform->id(), 'webform_handler' => 'email']),
          'attributes' => WebformDialogHelper::getModalDialogAttributes(800),
        ]
      ];
    }
    unset($handler_definitions['email']);
    if ($handler_definitions) {
      $local_actions['add_handler'] = [
        '#theme' => 'menu_local_action',
        '#link' => [
          'title' => $this->t('Add handler'),
          'url' => new Url('entity.webform.handler', ['webform' => $webform->id()]),
          'attributes' => WebformDialogHelper::getModalDialogAttributes(800),
        ]
      ];
    }
    $form['local_actions'] = [
      '#prefix' => '<ul class="action-links">',
      '#suffix' => '</ul>',
    ] + $local_actions;

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
      ],
      '#empty' => $this->t('There are currently no handlers setup for this webform.'),
    ] + $rows;

    // Must preload libraries required by (modal) dialogs.
    WebformDialogHelper::attachLibraries($form);

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
      'link' => $webform->toLink($this->t('Edit'), 'handlers')->toString()
    ];
    $this->logger('webform')->notice('Webform @label handler saved.', $context);

    drupal_set_message($this->t('Webform %label handler saved.', ['%label' => $webform->label()]));
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

}
