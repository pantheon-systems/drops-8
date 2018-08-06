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
   * @var \Drupal\webform\WebformHandlerManagerInterface
   */
  protected $handlerManager;

  /**
   * The webform element manager service.
   *
   * @var \Drupal\webform\WebformElementManagerInterface
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
   * @param \Drupal\webform\WebformHandlerManagerInterface $handler_manager
   *   The webform handler manager service.
   * @param \Drupal\webform\WebformElementManagerInterface $element_manager
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

    // If the view mode is default then display the HTML version.
    if ($view_mode == 'default') {
      $view_mode = 'html';
    }

    // Build submission display.
    foreach ($entities as $id => $webform_submission) {
      $build[$id]['submission'] = [
        '#theme' => 'webform_submission_' . $view_mode,
        '#webform_submission' => $webform_submission,
        '#source_entity' => $source_entity,
      ];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function buildElements(array $elements, array $data, array $options = [], $format = 'html') {
    $build_method = 'build' . ucfirst($format);
    $build = [];

    foreach ($elements as $key => $element) {
      if (!is_array($element) || Element::property($key) || !$this->isVisibleElement($element) || isset($options['excluded_elements'][$key])) {
        continue;
      }

      $plugin_id = $this->elementManager->getElementPluginId($element);
      /** @var \Drupal\webform\WebformElementInterface $webform_element */
      $webform_element = $this->elementManager->createInstance($plugin_id);

      // Check element view access.
      if (!$webform_element->checkAccessRules('view', $element)) {
        continue;
      }

      if ($webform_element->isContainer($element)) {
        $children = $this->buildElements($element, $data, $options, $format);
        if ($children) {
          // Add #first and #last property to $children.
          // This is used to remove return from #last with multiple lines of
          // text.
          // @see webform-element-base-text.html.twig
          reset($children);
          $first_key = key($children);
          if (isset($children[$first_key]['#options'])) {
            $children[$first_key]['#options']['first'] = TRUE;
          }

          end($children);
          $last_key = key($children);
          if (isset($children[$last_key]['#options'])) {
            $children[$last_key]['#options']['last'] = TRUE;
          }
        }
        // Build the container but make sure it is not empty. Containers
        // (ie details, fieldsets, etc...) without children will be empty
        // but markup should always be rendered.
        if ($build_container = $webform_element->$build_method($element, $children, $options)) {
          $build[$key] = $build_container;
        }
      }
      else {
        $value = isset($data[$key]) ? $data[$key] : NULL;
        if ($build_element = $webform_element->$build_method($element, $value, $options)) {
          $build[$key] = $build_element;
        }
      }
    }
    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function buildTable(array $elements, array $data, array $options = []) {
    $rows = [];
    foreach ($elements as $key => $element) {
      if (isset($options['excluded_elements'][$key])) {
        continue;
      }

      $plugin_id = $this->elementManager->getElementPluginId($element);
      /** @var \Drupal\webform\WebformElementInterface $webform_element */
      $webform_element = $this->elementManager->createInstance($plugin_id);

      // Check element view access.
      if (!$webform_element->checkAccessRules('view', $element)) {
        continue;
      }

      $title = $element['#admin_title'] ?: $element['#title'] ?: '(' . $key . ')';
      $value = (isset($data[$key])) ? $webform_element->formatHtml($element, $data[$key], $options) : '';
      $rows[] = [
        [
          'header' => TRUE,
          'data' => $title,
        ],
        [
          'data' => (is_string($value)) ? ['#markup' => $value] : $value,
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
   *
   * @return bool
   *   TRUE if the element is visible, otherwise FALSE.
   */
  protected function isVisibleElement(array $element) {
    return (!isset($element['#access']) || (($element['#access'] instanceof AccessResultInterface && $element['#access']->isAllowed()) || ($element['#access'] === TRUE)));
  }

}
