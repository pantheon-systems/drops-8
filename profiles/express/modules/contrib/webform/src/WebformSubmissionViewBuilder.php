<?php

namespace Drupal\webform;

use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityViewBuilder;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\Utility\Token;
use Drupal\webform\Plugin\WebformElementManagerInterface;
use Drupal\webform\Plugin\WebformHandlerManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Render controller for webform submissions.
 */
class WebformSubmissionViewBuilder extends EntityViewBuilder implements WebformSubmissionViewBuilderInterface {

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The token handler.
   *
   * @var \Drupal\Core\Utility\Token
   */
  protected $token;

  /**
   * Webform request handler.
   *
   * @var \Drupal\webform\WebformRequestInterface
   */
  protected $requestManager;

  /**
   * The webform handler manager service.
   *
   * @var \Drupal\webform\Plugin\WebformHandlerManagerInterface
   */
  protected $handlerManager;

  /**
   * The webform element manager service.
   *
   * @var \Drupal\webform\Plugin\WebformElementManagerInterface
   */
  protected $elementManager;

  /**
   * Constructs a WebformSubmissionViewBuilder.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition.
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager service.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Core\Utility\Token $token
   *   The token handler.
   * @param \Drupal\webform\WebformRequestInterface $webform_request
   *   The webform request handler.
   * @param \Drupal\webform\Plugin\WebformHandlerManagerInterface $handler_manager
   *   The webform handler manager service.
   * @param \Drupal\webform\Plugin\WebformElementManagerInterface $element_manager
   *   The webform element manager service.
   */
  public function __construct(EntityTypeInterface $entity_type, EntityManagerInterface $entity_manager, LanguageManagerInterface $language_manager, ConfigFactoryInterface $config_factory, Token $token, WebformRequestInterface $webform_request, WebformHandlerManagerInterface $handler_manager, WebformElementManagerInterface $element_manager) {
    parent::__construct($entity_type, $entity_manager, $language_manager);
    $this->configFactory = $config_factory;
    $this->token = $token;
    $this->requestManager = $webform_request;
    $this->handlerManager = $handler_manager;
    $this->elementManager = $element_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $entity_type,
      $container->get('entity.manager'),
      $container->get('language_manager'),
      $container->get('config.factory'),
      $container->get('token'),
      $container->get('webform.request'),
      $container->get('plugin.manager.webform.handler'),
      $container->get('plugin.manager.webform.element')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildComponents(array &$build, array $entities, array $displays, $view_mode) {
    /** @var \Drupal\webform\WebformSubmissionInterface[] $entities */
    /** @var \Drupal\webform\WebformSubmissionInterface $webform_submission */
    if (empty($entities)) {
      return;
    }
    $source_entity = $this->requestManager->getCurrentSourceEntity('webform_submission');
    parent::buildComponents($build, $entities, $displays, $view_mode);

    // Make sure $view_mode is supported, else use the HTML template and
    // display submission information because the submission is most likely
    // being rendered without any context.
    // @see webform_theme()
    // @see \Drupal\webform\Controller\WebformSubmissionController::index
    // @see https://www.drupal.org/project/entity_print
    if (in_array($view_mode, ['html', 'table', 'text', 'yaml'])) {
      $submission_template = 'webform_submission_' . $view_mode;
      $display_submission_information = FALSE;
    }
    else {
      $submission_template = 'webform_submission_html';
      $display_submission_information  = TRUE;
    }

    // Build submission display.
    foreach ($entities as $id => $webform_submission) {
      if ($display_submission_information ) {
        $build[$id]['information'] = [
          '#theme' => 'webform_submission_information',
          '#webform_submission' => $webform_submission,
          '#source_entity' => $source_entity,
        ];
      }
      $build[$id]['submission'] = [
        '#theme' => $submission_template,
        '#webform_submission' => $webform_submission,
        '#source_entity' => $source_entity,
      ];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function buildElements(array $elements, WebformSubmissionInterface $webform_submission, array $options = [], $format = 'html') {
    $build_method = 'build' . ucfirst($format);
    $build = [];

    foreach ($elements as $key => $element) {
      if (!is_array($element) || Element::property($key) || !$this->isVisibleElement($element, $options) || isset($options['excluded_elements'][$key])) {
        continue;
      }

      $plugin_id = $this->elementManager->getElementPluginId($element);
      /** @var \Drupal\webform\Plugin\WebformElementInterface $webform_element */
      $webform_element = $this->elementManager->createInstance($plugin_id);

      // Check element view access.
      if (empty($options['ignore_access']) && !$webform_element->checkAccessRules('view', $element)) {
        continue;
      }

      if ($build_element = $webform_element->$build_method($element, $webform_submission, $options)) {
        $build[$key] = $build_element;
      }
    }
    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function buildTable(array $elements, WebformSubmissionInterface $webform_submission, array $options = []) {
    $rows = [];
    foreach ($elements as $key => $element) {
      if (isset($options['excluded_elements'][$key])) {
        continue;
      }

      $plugin_id = $this->elementManager->getElementPluginId($element);
      /** @var \Drupal\webform\Plugin\WebformElementInterface $webform_element */
      $webform_element = $this->elementManager->createInstance($plugin_id);

      // Check element view access.
      if (!$webform_element->checkAccessRules('view', $element)) {
        continue;
      }

      $title = $element['#admin_title'] ?: $element['#title'] ?: '(' . $key . ')';
      $html = $webform_element->formatHtml($element, $webform_submission, $options);
      $rows[] = [
        [
          'header' => TRUE,
          'data' => $title,
        ],
        [
          'data' => (is_string($html)) ? ['#markup' => $html] : $html,
        ],
      ];
    }

    return [
      '#type' => 'table',
      '#rows' => $rows,
      '#attributes' => [
        'class' => ['webform-submission__table'],
      ],
    ];
  }

  /**
   * Determines if an element is visible.
   *
   * Copied from: \Drupal\Core\Render\Element::isVisibleElement
   * but does not hide hidden or value elements.
   *
   * @param array $element
   *   The element to check for visibility.
   * @param array $options
   *   - excluded_elements: An array of elements to be excluded.
   *   - ignore_access: Flag to ignore private and/or access controls and always
   *     display the element.
   *   - email: Format element to be send via email.
   *
   * @return bool
   *   TRUE if the element is visible, otherwise FALSE.
   */
  protected function isVisibleElement(array $element, array $options) {
    if (!empty($options['ignore_access'])) {
      return TRUE;
    }
    return (!isset($element['#access']) || (($element['#access'] instanceof AccessResultInterface && $element['#access']->isAllowed()) || ($element['#access'] === TRUE)));
  }

}
