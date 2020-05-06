<?php

namespace Drupal\webform\Plugin\Block;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\webform\Element\WebformHtmlEditor;
use Drupal\webform\Entity\Webform;
use Drupal\webform\Utility\WebformDateHelper;
use Drupal\webform\WebformRequestInterface;
use Drupal\webform\WebformTokenManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'Webform submission limit' block.
 *
 * @Block(
 *   id = "webform_submission_limit_block",
 *   admin_label = @Translation("Webform submission limits"),
 *   category = @Translation("Webform")
 * )
 */
class WebformSubmissionLimitBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The current account.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * The current webform with overridden settings.
   *
   * @var \Drupal\webform\WebformInterface|bool
   */
  protected $webform;

  /**
   * The current source entity.
   *
   * @var \Drupal\Core\Entity\EntityInterface|bool
   */
  protected $sourceEntity;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Webform request handler.
   *
   * @var \Drupal\webform\WebformRequestInterface
   */
  protected $requestHandler;

  /**
   * The webform token manager.
   *
   * @var \Drupal\webform\WebformTokenManagerInterface
   */
  protected $tokenManager;

  /**
   * Creates a WebformSubmissionLimitBlock instance.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The current user.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\webform\WebformRequestInterface $request_handler
   *   The webform request handler.
   * @param \Drupal\webform\WebformTokenManagerInterface $token_manager
   *   The webform token manager.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, AccountInterface $account, EntityTypeManagerInterface $entity_type_manager, WebformRequestInterface $request_handler, WebformTokenManagerInterface $token_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->currentUser = $account;
    $this->entityTypeManager = $entity_type_manager;
    $this->requestHandler = $request_handler;
    $this->tokenManager = $token_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('current_user'),
      $container->get('entity_type.manager'),
      $container->get('webform.request'),
      $container->get('webform.token_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'type' => 'webform',
      'source_entity' => TRUE,
      'content' => '',
      'progress_bar' => TRUE,
      'progress_bar_label' => '',
      'progress_bar_message' => '',
      'webform_id' => '',
      'entity_type' => '',
      'entity_id' => '',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    // General.
    $form['general'] = [
      '#type' => 'details',
      '#title' => $this->t('General settings'),
      '#open' => TRUE,
    ];
    $form['general']['type'] = [
      '#title' => $this->t('Display limit and total submissions for the'),
      '#type' => 'select',
      '#options' => [
        'webform' => $this->t('Current webform'),
        'user' => $this->t('Current user'),
      ],
      '#ajax' => self::getTokenAjaxSettings(),
      '#default_value' => $this->configuration['type'],
      '#parents' => ['settings', 'type'],
    ];
    $form['general']['source_entity'] = [
      '#title' => $this->t('Restrict limit and total submissions to current or specified source entity'),
      '#type' => 'checkbox',
      '#return_value' => TRUE,
      '#ajax' => self::getTokenAjaxSettings(),
      '#default_value' => $this->configuration['source_entity'],
      '#parents' => ['settings', 'source_entity'],
    ];
    $form['general']['content'] = [
      '#type' => 'webform_html_editor',
      '#title' => $this->t('Content'),
      '#description' => $this->t('The entered text appears before the progress bar.'),
      '#default_value' => $this->configuration['content'],
      '#parents' => ['settings', 'content'],
    ];

    // Tokens.
    $form['tokens'] = self::buildTokens($this->configuration['type'], $this->configuration['source_entity']);

    // Progress.
    $form['progress'] = [
      '#type' => 'details',
      '#title' => $this->t('Progress bar'),
      '#open' => TRUE,
    ];
    $form['progress']['progress_bar'] = [
      '#title' => $this->t('Show progress bar'),
      '#type' => 'checkbox',
      '#return_value' => TRUE,
      '#default_value' => $this->configuration['progress_bar'],
      '#parents' => ['settings', 'progress_bar'],
    ];
    $form['progress']['progress_bar_label'] = [
      '#title' => $this->t('Progress bar label'),
      '#type' => 'textfield',
      '#description' => $this->t('The entered text appears above the progress bar.'),
      '#default_value' => $this->configuration['progress_bar_label'],
      '#parents' => ['settings', 'progress_bar_label'],
      '#states' => [
        'visible' => [
          ':input[name="settings[progress_bar]"]' => ['checked' => TRUE],
        ],
      ],
    ];
    $form['progress']['progress_bar_message'] = [
      '#title' => $this->t('Progress bar message'),
      '#type' => 'textfield',
      '#description' => $this->t('The entered text appears below the progress bar.'),
      '#default_value' => $this->configuration['progress_bar_message'],
      '#parents' => ['settings', 'progress_bar_message'],
      '#states' => [
        'visible' => [
          ':input[name="settings[progress_bar]"]' => ['checked' => TRUE],
        ],
      ],
    ];

    // Advanced.
    $form['advanced'] = [
      '#title' => $this->t('Advanced settings'),
      '#type' => 'details',
      '#description' => $this->t("Webform and source entity are automatically detected based on the current page request. You can use the below settings to hardcode the submission limit block's webform and source entity."),
      '#open' => $this->configuration['webform_id'] || $this->configuration['entity_type'],
    ];
    $form['advanced']['webform_id'] = [
      '#title' => $this->t('Webform'),
      '#type' => 'entity_autocomplete',
      '#target_type' => 'webform',
      '#default_value' => ($this->configuration['webform_id']) ? $this->entityTypeManager->getStorage('webform')->load($this->configuration['webform_id']) : NULL,
      '#parents' => ['settings', 'webform_id'],
    ];
    $entity_type_options = [];
    foreach ($this->entityTypeManager->getDefinitions() as $entity_type_id => $entity_type) {
      $entity_type_options[$entity_type_id] = $entity_type->getLabel();
    }
    $form['advanced']['entity_type'] = [
      '#type' => 'select',
      '#title' => 'Source entity type',
      '#empty_option' => $this->t('- None -'),
      '#options' => $entity_type_options,
      '#default_value' => $this->configuration['entity_type'],
      '#parents' => ['settings', 'entity_type'],
      '#states' => [
        'visible' => [
          ':input[name="settings[advanced][webform_id]"]' => ['filled' => TRUE],
          ':input[name="settings[source_entity]"]' => ['checked' => TRUE],
        ],
      ],
    ];
    $form['advanced']['entity_id'] = [
      '#type' => 'textfield',
      '#title' => 'Source entity id',
      '#default_value' => $this->configuration['entity_id'],
      '#parents' => ['settings', 'entity_id'],
      '#states' => [
        'visible' => [
          ':input[name="settings[source_entity]"]' => ['checked' => TRUE],
          ':input[name="settings[advanced][webform_id]"]' => ['filled' => TRUE],
          ':input[name="settings[advanced][entity_type]"]' => ['filled' => TRUE],
        ],
      ],
    ];

    $this->tokenManager->elementValidate($form);

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockValidate($form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    if ($values['entity_type']) {
      $t_args = ['%label' => $form['advanced']['entity_id']['#title']];
      if (empty($values['entity_id'])) {
        $form_state->setError($form['advanced']['entity_id'], $this->t('An %label id is required.', $t_args));
      }
      elseif (!$this->entityTypeManager->getStorage($values['entity_type'])->load($values['entity_id'])) {
        $form_state->setError($form['advanced']['entity_id'], $this->t('A valid %label is required.', $t_args));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    foreach ($this->defaultConfiguration() as $key => $default_value) {
      $this->configuration[$key] = $values[$key];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build = [];
    if ($this->configuration['content']) {
      $build['content'] = WebformHtmlEditor::checkMarkup(
        $this->replaceTokens($this->configuration['content'])
      );
    }
    if ($this->configuration['progress_bar']) {
      $total = $this->getTotal();
      $limit = $this->getLimit();
      $build['progress_bar'] = [
        '#theme' => 'progress_bar',
        '#percent' => round(($total / $limit) * 100),
      ];
      if ($message = $this->configuration['progress_bar_message']) {
        $build['progress_bar']['#message']['#markup'] = $this->replaceTokens($message);
      }
      if ($label = $this->configuration['progress_bar_label']) {
        $build['progress_bar']['#label'] = $this->replaceTokens($label);
      }
    }

    $build['#attached']['library'][] = 'webform/webform.block.submission_limit';
    return $build;
  }

  /**
   * {@inheritdoc}
   */
  protected function blockAccess(AccountInterface $account) {
    $webform = $this->getWebform();
    if ($webform === FALSE) {
      return AccessResult::forbidden();
    }

    $source_entity = $this->getSourceEntity();
    if ($source_entity === FALSE) {
      return AccessResult::forbidden();
    }

    if ($this->getLimit() === FALSE) {
      return AccessResult::forbidden();
    }

    return parent::blockAccess($account);
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheMaxAge() {
    return 0;
  }

  /****************************************************************************/
  // Replace [limit], [total], and [webform] tokens.
  /****************************************************************************/

  /**
   * Replace tokens in text.
   *
   * @param string|array $text
   *   A string of text that may contain tokens.
   *
   * @return string|array
   *   Text or array with tokens replaced.
   */
  protected function replaceTokens($text) {
    // Replace [total] token.
    if (strpos($text, '[total]') !== FALSE) {
      $text = str_replace('[total]', $this->getTotal(), $text);
    }

    // Replace [limit] token.
    if (strpos($text, '[limit]') !== FALSE) {
      $text = str_replace('[limit]', $this->getLimit(), $text);
    }

    // Replace [remaining] token.
    if (strpos($text, '[remaining]') !== FALSE) {
      $text = str_replace('[remaining]', $this->getLimit() - $this->getTotal(), $text);
    }

    // Replace [interval] token.
    if (strpos($text, '[interval]') !== FALSE) {
      $text = str_replace('[interval]', $this->getIntervalText(), $text);
    }

    // Replace webform tokens.
    return $this->tokenManager->replace($text, $this->getWebform());
  }

  /**
   * Get submission limit.
   *
   * @return int|bool
   *   The submission limit or FALSE if not submission limit is defined.
   */
  protected function getLimit() {
    $name = ($this->configuration['source_entity']) ? 'entity_' : '';
    $name .= ($this->configuration['type'] == 'user') ? 'limit_user' : 'limit_total';
    return $this->getWebform()->getSetting($name) ?: FALSE;
  }

  /**
   * Get submission limit interval.
   *
   * @return int|bool
   *   The submission limit interval or FALSE if not submission limit is defined.
   */
  protected function getInterval() {
    $name = ($this->configuration['source_entity']) ? 'entity_' : '';
    $name .= ($this->configuration['type'] == 'user') ? 'limit_user_interval' : 'limit_total_interval';
    return $this->getWebform()->getSetting($name);
  }

  /**
   * Get submission limit interval text.
   *
   * @return string
   *   The submission limit interval or FALSE if not submission limit is defined.
   */
  protected function getIntervalText() {
    return WebformDateHelper::getIntervalText($this->getInterval());
  }

  /**
   * Get total number of submissions for selected limit type.
   *
   * @return int
   *   The total number of submissions.
   */
  protected function getTotal() {
    return $this->entityTypeManager->getStorage('webform_submission')->getTotal(
      $this->getWebform(),
      $this->getSourceEntity(),
      $this->getCurrentUser(),
      ['interval' => $this->getInterval()]
    );
  }

  /****************************************************************************/
  // Get submission limit webform, source entity, and/or user.
  /****************************************************************************/

  /**
   * Get the webform.
   *
   * @return \Drupal\webform\WebformInterface|bool
   *   The webform or FALSE if the webform is not available.
   */
  protected function getWebform() {
    if (isset($this->webform)) {
      return $this->webform;
    }

    if ($this->configuration['webform_id']) {
      $this->webform = Webform::load($this->configuration['webform_id']) ?: FALSE;
    }
    else {
      $this->webform = $this->requestHandler->getCurrentWebform() ?: FALSE;
    }

    // Apply overridden settings to the webform which requires
    // a temp webform submission.
    if ($this->webform) {
      /** @var \Drupal\webform\WebformSubmissionStorageInterface $webform_submission_storage */
      $webform_submission_storage = $this->entityTypeManager->getStorage('webform_submission');
      $values = ['webform_id' => $this->getWebform()->id()];
      if ($source_entity = $this->getSourceEntity()) {
        $values += [
          'entity_type' => $source_entity->getEntityTypeId(),
          'entity_id' => $source_entity->id(),
        ];
      }
      $temp_webform_submission = $webform_submission_storage->create($values);
      $this->webform->invokeHandlers('overrideSettings', $temp_webform_submission);
    }

    return $this->webform;
  }

  /**
   * Get the source entity.
   *
   * @return \Drupal\Core\Entity\EntityInterface|bool|null
   *   The source entity, NULL the if source entity is not applicable,
   *   or FALSE if the source entity is not available.
   */
  protected function getSourceEntity() {
    if (!$this->configuration['source_entity']) {
      return NULL;
    }

    if (!isset($this->sourceEntity)) {
      if ($this->configuration['entity_type'] && $this->configuration['entity_id']) {
        $entity_storage = $this->entityTypeManager->getStorage($this->configuration['entity_type']);
        if (!$entity_storage) {
          $this->sourceEntity = FALSE;
        }
        else {
          $this->sourceEntity = $entity_storage->load($this->configuration['entity_id']) ?: FALSE;
        }
      }
      else {
        $this->sourceEntity = $this->requestHandler->getCurrentSourceEntity('webform') ?: FALSE;
      }
    }

    return $this->sourceEntity;
  }

  /**
   * Get the current user account.
   *
   * @return \Drupal\Core\Session\AccountInterface|null
   *   The current user account or NULL if the user limit is not being displayed.
   */
  protected function getCurrentUser() {
    return ($this->configuration['type'] == 'user') ? $this->currentUser : NULL;
  }

  /****************************************************************************/
  // Ajax token callback.
  /****************************************************************************/

  /**
   * Get token refresh Ajax settings.
   *
   * @return array
   *   Array containing #ajax settings.
   */
  public static function getTokenAjaxSettings() {
    return [
      'callback' => [get_called_class(), 'tokenAjaxCallback'],
      'wrapper' => 'webform-submission-limit-block-tokens',
      'progress' => ['type' => 'fullscreen'],
    ];
  }

  /**
   * Ajax callback that returns the block form.
   */
  public static function tokenAjaxCallback(array &$form, FormStateInterface $form_state) {
    $settings = $form_state->getValue('settings');
    return self::buildTokens($settings['type'], $settings['source_entity']);
  }

  /**
   * Build available tokens for submission limit type and source entity.
   *
   * NOTE: Using inline style attributes of fix in-place block editing UX.
   *
   * @param string $type
   *   The submission type which can be 'webform' or 'user'.
   * @param string $source_entity
   *   Flag indicating if source entity should be included in available tokens.
   *
   * @return array
   *   A render array containing a list of available tokens.
   */
  public static function buildTokens($type, $source_entity) {
    /** @var \Drupal\webform\WebformTokenManagerInterface $token_manager */
    $token_manager = \Drupal::service('webform.token_manager');

    // Get token name and descriptions.
    module_load_include('inc', 'webform', 'webform.tokens');
    $token_info = webform_token_info();
    $tokens = $token_info['tokens']['webform_submission'];

    $token_types = ['limit', 'interval', 'total', 'remaining'];
    $rows = [];
    foreach ($token_types as $token_type) {
      $token_name = self::getTokenName($token_type, $type, $source_entity);
      $rows[] = [
        ['data' => '[' . $token_type . ']', 'style' => 'vertical-align: top'],
        [
          'data' => [
            'name' => [
              '#markup' => $tokens[$token_name]['name'],
              '#prefix' => '<strong>',
              '#suffix' => '</strong><br/>',
            ],
            'description' => [
              '#markup' => $tokens[$token_name]['description'],
            ],
          ],
          'style' => 'vertical-align: top',
        ],
      ];
    }

    return [
      '#type' => 'container',
      '#attributes' => ['id' => 'webform-submission-limit-block-tokens'],
      'details' => [
        '#type' => 'details',
        '#title' => t('Available tokens'),
        '#open' => TRUE,
        'table' => [
          '#type' => 'table',
          '#header' => [
            ['data' => t('Token'), 'style' => 'width: auto'],
            ['data' => t('Name / Description'), 'style' => 'width: 100%'],
          ],
          '#rows' => $rows,
          '#attributes' => ['style' => 'margin: 1em 0'],
        ],
        'token_tree_link' => [
          'token' => $token_manager->buildTreeElement(),
        ],
      ],
    ];
  }

  /**
   * Get token name.
   *
   * @param string $prefix
   *   Token prefix which can be 'total' or 'limit'.
   * @param string $type
   *   The submission type which can be 'webform' or 'user'.
   * @param bool $source_entity
   *   Flag indicating if source entity should be included in available tokens.
   *
   * @return string
   *   A token name.
   */
  protected static function getTokenName($prefix = 'limit', $type = 'webform', $source_entity = FALSE) {
    $parts = [$prefix, $type];
    if ($source_entity) {
      $parts[] = 'source_entity';
    }
    return implode(':', $parts);
  }

}
