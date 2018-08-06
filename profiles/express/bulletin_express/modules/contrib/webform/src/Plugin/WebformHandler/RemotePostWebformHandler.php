<?php

namespace Drupal\webform\Plugin\WebformHandler;

use Drupal\Core\Serialization\Yaml;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\webform\WebformHandlerBase;
use Drupal\webform\WebformSubmissionInterface;
use Drupal\webform\WebformTokenManagerInterface;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\RequestException;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Webform submission remote post handler.
 *
 * @WebformHandler(
 *   id = "remote_post",
 *   label = @Translation("Remote post"),
 *   category = @Translation("External"),
 *   description = @Translation("Posts webform submissions to a URL."),
 *   cardinality = \Drupal\webform\WebformHandlerInterface::CARDINALITY_UNLIMITED,
 *   results = \Drupal\webform\WebformHandlerInterface::RESULTS_PROCESSED,
 *   submission = \Drupal\webform\WebformHandlerInterface::SUBMISSION_OPTIONAL,
 * )
 */
class RemotePostWebformHandler extends WebformHandlerBase {

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The HTTP client to fetch the feed data with.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  protected $httpClient;

  /**
   * The token manager.
   *
   * @var \Drupal\webform\WebformTranslationManagerInterface
   */
  protected $tokenManager;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, LoggerInterface $logger, EntityTypeManagerInterface $entity_type_manager, ModuleHandlerInterface $module_handler, ClientInterface $http_client, WebformTokenManagerInterface $token_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $logger, $entity_type_manager);
    $this->moduleHandler = $module_handler;
    $this->httpClient = $http_client;
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
      $container->get('logger.factory')->get('webform.remote_post'),
      $container->get('entity_type.manager'),
      $container->get('module_handler'),
      $container->get('http_client'),
      $container->get('webform.token_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getSummary() {
    $configuration = $this->getConfiguration();

    // If the saving of results is disabled clear update and delete URL.
    if ($this->getWebform()->getSetting('results_disabled')) {
      $configuration['settings']['update_url'] = '';
      $configuration['settings']['delete_url'] = '';
    }

    return [
      '#settings' => $configuration['settings'],
    ] + parent::getSummary();
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    $field_names = array_keys(\Drupal::service('entity_field.manager')->getBaseFieldDefinitions('webform_submission'));
    $excluded_data = array_combine($field_names, $field_names);
    return [
      'type' => 'x-www-form-urlencoded',
      'insert_url' => '',
      'update_url' => '',
      'delete_url' => '',
      'excluded_data' => $excluded_data,
      'custom_data' => '',
      'insert_custom_data' => '',
      'update_custom_data' => '',
      'delete_custom_data' => '',
      'debug' => FALSE,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $webform = $this->getWebform();
    $results_disabled = $webform->getSetting('results_disabled');

    $form['insert_url'] = [
      '#type' => 'url',
      '#title' => $this->t('Insert URL'),
      '#description' => $this->t('The full URL to POST to when a new webform submission is saved. E.g. http://www.mycrm.com/form_insert_handler.php'),
      '#required' => TRUE,
      '#default_value' => $this->configuration['insert_url'],
    ];

    $form['update_url'] = [
      '#type' => 'url',
      '#title' => $this->t('Update URL'),
      '#description' => $this->t('The full URL to POST to when an existing webform submission is updated. E.g. http://www.mycrm.com/form_insert_handler.php'),
      '#default_value' => $this->configuration['update_url'],
      '#access' => !$results_disabled,
    ];

    $form['delete_url'] = [
      '#type' => 'url',
      '#title' => $this->t('Delete URL'),
      '#description' => $this->t('The full URL to POST to call when a webform submission is deleted. E.g. http://www.mycrm.com/form_delete_handler.php'),
      '#default_value' => $this->configuration['delete_url'],
      '#access' => !$results_disabled,
    ];

    $form['type'] = [
      '#type' => 'select',
      '#title' => $this->t('Post type'),
      '#description' => $this->t('Use x-www-form-urlencoded if unsure, as it is the default format for HTML webforms. You also have the option to post data in <a href="http://www.json.org/" target="_blank">JSON</a> format.'),
      '#options' => [
        'x-www-form-urlencoded' => $this->t('x-www-form-urlencoded'),
        'json' => $this->t('JSON'),
      ],
      '#required' => TRUE,
      '#default_value' => $this->configuration['type'],
    ];

    $form['submission_data'] = [
      '#type' => 'details',
      '#title' => $this->t('Submission data'),
    ];
    $form['submission_data']['excluded_data'] = [
      '#type' => 'webform_excluded_columns',
      '#title' => $this->t('Posted data'),
      '#title_display' => 'invisible',
      '#webform_id' => $webform->id(),
      '#required' => TRUE,
      '#parents' => ['settings', 'excluded_data'],
      '#default_value' => $this->configuration['excluded_data'],
    ];

    $form['custom_data'] = [
      '#type' => 'details',
      '#title' => $this->t('Custom data'),
      '#description' => $this->t('Custom data will take precedence over submission data. You may use tokens.'),
    ];

    $form['custom_data']['custom_data'] = [
      '#type' => 'webform_codemirror',
      '#mode' => 'yaml',
      '#title' => $this->t('Custom data'),
      '#description' => $this->t('Enter custom data that will be included in all remote post requests.'),
      '#parents' => ['settings', 'custom_data'],
      '#default_value' => $this->configuration['custom_data'],
    ];
    $form['custom_data']['insert_custom_data'] = [
      '#type' => 'webform_codemirror',
      '#mode' => 'yaml',
      '#title' => $this->t('Insert data'),
      '#description' => $this->t("Enter custom data that will be included when a new webform submission is saved."),
      '#parents' => ['settings', 'insert_custom_data'],
      '#states' => [
        'visible' => [
          [':input[name="settings[update_url]"]' => ['filled' => TRUE]],
          'or',
          [':input[name="settings[delete_url]"]' => ['filled' => TRUE]],
        ],
      ],
      '#default_value' => $this->configuration['insert_custom_data'],
    ];
    $form['custom_data']['update_custom_data'] = [
      '#type' => 'webform_codemirror',
      '#mode' => 'yaml',
      '#title' => $this->t('Update data'),
      '#description' => $this->t("Enter custom data that will be included when a webform submission is updated."),
      '#parents' => ['settings', 'update_custom_data'],
      '#states' => ['visible' => [':input[name="settings[update_url]"]' => ['filled' => TRUE]]],
      '#default_value' => $this->configuration['update_custom_data'],
    ];
    $form['custom_data']['delete_custom_data'] = [
      '#type' => 'webform_codemirror',
      '#mode' => 'yaml',
      '#title' => $this->t('Delete data'),
      '#description' => $this->t("Enter custom data that will be included when a webform submission is deleted."),
      '#parents' => ['settings', 'delete_custom_data'],
      '#states' => ['visible' => [':input[name="settings[delete_url]"]' => ['filled' => TRUE]]],
      '#default_value' => $this->configuration['delete_custom_data'],
    ];
    $form['custom_data']['token_tree_link'] = $this->tokenManager->buildTreeLink();

    $form['debug'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable debugging'),
      '#description' => $this->t('If checked, posted submissions will be displayed onscreen to all users.'),
      '#return_value' => TRUE,
      '#default_value' => $this->configuration['debug'],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);
    $values = $form_state->getValues();
    foreach ($this->configuration as $name => $value) {
      if (isset($values[$name])) {
        $this->configuration[$name] = $values[$name];
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function postSave(WebformSubmissionInterface $webform_submission, $update = TRUE) {
    $operation = ($update) ? 'update' : 'insert';
    $this->remotePost($operation, $webform_submission);
  }

  /**
   * {@inheritdoc}
   */
  public function postDelete(WebformSubmissionInterface $webform_submission) {
    $this->remotePost('delete', $webform_submission);
  }

  /**
   * Execute a remote post.
   *
   * @param string $operation
   *   The type of webform submission operation to be posted. Can be 'insert',
   *   'update', or 'delete'.
   * @param \Drupal\webform\WebformSubmissionInterface $webform_submission
   *   The webform submission to be posted.
   */
  protected function remotePost($operation, WebformSubmissionInterface $webform_submission) {
    $request_url = $this->configuration[$operation . '_url'];
    if (empty($request_url)) {
      return;
    }

    $request_type = $this->configuration['type'];
    $request_post_data = $this->getPostData($operation, $webform_submission);

    try {
      switch ($request_type) {
        case 'json':
          $response = $this->httpClient->post($request_url, ['json' => $request_post_data]);
          break;

        case 'x-www-form-urlencoded':
        default:
          $response = $this->httpClient->post($request_url, ['form_params' => $request_post_data]);
          break;
      }
    }
    catch (RequestException $request_exception) {
      $message = $request_exception->getMessage();
      $response = $request_exception->getResponse();

      // Encode HTML entities to prevent broken markup from breaking the page.
      $message = nl2br(htmlentities($message));

      // If debugging is enabled, display the error message on screen.
      $this->debug($message, $operation, $request_url, $request_type, $request_post_data, $response, 'error');

      // Log error message.
      $context = [
        '@form' => $this->getWebform()->label(),
        '@operation' => $operation,
        '@type' => $request_type,
        '@url' => $request_url,
        '@message' => $message,
        'link' => $this->getWebform()->toLink($this->t('Edit'), 'handlers-form')->toString(),
      ];
      $this->logger->error('@form webform remote @type post (@operation) to @url failed. @message', $context);
      return;
    }

    // If debugging is enabled, display the request and response.
    $this->debug(t('Remote post successful!'), $operation, $request_url, $request_type, $request_post_data, $response, 'warning');
  }

  /**
   * Get a webform submission's post data.
   *
   * @param string $operation
   *   The type of webform submission operation to be posted. Can be 'insert',
   *   'update', or 'delete'.
   * @param \Drupal\webform\WebformSubmissionInterface $webform_submission
   *   The webform submission to be posted.
   *
   * @return array
   *   A webform submission converted to an associative array.
   */
  protected function getPostData($operation, WebformSubmissionInterface $webform_submission) {
    // Get submission and elements data.
    $data = $webform_submission->toArray(TRUE);

    // Flatten data.
    // Prioritizing elements before the submissions fields.
    $data = $data['data'] + $data;
    unset($data['data']);

    // Excluded selected submission data.
    $data = array_diff_key($data, $this->configuration['excluded_data']);

    // Append custom data.
    if (!empty($this->configuration['custom_data'])) {
      $data = Yaml::decode($this->configuration['custom_data']) + $data;
    }

    // Append operation data.
    if (!empty($this->configuration[$operation . '_custom_data'])) {
      $data = Yaml::decode($this->configuration[$operation . '_custom_data']) + $data;
    }

    // Replace tokens.
    $data = $this->tokenManager->replace($data, $webform_submission);

    return $data;
  }

  /**
   * Display debugging information.
   *
   * @param string $message
   *   Message to be displayed.
   * @param string $operation
   *   The operation being performed, can be either insert, update, or delete.
   * @param string $request_url
   *   The remote URL the request is being posted to.
   * @param string $request_type
   *   The type of remote post.
   * @param string $request_post_data
   *   The webform submission data being posted.
   * @param \Psr\Http\Message\ResponseInterface|null $response
   *   The response returned by the remote server.
   * @param string $type
   *   The type of message to be displayed to the end use.
   */
  protected function debug($message, $operation, $request_url, $request_type, $request_post_data, ResponseInterface $response = NULL, $type = 'warning') {
    if (empty($this->configuration['debug'])) {
      return;
    }

    $build = [
      '#type' => 'details',
      '#title' => $this->t('Debug: Remote post: @title', ['@title' => $this->label()]),
    ];

    // Operation.
    $build['operation'] = [
      '#type' => 'item',
      '#title' => $this->t('Remote operation'),
      '#markup' => $operation,
      '#wrapper_attributes' => ['class' => ['container-inline'], 'style' => 'margin: 0'],
    ];

    $build['returned'] = ['#markup' => '<hr/>'];

    // Request.
    $build['request_url'] = [
      '#type' => 'item',
      '#title' => $this->t('Request URL'),
      '#markup' => $request_url,
      '#wrapper_attributes' => ['class' => ['container-inline'], 'style' => 'margin: 0'],
    ];
    $build['request_type'] = [
      '#type' => 'item',
      '#title' => $this->t('Request type'),
      '#markup' => $request_type,
      '#wrapper_attributes' => ['class' => ['container-inline'], 'style' => 'margin: 0'],
    ];
    $build['request_post_data'] = [
      '#type' => 'item',
      '#title' => $this->t('Request data'),
      '#wrapper_attributes' => ['style' => 'margin: 0'],
      'data' => [
        '#markup' => htmlspecialchars(Yaml::encode($request_post_data)),
        '#prefix' => '<pre>',
        '#suffix' => '</pre>',
      ],
    ];

    $build['returned'] = ['#markup' => '<hr/>'];

    // Response.
    if ($response) {
      $build['response_code'] = [
        '#type' => 'item',
        '#title' => $this->t('Response status code'),
        '#markup' => $response->getStatusCode(),
        '#wrapper_attributes' => ['class' => ['container-inline'], 'style' => 'margin: 0'],
      ];
      $build['response_header'] = [
        '#type' => 'item',
        '#title' => $this->t('Response header'),
        '#wrapper_attributes' => ['style' => 'margin: 0'],
        'data' => [
          '#markup' => htmlspecialchars(Yaml::encode($response->getHeaders())),
          '#prefix' => '<pre>',
          '#suffix' => '</pre>',
        ],
      ];
      $build['response_body'] = [
        '#type' => 'item',
        '#wrapper_attributes' => ['style' => 'margin: 0'],
        '#title' => $this->t('Response body'),
        'data' => [
          '#markup' => htmlspecialchars($response->getBody()),
          '#prefix' => '<pre>',
          '#suffix' => '</pre>',
        ],
      ];
    }
    else {
      $build['response_code'] = [
        '#markup' => t('No response. Please see the recent log messages.'),
        '#prefix' => '<p>',
        '#suffix' => '</p>',
      ];
    }

    drupal_set_message(\Drupal::service('renderer')->renderPlain($build), $type);
  }

}
