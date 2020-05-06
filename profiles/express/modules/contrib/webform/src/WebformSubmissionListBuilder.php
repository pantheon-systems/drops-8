<?php

namespace Drupal\webform;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Database\Database;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Link;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Drupal\Core\Utility\TableSort;
use Drupal\views\Views;
use Drupal\webform\Controller\WebformSubmissionController;
use Drupal\webform\Plugin\WebformElementManagerInterface;
use Drupal\webform\Utility\WebformArrayHelper;
use Drupal\webform\Utility\WebformDialogHelper;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Provides a list controller for webform submission entity.
 *
 * @ingroup webform
 */
class WebformSubmissionListBuilder extends EntityListBuilder {

  /**
   * Submission state starred.
   */
  const STATE_STARRED = 'starred';

  /**
   * Submission state unstarred.
   */
  const STATE_UNSTARRED = 'unstarred';

  /**
   * Submission state locked.
   */
  const STATE_LOCKED = 'locked';

  /**
   * Submission state unlocked.
   */
  const STATE_UNLOCKED = 'unlocked';

  /**
   * Submission state completed.
   */
  const STATE_COMPLETED = 'completed';

  /**
   * Submission state draft.
   */
  const STATE_DRAFT = 'draft';

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The route match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * The current request.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected $request;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * The configuration factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The webform request handler.
   *
   * @var \Drupal\webform\WebformRequestInterface
   */
  protected $requestHandler;

  /**
   * The webform element manager.
   *
   * @var \Drupal\webform\Plugin\WebformElementManagerInterface
   */
  protected $elementManager;

  /**
   * The webform message manager.
   *
   * @var \Drupal\webform\WebformMessageManagerInterface
   */
  protected $messageManager;

  /**
   * The webform.
   *
   * @var \Drupal\webform\WebformInterface
   */
  protected $webform;

  /**
   * The entity that a webform is attached to. Currently only applies to nodes.
   *
   * @var \Drupal\Core\Entity\EntityInterface
   */
  protected $sourceEntity;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $account;

  /**
   * Draft flag.
   *
   * @var bool
   */
  protected $draft;


  /**
   * The columns being displayed.
   *
   * @var array
   */
  protected $columns;

  /**
   * The table's header.
   *
   * @var array
   */
  protected $header;

  /**
   * The table's header and element format settings.
   *
   * @var array
   */
  protected $format = [
    'header_format' => 'label',
    'element_format' => 'value',
  ];

  /**
   * The submission link type. (canonical, table, or edit)
   *
   * @var array
   */
  protected $linkType = 'canonical';

  /**
   * The webform elements.
   *
   * @var array
   */
  protected $elements;

  /**
   * Search keys.
   *
   * @var string
   */
  protected $keys;

  /**
   * Sort by.
   *
   * @var string
   */
  protected $sort;

  /**
   * Search source entity.
   *
   * @var string
   */
  protected $sourceEntityTypeId;

  /**
   * Sort direction.
   *
   * @var string
   */
  protected $direction;

  /**
   * Total number of submissions.
   *
   * @var int
   */
  protected $total;

  /**
   * Search state.
   *
   * @var string
   */
  protected $state;

  /**
   * Track if table can be customized.
   *
   * @var bool
   */
  protected $customize;

  /**
   * The name of current submission view.
   *
   * @var string
   */
  protected $submissionView;

  /**
   * An associative array of submission views.
   *
   * @var string
   */
  protected $submissionViews;

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $entity_type,
      $container->get('entity_type.manager')->getStorage($entity_type->id()),
      $container->get('entity_type.manager'),
      $container->get('current_route_match'),
      $container->get('request_stack'),
      $container->get('current_user'),
      $container->get('config.factory'),
      $container->get('webform.request'),
      $container->get('plugin.manager.webform.element'),
      $container->get('webform.message_manager')
    );
  }

  /**
   * Constructs a new WebformSubmissionListBuilder object.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition.
   * @param \Drupal\Core\Entity\EntityStorageInterface $storage
   *   The entity storage class.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The current route match.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration factory.
   * @param \Drupal\webform\WebformRequestInterface $webform_request
   *   The webform request handler.
   * @param \Drupal\webform\Plugin\WebformElementManagerInterface $element_manager
   *   The webform element manager.
   * @param \Drupal\webform\WebformMessageManagerInterface $message_manager
   *   The webform message manager.
   */
  public function __construct(EntityTypeInterface $entity_type, EntityStorageInterface $storage, EntityTypeManagerInterface $entity_type_manager, RouteMatchInterface $route_match, RequestStack $request_stack, AccountInterface $current_user, ConfigFactoryInterface $config_factory, WebformRequestInterface $webform_request, WebformElementManagerInterface $element_manager, WebformMessageManagerInterface $message_manager) {
    parent::__construct($entity_type, $storage);
    $this->entityTypeManager = $entity_type_manager;
    $this->routeMatch = $route_match;
    $this->request = $request_stack->getCurrentRequest();
    $this->currentUser = $current_user;
    $this->configFactory = $config_factory;
    $this->requestHandler = $webform_request;
    $this->elementManager = $element_manager;
    $this->messageManager = $message_manager;

    $this->keys = $this->request->query->get('search');
    $this->state = $this->request->query->get('state');
    $this->sourceEntityTypeId = $this->request->query->get('entity');

    list($this->webform, $this->sourceEntity) = $this->requestHandler->getWebformEntities();

    $this->messageManager->setWebform($this->webform);
    $this->messageManager->setSourceEntity($this->sourceEntity);

    /** @var WebformSubmissionStorageInterface $webform_submission_storage */
    $webform_submission_storage = $this->getStorage();

    $route_name = $this->routeMatch->getRouteName();
    $base_route_name = ($this->webform) ? $this->requestHandler->getBaseRouteName($this->webform, $this->sourceEntity) : '';

    // Set account and state based on the current route.
    switch ($route_name) {
      case 'entity.webform_submission.user':
        $this->account = $this->routeMatch->getParameter('user');
        break;

      case "$base_route_name.webform.user.submissions":
        $this->account = $this->currentUser;
        $this->draft = FALSE;
        break;

      case "$base_route_name.webform.user.drafts":
        $this->account = $this->currentUser;
        $this->draft = TRUE;
        break;

      default:
        $this->account = NULL;
        break;
    }

    // Initialize submission views and view.
    $this->submissionView = $this->routeMatch->getParameter('submission_view') ?: '';
    $this->submissionViews = $this->getSubmissionViews();
    if ($this->submissionViews && empty($this->submissionView) && $this->isSubmissionViewResultsReplaced()) {
      $this->submissionView = WebformArrayHelper::getFirstKey($this->submissionViews);
    }

    // Check submission view access.
    if ($this->submissionView && !isset($this->submissionViews[$this->submissionView])) {
      $submission_views = $this->getSubmissionViewsConfig();
      if (isset($submission_views[$this->submissionView])) {
        throw new AccessDeniedHttpException();
      }
      else {
        throw new NotFoundHttpException();
      }
    }

    // If there is a submission view, we do not need to initialize
    // the entity list.
    if ($this->submissionView) {
      return;
    }

    // Set default display settings.
    $this->direction = 'desc';
    $this->limit = 20;
    $this->customize = FALSE;
    $this->sort = 'created';
    $this->total = $this->getTotal($this->keys, $this->state, $this->sourceEntityTypeId);

    switch ($route_name) {
      // Display webform submissions which includes properties and elements.
      // @see /admin/structure/webform/manage/{webform}/results/submissions
      // @see /node/{node}/webform/results/submissions
      case "$base_route_name.webform.results_submissions":
        $this->columns = $webform_submission_storage->getCustomColumns($this->webform, $this->sourceEntity, $this->account, TRUE);
        $this->sort = $this->getCustomSetting('sort', 'created');
        $this->direction = $this->getCustomSetting('direction', 'desc');
        $this->limit = $this->getCustomSetting('limit', 20);
        $this->format = $this->getCustomSetting('format', $this->format);
        $this->linkType = $this->getCustomSetting('link_type', $this->linkType);

        $this->customize = $this->webform->access('update')
          || $this->webform->getSetting('results_customize', TRUE);

        if ($this->format['element_format'] == 'raw') {
          foreach ($this->columns as &$column) {
            $column['format'] = 'raw';
            if (isset($column['element'])) {
              $column['element']['#format'] = 'raw';
            }
          }
        }
        break;

      // Display all submissions.
      // @see /admin/structure/webform/submissions/manage
      case 'entity.webform_submission.collection':
        // Display only submission properties.
        // @see /admin/structure/webform/submissions/manage
        $this->columns = $webform_submission_storage->getSubmissionsColumns();
        break;

      // Display user's submissions.
      // @see /user/{user}/submissions
      case 'entity.webform_submission.user':
        $this->columns = $webform_submission_storage->getUsersSubmissionsColumns();
        break;

      // Display user's submissions.
      // @see /webform/{webform}/submissions
      // @see /webform/{webform}/drafts
      // @see /node/{node}/webform/submissions
      // @see /node/{node}/webform/drafts
      case "$base_route_name.webform.user.drafts":
      case "$base_route_name.webform.user.submissions":
      default:
        $this->columns = $webform_submission_storage->getUserColumns($this->webform, $this->sourceEntity, $this->account, TRUE);
        break;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function render() {
    $build = [];

    // Set user specific page title.
    if ($this->webform && $this->account) {
      $t_args = [
        '%webform' => $this->webform->label(),
        '%user' => $this->account->getDisplayName(),
      ];
      if ($this->draft) {
        $build['#title'] = $this->t('Drafts for %webform for %user', $t_args);
      }
      else {
        $build['#title'] = $this->t('Submissions to %webform for %user', $t_args);
      }
    }
    elseif ($this->account) {
      $build['#title'] = $this->account->getDisplayName();
    }

    // Display warning when the webform has a submission but saving of results.
    // are disabled.
    if ($this->webform && $this->webform->getSetting('results_disabled')) {
      $this->messageManager->display(WebformMessageManagerInterface::FORM_SAVE_EXCEPTION, 'warning');
    }

    $build += $this->buildSubmissionViewsMenu();

    if ($this->submissionView) {
      $build += $this->buildSubmissionViews();
    }
    else {
      $build += $this->buildEntityList();
    }

    $build['#attached']['library'][] = 'webform/webform.admin';

    return $build;
  }

  /**
   * Build the webform submission view.
   *
   * @return array
   *   A renderable array containing a submission view.
   */
  protected function buildSubmissionViews() {
    $settings = $this->submissionViews[$this->submissionView];

    // Get view name and display id.
    list($name, $display_id) = explode(':', $settings['view']);

    // Load the view and set custom property used to fix the exposed
    // filter action.
    // @see webform_form_views_exposed_form_alter()
    $view = Views::getView($name);
    $view->webform_submission_view = TRUE;

    // Get the current display or default arguments.
    $displays = $view->storage->get('display');
    if (!empty($displays[$display_id]['display_options']['arguments'])) {
      $display_arguments = $displays[$display_id]['display_options']['arguments'];
    }
    elseif (!empty($displays['default']['display_options']['arguments'])) {
      $display_arguments = $displays['default']['display_options']['arguments'];
    }
    else {
      $display_arguments = [];
    }

    // Populate the views arguments.
    $arguments = [];
    foreach ($display_arguments as $argument_name => $display_argument) {
      if ($display_argument['table'] === 'webform_submission') {
        switch ($argument_name) {
          case 'webform_id':
            $arguments[] = (isset($this->webform)) ? $this->webform->id() : 'all';
            break;

          case 'entity_type':
            $arguments[] = (isset($this->sourceEntity)) ? $this->sourceEntity->getEntityTypeId() : 'all';
            break;

          case 'entity_id':
            $arguments[] = (isset($this->sourceEntity)) ? $this->sourceEntity->id() : 'all';
            break;

          case 'uid':
            $arguments[] = (isset($this->account)) ? $this->account->id() : 'all';
            break;

          case 'in_draft':
            $arguments[] = (isset($this->draft)) ? ($this->draft ? '1' : '0') : 'all';
            break;

          default:
            $arguments[] = 'all';
            break;
        }
      }
    }

    $build = [];
    $build['view'] = [
      '#type' => 'view',
      '#view' => $view,
      '#display_id' => $display_id,
      '#arguments' => $arguments,
    ];
    return $build;
  }

  /**
   * Build the webform submission entity list.
   *
   * @return array
   *   A renderable array containing the entity list.
   */
  protected function buildEntityList() {
    $build = [];

    // Filter form.
    if (empty($this->account)) {
      $build['filter_form'] = $this->buildFilterForm();
    }

    // Customize buttons.
    if ($this->customize) {
      $build['custom_top'] = $this->buildCustomizeButton();
    }

    // Display info.
    if ($this->total) {
      $build['info'] = $this->buildInfo();
    }

    // Table.
    $build += parent::render();
    $build['table']['#sticky'] = TRUE;
    $build['table']['#attributes']['class'][] = 'webform-results-table';

    // Customize.
    // Only displayed when more than 20 submissions are being displayed.
    if ($this->customize && isset($build['table']['#rows']) && count($build['table']['#rows']) >= 20) {
      $build['custom_bottom'] = $this->buildCustomizeButton();
      if (isset($build['pager'])) {
        $build['pager']['#weight'] = 10;
      }
    }

    // Must preload libraries required by (modal) dialogs.
    WebformDialogHelper::attachLibraries($build);

    return $build;
  }

  /**
   * Build the submission views menu.
   *
   * @return array
   *   A render array representing the submission views menu.
   */
  protected function buildSubmissionViewsMenu() {
    if (empty($this->submissionViews)) {
      return [];
    }

    $route_name = $this->routeMatch->getRouteName();
    $route_parameters = $this->routeMatch->getRawParameters()->all();
    unset($route_parameters['submission_view']);

    $links = [];
    if (!$this->isSubmissionViewResultsReplaced()) {
      $links['_default_'] = [
        'title' => $this->t('Submissions'),
        'url' => Url::fromRoute($route_name, $route_parameters),
      ];
    }
    foreach ($this->submissionViews as $name => $submission_view) {
      $links[$name] = [
        'title' => $submission_view['title'],
        'url' => Url::fromRoute($route_name, $route_parameters + ['submission_view' => $name]),
      ];
    }

    // Only display the submission views menu when there is more than 1 link.
    if (count($links) <= 1) {
      return [];
    }

    // Make sure the current submission view is first.
    if ($this->submissionView) {
      $links = [$this->submissionView => $links[$this->submissionView]] + $links;
    }

    $build = [];
    $build['submission_views'] = [
      '#type' => 'operations',
      '#links' => $links,
      '#prefix' => '<div class="webform-dropbutton webform-submission-views-dropbutton">',
      '#suffix' => '</div>' . ($this->submissionView ? '<p><hr/></p>' : ''),
    ];
    return $build;
  }

  /**
   * Build the filter form.
   *
   * @return array
   *   A render array representing the filter form.
   */
  protected function buildFilterForm() {
    // State options.
    $state_options = [
      '' => $this->t('All [@total]', ['@total' => $this->getTotal(NULL, NULL, $this->sourceEntityTypeId)]),
      self::STATE_STARRED => $this->t('Starred [@total]', ['@total' => $this->getTotal(NULL, self::STATE_STARRED, $this->sourceEntityTypeId)]),
      self::STATE_UNSTARRED => $this->t('Unstarred [@total]', ['@total' => $this->getTotal(NULL, self::STATE_UNSTARRED, $this->sourceEntityTypeId)]),
      self::STATE_LOCKED => $this->t('Locked [@total]', ['@total' => $this->getTotal(NULL, self::STATE_LOCKED, $this->sourceEntityTypeId)]),
      self::STATE_UNLOCKED => $this->t('Unlocked [@total]', ['@total' => $this->getTotal(NULL, self::STATE_UNLOCKED, $this->sourceEntityTypeId)]),
    ];
    // Add draft to state options.
    if (!$this->webform || $this->webform->getSetting('draft') != WebformInterface::DRAFT_NONE) {
      $state_options += [
        self::STATE_COMPLETED => $this->t('Completed [@total]', ['@total' => $this->getTotal(NULL, self::STATE_COMPLETED, $this->sourceEntityTypeId)]),
        self::STATE_DRAFT => $this->t('Draft [@total]', ['@total' => $this->getTotal(NULL, self::STATE_DRAFT, $this->sourceEntityTypeId)]),
      ];
    }

    // Source entity options.
    if ($this->webform && !$this->sourceEntity) {
      // < 100 source entities a select menuwill be displayed.
      // > 100 source entities an autocomplete input will be displayed.
      $source_entity_total = $this->storage->getSourceEntitiesTotal($this->webform);
      if ($source_entity_total < 100) {
        $source_entity_options = $this->storage->getSourceEntitiesAsOptions($this->webform);
        $source_entity_default_value = $this->sourceEntityTypeId;
      }
      elseif ($this->sourceEntityTypeId && strpos($this->sourceEntityTypeId, ':') !== FALSE) {
        $source_entity_options = $this->webform;
        try {
          list($source_entity_type, $source_entity_id) = explode(':', $this->sourceEntityTypeId);
          $source_entity = $this->entityTypeManager->getStorage($source_entity_type)->load($source_entity_id);
          $source_entity_default_value = $source_entity->label() . " ($source_entity_type:$source_entity_id)";
        }
        catch (\Exception $exception) {
          $source_entity_default_value = '';
        }
      }
      else {
        $source_entity_options = $this->webform;
        $source_entity_default_value = '';
      }
    }
    else {
      $source_entity_options = NULL;
      $source_entity_default_value = '';
    }

    return \Drupal::formBuilder()->getForm('\Drupal\webform\Form\WebformSubmissionFilterForm', $this->keys, $this->state, $state_options, $source_entity_default_value, $source_entity_options);
  }

  /**
   * Build the customize button.
   *
   * @return array
   *   A render array representing the customize button.
   */
  protected function buildCustomizeButton() {
    $results_customize = $this->webform->getSetting('results_customize', TRUE);

    $title = ($results_customize)
      ? $this->t('Customize my table')
      : $this->t('Customize');

    $route_name = $this->requestHandler->getRouteName(
      $this->webform,
      $this->sourceEntity,
      'webform.results_submissions.custom' . ($results_customize ? '.user' : '')
    );

    $route_parameters = $this->requestHandler->getRouteParameters(
      $this->webform,
      $this->sourceEntity
    );

    return [
      '#type' => 'link',
      '#title' => $title,
      '#url' => $this->ensureDestination(Url::fromRoute($route_name, $route_parameters)),
      '#attributes' => WebformDialogHelper::getModalDialogAttributes(WebformDialogHelper::DIALOG_NORMAL, ['button', 'button-action', 'button--small', 'button-webform-table-setting']),
    ];
  }

  /**
   * Build information summary.
   *
   * @return array
   *   A render array representing the information summary.
   */
  protected function buildInfo() {
    if ($this->draft) {
      $info = $this->formatPlural($this->total, '@total draft', '@total drafts', ['@total' => $this->total]);
    }
    else {
      $info = $this->formatPlural($this->total, '@total submission', '@total submissions', ['@total' => $this->total]);
    }
    return [
      '#markup' => $info,
      '#prefix' => '<div>',
      '#suffix' => '</div>',
    ];
  }

  /****************************************************************************/
  // Header functions.
  /****************************************************************************/

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    if (isset($this->header)) {
      return $this->header;
    }

    $responsive_priorities = [
      'created' => RESPONSIVE_PRIORITY_MEDIUM,
      'langcode' => RESPONSIVE_PRIORITY_LOW,
      'remote_addr' => RESPONSIVE_PRIORITY_LOW,
      'uid' => RESPONSIVE_PRIORITY_MEDIUM,
      'webform' => RESPONSIVE_PRIORITY_LOW,
    ];

    $header = [];
    foreach ($this->columns as $column_name => $column) {
      $header[$column_name] = $this->buildHeaderColumn($column);

      // Apply custom sorting to header.
      if ($column_name === $this->sort) {
        $header[$column_name]['sort'] = $this->direction;
      }

      // Apply responsive priorities to header.
      if (isset($responsive_priorities[$column_name])) {
        $header[$column_name]['class'][] = $responsive_priorities[$column_name];
      }
    }
    $this->header = $header;
    return $this->header;
  }

  /**
   * Build table header column.
   *
   * @param array $column
   *   The column.
   *
   * @return array
   *   A renderable array containing a table header column.
   *
   * @throws \Exception
   *   Throw exception if table header column is not found.
   */
  protected function buildHeaderColumn(array $column) {
    $name = $column['name'];
    if ($this->format['header_format'] == 'key') {
      $title = isset($column['key']) ? $column['key'] : $column['name'];
    }
    else {
      $title = $column['title'];
    }

    switch ($name) {
      case 'notes':
      case 'sticky':
      case 'locked':
        return [
          'data' => new FormattableMarkup('<span class="webform-icon webform-icon-@name webform-icon-@name--link"></span><span class="visually-hidden">@title</span> ', ['@name' => $name, '@title' => $title]),
          'class' => ['webform-results-table__icon'],
          'field' => $name,
          'specifier' => $name,
        ];

      default:
        if (isset($column['sort']) && $column['sort'] === FALSE) {
          return ['data' => $title];
        }
        else {
          return [
            'data' => $title,
            'field' => $name,
            'specifier' => $name,
          ];
        }
    }
  }

  /****************************************************************************/
  // Row functions.
  /****************************************************************************/

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $url = $this->requestHandler->getUrl($entity, $this->sourceEntity, $this->getSubmissionRouteName());
    $row = [
      'data' => [],
      'data-webform-href' => $url->toString(),
    ];
    foreach ($this->columns as $column_name => $column) {
      $row['data'][$column_name] = $this->buildRowColumn($column, $entity);
    }

    return $row;
  }

  /**
   * Build row column.
   *
   * @param array $column
   *   Column settings.
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   A webform submission.
   *
   * @return array|mixed
   *   The row column value or renderable array.
   *
   * @throws \Exception
   *   Throw exception if table row column is not found.
   */
  public function buildRowColumn(array $column, EntityInterface $entity) {
    /** @var \Drupal\webform\WebformSubmissionInterface $entity */

    $is_raw = ($column['format'] == 'raw');
    $name = $column['name'];

    switch ($name) {
      case 'created':
      case 'completed':
      case 'changed':
        /** @var \Drupal\Core\Datetime\DateFormatterInterface $date_formatter */
        $date_formatter = \Drupal::service('date.formatter');
        return ($is_raw ? $entity->{$name}->value :
          ($entity->{$name}->value ? $date_formatter->format($entity->{$name}->value) : '')
        );

      case 'entity':
        $source_entity = $entity->getSourceEntity();
        if (!$source_entity) {
          return '';
        }
        return ($is_raw) ? $source_entity->getEntityTypeId . ':' . $source_entity->id() : ($source_entity->hasLinkTemplate('canonical') ? $source_entity->toLink() : $source_entity->label());

      case 'langcode':
        $langcode = $entity->langcode->value;
        if (!$langcode) {
          return '';
        }
        if ($is_raw) {
          return $langcode;
        }
        else {
          $language = \Drupal::languageManager()->getLanguage($langcode);
          return ($language) ? $language->getName() : $langcode;
        }

      case 'notes':
        $notes_url = $this->ensureDestination($this->requestHandler->getUrl($entity, $entity->getSourceEntity(), 'webform_submission.notes_form'));
        $state = $entity->get('notes')->value ? 'on' : 'off';
        $t_args = ['@label' => $entity->label()];
        $label = $entity->get('notes')->value ? $this->t('Edit @label notes', $t_args) : $this->t('Add notes to @label', $t_args);
        return [
          'data' => [
            '#type' => 'link',
            '#title' => new FormattableMarkup('<span class="webform-icon webform-icon-notes webform-icon-notes--@state"></span><span class="visually-hidden">@label</span>', ['@state' => $state, '@label' => $label]),
            '#url' => $notes_url,
            '#attributes' => WebformDialogHelper::getModalDialogAttributes(WebformDialogHelper::DIALOG_NARROW),
          ],
          'class' => ['webform-results-table__icon'],
        ];

      case 'operations':
        return ['data' => $this->buildOperations($entity), 'class' => ['webform-dropbutton-wrapper']];

      case 'remote_addr':
        return $entity->getRemoteAddr();

      case 'sid':
        return $entity->id();

      case 'serial':
      case 'label':
        // Note: Use the submission's token URL which points to the
        // submission's source URL with a secure token.
        // @see \Drupal\webform\Entity\WebformSubmission::getTokenUrl
        if ($entity->isDraft()) {
          $link_url = $entity->getTokenUrl();
        }
        else {
          $link_url = $this->requestHandler->getUrl($entity, $entity->getSourceEntity(), $this->getSubmissionRouteName());
        }
        if ($name == 'serial') {
          $link_text = $entity->serial();
        }
        else {
          $link_text = $entity->label();
        }
        $link = Link::fromTextAndUrl($link_text, $link_url)->toRenderable();
        if ($name == 'serial') {
          $link['#attributes']['title'] = $entity->label();
          $link['#attributes']['aria-label'] = $entity->label();
        }
        if ($entity->isDraft()) {
          $link['#suffix'] = ' (' . $this->t('draft') . ')';
        }
        return ['data' => $link];

      case 'in_draft':
        return ($entity->isDraft()) ? $this->t('Yes') : $this->t('No');

      case 'sticky':
        // @see \Drupal\webform\Controller\WebformSubmissionController::sticky
        $route_name = 'entity.webform_submission.sticky_toggle';
        $route_parameters = ['webform' => $entity->getWebform()->id(), 'webform_submission' => $entity->id()];
        return [
          'data' => [
            '#type' => 'link',
            '#title' => WebformSubmissionController::buildSticky($entity),
            '#url' => Url::fromRoute($route_name, $route_parameters),
            '#attributes' => [
              'id' => 'webform-submission-' . $entity->id() . '-sticky',
              'class' => ['use-ajax'],
            ],
          ],
          'class' => ['webform-results-table__icon'],
        ];

      case 'locked':
        // @see \Drupal\webform\Controller\WebformSubmissionController::locked
        $route_name = 'entity.webform_submission.locked_toggle';
        $route_parameters = ['webform' => $entity->getWebform()->id(), 'webform_submission' => $entity->id()];
        return [
          'data' => [
            '#type' => 'link',
            '#title' => WebformSubmissionController::buildLocked($entity),
            '#url' => Url::fromRoute($route_name, $route_parameters),
            '#attributes' => [
              'id' => 'webform-submission-' . $entity->id() . '-locked',
              'class' => ['use-ajax'],
            ],
          ],
          'class' => ['webform-results-table__icon'],
        ];

      case 'uid':
        return ($is_raw) ? $entity->getOwner()->id() : ($entity->getOwner()->getAccountName() ?: t('Anonymous'));

      case 'uuid':
        return $entity->uuid();

      case 'webform_id':
        return ($is_raw) ? $entity->getWebform()->id() : $entity->getWebform()->toLink();

      default:
        if (strpos($name, 'element__') === 0) {
          $element = $column['element'];
          $options = $column;

          /** @var \Drupal\webform\Plugin\WebformElementInterface $element_plugin */
          $element_plugin = $column['plugin'];
          $html = $element_plugin->formatTableColumn($element, $entity, $options);
          return (is_array($html)) ? ['data' => $html] : $html;
        }
        else {
          return '';
        }
    }
  }

  /****************************************************************************/
  // Operations.
  /****************************************************************************/

  /**
   * {@inheritdoc}
   */
  public function buildOperations(EntityInterface $entity) {
    return parent::buildOperations($entity) + [
      '#prefix' => '<div class="webform-dropbutton">',
      '#suffix' => '</div>',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultOperations(EntityInterface $entity) {
    /** @var \Drupal\webform\WebformInterface $webform */
    $webform = $entity->getWebform();

    $operations = [];

    if ($this->account) {
      if ($entity->access('update')) {
        $operations['edit'] = [
          'title' => $this->t('Edit'),
          'weight' => 10,
          'url' => $this->requestHandler->getUrl($entity, $this->sourceEntity, 'webform.user.submission.edit'),
        ];
      }

      if ($entity->access('view')) {
        $operations['view'] = [
          'title' => $this->t('View'),
          'weight' => 20,
          'url' => $this->requestHandler->getUrl($entity, $this->sourceEntity, 'webform.user.submission'),
        ];
      }

      if ($entity->access('duplicate') && $webform->getSetting('submission_user_duplicate')) {
        $operations['duplicate'] = [
          'title' => $this->t('Duplicate'),
          'weight' => 23,
          'url' => $this->requestHandler->getUrl($entity, $this->sourceEntity, 'webform.user.submission.duplicate'),
        ];
      }

      if ($entity->access('delete')) {
        $operations['delete'] = [
          'title' => $this->t('Delete'),
          'weight' => 100,
          'url' => $this->requestHandler->getUrl($entity, $this->sourceEntity, 'webform.user.submission.delete'),
          'attributes' => WebformDialogHelper::getModalDialogAttributes(WebformDialogHelper::DIALOG_NARROW),
        ];
      }
    }
    else {
      if ($entity->access('update')) {
        $operations['edit'] = [
          'title' => $this->t('Edit'),
          'weight' => 10,
          'url' => $this->requestHandler->getUrl($entity, $this->sourceEntity, 'webform_submission.edit_form'),
        ];
      }

      if ($entity->access('view')) {
        $operations['view'] = [
          'title' => $this->t('View'),
          'weight' => 20,
          'url' => $this->requestHandler->getUrl($entity, $this->sourceEntity, 'webform_submission.canonical'),
        ];
      }

      if ($entity->access('notes')) {
        $operations['notes'] = [
          'title' => $this->t('Notes'),
          'weight' => 21,
          'url' => $this->requestHandler->getUrl($entity, $this->sourceEntity, 'webform_submission.notes_form'),
        ];
      }

      if ($entity->access('resend') && $webform->hasMessageHandler()) {
        $operations['resend'] = [
          'title' => $this->t('Resend'),
          'weight' => 22,
          'url' => $this->requestHandler->getUrl($entity, $this->sourceEntity, 'webform_submission.resend_form'),
        ];
      }
      if ($entity->access('duplicate')) {
        $operations['duplicate'] = [
          'title' => $this->t('Duplicate'),
          'weight' => 23,
          'url' => $this->requestHandler->getUrl($entity, $this->sourceEntity, 'webform_submission.duplicate_form'),
        ];
      }

      if ($entity->access('delete')) {
        $operations['delete'] = [
          'title' => $this->t('Delete'),
          'weight' => 100,
          'url' => $this->requestHandler->getUrl($entity, $this->sourceEntity, 'webform_submission.delete_form'),
          'attributes' => WebformDialogHelper::getModalDialogAttributes(WebformDialogHelper::DIALOG_NARROW),
        ];
      }

      if ($entity->access('view_any')
        && $this->currentUser->hasPermission('access webform submission log')
        && $webform->hasSubmissionLog()
        && $this->moduleHandler->moduleExists('webform_submission_log')) {
        $operations['log'] = [
          'title' => $this->t('Log'),
          'weight' => 100,
          'url' => $this->requestHandler->getUrl($entity, $this->sourceEntity, 'webform_submission.log'),
        ];
      }
    }

    // Add destination to all operation links.
    foreach ($operations as &$operation) {
      $this->ensureDestination($operation['url']);
    }

    return $operations;
  }

  /****************************************************************************/
  // Route functions.
  /****************************************************************************/

  /**
   * Get submission route name based on the current route.
   *
   * @return string
   *   The submission route name which can be either 'webform.user.submission'
   *   or 'webform_submission.canonical.
   */
  protected function getSubmissionRouteName() {
    return (strpos($this->routeMatch->getRouteName(), 'webform.user.submissions') !== FALSE) ? 'webform.user.submission' : 'webform_submission.' . $this->linkType;
  }

  /**
   * Get base route name for the webform or webform source entity.
   *
   * @return string
   *   The base route name for webform or webform source entity.
   */
  protected function getBaseRouteName() {
    return $this->requestHandler->getBaseRouteName($this->webform, $this->sourceEntity);
  }

  /**
   * Get route parameters for the webform or webform source entity.
   *
   * @param \Drupal\webform\WebformSubmissionInterface $webform_submission
   *   A webform submission.
   *
   * @return array
   *   Route parameters for the webform or webform source entity.
   */
  protected function getRouteParameters(WebformSubmissionInterface $webform_submission) {
    $route_parameters = ['webform_submission' => $webform_submission->id()];
    if ($this->sourceEntity) {
      $route_parameters[$this->sourceEntity->getEntityTypeId()] = $this->sourceEntity->id();
    }
    return $route_parameters;
  }

  /****************************************************************************/
  // Submission views functions.
  /****************************************************************************/

  /**
   * Get the submission view type for the current route.
   *
   * @return string
   *   The submission view type for the current route.
   */
  protected function getSubmissionViewType() {
    if (!$this->webform) {
      return 'global';
    }
    elseif ($this->sourceEntity && $this->sourceEntity->getEntityTypeId() === 'node') {
      return 'node';
    }
    else {
      return 'webform';
    }
  }

  /**
   * Determine if the submission view(s) replaced the default results table.
   *
   * @return bool
   *   TRUE if the submission view(s) replaced the default results table.
   */
  protected function isSubmissionViewResultsReplaced() {
    $type = $this->getSubmissionViewType();

    $replace_routes = [];

    $default_replace = $this->configFactory->get('webform.settings')->get('settings.default_submission_views_replace') ?: [];
    if (isset($default_replace[$type . '_routes'])) {
      $replace_routes = array_merge($replace_routes, $default_replace[$type . '_routes']);
    }

    if ($this->webform) {
      $webform_replace = $this->webform->getSetting('submission_views_replace') ?: [];
      if (isset($webform_replace[$type . '_routes'])) {
        $replace_routes = array_merge($replace_routes, $webform_replace[$type . '_routes']);
      }
    }

    $route_name = $this->routeMatch->getRouteName();
    return ($replace_routes && in_array($route_name, $replace_routes)) ? TRUE : FALSE;
  }

  /**
   * Get all submission views applicable.
   *
   * @return array
   *   An associative array of all submission views.
   */
  protected function getSubmissionViewsConfig() {
    // Merge webform submission views with global submission views.
    $submission_views = [];
    if ($this->webform) {
      $submission_views += $this->webform->getSetting('submission_views') ?: [];
    }
    $submission_views += $this->configFactory->get('webform.settings')->get('settings.default_submission_views') ?: [];
    return $submission_views;
  }

  /**
   * Get submission views applicable for the current route and user.
   *
   * @return array
   *   An associative array of submission views applicable for the
   *   current route and user.
   */
  protected function getSubmissionViews() {
    if (!$this->moduleHandler()->moduleExists('views')) {
      return [];
    }

    $type = $this->getSubmissionViewType();
    $route_name = $this->routeMatch->getRouteName();

    $submission_views = $this->getSubmissionViewsConfig();
    foreach ($submission_views as $name => $submission_view) {
      $submission_view += [
        'global_routes' => [],
        'webform_routes' => [],
        'node_routes' => [],
      ];

      // Check global, webform, or node routes.
      $routes = $submission_view[$type . '_routes'];
      if (empty($routes) || !in_array($route_name, $routes)) {
        unset($submission_views[$name]);
        continue;
      }

      list($view_name, $view_display_id) = explode(':', $submission_view['view']);
      $view = Views::getView($view_name);
      if (!$view || !$view->access($view_display_id)) {
        unset($submission_views[$name]);
        continue;
      }
    }

    return $submission_views;
  }

  /****************************************************************************/
  // Query functions.
  /****************************************************************************/

  /**
   * {@inheritdoc}
   */
  protected function getEntityIds() {
    $query = $this->getQuery($this->keys, $this->state, $this->sourceEntityTypeId);
    $query->pager($this->limit);

    $header = $this->buildHeader();
    $order = TableSort::getOrder($header, $this->request);
    $direction = TableSort::getSort($header, $this->request);

    // If query is order(ed) by 'element__*' we need to build a custom table
    // sort using hook_query_TAG_alter().
    // @see webform_query_webform_submission_list_builder_alter()
    if ($order && strpos($order['sql'], 'element__') === 0) {
      $name = $order['sql'];
      $column = $this->columns[$name];
      $query->addTag('webform_submission_list_builder')
        ->addMetaData('webform_submission_element_name', $column['key'])
        ->addMetaData('webform_submission_element_property_name', $column['property_name'])
        ->addMetaData('webform_submission_element_direction', $direction);
      $result = $query->execute();
      // Must manually initialize the pager because the DISTINCT clause in the
      // query is breaking the row counting.
      // @see webform_query_alter()
      pager_default_initialize($this->total, $this->limit);
      return $result;
    }
    else {
      if ($order && $order['sql']) {
        $query->tableSort($header);
      }
      else {
        // If no order is specified, make sure the first column is sortable,
        // else default sorting to the sid.
        // @see \Drupal\Core\Entity\Query\QueryBase::tableSort
        // @see tablesort_get_order()
        $default = reset($header);
        if (isset($default['specified'])) {
          $query->tableSort($header);
        }
        else {
          $query->sort('sid', 'DESC');
        }
      }
      return $query->execute();
    }
  }

  /**
   * Get the total number of submissions.
   *
   * @param string $keys
   *   (optional) Search key.
   * @param string $state
   *   (optional) Submission state.
   * @param string $source_entity
   *   (optional) Source entity (type:id).
   *
   * @return int
   *   The total number of submissions.
   */
  protected function getTotal($keys = '', $state = '', $source_entity = '') {
    return $this->getQuery($keys, $state, $source_entity)
      ->count()
      ->execute();
  }

  /**
   * Get the base entity query filtered by webform and search.
   *
   * @param string $keys
   *   (optional) Search key.
   * @param string $state
   *   (optional) Submission state.
   * @param string $source_entity
   *   (optional) Source entity (type:id).
   *
   * @return \Drupal\Core\Entity\Query\QueryInterface
   *   An entity query.
   */
  protected function getQuery($keys = '', $state = '', $source_entity = '') {
    /** @var \Drupal\webform\WebformSubmissionStorageInterface $submission_storage */
    $submission_storage = $this->getStorage();
    $query = $submission_storage->getQuery();
    $submission_storage->addQueryConditions($query, $this->webform, $this->sourceEntity, $this->account);

    // Filter by key(word).
    if ($keys) {
      // Search values.
      $sub_query = Database::getConnection()->select('webform_submission_data', 'sd')
        ->fields('sd', ['sid'])
        ->condition('value', '%' . $keys . '%', 'LIKE');
      $submission_storage->addQueryConditions($sub_query, $this->webform);

      // Search UUID and Notes.
      $or_condition = $query->orConditionGroup();
      $or_condition->condition('notes', '%' . $keys . '%', 'LIKE');
      // Only search UUID if keys is alphanumeric with dashes.
      // @see Issue #2978420: Error SQL with accent mark submissions filter.
      if (preg_match('/^[0-9a-z-]+$/', $keys)) {
        $or_condition->condition('uuid', $keys);
      }
      $query->condition(
        $query->orConditionGroup()
          ->condition('sid', $sub_query, 'IN')
          ->condition($or_condition)
      );
    }

    // Filter by (submission) state.
    switch ($state) {
      case self::STATE_STARRED:
        $query->condition('sticky', 1);
        break;

      case self::STATE_UNSTARRED:
        $query->condition('sticky', 0);
        break;

      case self::STATE_LOCKED:
        $query->condition('locked', 1);
        break;

      case self::STATE_UNLOCKED:
        $query->condition('locked', 0);
        break;

      case self::STATE_DRAFT:
        $query->condition('in_draft', 1);
        break;

      case self::STATE_COMPLETED:
        $query->condition('in_draft', 0);
        break;
    }

    // Filter by source entity.
    if ($source_entity && strpos($source_entity, ':') !== FALSE) {
      list($entity_type, $entity_id) = explode(':', $source_entity);
      $query->condition('entity_type', $entity_type);
      $query->condition('entity_id', $entity_id);
    }

    // Filter by draft. (Only applies to user submissions and drafts)
    if (isset($this->draft)) {
      // Cast boolean to integer to support SQLite.
      $query->condition('in_draft', (int) $this->draft);
    }

    return $query;
  }

  /**
   * Get custom setting.
   *
   * @param string $name
   *   The custom setting name.
   * @param mixed $default
   *   Default custom setting value.
   *
   * @return mixed
   *   The custom setting value.
   */
  protected function getCustomSetting($name, $default = NULL) {
    /** @var WebformSubmissionStorageInterface $webform_submission_storage */
    $webform_submission_storage = $this->getStorage();
    return $webform_submission_storage->getCustomSetting($name, $default, $this->webform, $this->sourceEntity);
  }

}
