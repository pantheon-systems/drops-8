<?php

namespace Drupal\webform;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Database\Database;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\webform\Utility\WebformDialogHelper;

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
   * Submission state completed.
   */
  const STATE_COMPLETED = 'completed';

  /**
   * Submission state draft.
   */
  const STATE_DRAFT = 'draft';

  /**
   * The webform request handler.
   *
   * @var \Drupal\webform\WebformRequestInterface
   */
  protected $requestHandler;

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
   * Sort direction.
   *
   * @var string
   */
  protected $direction;

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
   * The webform element manager.
   *
   * @var \Drupal\webform\Plugin\WebformElementManagerInterface
   */
  protected $elementManager;

  /**
   * {@inheritdoc}
   */
  public function __construct(EntityTypeInterface $entity_type, EntityStorageInterface $storage) {
    parent::__construct($entity_type, $storage);

    $this->requestHandler = \Drupal::service('webform.request');

    $this->keys = \Drupal::request()->query->get('search');
    $this->state = \Drupal::request()->query->get('state');

    list($this->webform, $this->sourceEntity) = $this->requestHandler->getWebformEntities();

    $base_route_name = ($this->webform) ? $this->requestHandler->getBaseRouteName($this->webform, $this->sourceEntity) : '';
    if (in_array(\Drupal::routeMatch()->getRouteName(), ["$base_route_name.webform.user.submissions", "$base_route_name.webform.user.drafts"])) {
      $this->account = \Drupal::currentUser();
      // Set submission filter so that we can support user.submissions and
      // user.drafts routes.
      $this->state = (\Drupal::routeMatch()->getRouteName() === "$base_route_name.webform.user.submissions") ? self::STATE_COMPLETED : self::STATE_DRAFT;
    }
    else {
      $this->account = NULL;
    }

    $this->elementManager = \Drupal::service('plugin.manager.webform.element');

    /** @var \Drupal\webform\WebformMessageManagerInterface $message_manager */
    $this->messageManager = \Drupal::service('webform.message_manager');
    $this->messageManager->setWebform($this->webform);
    $this->messageManager->setSourceEntity($this->sourceEntity);

    /** @var WebformSubmissionStorageInterface $webform_submission_storage */
    $webform_submission_storage = $this->getStorage();

    $route_name = \Drupal::routeMatch()->getRouteName();
    if ($route_name == "$base_route_name.webform.results_submissions") {
      // Display submission properties and elements.
      // @see /admin/structure/webform/manage/{webform}/results/submissions
      // @see /node/{node}/webform/results/submissions
      $this->columns = $webform_submission_storage->getCustomColumns($this->webform, $this->sourceEntity, $this->account, TRUE);
      $this->sort = $webform_submission_storage->getCustomSetting('sort', 'serial', $this->webform, $this->sourceEntity);
      $this->direction = $webform_submission_storage->getCustomSetting('direction', 'desc', $this->webform, $this->sourceEntity);
      $this->limit = $webform_submission_storage->getCustomSetting('limit', 50, $this->webform, $this->sourceEntity);
      $this->format = $webform_submission_storage->getCustomSetting('format', $this->format, $this->webform, $this->sourceEntity);
      $this->customize = TRUE;
      if ($this->format['element_format'] == 'raw') {
        foreach ($this->columns as &$column) {
          $column['format'] = 'raw';
          if (isset($column['element'])) {
            $column['element']['#format'] = 'raw';
          }
        }
      }
    }
    else {
      if ($route_name == 'entity.webform_submission.collection') {
        // Display only submission properties.
        // @see /admin/structure/webform/submissions/manage
        $this->columns = $webform_submission_storage->getDefaultColumns($this->webform, $this->sourceEntity, $this->account, FALSE);
        // Replace serial with sid when showing results from all webforms.
        unset($this->columns['serial']);
        $this->columns = [
            'sid' => [
              'title' => $this->t('SID'),
              'name' => 'sid',
              'format' => 'value',
            ],
          ] + $this->columns;
        $this->sort = 'sid';
      }
      else {
        $this->columns = $webform_submission_storage->getUserColumns($this->webform, $this->sourceEntity, $this->account, TRUE);
        unset($this->columns['sid']);
        $this->sort = 'serial';
      }
      $this->direction = 'desc';
      $this->limit = 50;
      $this->customize = FALSE;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function render() {
    // Set user specific page title.
    if ($this->webform && $this->account) {
      $t_args = [
        '%webform' => $this->webform->label(),
        '%user' => $this->account->getDisplayName(),
      ];
      if ($this->state == self::STATE_DRAFT) {
        $build['#title'] = $this->t('Drafts for %webform for %user', $t_args);
      }
      else {
        $build['#title'] = $this->t('Submissions to %webform for %user', $t_args);
      }
    }

    // Display warning when the webform has a submission but saving of results.
    // are disabled.
    if ($this->webform && $this->webform->getSetting('results_disabled')) {
      $this->messageManager->display(WebformMessageManagerInterface::FORM_SAVE_EXCEPTION, 'warning');
    }

    // Add the filter.
    if (empty($this->account)) {
      $state_options = [
        '' => $this->t('All [@total]', ['@total' => $this->getTotal(NULL, NULL)]),
        self::STATE_STARRED => $this->t('Starred [@total]', ['@total' => $this->getTotal(NULL, self::STATE_STARRED)]),
        self::STATE_UNSTARRED => $this->t('Unstarred [@total]', ['@total' => $this->getTotal(NULL, self::STATE_UNSTARRED)]),
      ];
      // Add draft to state options.
      if (!$this->webform || $this->webform->getSetting('draft') != WebformInterface::DRAFT_NONE) {
        $state_options += [
          self::STATE_COMPLETED => $this->t('Completed [@total]', ['@total' => $this->getTotal(NULL, self::STATE_COMPLETED)]),
          self::STATE_DRAFT => $this->t('Draft [@total]', ['@total' => $this->getTotal(NULL, self::STATE_DRAFT)]),
        ];
      }
      $build['filter_form'] = \Drupal::formBuilder()
        ->getForm('\Drupal\webform\Form\WebformSubmissionFilterForm', $this->keys, $this->state, $state_options);
    }

    // Customize.
    if ($this->customize) {
      $build['custom_top'] = $this->buildCustomizeButton();
    }

    // Display info.
    if ($total = $this->getTotal($this->keys, $this->state)) {
      if ($this->account && $this->state == self::STATE_DRAFT) {
        $info = $this->formatPlural($total, '@total draft', '@total drafts', ['@total' => $total]);
      }
      else {
        $info = $this->formatPlural($total, '@total submission', '@total submissions', ['@total' => $total]);
      }
      $build['info'] = [
        '#markup' => $info,
        '#prefix' => '<div>',
        '#suffix' => '</div>',
      ];
    }

    $build += parent::render();

    // Customize.
    // Only displayed when more than 20 submissions are being displayed.
    if ($this->customize && isset($build['table']['#rows']) && count($build['table']['#rows']) >= 20) {
      $build['custom_bottom'] = $this->buildCustomizeButton();
      if (isset($build['pager'])) {
        $build['pager']['#weight'] = 10;
      }
    }

    $build['table']['#attributes']['class'][] = 'webform-results__table';

    $build['#attached']['library'][] = 'webform/webform.admin';

    // Must preload libraries required by (modal) dialogs.
    WebformDialogHelper::attachLibraries($build);

    return $build;
  }

  /**
   * Build the customize button.
   *
   * @return array
   *   A render array representing the customize button.
   */
  protected function buildCustomizeButton() {
    $route_name = $this->requestHandler->getRouteName($this->webform, $this->sourceEntity, 'webform.results_submissions.custom');
    $route_parameters = $this->requestHandler->getRouteParameters($this->webform, $this->sourceEntity) + ['webform' => $this->webform->id()];
    $route_options = ['query' => \Drupal::destination()->getAsArray()];
    return [
      '#type' => 'link',
      '#title' => $this->t('Customize'),
      '#url' => Url::fromRoute($route_name, $route_parameters, $route_options),
      '#attributes' => WebformDialogHelper::getModalDialogAttributes(800, ['button', 'button-action', 'button--small', 'button-webform-table-setting']),
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
        return [
          'data' => new FormattableMarkup('<span class="webform-icon webform-icon-@name webform-icon-@name--link"></span>', ['@name' => $name]),
          'class' => ['webform-results__icon'],
          'field' => 'sticky',
          'specifier' => 'sticky',
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
    $is_raw = ($column['format'] == 'raw');
    $name = $column['name'];

    switch ($name) {
      case 'created':
      case 'completed':
      case 'changed':
        return ($is_raw) ? $entity->created->value : $entity->created->value ? \Drupal::service('date.formatter')->format($entity->created->value) : '';

      case 'entity':
        $source_entity = $entity->getSourceEntity();
        if (!$source_entity) {
          return '';
        }
        return ($is_raw) ? $source_entity->getEntityTypeId . ':' . $source_entity->id() : ($source_entity->hasLinkTemplate('canonical') ? $source_entity->toLink() : '');

      case 'langcode':
        return ($is_raw) ? $entity->langcode->value : \Drupal::languageManager()->getLanguage($entity->langcode->value)->getName();

      case 'notes':
        $notes_url = $this->requestHandler->getUrl($entity, $entity->getSourceEntity(), 'webform_submission.notes_form', ['query' => \Drupal::destination()->getAsArray()]);
        $state = $entity->get('notes')->value ? 'on' : 'off';
        return [
          'data' => [
            '#type' => 'link',
            '#title' => new FormattableMarkup('<span class="webform-icon webform-icon-notes webform-icon-notes--@state"></span>', ['@state' => $state]),
            '#url' => $notes_url,
            '#attributes' => WebformDialogHelper::getModalDialogAttributes(700),
          ],
          'class' => ['webform-results__icon'],
        ];

      case 'operations':
        return ['data' => $this->buildOperations($entity)];

      case 'remote_addr':
        return $entity->getRemoteAddr();

      case 'sid':
        return $entity->id();

      case 'serial':
      case 'label':
        // Note: Using source entity associate with the submission and not
        // the current webform.
        if ($entity->isDraft()) {
          if ($entity->getSourceEntity()  && $entity->getSourceEntity()->hasLinkTemplate('canonical')) {
            $link_url = $entity->getSourceEntity()->toUrl('canonical', ['query' => ['token' => $entity->getToken()]]);
          }
          else {
            $link_url = $this->webform->toUrl('canonical', ['query' => ['token' => $entity->getToken()]]);
          }
        }
        else {
          $link_url = $this->requestHandler->getUrl($entity, $entity->getSourceEntity(), $this->getSubmissionRouteName());
        }
        if ($name == 'serial') {
          $link_text = $entity->serial() . ($entity->isDraft() ? ' (' . $this->t('draft') . ')' : '');
        }
        else {
          $link_text = $entity->label();
        }
        return Link::fromTextAndUrl($link_text, $link_url);

      case 'in_draft':
        return ($entity->isDraft()) ? $this->t('Yes') : $this->t('No');

      case 'sticky':
        $route_name = 'entity.webform_submission.sticky_toggle';
        $route_parameters = ['webform' => $entity->getWebform()->id(), 'webform_submission' => $entity->id()];
        $state = $entity->isSticky() ? 'on' : 'off';
        return [
          'data' => [
            '#type' => 'link',
            '#title' => new FormattableMarkup('<span class="webform-icon webform-icon-sticky webform-icon-sticky--@state"></span>', ['@state' => $state]),
            '#url' => Url::fromRoute($route_name, $route_parameters),
            '#attributes' => [
              'id' => 'webform-submission-' . $entity->id() . '-sticky',
              'class' => ['use-ajax'],
            ],
          ],
          'class' => ['webform-results__icon'],
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
    $route_options = ['query' => \Drupal::destination()->getAsArray()];

    $operations = [];

    if ($entity->access('update')) {
      $operations['edit'] = [
        'title' => $this->t('Edit'),
        'weight' => 10,
        'url' => $this->requestHandler->getUrl($entity, $this->sourceEntity, 'webform_submission.edit_form', $route_options),
      ];
    }

    if ($entity->access('view')) {
      $operations['view'] = [
        'title' => $this->t('View'),
        'weight' => 20,
        'url' => $this->requestHandler->getUrl($entity, $this->sourceEntity, 'webform_submission.canonical', $route_options),
      ];
    }

    if ($entity->access('update')) {
      $operations['notes'] = [
        'title' => $this->t('Notes'),
        'weight' => 21,
        'url' => $this->requestHandler->getUrl($entity, $this->sourceEntity, 'webform_submission.notes_form', $route_options),
      ];
      $operations['resend'] = [
        'title' => $this->t('Resend'),
        'weight' => 22,
        'url' => $this->requestHandler->getUrl($entity, $this->sourceEntity, 'webform_submission.resend_form', $route_options),
      ];
      $operations['duplicate'] = [
        'title' => $this->t('Duplicate'),
        'weight' => 23,
        'url' => $this->requestHandler->getUrl($entity, $this->sourceEntity, 'webform_submission.duplicate_form', $route_options),
      ];
    }

    if ($entity->access('delete')) {
      $operations['delete'] = [
        'title' => $this->t('Delete'),
        'weight' => 100,
        'url' => $this->requestHandler->getUrl($entity, $this->sourceEntity, 'webform_submission.delete_form', $route_options),
      ];
    }

    if ($entity->access('view_any') && \Drupal::currentUser()->hasPermission('access webform submission log')) {
      $operations['log'] = [
        'title' => $this->t('Log'),
        'weight' => 100,
        'url' => $this->requestHandler->getUrl($entity, $this->sourceEntity, 'webform_submission.log', $route_options),
      ];
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
    return (strpos(\Drupal::routeMatch()->getRouteName(), 'webform.user.submissions') !== FALSE) ? 'webform.user.submission' : 'webform_submission.canonical';
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
  // Query functions.
  /****************************************************************************/

  /**
   * {@inheritdoc}
   */
  protected function getEntityIds() {
    $query = $this->getQuery($this->keys, $this->state);
    $query->pager($this->limit);

    $header = $this->buildHeader();
    $order = tablesort_get_order($header);
    $direction = tablesort_get_sort($header);

    // If query is order(ed) by 'element__*' we need to build a custom table
    // sort using hook_query_alter().
    // @see: webform_query_alter()
    if ($order && strpos($order['sql'], 'element__') === 0) {
      $name = $order['sql'];
      $column = $this->columns[$name];
      $query->addMetaData('webform_submission_element_name', $column['key']);
      $query->addMetaData('webform_submission_element_property_name', $column['property_name']);
      $query->addMetaData('webform_submission_element_direction', $direction);
    }
    else {
      $order = \Drupal::request()->query->get('order', '');
      if ($order) {
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
    }

    return $query->execute();
  }

  /**
   * Get the total number of submissions.
   *
   * @param string $keys
   *   (optional) Search key.
   * @param string $state
   *   (optional) Submission state.
   *
   * @return int
   *   The total number of submissions.
   */
  protected function getTotal($keys = '', $state = '') {
    return $this->getQuery($keys, $state)
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
   *
   * @return \Drupal\Core\Entity\Query\QueryInterface
   *   An entity query.
   */
  protected function getQuery($keys = '', $state = '') {
    /** @var \Drupal\webform\WebformSubmissionStorageInterface $submission_storage */
    $submission_storage = $this->getStorage();
    $query = $submission_storage->getQuery();
    $submission_storage->addQueryConditions($query, $this->webform, $this->sourceEntity, $this->account);

    // Filter by key(word).
    if ($keys) {
      $sub_query = Database::getConnection()->select('webform_submission_data', 'sd')
        ->fields('sd', ['sid'])
        ->condition('value', '%' . $keys . '%', 'LIKE');
      $submission_storage->addQueryConditions($sub_query, $this->webform);

      $or = $query->orConditionGroup()
        ->condition('sid', $sub_query, 'IN')
        ->condition('notes', '%' . $keys . '%', 'LIKE');

      $query->condition($or);
    }

    // Filter by (submission) state.
    switch ($state) {
      case self::STATE_STARRED:
        $query->condition('sticky', 1);
        break;

      case self::STATE_UNSTARRED:
        $query->condition('sticky', 0);
        break;

      case self::STATE_DRAFT:
        $query->condition('in_draft', 1);
        break;

      case self::STATE_COMPLETED:
        $query->condition('in_draft', 0);
        break;
    }

    return $query;
  }

}
