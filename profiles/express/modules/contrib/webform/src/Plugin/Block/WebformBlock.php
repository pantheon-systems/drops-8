<?php

namespace Drupal\webform\Plugin\Block;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\EventSubscriber\MainContentViewSubscriber;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\webform\WebformInterface;
use Drupal\webform\WebformTokenManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Provides a 'Webform' block.
 *
 * @Block(
 *   id = "webform_block",
 *   admin_label = @Translation("Webform"),
 *   category = @Translation("Webform")
 * )
 */
class WebformBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * Entity type manager.
   *
   * @var \Drupal\core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The webform token manager.
   *
   * @var \Drupal\webform\WebformTokenManagerInterface
   */
  protected $tokenManager;

  /**
   * Creates a WebformBlock instance.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\webform\WebformTokenManagerInterface $token_manager
   *   The webform token manager.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, RequestStack $request_stack, EntityTypeManagerInterface $entity_type_manager, WebformTokenManagerInterface $token_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->requestStack = $request_stack;
    $this->entityTypeManager = $entity_type_manager;
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
      $container->get('request_stack'),
      $container->get('entity_type.manager'),
      $container->get('webform.token_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'webform_id' => '',
      'default_data' => '',
      'redirect' => FALSE,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $wrapper_format = $this->requestStack->getCurrentRequest()
      ->get(MainContentViewSubscriber::WRAPPER_FORMAT);
    $is_off_canvas = in_array($wrapper_format, ['drupal_dialog.off_canvas']);

    // Get title, description, and code example.
    $title = $this->t('Default webform submission data (YAML)');
    $description = [
      'content' => ['#markup' => $this->t('Enter submission data as name and value pairs as <a href=":href">YAML</a> which will be used to prepopulate the selected webform.', [':href' => 'https://en.wikipedia.org/wiki/YAML']), '#suffix' => ' '],
      'token' => $this->tokenManager->buildTreeLink(),
    ];
    $example = [];
    $example[] = '# ' . $this->t('This is an example of a comment.');
    $example[] = "element_key: 'some value'";
    $example[] = '';
    $example[] = '# ' . $this->t("The below example uses a token to get the current node's title.");
    $example[] = "title: '[webform_submission:node:title:clear]'";
    $example[] = '';
    $example[] = '# ' . $this->t("Add ':clear' to the end token to return an empty value when the token is missing.");
    $example[] = '# ' . $this->t('The below example uses a token to get a field value from the current node.');
    $example[] = "full_name: '[webform_submission:node:field_full_name:clear]'";
    $example[] = '';

    $form['#attributes'] = ['class' => ['webform-block-settings-tray-form']];
    $form['webform_id'] = [
      '#title' => $this->t('Webform'),
      '#type' => 'entity_autocomplete',
      '#target_type' => 'webform',
      '#required' => TRUE,
      '#default_value' => $this->getWebform(),
    ];
    if ($is_off_canvas) {
      $form['default_data'] = [
        '#title' => $title,
        '#type' => 'textarea',
        '#default_value' => $this->configuration['default_data'],
        '#webform_element' => TRUE,
        '#description' => $description,
        '#more_title' => $this->t('Example'),
        '#wrapper_attributes' => [
          'class' => ['webform-default-data'],
        ],
        '#more' => [
          '#markup' => implode(PHP_EOL, $example),
          '#prefix' => '<pre>',
          '#suffix' => '</pre>',
        ],
      ];
      $form['#attached']['library'][] = 'webform/webform.off_canvas';
    }
    else {
      $form['default_data'] = [
        '#title' => $title,
        '#type' => 'webform_codemirror',
        '#mode' => 'yaml',
        '#default_value' => $this->configuration['default_data'],
        '#webform_element' => TRUE,
        '#description' => $description,
        '#more_title' => $this->t('Example'),
        '#more' => [
          '#theme' => 'webform_codemirror',
          '#type' => 'yaml',
          '#code' => implode(PHP_EOL, $example),
        ],
      ];
    }
    $form['redirect'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Redirect to the webform'),
      '#default_value' => $this->configuration['redirect'],
      '#return_value' => TRUE,
      '#description' => $this->t('If your webform has multiple pages, this will change the behavior of the "Next" button. This will also affect where validation messages show up after an error.'),
    ];

    $form['token_tree_link'] = $this->tokenManager->buildTreeElement();

    $this->tokenManager->elementValidate($form);

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->configuration['webform_id'] = $form_state->getValue('webform_id');
    $this->configuration['default_data'] = $form_state->getValue('default_data');
    $this->configuration['redirect'] = $form_state->getValue('redirect');
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build = [
      '#type' => 'webform',
      '#webform' => $this->getWebform(),
      '#default_data' => $this->configuration['default_data'],
    ];

    // If redirect, set the #action property on the form.
    if ($this->configuration['redirect']) {
      $build['#action'] = $this->getWebform()->toUrl()
        ->setOption('query', $this->requestStack->getCurrentRequest()->query->all())
        ->toString();
    }

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  protected function blockAccess(AccountInterface $account) {
    $webform = $this->getWebform();
    if (!$webform) {
      return AccessResult::forbidden();
    }

    $access_result = $webform->access('submission_create', $account, TRUE);
    if ($access_result->isAllowed()) {
      return $access_result;
    }

    $has_access_denied_message = ($webform->getSetting('form_access_denied') !== WebformInterface::ACCESS_DENIED_DEFAULT);
    return AccessResult::allowedIf($has_access_denied_message)
      ->addCacheableDependency($access_result);
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    $dependencies = parent::calculateDependencies();

    if ($webform = $this->getWebform()) {
      $dependencies[$webform->getConfigDependencyKey()][] = $webform->getConfigDependencyName();
    }

    return $dependencies;
  }

  /**
   * Get this block instance webform.
   *
   * @return \Drupal\webform\WebformInterface
   *   A webform or NULL.
   */
  protected function getWebform() {
    return $this->entityTypeManager->getStorage('webform')->load($this->configuration['webform_id']);
  }

}
