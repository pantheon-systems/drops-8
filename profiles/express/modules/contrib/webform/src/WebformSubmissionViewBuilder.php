<?php

namespace Drupal\webform;

use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityViewBuilder;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\webform\Plugin\WebformElementAttachmentInterface;
use Drupal\webform\Plugin\WebformElementManagerInterface;
use Drupal\webform\Twig\WebformTwigExtension;
use Drupal\webform\Utility\WebformElementHelper;
use Drupal\webform\Utility\WebformYaml;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Render controller for webform submissions.
 */
class WebformSubmissionViewBuilder extends EntityViewBuilder implements WebformSubmissionViewBuilderInterface {

  /**
   * The route match object.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * Webform request handler.
   *
   * @var \Drupal\webform\WebformRequestInterface
   */
  protected $requestHandler;

  /**
   * The webform element manager service.
   *
   * @var \Drupal\webform\Plugin\WebformElementManagerInterface
   */
  protected $elementManager;

  /**
   * The webform submission (server-side) conditions (#states) validator.
   *
   * @var \Drupal\webform\WebformSubmissionConditionsValidator
   */
  protected $conditionsValidator;

  /**
   * Constructs a WebformSubmissionViewBuilder.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition.
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager service.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   * @param \Drupal\webform\WebformRequestInterface $webform_request
   *   The webform request handler.
   * @param \Drupal\webform\Plugin\WebformElementManagerInterface $element_manager
   *   The webform element manager service.
   * @param \Drupal\webform\WebformSubmissionConditionsValidatorInterface $conditions_validator
   *   The webform submission conditions (#states) validator.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The route match object.
   *
   * @todo Webform 8.x-6.x: Move $route_match before $webform_request.
   */
  public function __construct(EntityTypeInterface $entity_type, EntityManagerInterface $entity_manager, LanguageManagerInterface $language_manager, WebformRequestInterface $webform_request, WebformElementManagerInterface $element_manager, WebformSubmissionConditionsValidatorInterface $conditions_validator, RouteMatchInterface $route_match = NULL) {
    parent::__construct($entity_type, $entity_manager, $language_manager);
    $this->requestHandler = $webform_request;
    $this->elementManager = $element_manager;
    $this->conditionsValidator = $conditions_validator;
    $this->routeMatch = $route_match ?: \Drupal::routeMatch();
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $entity_type,
      $container->get('entity.manager'),
      $container->get('language_manager'),
      $container->get('webform.request'),
      $container->get('plugin.manager.webform.element'),
      $container->get('webform_submission.conditions_validator'),
      $container->get('current_route_match')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function view(EntityInterface $entity, $view_mode = 'full', $langcode = NULL) {
    // Allow modules to set custom webform submission view mode.
    // @see \Drupal\webform_entity_print\Plugin\WebformExporter\WebformEntityPrintWebformExporter::writeSubmission
    if ($webform_submissions_view_mode = \Drupal::request()->request->get('_webform_submissions_view_mode')) {
      $view_mode = $webform_submissions_view_mode;
    }

    // Apply variants.
    /** @var \Drupal\webform\WebformSubmissionInterface $entity */
    /** @var \Drupal\webform\WebformInterface $webform */
    $webform = $entity->getWebform();
    $webform->applyVariants($entity);

    return parent::view($entity, $view_mode, $langcode);
  }

  /**
   * {@inheritdoc}
   */
  protected function getBuildDefaults(EntityInterface $entity, $view_mode) {
    $build = parent::getBuildDefaults($entity, $view_mode);
    // The webform submission will be rendered in the wrapped webform submission
    // template already. Instead we are going to wrap the rendered submission
    // in a webform submission data template.
    // @see \Drupal\contact_storage\ContactMessageViewBuilder
    // @see \Drupal\comment\CommentViewBuilder::getBuildDefaults
    // @see \Drupal\block_content\BlockContentViewBuilder::getBuildDefaults
    // @see webform-submission-data.html.twig
    $build['#theme'] = 'webform_submission_data';
    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function buildComponents(array &$build, array $entities, array $displays, $view_mode) {
    if (empty($entities)) {
      return;
    }

    /** @var \Drupal\webform\WebformSubmissionInterface[] $entities */
    foreach ($entities as $id => $webform_submission) {
      $webform = $webform_submission->getWebform();

      if ($view_mode === 'preview') {
        $options = [
          'view_mode' => $view_mode,
          'excluded_elements' => $webform->getSetting('preview_excluded_elements'),
          'exclude_empty' => $webform->getSetting('preview_exclude_empty'),
          'exclude_empty_checkbox' => $webform->getSetting('preview_exclude_empty_checkbox'),
        ];
      }
      else {
        // Track PDF.
        // @see webform_entity_print.module
        $route_name = $this->routeMatch->getRouteName();
        $pdf = in_array($route_name, ['entity_print.view.debug', 'entity_print.view'])
          || \Drupal::request()->request->get('_webform_entity_print');
        $options = [
          'view_mode' => $view_mode,
          'excluded_elements' => $webform->getSetting('submission_excluded_elements'),
          'exclude_empty' => $webform->getSetting('submission_exclude_empty'),
          'exclude_empty_checkbox' => $webform->getSetting('submission_exclude_empty_checkbox'),
          'pdf' => $pdf,
        ];
      }

      switch ($view_mode) {
        case 'twig':
          // @see \Drupal\webform_entity_print_attachment\Element\WebformEntityPrintAttachment::getFileContent
          $build[$id]['data'] = WebformTwigExtension::buildTwigTemplate(
            $webform_submission,
            $webform_submission->_webform_view_mode_twig
          );
          break;

        case 'yaml':
          // Note that the YAML view ignores all access controls and excluded
          // settings.
          $data = $webform_submission->toArray(TRUE, TRUE);
          // Covert computed element value markup to strings to
          // 'Object support when dumping a YAML file has been disabled' errors.
          WebformElementHelper::convertRenderMarkupToStrings($data);
          $build[$id]['data'] = [
            '#theme' => 'webform_codemirror',
            '#code' => WebformYaml::encode($data),
            '#type' => 'yaml',
          ];
          break;

        case 'text':
          $elements = $webform->getElementsInitialized();
          $build[$id]['data'] = [
            '#theme' => 'webform_codemirror',
            '#code' => $this->buildElements($elements, $webform_submission, $options, 'text'),
          ];
          break;

        case 'table':
          $elements = $webform->getElementsInitializedFlattenedAndHasValue();
          $build[$id]['data'] = $this->buildTable($elements, $webform_submission, $options);
          break;

        default:
        case 'html':
          $elements = $webform->getElementsInitialized();
          $build[$id]['data'] = $this->buildElements($elements, $webform_submission, $options);
          break;
      }
    }

    parent::buildComponents($build, $entities, $displays, $view_mode);
  }

  /**
   * {@inheritdoc}
   */
  public function buildElements(array $elements, WebformSubmissionInterface $webform_submission, array $options = [], $format = 'html') {
    $build_method = 'build' . ucfirst($format);
    $build = [];

    foreach ($elements as $key => $element) {
      if (!WebformElementHelper::isElement($element, $key)) {
        continue;
      }

      /** @var \Drupal\webform\Plugin\WebformElementInterface $webform_element */
      $webform_element = $this->elementManager->getElementInstance($element);

      // Replace tokens before building the element.
      $webform_element->replaceTokens($element, $webform_submission);

      if ($build_element = $webform_element->$build_method($element, $webform_submission, $options)) {
        $build[$key] = $build_element;
        if (!$this->isElementVisible($element, $webform_submission, $options)) {
          $build[$key]['#access'] = FALSE;
        };
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
      if (!$this->isElementVisible($element, $webform_submission, $options)) {
        continue;
      }

      /** @var \Drupal\webform\Plugin\WebformElementInterface $webform_element */
      $webform_element = $this->elementManager->getElementInstance($element);

      // Replace tokens before building the element.
      $webform_element->replaceTokens($element, $webform_submission);

      // Check if empty value is excluded.
      if ($webform_element->isEmptyExcluded($element, $options) && !$webform_element->getValue($element, $webform_submission, $options)) {
        continue;
      }

      $title = $element['#admin_title'] ?: $element['#title'] ?: '(' . $key . ')';
      // Note: Not displaying an empty message since empty values just render
      // an empty table cell.
      $html = $webform_element->formatHtml($element, $webform_submission, $options);
      $rows[$key] = [
        ['header' => TRUE, 'data' => $title],
        ['data' => (is_string($html)) ? ['#markup' => $html] : $html],
      ];
    }

    return [
      '#type' => 'table',
      '#rows' => $rows,
      '#attributes' => [
        'class' => ['webform-submission-table'],
      ],
    ];
  }

  /**
   * Determines if an element is visible.
   *
   * @param array $element
   *   The element to check for visibility.
   * @param \Drupal\webform\WebformSubmissionInterface $webform_submission
   *   A webform submission.
   * @param array $options
   *   - excluded_elements: An array of elements to be excluded.
   *   - ignore_access: Flag to ignore private and/or access controls and always
   *     display the element.
   *   - email: Format element to be send via email.
   *
   * @return bool
   *   TRUE if the element is visible, otherwise FALSE.
   *
   * @see \Drupal\webform\WebformSubmissionConditionsValidatorInterface::isElementVisible
   * @see \Drupal\Core\Render\Element::isVisibleElement
   */
  protected function isElementVisible(array $element, WebformSubmissionInterface $webform_submission, array $options) {
    // Checked excluded elements.
    if (isset($element['#webform_key']) && isset($options['excluded_elements'][$element['#webform_key']])) {
      return FALSE;
    }

    // Checked excluded attachments.
    if (!empty($options['exclude_attachments'])) {
      /** @var \Drupal\webform\Plugin\WebformElementInterface $webform_element */
      $webform_element = $this->elementManager->getElementInstance($element, $webform_submission);
      if ($webform_element instanceof WebformElementAttachmentInterface) {
        return FALSE;
      }
    }

    // Check if the element is conditionally hidden.
    if (!$this->conditionsValidator->isElementVisible($element, $webform_submission)) {
      return FALSE;
    }

    // Check if ignore access is set.
    // This is used email handlers to include administrative elements in emails.
    if (!empty($options['ignore_access'])) {
      return TRUE;
    }

    // Check check the element's #access.
    if (isset($element['#access']) && (($element['#access'] instanceof AccessResultInterface && $element['#access']->isForbidden()) || ($element['#access'] === FALSE))) {
      return FALSE;
    }

    // Finally, check the element's 'view' access.
    /** @var \Drupal\webform\Plugin\WebformElementInterface $webform_element */
    $webform_element = $this->elementManager->getElementInstance($element, $webform_submission);
    return $webform_element->checkAccessRules('view', $element) ? TRUE : FALSE;
  }

}
