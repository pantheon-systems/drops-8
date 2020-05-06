<?php

namespace Drupal\webform\Form;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\webform\Utility\WebformDialogHelper;
use Drupal\webform\WebformRequestInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Webform for webform results custom(ize) webform.
 */
class WebformResultsCustomForm extends FormBase {

  /**
   * Customize results default type.
   */
  const CUSTOMIZE_DEFAULT = 'default';

  /**
   * Customize results user type.
   */
  const CUSTOMIZE_USER = 'user';

  /**
   * Result customize.
   *
   * @var bool
   */
  protected $customize = FALSE;

  /**
   * Result custom setting type.
   *
   * @var string
   */
  protected $type = self::CUSTOMIZE_DEFAULT;

  /**
   * Result custom setting names.
   *
   * @var array
   */
  protected $names = [
    'columns',
    'sort',
    'direction',
    'limit',
    'link_type',
    'format',
    'default',
  ];

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'webform_results_custom';
  }

  /**
   * The webform entity.
   *
   * @var \Drupal\webform\WebformInterface
   */
  protected $webform;

  /**
   * The webform source entity.
   *
   * @var \Drupal\Core\Entity\EntityInterface
   */
  protected $sourceEntity;

  /**
   * The webform submission storage.
   *
   * @var \Drupal\webform\WebformSubmissionStorageInterface
   */
  protected $submissionStorage;

  /**
   * Webform request handler.
   *
   * @var \Drupal\webform\WebformRequestInterface
   */
  protected $requestHandler;

  /**
   * Constructs a WebformResultsCustomForm object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\webform\WebformRequestInterface $request_handler
   *   The webform request handler.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, WebformRequestInterface $request_handler) {
    $this->submissionStorage = $entity_type_manager->getStorage('webform_submission');
    $this->requestHandler = $request_handler;
    list($this->webform, $this->sourceEntity) = $this->requestHandler->getWebformEntities();
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('webform.request')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $this->customize = $this->webform->getSetting('results_customize', TRUE);
    $this->type = $this->getRouteMatch()->getParameter('type') ?: 'default';

    // Deny access to customize user table if results customization is disabled.
    if (!$this->customize && $this->type === static::CUSTOMIZE_USER) {
      throw new AccessDeniedHttpException();
    }

    // Customize the title and description for customized table.
    if ($this->customize) {
      $form['#title'] = ($this->type === static::CUSTOMIZE_USER) ? $this->t('Customize my table') : $this->t('Customize default table');
      $form['description'] = [
        '#markup' => ($this->type === static::CUSTOMIZE_USER) ? $this->t('Below you can customize your dedicated results table, which is displayed only to you.') : $this->t('Below you can customize the default results table, which is displayed to all users.'),
        '#prefix' => '<p>',
        '#suffix' => '</p>',
      ];

      if ($this->type === static::CUSTOMIZE_USER
        && $this->webform->access('update')) {
        $route_name = $this->requestHandler->getRouteName($this->webform, $this->sourceEntity, 'webform.results_submissions.custom');
        $route_parameters = $this->requestHandler->getRouteParameters($this->webform, $this->sourceEntity);
        $url = Url::fromRoute($route_name, $route_parameters)
          ->mergeOptions(['query' => $this->getRedirectDestination()->getAsArray()]);
        $form['description']['link'] = [
          '#type' => 'link',
          '#title' => $this->t('Customize default table'),
          '#url' => $url,
          '#attributes' => WebformDialogHelper::getModalDialogAttributes(WebformDialogHelper::DIALOG_NORMAL, ['button', 'button-action', 'button--small', 'button-webform-table-setting']),
          '#prefix' => '<p>',
          '#suffix' => '</p>',
        ];
      }
      elseif ($this->type !== static::CUSTOMIZE_USER) {
        $route_name = $this->requestHandler->getRouteName($this->webform, $this->sourceEntity, 'webform.results_submissions.custom.user');
        $route_parameters = $this->requestHandler->getRouteParameters($this->webform, $this->sourceEntity);
        $url = Url::fromRoute($route_name, $route_parameters)
          ->mergeOptions(['query' => $this->getRedirectDestination()->getAsArray()]);
        $form['description']['link'] = [
          '#type' => 'link',
          '#title' => $this->t('Customize my table'),
          '#url' => $url,
          '#attributes' => WebformDialogHelper::getModalDialogAttributes(WebformDialogHelper::DIALOG_NORMAL, ['button', 'button-action', 'button--small', 'button-webform-table-setting']),
          '#prefix' => '<p>',
          '#suffix' => '</p>',
        ];

      }
    }

    // @see \Drupal\webform\WebformEntitySettingsForm::form
    $available_columns = $this->submissionStorage->getColumns($this->webform, $this->sourceEntity, NULL, TRUE);
    // Change sid's # to an actual label.
    $available_columns['sid']['title'] = $this->t('Submission ID');
    // Get available columns as option.
    $columns_options = [];
    foreach ($available_columns as $column_name => $column) {
      $title = (strpos($column_name, 'element__') === 0) ? ['data' => ['#markup' => '<b>' . $column['title'] . '</b>']] : $column['title'];
      $key = (isset($column['key'])) ? str_replace('webform_', '', $column['key']) : $column['name'];
      $columns_options[$column_name] = ['title' => $title, 'key' => $key];
    }

    // Get custom columns as the default value.
    $custom_columns = $this->getData('columns', array_keys($available_columns));

    // Table settings.
    $form['table'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Table settings'),
    ];
    if ($this->customize) {
      $form['table']['#title'] = ($this->type === static::CUSTOMIZE_USER) ? $this->t('My table settings') : $this->t('Default table settings');
    }

    // Display columns in sortable table select element.
    $form['table']['columns'] = [
      '#type' => 'webform_tableselect_sort',
      '#header' => [
        'title' => $this->t('Title'),
        'key' => $this->t('Key'),
      ],
      '#options' => $columns_options,
      '#default_value' => array_combine($custom_columns, $custom_columns),
    ];

    // Get available sort options.
    $sort_options = [];
    $sort_columns = $available_columns;
    ksort($sort_columns);
    foreach ($sort_columns as $column_name => $column) {
      if (!isset($column['sort']) || $column['sort'] === TRUE) {
        $sort_options[$column_name] = (string) $column['title'];
      };
    }
    asort($sort_options);

    // Sort and direction.
    // Display available columns sorted alphabetically.
    $sort = $this->getData('sort', 'created');
    $direction = $this->getData('direction', 'desc');
    $form['table']['sort'] = [
      '#prefix' => '<div class="container-inline">',
      '#type' => 'select',
      '#title' => $this->t('Sort by'),
      '#title_display' => 'invisible',
      '#field_prefix' => $this->t('Sort by'),
      '#options' => $sort_options,
      '#default_value' => $sort,
    ];
    $form['table']['direction'] = [
      '#type' => 'select',
      '#title' => $this->t('Direction'),
      '#title_display' => 'invisible',
      '#field_prefix' => ' ' . $this->t('in', [], ['context' => 'Sort by {sort} in {direction} order']) . ' ',
      '#field_suffix' => ' ' . $this->t('order', [], ['context' => 'Sort by {sort} in {direction} order']),
      '#options' => [
        'asc' => $this->t('Ascending (ASC)'),
        'desc' => $this->t('Descending (DESC)'),
      ],
      '#default_value' => $direction,
      '#suffix' => '</div>',
    ];

    // Limit.
    $limit = $this->getData('limit');
    $form['table']['limit'] = [
      '#type' => 'select',
      '#title' => $this->t('Results per page'),
      '#title_display' => 'invisible',
      '#field_prefix' => $this->t('Show', [], ['context' => 'Show {limit} results per page']),
      '#field_suffix' => $this->t('results per page'),
      '#options' => [
        '20' => '20',
        '50' => '50',
        '100' => '100',
        '200' => '200',
        '500' => '500',
      ],
      '#default_value' => ($limit !== NULL) ? $limit : 20,
    ];

    /**************************************************************************/
    // Default settings only.
    /**************************************************************************/

    if ($this->type === static::CUSTOMIZE_DEFAULT) {
      // Default configuration.
      if (empty($this->sourceEntity)) {
        $form['config'] = [
          '#type' => 'details',
          '#title' => $this->t('Configuration settings'),
        ];
        $form['config']['default'] = [
          '#type' => 'checkbox',
          '#title' => $this->t('Use as default configuration'),
          '#description' => $this->t('If checked, the above settings will be used as the default configuration for all associated Webform nodes.'),
          '#return_value' => TRUE,
          '#default_value' => $this->getData('default', TRUE),
        ];
      }

      // Format settings.
      $format = $this->getData('format', [
        'header_format' => 'label',
        'element_format' => 'value',
      ]);
      $form['format'] = [
        '#type' => 'details',
        '#title' => $this->t('Format settings'),
        '#tree' => TRUE,
      ];
      $form['format']['header_format'] = [
        '#type' => 'radios',
        '#title' => $this->t('Column header format'),
        '#description' => $this->t('Choose whether to show the element label or element key in each column header.'),
        '#options' => [
          'label' => $this->t('Element titles (label)'),
          'key' => $this->t('Element keys (key)'),
        ],
        '#default_value' => $format['header_format'],
      ];
      $form['format']['element_format'] = [
        '#type' => 'radios',
        '#title' => $this->t('Element format'),
        '#options' => [
          'value' => $this->t('Labels/values, the human-readable value (value)'),
          'raw' => $this->t('Raw values, the raw value stored in the database (raw)'),
        ],
        '#default_value' => $format['element_format'],
      ];

      // Submission settings.
      $form['submission'] = [
        '#type' => 'details',
        '#title' => $this->t('Submission settings'),
      ];
      // Get link types.
      // @see entity.webform_submission.* route names.
      $link_type_options = [
        'canonical' => $this->t('View'),
        'table' => $this->t('Table'),
      ];
      $form['submission']['link_type'] = [
        '#type' => 'select',
        '#title' => $this->t('Link submissions to…'),
        '#descriptions' => $this->t('Please note: Drafts will always be linked to submission form.'),
        '#options' => $link_type_options,
        '#default_value' => $this->getData('link_type', 'canonical'),
      ];

      // User settings.
      if (!$this->configFactory()->get('webform.settings')->get('settings.default_results_customize')) {
        $form['user'] = [
          '#type' => 'details',
          '#title' => $this->t('User settings'),
        ];
        $form['user']['results_customize'] = [
          '#type' => 'checkbox',
          '#title' => $this->t('Allow users to customize the submission results table'),
          '#description' => $this->t('If checked, users can customize the submission results table for this webform.'),
          '#return_value' => TRUE,
          '#default_value' => $this->webform->getSetting('results_customize'),
        ];
      }
    }

    // Build actions.
    $form['actions']['#type'] = 'actions';
    $form['actions']['save'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save'),
      '#button_type' => 'primary',
    ];
    $form['actions']['delete'] = [
      '#type' => 'submit',
      '#value' => $this->t('Reset'),
      '#attributes' => [
        'class' => ['button', 'button--danger'],
      ],
      '#access' => $this->hasData('columns') ? TRUE : FALSE,
      '#submit' => ['::delete'],
    ];

    return $form;
  }

  /**
   * Build table row for a results columns.
   *
   * @param string $column_name
   *   The column name.
   * @param array $column
   *   The column.
   * @param bool $default_value
   *   Whether the column should be checked.
   * @param int $weight
   *   The columns weights.
   * @param int $delta
   *   The max delta for the weight element.
   *
   * @return array
   *   A renderable containing a table row for a results column.
   */
  protected function buildRow($column_name, array $column, $default_value, $weight, $delta) {
    return [
      '#attributes' => ['class' => ['draggable']],
      'name' => [
        '#type' => 'checkbox',
        '#default_value' => $default_value,
      ],
      'title' => [
        '#markup' => $column['title'],
      ],
      'key' => [
        '#markup' => (isset($column['key'])) ? $column['key'] : $column['name'],
      ],
      'weight' => [
        '#type' => 'weight',
        '#title' => $this->t('Weight for @label', ['@label' => $column['title']]),
        '#title_display' => 'invisible',
        '#attributes' => [
          'class' => ['table-sort-weight'],
        ],
        '#delta' => $delta,
        '#default_value' => $weight,
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $columns = $form_state->getValue('columns');
    if (empty($columns)) {
      $form_state->setErrorByName('columns', $this->t('At least once column is required'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Set form value in webform state.
    $values = $form_state->getValues();
    // Convert columns to simple array.
    $values['columns'] = array_values($values['columns']);
    // Convert limit to integer.
    $values['limit'] = (int) $values['limit'];
    // Remove default if source entity is defined.
    if (!empty($this->sourceEntity) || $this->type === 'user') {
      unset($values['default']);
    }
    foreach ($this->names as $name) {
      if (isset($values[$name])) {
        $this->setData($name, $values[$name]);
      }
    }

    // Set results customize.
    if (isset($values['results_customize'])) {
      $this->webform
        ->setSetting('results_customize', $values['results_customize'])
        ->save();
    }

    // Display message.
    if ($this->customize) {
      if ($this->type === static::CUSTOMIZE_USER) {
        $this->messenger()->addStatus($this->t('Your customized table has been saved.'));
      }
      else {
        $this->messenger()->addStatus($this->t('The default customized table has been saved.'));
      }
    }
    else {
      $this->messenger()->addStatus($this->t('The customized table has been saved.'));
    }

    // Set redirect.
    $redirect_url = $this->requestHandler->getUrl($this->webform, $this->sourceEntity, 'webform.results_submissions');
    $form_state->setRedirectUrl($redirect_url);
  }

  /**
   * Webform delete customized columns handler.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function delete(array &$form, FormStateInterface $form_state) {
    foreach ($this->names as $name) {
      $this->deleteData($name);
    }

    if ($this->customize) {
      if ($this->type === static::CUSTOMIZE_USER) {
        $this->messenger()->addStatus($this->t('Your customized table has been reset.'));
      }
      else {
        $this->messenger()->addStatus($this->t('The default customized table has been reset.'));
      }
    }
    else {
      $this->messenger()->addStatus($this->t('The customized table has been reset.'));
    }

    // Set redirect.
    $redirect_url = $this->requestHandler->getUrl($this->webform, $this->sourceEntity, 'webform.results_submissions');
    $form_state->setRedirectUrl($redirect_url);
  }

  /****************************************************************************/
  // Customize data methods.
  /****************************************************************************/

  /**
   * Get the data method name depending of the custom type.
   *
   * @param string $method
   *   The method prefix.
   *
   * @return string
   *   The data method name depending of the custom type.
   */
  protected function getDataMethod($method) {
    return $method . ($this->type === static::CUSTOMIZE_USER ? 'UserData' : 'State');
  }

  /**
   * Get the full key for the custom data.
   *
   * @param $name
   *   The name for the custom data.
   *
   * @return string
   *   The full key for the custom data.
   */
  protected function getDataKey($name) {
    if ($source_entity = $this->sourceEntity) {
      return "results.custom.$name." . $source_entity->getEntityTypeId() . '.' . $source_entity->id();
    }
    else {
      return "results.custom.$name";
    }
  }

  /**
   * Determine if there is custom data for given name.
   *
   * @param string $name
   *   The name for the custom data.
   *
   * @return bool
   *   TRUE if there is custom data for given name.
   */
  protected function hasData($name) {
    $method = $this->getDataMethod('has');
    $key = $this->getDataKey($name);
    return $this->webform->$method($key);
  }

  /**
   * Get custom data.
   *
   * @param string $name
   *   The name for the custom data.
   * @param mixed $default
   *   Default data.
   *
   * @return mixed
   *   The custom data.
   */
  protected function getData($name, $default = NULL) {
    $method = $this->getDataMethod('get');
    $key = $this->getDataKey($name);
    if ($this->type === static::CUSTOMIZE_USER) {
      return $this->webform->$method($key)
        ?: $this->webform->getState($key)
        ?: $default;
    }
    else {
      return $this->webform->$method($key, $default) ?: $default;
    }
  }

  /**
   * Set custom data.
   *
   * @param string $name
   *   The name for the custom data.
   * @param mixed $value
   *   The data to store.
   */
  protected function setData($name, $value) {
    $method = $this->getDataMethod('set');
    $key = $this->getDataKey($name);
    $this->webform->$method($key, $value);
  }

  /**
   * Delete custom data.
   *
   * @param string $name
   *   The name for the custom data.
   */
  protected function deleteData($name) {
    $method = $this->getDataMethod('delete');
    $key = $this->getDataKey($name);
    $this->webform->$method($key);
  }

}
