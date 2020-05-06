<?php

namespace Drupal\webform\Plugin\WebformHandler;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Routing\TrustedRedirectResponse;
use Drupal\Core\Serialization\Yaml;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\file\Entity\File;
use Drupal\webform\Element\WebformMessage;
use Drupal\webform\Plugin\WebformElement\BooleanBase;
use Drupal\webform\Plugin\WebformElement\NumericBase;
use Drupal\webform\Plugin\WebformElement\WebformCompositeBase;
use Drupal\webform\Plugin\WebformElement\WebformManagedFileBase;
use Drupal\webform\Plugin\WebformElementInterface;
use Drupal\webform\Plugin\WebformElementManagerInterface;
use Drupal\webform\Plugin\WebformHandlerBase;
use Drupal\webform\WebformInterface;
use Drupal\webform\WebformMessageManagerInterface;
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
 *   tokens = TRUE,
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
   * The webform message manager.
   *
   * @var \Drupal\webform\WebformMessageManagerInterface
   */
  protected $messageManager;

  /**
   * A webform element plugin manager.
   *
   * @var \Drupal\webform\Plugin\WebformElementManagerInterface
   */
  protected $elementManager;

  /**
   * The current request.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected $request;

  /**
   * The DrupalKernel instance used in the test.
   *
   * @var \Drupal\Core\DrupalKernel
   */
  protected $kernel;

  /**
   * List of unsupported webform submission properties.
   *
   * The below properties will not being included in a remote post.
   *
   * @var array
   */
  protected $unsupportedProperties = [
    'metatag',
  ];

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, LoggerChannelFactoryInterface $logger_factory, ConfigFactoryInterface $config_factory, EntityTypeManagerInterface $entity_type_manager, WebformSubmissionConditionsValidatorInterface $conditions_validator, ModuleHandlerInterface $module_handler, ClientInterface $http_client, WebformTokenManagerInterface $token_manager, WebformMessageManagerInterface $message_manager, WebformElementManagerInterface $element_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $logger_factory, $config_factory, $entity_type_manager, $conditions_validator);
    $this->moduleHandler = $module_handler;
    $this->httpClient = $http_client;
    $this->tokenManager = $token_manager;
    $this->messageManager = $message_manager;
    $this->elementManager = $element_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('logger.factory'),
      $container->get('config.factory'),
      $container->get('entity_type.manager'),
      $container->get('webform_submission.conditions_validator'),
      $container->get('module_handler'),
      $container->get('http_client'),
      $container->get('webform.token_manager'),
      $container->get('webform.message_manager'),
      $container->get('plugin.manager.webform.element')
    );

    $instance->request = $container->get('request_stack')->getCurrentRequest();
    $instance->kernel = $container->get('kernel');

    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function getSummary() {
    $configuration = $this->getConfiguration();
    $settings = $configuration['settings'];

    if (!$this->isResultsEnabled()) {
      $settings['updated_url'] = '';
      $settings['deleted_url'] = '';
    }
    if (!$this->isDraftEnabled()) {
      $settings['draft_created_url'] = '';
      $settings['draft_updated_url'] = '';
    }
    if (!$this->isConvertEnabled()) {
      $settings['converted_url'] = '';
    }

    return [
      '#settings' => $settings,
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
      'cast' => FALSE,
      'debug' => FALSE,
      // States.
      'completed_url' => '',
      'completed_custom_data' => '',
      'updated_url' => '',
      'updated_custom_data' => '',
      'deleted_url' => '',
      'deleted_custom_data' => '',
      'draft_created_url' => '',
      'draft_created_custom_data' => '',
      'draft_updated_url' => '',
      'draft_updated_custom_data' => '',
      'converted_url' => '',
      'converted_custom_data' => '',
      // Custom error response messages.
      'message' => '',
      'messages' => [],
      // Custom error response redirect URL.
      'error_url' => '',
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
        'description' => $this->t('Post data when <b>submission is completed</b>.'),
        'access' => TRUE,
      ],
      WebformSubmissionInterface::STATE_UPDATED => [
        'state' => $this->t('updated'),
        'label' => $this->t('Updated'),
        'description' => $this->t('Post data when <b>submission is updated</b>.'),
        'access' => $this->isResultsEnabled(),
      ],
      WebformSubmissionInterface::STATE_DELETED => [
        'state' => $this->t('deleted'),
        'label' => $this->t('Deleted'),
        'description' => $this->t('Post data when <b>submission is deleted</b>.'),
        'access' => $this->isResultsEnabled(),
      ],
      WebformSubmissionInterface::STATE_DRAFT_CREATED => [
        'state' => $this->t('draft created'),
        'label' => $this->t('Draft created'),
        'description' => $this->t('Post data when <b>draft is created.</b>'),
        'access' => $this->isDraftEnabled(),
      ],
      WebformSubmissionInterface::STATE_DRAFT_UPDATED => [
        'state' => $this->t('draft updated'),
        'label' => $this->t('Draft updated'),
        'description' => $this->t('Post data when <b>draft is updated.</b>'),
        'access' => $this->isDraftEnabled(),
      ],
      WebformSubmissionInterface::STATE_CONVERTED => [
        'state' => $this->t('converted'),
        'label' => $this->t('Converted'),
        'description' => $this->t('Post data when anonymous <b>submission is converted</b> to authenticated.'),
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
        '#default_value' => $this->configuration[$state_url],
      ];
      $form[$state][$state_custom_data] = [
        '#type' => 'webform_codemirror',
        '#mode' => 'yaml',
        '#title' => $this->t('@title custom data', $t_args),
        '#description' => $this->t('Enter custom data that will be included when a webform submission is @state.', $t_args),
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
        'PUT' => 'PUT',
        'GET' => 'GET',
      ],
      '#default_value' => $this->configuration['method'],
    ];
    $form['additional']['type'] = [
      '#type' => 'select',
      '#title' => $this->t('Post type'),
      '#description' => $this->t('Use x-www-form-urlencoded if unsure, as it is the default format for HTML webforms. You also have the option to post data in <a href="http://www.json.org/">JSON</a> format.'),
      '#options' => [
        'x-www-form-urlencoded' => $this->t('x-www-form-urlencoded'),
        'json' => $this->t('JSON'),
      ],
      '#states' => [
        '!visible' => [':input[name="settings[method]"]' => ['value' => 'GET']],
        '!required' => [':input[name="settings[method]"]' => ['value' => 'GET']],
      ],
      '#default_value' => $this->configuration['type'],
    ];
    $form['additional']['cast'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Cast posted data'),
      '#description' => $this->t('If checked, posted data will be casted to booleans and floats as needed.'),
      '#return_value' => TRUE,
      '#default_value' => $this->configuration['cast'],
    ];
    $form['additional']['custom_data'] = [
      '#type' => 'webform_codemirror',
      '#mode' => 'yaml',
      '#title' => $this->t('Custom data'),
      '#description' => $this->t('Enter custom data that will be included in all remote post requests.'),
      '#default_value' => $this->configuration['custom_data'],
    ];
    $form['additional']['custom_options'] = [
      '#type' => 'webform_codemirror',
      '#mode' => 'yaml',
      '#title' => $this->t('Custom options'),
      '#description' => $this->t('Enter custom <a href=":href">request options</a> that will be used by the Guzzle HTTP client. Request options can include custom headers.', [':href' => 'http://docs.guzzlephp.org/en/stable/request-options.html']),
      '#default_value' => $this->configuration['custom_options'],
    ];
    $form['additional']['message'] = [
      '#type' => 'webform_html_editor',
      '#title' => $this->t('Custom error response message'),
      '#description' => $this->t('This message is displayed when the response status code is not 2xx'),
      '#default_value' => $this->configuration['message'],
    ];
    $form['additional']['messages_token'] = [
      '#type' => 'webform_message',
      '#message_message' => $this->t('Response data can be passed to response message using [webform:handler:{machine_name}:{key}] tokens. (i.e. [webform:handler:remote_post:message])'),
      '#message_type' => 'info',
    ];
    $form['additional']['messages'] = [
      '#type' => 'webform_multiple',
      '#title' => $this->t('Custom error response messages'),
      '#description' => $this->t('Enter custom response messages for specific status codes.') . '<br/>' . $this->t('Defaults to: %value', ['%value' => $this->messageManager->render(WebformMessageManagerInterface::SUBMISSION_EXCEPTION_MESSAGE)]),
      '#empty_items' => 0,
      '#no_items_message' => $this->t('No error response messages entered. Please add messages below.'),
      '#add' => FALSE,
      '#element' => [
        'code' => [
          '#type' => 'webform_select_other',
          '#title' => $this->t('Response status code'),
          '#options' => [
            '400' => $this->t('400 Bad Request'),
            '401' => $this->t('401 Unauthorized'),
            '403' => $this->t('403 Forbidden'),
            '404' => $this->t('404 Not Found'),
            '500' => $this->t('500 Internal Server Error'),
            '502' => $this->t('502 Bad Gateway'),
            '503' => $this->t('503 Service Unavailable'),
            '504' => $this->t('504 Gateway Timeout'),
          ],
          '#other__type' => 'number',
          '#other__description' => $this->t('<a href="https://en.wikipedia.org/wiki/List_of_HTTP_status_codes">List of HTTP status codes</a>.'),
        ],
        'message' => [
          '#type' => 'webform_html_editor',
          '#title' => $this->t('Response message'),
        ],
      ],
      '#default_value' => $this->configuration['messages'],
    ];
    $form['additional']['error_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Custom error response redirect URL'),
      '#description' => $this->t('The URL or path to redirect to when a remote fails.', $t_args),
      '#default_value' => $this->configuration['error_url'],
      '#pattern' => '(https?:\/\/|\/).+',
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
      '#default_value' => $this->configuration['debug'],
    ];

    // Submission data.
    $form['submission_data'] = [
      '#type' => 'details',
      '#title' => $this->t('Submission data'),
    ];
    // Display warning about file uploads.
    if ($this->getWebform()->hasManagedFile()) {
      $form['submission_data']['managed_file_message'] = [
        '#type' => 'webform_message',
        '#message_message' => $this->t('Upload files will include the file\'s id, name, uri, and data (<a href=":href">Base64</a> encode).', [':href' => 'https://en.wikipedia.org/wiki/Base64']),
        '#message_type' => 'warning',
        '#message_close' => TRUE,
        '#message_id' => 'webform_node.references',
        '#message_storage' => WebformMessage::STORAGE_SESSION,
      ];
    }
    $form['submission_data']['excluded_data'] = [
      '#type' => 'webform_excluded_columns',
      '#title' => $this->t('Posted data'),
      '#title_display' => 'invisible',
      '#webform_id' => $webform->id(),
      '#required' => TRUE,
      '#default_value' => $this->configuration['excluded_data'],
    ];

    $this->elementTokenValidate($form);

    return $this->setSettingsParents($form);
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
   *   Either STATE_NEW, STATE_DRAFT_CREATED, STATE_DRAFT_UPDATED,
   *   STATE_COMPLETED, STATE_UPDATED, or STATE_CONVERTED
   *   depending on the last save operation performed.
   * @param \Drupal\webform\WebformSubmissionInterface $webform_submission
   *   The webform submission to be posted.
   */
  protected function remotePost($state, WebformSubmissionInterface $webform_submission) {
    $state_url = $state . '_url';
    if (empty($this->configuration[$state_url])) {
      return;
    }

    $this->messageManager->setWebformSubmission($webform_submission);

    $request_url = $this->configuration[$state_url];
    $request_url = $this->replaceTokens($request_url, $webform_submission);
    $request_method = (!empty($this->configuration['method'])) ? $this->configuration['method'] : 'POST';
    $request_type = ($request_method !== 'GET') ? $this->configuration['type'] : NULL;

    // Get request options with tokens replaced.
    $request_options = (!empty($this->configuration['custom_options'])) ? Yaml::decode($this->configuration['custom_options']) : [];
    $request_options = $this->replaceTokens($request_options, $webform_submission);

    try {
      if ($request_method === 'GET') {
        // Append data as query string to the request URL.
        $query = $this->getRequestData($state, $webform_submission);
        $request_url = Url::fromUri($request_url, ['query' => $query])->toString();
        $response = $this->httpClient->get($request_url, $request_options);
      }
      else {
        $method = strtolower($request_method);
        $request_options[($request_type == 'json' ? 'json' : 'form_params')] = $this->getRequestData($state, $webform_submission);
        $response = $this->httpClient->$method($request_url, $request_options);
      }
    }
    catch (RequestException $request_exception) {
      $response = $request_exception->getResponse();

      // Encode HTML entities to prevent broken markup from breaking the page.
      $message = $request_exception->getMessage();
      $message = nl2br(htmlentities($message));

      $this->handleError($state, $message, $request_url, $request_method, $request_type, $request_options, $response);
      return;
    }

    // Display submission exception if response code is not 2xx.
    $status_code = $response->getStatusCode();
    if ($status_code < 200 || $status_code >= 300) {
      $message = $this->t('Remote post request return @status_code status code.', ['@status_code' => $status_code]);
      $this->handleError($state, $message, $request_url, $request_method, $request_type, $request_options, $response);
      return;
    }

    // If debugging is enabled, display the request and response.
    $this->debug(t('Remote post successful!'), $state, $request_url, $request_method, $request_type, $request_options, $response, 'warning');

    // Replace [webform:handler] tokens in submission data.
    // Data structured for [webform:handler:remote_post:completed:key] tokens.
    $submission_data = $webform_submission->getData();
    $submission_has_token = (strpos(print_r($submission_data, TRUE), '[webform:handler:' . $this->getHandlerId() . ':') !== FALSE) ? TRUE : FALSE;
    if ($submission_has_token) {
      $response_data = $this->getResponseData($response);
      $token_data = ['webform_handler' => [$this->getHandlerId() => [$state => $response_data]]];
      $submission_data = $this->replaceTokens($submission_data, $webform_submission, $token_data);
      $webform_submission->setData($submission_data);
      // Resave changes to the submission data without invoking any hooks
      // or handlers.
      if ($this->isResultsEnabled()) {
        $webform_submission->resave();
      }
    }
  }

  /**
   * Get a webform submission's request data.
   *
   * @param string $state
   *   The state of the webform submission.
   *   Either STATE_NEW, STATE_DRAFT_CREATED, STATE_DRAFT_UPDATED,
   *   STATE_COMPLETED, STATE_UPDATED, or STATE_CONVERTED
   *   depending on the last save operation performed.
   * @param \Drupal\webform\WebformSubmissionInterface $webform_submission
   *   The webform submission to be posted.
   *
   * @return array
   *   A webform submission converted to an associative array.
   */
  protected function getRequestData($state, WebformSubmissionInterface $webform_submission) {
    // Get submission and elements data.
    $data = $webform_submission->toArray(TRUE);

    // Remove unsupported properties from data.
    // These are typically added by other module's like metatag.
    $unsupported_properties = array_combine($this->unsupportedProperties, $this->unsupportedProperties);
    $data = array_diff_key($data, $unsupported_properties);

    // Flatten data and prioritize the element data over the
    // webform submission data.
    $element_data = $data['data'];
    unset($data['data']);
    $data = $element_data + $data;

    // Excluded selected submission data.
    $data = array_diff_key($data, $this->configuration['excluded_data']);

    // Append uploaded file name, uri, and base64 data to data.
    $webform = $this->getWebform();
    foreach ($data as $element_key => $element_value) {
      if (empty($element_value)) {
        continue;
      }

      $element = $webform->getElement($element_key);
      if (!$element) {
        continue;
      }

      $element_plugin = $this->elementManager->getElementInstance($element);

      if ($element_plugin instanceof WebformManagedFileBase) {
        if ($element_plugin->hasMultipleValues($element)) {
          foreach ($element_value as $fid) {
            $data['_' . $element_key][] = $this->getResponseFileData($fid);
          }
        }
        else {
          $data['_' . $element_key] = $this->getResponseFileData($element_value);
          // @deprecated in Webform 8.x-5.0-rc17. Use new format
          // This code will be removed in 8.x-6.x.
          $data += $this->getResponseFileData($element_value, $element_key . '__');
        }
      }
      elseif (!empty($this->configuration['cast'])) {
        $data[$element_key] = $this->castRequestValues($element, $element_plugin, $element_value);
      }
    }

    // Append custom data.
    if (!empty($this->configuration['custom_data'])) {
      $data = Yaml::decode($this->configuration['custom_data']) + $data;
    }

    // Append state custom data.
    if (!empty($this->configuration[$state . '_custom_data'])) {
      $data = Yaml::decode($this->configuration[$state . '_custom_data']) + $data;
    }

    // Replace tokens.
    $data = $this->replaceTokens($data, $webform_submission);

    return $data;
  }

  /**
   * Cast request values.
   *
   * @param array $element
   *   An element.
   * @param \Drupal\webform\Plugin\WebformElementInterface $element_plugin
   *   The element's webform plugin.
   * @param mixed $value
   *   The element's value.
   *
   * @return mixed
   *   The element's values cast to boolean or float when appropriate.
   */
  protected function castRequestValues(array $element, WebformElementInterface $element_plugin, $value) {
    $element_plugin->initialize($element);
    if ($element_plugin->hasMultipleValues($element)) {
      foreach ($value as $index => $item) {
        $value[$index] = $this->castRequestValue($element, $element_plugin, $item);
      }
      return $value;
    }
    else {
      return $this->castRequestValue($element, $element_plugin, $value);
    }
  }

  /**
   * Cast request value.
   *
   * @param array $element
   *   An element.
   * @param \Drupal\webform\Plugin\WebformElementInterface $element_plugin
   *   The element's webform plugin.
   * @param mixed $value
   *   The element's value.
   *
   * @return mixed
   *   The element's value cast to boolean or float when appropriate.
   */
  protected function castRequestValue(array $element, WebformElementInterface $element_plugin, $value) {
    if ($element_plugin instanceof BooleanBase) {
      return (boolean) $value;
    }
    elseif ($element_plugin instanceof NumericBase) {
      return (float) $value;
    }
    elseif ($element_plugin instanceof WebformCompositeBase) {
      $composite_elements = (isset($element['#element']))
        ? $element['#element']
        : $element_plugin->getCompositeElements();
      foreach ($composite_elements as $key => $composite_element) {
        if (isset($value[$key])) {
          $composite_element_plugin = $this->elementManager->getElementInstance($composite_element);
          $value[$key] = $this->castRequestValue($composite_element, $composite_element_plugin, $value[$key]);
        }
      }
      return $value;
    }
    else {
      return $value;
    }
  }

  /**
   * Get request file data.
   *
   * @param int $fid
   *   A file id.
   * @param string|null $prefix
   *   A prefix to prepended to data.
   *
   * @return array
   *   An associative array containing file data (name, uri, mime, and data).
   */
  protected function getResponseFileData($fid, $prefix = '') {
    /** @var \Drupal\file\FileInterface $file */
    $file = File::load($fid);
    if (!$file) {
      return [];
    }

    $data = [];
    $data[$prefix . 'id'] = (int) $file->id();
    $data[$prefix . 'name'] = $file->getFilename();
    $data[$prefix . 'uri'] = $file->getFileUri();
    $data[$prefix . 'mime'] = $file->getMimeType();
    $data[$prefix . 'uuid'] = $file->uuid();
    $data[$prefix . 'data'] = base64_encode(file_get_contents($file->getFileUri()));
    return $data;
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
   * Determine if converting anonymous submissions to authenticated is enabled.
   *
   * @return bool
   *   TRUE if converting anonymous submissions to authenticated is enabled.
   */
  protected function isConvertEnabled() {
    return $this->isDraftEnabled() && ($this->getWebform()->getSetting('form_convert_anonymous') === TRUE);
  }

  /****************************************************************************/
  // Debug and exception handlers.
  /****************************************************************************/

  /**
   * Display debugging information.
   *
   * @param string $message
   *   Message to be displayed.
   * @param string $state
   *   The state of the webform submission.
   *   Either STATE_NEW, STATE_DRAFT_CREATED, STATE_DRAFT_UPDATED,
   *   STATE_COMPLETED, STATE_UPDATED, or STATE_CONVERTED
   *   depending on the last save operation performed.
   * @param string $request_url
   *   The remote URL the request is being posted to.
   * @param string $request_method
   *   The method of remote post.
   * @param string $request_type
   *   The type of remote post.
   * @param string $request_options
   *   The requests options including the submission data.
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
        '#markup' => $this->t('No response. Please see the recent log messages.'),
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

    $this->messenger()->addMessage(\Drupal::service('renderer')->renderPlain($build), $type);
  }

  /**
   * Handle error by logging and display debugging and/or exception message.
   *
   * @param string $state
   *   The state of the webform submission.
   *   Either STATE_NEW, STATE_DRAFT_CREATED, STATE_DRAFT_UPDATED,
   *   STATE_COMPLETED, STATE_UPDATED, or STATE_CONVERTED
   *   depending on the last save operation performed.
   * @param string $message
   *   Message to be displayed.
   * @param string $request_url
   *   The remote URL the request is being posted to.
   * @param string $request_method
   *   The method of remote post.
   * @param string $request_type
   *   The type of remote post.
   * @param string $request_options
   *   The requests options including the submission data.
   * @param \Psr\Http\Message\ResponseInterface|null $response
   *   The response returned by the remote server.
   */
  protected function handleError($state, $message, $request_url, $request_method, $request_type, $request_options, $response) {
    global $base_url, $base_path;

    // If debugging is enabled, display the error message on screen.
    $this->debug($message, $state, $request_url, $request_method, $request_type, $request_options, $response, 'error');

    // Log error message.
    $context = [
      '@form' => $this->getWebform()->label(),
      '@state' => $state,
      '@type' => $request_type,
      '@url' => $request_url,
      '@message' => $message,
      'webform_submission' => $this->getWebformSubmission(),
      'handler_id' => $this->getHandlerId(),
      'operation' => 'error',
      'link' => $this->getWebform()
        ->toLink($this->t('Edit'), 'handlers')
        ->toString(),
    ];
    $this->getLogger('webform_submission')
      ->error('@form webform remote @type post (@state) to @url failed. @message', $context);

    // Display custom or default exception message.
    if ($custom_response_message = $this->getCustomResponseMessage($response)) {
      $token_data = [
        'webform_handler' => [
          $this->getHandlerId() => $this->getResponseData($response),
        ],
      ];
      $build_message = [
        '#markup' => $this->replaceTokens($custom_response_message, $this->getWebform(), $token_data),
      ];
      $this->messenger()->addError(\Drupal::service('renderer')->renderPlain($build_message));
    }
    else {
      $this->messageManager->display(WebformMessageManagerInterface::SUBMISSION_EXCEPTION_MESSAGE, 'error');
    }

    // Redirect the current request to the error url.
    $error_url = $this->configuration['error_url'];
    if ($error_url && PHP_SAPI !== 'cli') {
      // Convert error path to URL.
      if (strpos($error_url, '/') === 0) {
        $error_url = $base_url . preg_replace('#^' . $base_path . '#', '/', $error_url);
      }
      $response = new TrustedRedirectResponse($error_url);
      // Save the session so things like messages get saved.
      $this->request->getSession()->save();
      $response->prepare($this->request);
      // Make sure to trigger kernel events.
      $this->kernel->terminate($this->request, $response);
      $response->send();
    }
  }

  /**
   * Get custom custom response message.
   *
   * @param \Psr\Http\Message\ResponseInterface|null $response
   *   The response returned by the remote server.
   *
   * @return string
   *   A custom custom response message.
   */
  protected function getCustomResponseMessage($response) {
    if ($response instanceof ResponseInterface) {
      $status_code = $response->getStatusCode();
      foreach ($this->configuration['messages'] as $message_item) {
        if ($message_item['code'] == $status_code) {
          return $message_item['message'];
        }
      }
    }
    return (!empty($this->configuration['message'])) ? $this->configuration['message'] : '';
  }

  /**
   * {@inheritdoc}
   */
  protected function buildTokenTreeElement(array $token_types = ['webform', 'webform_submission'], $description = NULL) {
    $description = $description ?: $this->t('Use [webform_submission:values:ELEMENT_KEY:raw] to get plain text values.');
    return parent::buildTokenTreeElement($token_types, $description);
  }

}
