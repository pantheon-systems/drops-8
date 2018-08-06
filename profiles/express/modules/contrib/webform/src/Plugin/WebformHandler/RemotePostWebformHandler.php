<?php

namespace Drupal\webform\Plugin\WebformHandler;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Serialization\Yaml;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\webform\Plugin\WebformHandlerBase;
use Drupal\webform\WebformInterface;
use Drupal\webform\WebformSubmissionConditionsValidatorInterface;
use Drupal\webform\WebformSubmissionInterface;
use Drupal\webform\WebformTokenManagerInterface;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\RequestException;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Webform submission remote post handler.
 *
 * @WebformHandler(
 *   id = "remote_post",
 *   label = @Translation("Remote post"),
 *   category = @Translation("External"),
 *   description = @Translation("Posts webform submissions to a URL."),
 *   cardinality = \Drupal\webform\Plugin\WebformHandlerInterface::CARDINALITY_UNLIMITED,
 *   results = \Drupal\webform\Plugin\WebformHandlerInterface::RESULTS_PROCESSED,
 *   submission = \Drupal\webform\Plugin\WebformHandlerInterface::SUBMISSION_OPTIONAL,
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
   * @var \Drupal\webform\WebformTokenManagerInterface
   */
  protected $tokenManager;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, LoggerChannelFactoryInterface $logger_factory, ConfigFactoryInterface $config_factory, EntityTypeManagerInterface $entity_type_manager, WebformSubmissionConditionsValidatorInterface $conditions_validator, ModuleHandlerInterface $module_handler, ClientInterface $http_client, WebformTokenManagerInterface $token_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $logger_factory, $config_factory, $entity_type_manager, $conditions_validator);
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
      $container->get('logger.factory'),
      $container->get('config.factory'),
      $container->get('entity_type.manager'),
      $container->get('webform_submission.conditions_validator'),
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
    if (!$this->isResultsEnabled()) {
      $configuration['settings']['updated_url'] = '';
      $configuration['settings']['deleted_url'] = '';
    }
    if (!$this->isDraftEnabled()) {
      $configuration['settings']['draft_url'] = '';
    }
    if (!$this->isConvertEnabled()) {
      $configuration['settings']['converted_url'] = '';
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
      'method' => 'POST',
      'type' => 'x-www-form-urlencoded',
      'excluded_data' => $excluded_data,
      'custom_data' => '',
      'custom_options' => '',
      'debug' => FALSE,
      // States.
      'completed_url' => '',
      'completed_custom_data' => '',
      'updated_url' => '',
      'updated_custom_data' => '',
      'deleted_url' => '',
      'deleted_custom_data' => '',
      'draft_url' => '',
      'draft_custom_data' => '',
      'converted_url' => '',
      'converted_custom_data' => '',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $webform = $this->getWebform();

    // States.
    $states = [
      WebformSubmissionInterface::STATE_COMPLETED => [
        'state' => $this->t('completed'),
        'label' => $this->t('Completed'),
        'description' => $this->t('Post data when submission is <b>completed</b>.'),
        'access' => TRUE,
      ],
      WebformSubmissionInterface::STATE_UPDATED => [
        'state' => $this->t('updated'),
        'label' => $this->t('Updated'),
        'description' => $this->t('Post data when submission is <b>updated</b>.'),
        'access' => $this->isResultsEnabled(),
      ],
      WebformSubmissionInterface::STATE_DELETED => [
        'state' => $this->t('deleted'),
        'label' => $this->t('Deleted'),
        'description' => $this->t('Post data when submission is <b>deleted</b>.'),
        'access' => $this->isResultsEnabled(),
      ],
      WebformSubmissionInterface::STATE_DRAFT => [
        'state' => $this->t('draft'),
        'label' => $this->t('Draft'),
        'description' => $this->t('Post data when <b>draft</b> is saved.'),
        'access' => $this->isDraftEnabled(),
      ],
      WebformSubmissionInterface::STATE_CONVERTED => [
        'state' => $this->t('converted'),
        'label' => $this->t('Converted'),
        'description' => $this->t('Post data when anonymous submission is <b>converted</b> to authenticated.'),
        'access' => $this->isConvertEnabled(),
      ],
    ];
    foreach ($states as $state => $state_item) {
      $state_url = $state . '_url';
      $state_custom_data = $state . '_custom_data';
      $t_args = [
        '@state' => $state_item['state'],
        '@title' => $state_item['label'],
        '@url' => 'http://www.mycrm.com/form_' . $state . '_handler.php',
      ];
      $form[$state] = [
        '#type' => 'details',
        '#open' => ($state === WebformSubmissionInterface::STATE_COMPLETED),
        '#title' => $state_item['label'],
        '#description' => $state_item['description'],
        '#access' => $state_item['access'],
      ];
      $form[$state][$state_url] = [
        '#type' => 'url',
        '#title' => $this->t('@title URL', $t_args),
        '#description' => $this->t('The full URL to POST to when an existing webform submission is @state. (e.g. @url)', $t_args),
        '#required' => ($state === WebformSubmissionInterface::STATE_COMPLETED),
        '#parents' => ['settings', $state_url],
        '#default_value' => $this->configuration[$state_url],
      ];
      $form[$state][$state_custom_data] = [
        '#type' => 'webform_codemirror',
        '#mode' => 'yaml',
        '#title' => $this->t('@title custom data', $t_args),
        '#description' => $this->t('Enter custom data that will be included when a webform submission is @state.', $t_args),
        '#parents' => ['settings', $state_custom_data],
        '#states' => ['visible' => [':input[name="settings[' . $state_url . ']"]' => ['filled' => TRUE]]],
        '#default_value' => $this->configuration[$state_custom_data],
      ];
      if ($state === WebformSubmissionInterface::STATE_COMPLETED) {
        $form[$state]['token'] = [
          '#type' => 'webform_message',
          '#message_message' => $this->t('Response data can be passed to the submission data using [webform:handler:{machine_name}:{state}:{key}] tokens. (i.e. [webform:handler:remote_post:completed:confirmation_number])'),
          '#message_type' => 'info',
        ];
      }
    }

    // Additional.
    $form['additional'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Additional settings'),
    ];
    $form['additional']['method'] = [
      '#type' => 'select',
      '#title' => $this->t('Method'),
      '#description' => $this->t('The <b>POST</b> request method requests that a web server accept the data enclosed in the body of the request message. It is often used when uploading a file or when submitting a completed webform. In contrast, the HTTP <b>GET</b> request method retrieves information from the server.'),
      '#required' => TRUE,
      '#options' => [
        'POST' => 'POST',
        'GET' => 'GET',
      ],
      '#parents' => ['settings', 'method'],
      '#default_value' => $this->configuration['method'],
    ];
    $form['additional']['type'] = [
      '#type' => 'select',
      '#title' => $this->t('Post type'),
      '#description' => $this->t('Use x-www-form-urlencoded if unsure, as it is the default format for HTML webforms. You also have the option to post data in <a href="http://www.json.org/" target="_blank">JSON</a> format.'),
      '#options' => [
        'x-www-form-urlencoded' => $this->t('x-www-form-urlencoded'),
        'json' => $this->t('JSON'),
      ],
      '#parents' => ['settings', 'type'],
      '#states' => [
        'visible' => [':input[name="settings[method]"]' => ['value' => 'POST']],
        'required' => [':input[name="settings[method]"]' => ['value' => 'POST']],
      ],
      '#default_value' => $this->configuration['type'],
    ];
    $form['additional']['custom_data'] = [
      '#type' => 'webform_codemirror',
      '#mode' => 'yaml',
      '#title' => $this->t('Custom data'),
      '#description' => $this->t('Enter custom data that will be included in all remote post requests.'),
      '#parents' => ['settings', 'custom_data'],
      '#default_value' => $this->configuration['custom_data'],
    ];
    $form['additional']['custom_options'] = [
      '#type' => 'webform_codemirror',
      '#mode' => 'yaml',
      '#title' => $this->t('Custom options'),
      '#description' => $this->t('Enter custom <a href=":href">request options</a> that will be used by the Guzzle HTTP client. Request options can included custom headers.', [':href' => 'http://docs.guzzlephp.org/en/stable/request-options.html']),
      '#parents' => ['settings', 'custom_options'],
      '#default_value' => $this->configuration['custom_options'],
    ];

    // Development.
    $form['development'] = [
      '#type' => 'details',
      '#title' => $this->t('Development settings'),
    ];
    $form['development']['debug'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable debugging'),
      '#description' => $this->t('If checked, posted submissions will be displayed onscreen to all users.'),
      '#return_value' => TRUE,
      '#parents' => ['settings', 'debug'],
      '#default_value' => $this->configuration['debug'],
    ];

    // Submission data.
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

    $form['token_tree_link'] = $this->tokenManager->buildTreeLink();

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);
    $this->applyFormStateToConfiguration($form_state);
    if ($this->configuration['method'] === 'GET') {
      $this->configuration['type'] = '';
    }
  }

  /**
   * {@inheritdoc}
   */
  public function postSave(WebformSubmissionInterface $webform_submission, $update = TRUE) {
    $state = $webform_submission->getWebform()->getSetting('results_disabled') ? WebformSubmissionInterface::STATE_COMPLETED : $webform_submission->getState();
    $this->remotePost($state, $webform_submission);
  }

  /**
   * {@inheritdoc}
   */
  public function postDelete(WebformSubmissionInterface $webform_submission) {
    $this->remotePost(WebformSubmissionInterface::STATE_DELETED, $webform_submission);
  }

  /**
   * Execute a remote post.
   *
   * @param string $state
   *   The state of the webform submission.
   *   Either STATE_NEW, STATE_DRAFT, STATE_COMPLETED, STATE_UPDATED, or
   *   STATE_CONVERTED depending on the last save operation performed.
   * @param \Drupal\webform\WebformSubmissionInterface $webform_submission
   *   The webform submission to be posted.
   */
  protected function remotePost($state, WebformSubmissionInterface $webform_submission) {
    if (empty($this->configuration[$state . '_url'])) {
      return;
    }

    $request_url = $this->configuration[$state . '_url'];
    $request_method = (!empty($this->configuration['method'])) ? $this->configuration['method'] : 'POST';
    $request_type = ($request_method == 'POST') ? $this->configuration['type'] : NULL;
    $request_options = (!empty($this->configuration['custom_options'])) ? Yaml::decode($this->configuration['custom_options']) : [];

    try {
      if ($request_method === 'GET') {
        // Append data as query string to the request URL.
        $query = $this->getRequestData($state, $webform_submission);
        $request_url = Url::fromUri($request_url, ['query' => $query])->toString();
        $response = $this->httpClient->get($request_url, $request_options);
      }
      else {
        $request_options[($request_type == 'json' ? 'json' : 'form_params')] = $this->getRequestData($state, $webform_submission);
        $response = $this->httpClient->post($request_url, $request_options);
      }
    }
    catch (RequestException $request_exception) {
      $message = $request_exception->getMessage();
      $response = $request_exception->getResponse();

      // Encode HTML entities to prevent broken markup from breaking the page.
      $message = nl2br(htmlentities($message));

      // If debugging is enabled, display the error message on screen.
      $this->debug($message, $state, $request_url, $request_method, $request_type, $request_options, $response, 'error');

      // Log error message.
      $context = [
        '@form' => $this->getWebform()->label(),
        '@state' => $state,
        '@type' => $request_type,
        '@url' => $request_url,
        '@message' => $message,
        'link' => $this->getWebform()->toLink($this->t('Edit'), 'handlers')->toString(),
      ];
      $this->getLogger()->error('@form webform remote @type post (@state) to @url failed. @message', $context);
      return;
    }

    // If debugging is enabled, display the request and response.
    $this->debug(t('Remote post successful!'), $state, $request_url, $request_method, $request_type, $request_options, $response, 'warning');

    // Replace [webform:handler] tokens in submission data.
    // Data structured for [webform:handler:remote_post:completed:key] tokens.
    $submission_data = $webform_submission->getData();
    $has_token = (strpos(print_r($submission_data, TRUE), '[webform:handler:' . $this->getHandlerId() . ':') !== FALSE) ? TRUE : FALSE;
    if ($has_token) {
      $response_data = $this->getResponseData($response);
      $token_data = ['webform_handler' => [$this->getHandlerId() => [$state => $response_data]]];
      $submission_data = $this->tokenManager->replace($submission_data, $webform_submission, $token_data);
      $webform_submission->setData($submission_data);
      // Save changes to the submission data without invoking any hooks
      // or handlers.
      if ($this->isResultsEnabled()) {
        $this->submissionStorage->saveData($webform_submission);
      }
    }
  }

  /**
   * Get a webform submission's request data.
   *
   * @param string $state
   *   The state of the webform submission.
   *   Either STATE_NEW, STATE_DRAFT, STATE_COMPLETED, STATE_UPDATED, or
   *   STATE_CONVERTED depending on the last save operation performed.
   * @param \Drupal\webform\WebformSubmissionInterface $webform_submission
   *   The webform submission to be posted.
   *
   * @return array
   *   A webform submission converted to an associative array.
   */
  protected function getRequestData($state, WebformSubmissionInterface $webform_submission) {
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

    // Append state custom data.
    if (!empty($this->configuration[$state . '_custom_data'])) {
      $data = Yaml::decode($this->configuration[$state . '_custom_data']) + $data;
    }

    // Replace tokens.
    $data = $this->tokenManager->replace($data, $webform_submission);

    return $data;
  }

  /**
   * Authentication remote post options and authentication tokens.
   *
   * @param array $options
   *   Request options including the form_params or json and the request header.
   *
   * @return array
   *   The request options with authentication tokens add to the
   *   parameters or header.
   */
  protected function authenticate(array $options) {
    // Here you can set a custom authentication token to the remote post options.
    return $options;
  }

  /**
   * Get response data.
   *
   * @param \Psr\Http\Message\ResponseInterface $response
   *   The response returned by the remote server.
   *
   * @return array|string
   *   An array of data, parse from JSON, or a string.
   */
  protected function getResponseData(ResponseInterface $response) {
    $body = (string) $response->getBody();
    $data = json_decode($body, TRUE);
    return (json_last_error() === JSON_ERROR_NONE) ? $data : $body;
  }

  /**
   * Display debugging information.
   *
   * @param string $message
   *   Message to be displayed.
   * @param string $state
   *   The state of the webform submission.
   *   Either STATE_NEW, STATE_DRAFT, STATE_COMPLETED, STATE_UPDATED, or
   *   STATE_CONVERTED depending on the last save operation performed.
   * @param string $request_url
   *   The remote URL the request is being posted to.
   * @param string $request_method
   *   The method of remote post.
   * @param string $request_type
   *   The type of remote post.
   * @param string $request_options
   *   The requests options including the submission data..
   * @param \Psr\Http\Message\ResponseInterface|null $response
   *   The response returned by the remote server.
   * @param string $type
   *   The type of message to be displayed to the end use.
   */
  protected function debug($message, $state, $request_url, $request_method, $request_type, $request_options, ResponseInterface $response = NULL, $type = 'warning') {
    if (empty($this->configuration['debug'])) {
      return;
    }

    $build = [
      '#type' => 'details',
      '#title' => $this->t('Debug: Remote post: @title [@state]', ['@title' => $this->label(), '@state' => $state]),
    ];

    // State.
    $build['state'] = [
      '#type' => 'item',
      '#title' => $this->t('Submission state/operation:'),
      '#markup' => $state,
      '#wrapper_attributes' => ['class' => ['container-inline'], 'style' => 'margin: 0'],
    ];

    // Request.
    $build['request'] = ['#markup' => '<hr />'];
    $build['request_url'] = [
      '#type' => 'item',
      '#title' => $this->t('Request URL'),
      '#markup' => $request_url,
      '#wrapper_attributes' => ['class' => ['container-inline'], 'style' => 'margin: 0'],
    ];
    $build['request_method'] = [
      '#type' => 'item',
      '#title' => $this->t('Request method'),
      '#markup' => $request_method,
      '#wrapper_attributes' => ['class' => ['container-inline'], 'style' => 'margin: 0'],
    ];
    $build['request_type'] = [
      '#type' => 'item',
      '#title' => $this->t('Request type'),
      '#markup' => $request_type,
      '#wrapper_attributes' => ['class' => ['container-inline'], 'style' => 'margin: 0'],
    ];
    $build['request_options'] = [
      '#type' => 'item',
      '#title' => $this->t('Request options'),
      '#wrapper_attributes' => ['style' => 'margin: 0'],
      'data' => [
        '#markup' => htmlspecialchars(Yaml::encode($request_options)),
        '#prefix' => '<pre>',
        '#suffix' => '</pre>',
      ],
    ];

    // Response.
    $build['response'] = ['#markup' => '<hr />'];
    if ($response) {
      $build['response_code'] = [
        '#type' => 'item',
        '#title' => $this->t('Response status code:'),
        '#markup' => $response->getStatusCode(),
        '#wrapper_attributes' => ['class' => ['container-inline'], 'style' => 'margin: 0'],
      ];
      $build['response_header'] = [
        '#type' => 'item',
        '#title' => $this->t('Response header:'),
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
        '#title' => $this->t('Response body:'),
        'data' => [
          '#markup' => htmlspecialchars($response->getBody()),
          '#prefix' => '<pre>',
          '#suffix' => '</pre>',
        ],
      ];
      $response_data = $this->getResponseData($response);
      if ($response_data) {
        $build['response_data'] = [
          '#type' => 'item',
          '#wrapper_attributes' => ['style' => 'margin: 0'],
          '#title' => $this->t('Response data:'),
          'data' => [
            '#markup' => Yaml::encode($response_data),
            '#prefix' => '<pre>',
            '#suffix' => '</pre>',
          ],
        ];

      }
      if ($tokens = $this->getResponseTokens($response_data, ['webform', 'handler', $this->getHandlerId(), $state])) {
        asort($tokens);
        $build['response_tokens'] = [
          '#type' => 'item',
          '#wrapper_attributes' => ['style' => 'margin: 0'],
          '#title' => $this->t('Response tokens:'),
          'description' => ['#markup' => $this->t('Below tokens can ONLY be used to insert response data into value and hidden elements.')],
          'data' => [
            '#markup' => implode(PHP_EOL, $tokens),
            '#prefix' => '<pre>',
            '#suffix' => '</pre>',
          ],
        ];
      }
    }
    else {
      $build['response_code'] = [
        '#markup' => t('No response. Please see the recent log messages.'),
        '#prefix' => '<p>',
        '#suffix' => '</p>',
      ];
    }

    // Message.
    $build['message'] = ['#markup' => '<hr />'];
    $build['message_message'] = [
      '#type' => 'item',
      '#wrapper_attributes' => ['style' => 'margin: 0'],
      '#title' => $this->t('Message:'),
      '#markup' => $message,
    ];

    drupal_set_message(\Drupal::service('renderer')->renderPlain($build), $type);
  }

  /**
   * Get webform handler tokens from response data.
   *
   * @param mixed $data
   *   Response data.
   * @param array $parents
   *   Webform handler token parents.
   *
   * @return array
   *   A list of webform handler tokens.
   */
  protected function getResponseTokens($data, array $parents = []) {
    $tokens = [];
    if (is_array($data)) {
      foreach ($data as $key => $value) {
        $tokens = array_merge($tokens, $this->getResponseTokens($value, array_merge($parents, [$key])));
      }
    }
    else {
      $tokens[] = '[' . implode(':', $parents) . ']';
    }
    return $tokens;
  }

  /**
   * Determine if saving of results is enabled.
   *
   * @return bool
   *   TRUE if saving of results is enabled.
   */
  protected function isResultsEnabled() {
    return ($this->getWebform()->getSetting('results_disabled') === FALSE);
  }

  /**
   * Determine if saving of draft is enabled.
   *
   * @return bool
   *   TRUE if saving of draft is enabled.
   */
  protected function isDraftEnabled() {
    return $this->isResultsEnabled() && ($this->getWebform()->getSetting('draft') != WebformInterface::DRAFT_NONE);
  }

  /**
   * Determine if converting anoynmous submissions to authenticated is enabled.
   *
   * @return bool
   *   TRUE if converting anoynmous submissions to authenticated is enabled.
   */
  protected function isConvertEnabled() {
    return $this->isDraftEnabled() && ($this->getWebform()->getSetting('form_convert_anonymous') === TRUE);
  }

}
