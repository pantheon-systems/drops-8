<?php

namespace Drupal\webform_templates\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Drupal\webform\Utility\WebformDialogHelper;
use Drupal\webform\WebformInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Provides route responses for webform templates.
 */
class WebformTemplatesController extends ControllerBase implements ContainerInjectionInterface {

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * The webform builder.
   *
   * @var \Drupal\Core\Form\FormBuilderInterface
   */
  protected $formBuilder;

  /**
   * Webform storage.
   *
   * @var \Drupal\Core\Config\Entity\ConfigEntityStorageInterface
   */
  protected $webformStorage;

  /**
   * Constructs a WebformTemplatesController object.
   *
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   * @param \Drupal\Core\Form\FormBuilderInterface $form_builder
   *   The webform builder.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity manager.
   */
  public function __construct(AccountInterface $current_user, FormBuilderInterface $form_builder, EntityTypeManagerInterface $entity_type_manager) {
    $this->currentUser = $current_user;
    $this->formBuilder = $form_builder;
    $this->webformStorage = $entity_type_manager->getStorage('webform');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('current_user'),
      $container->get('form_builder'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * Returns the webform templates index page.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request.
   *
   * @return array|RedirectResponse
   *   A render array representing the webform templates index page or redirect
   *   response to a selected webform via the filter's autocomplete.
   */
  public function index(Request $request) {
    $keys = $request->get('search');
    $category = $request->get('category');

    // Handler autocomplete redirect.
    if ($keys && preg_match('#\(([^)]+)\)$#', $keys, $match)) {
      if ($webform = $this->webformStorage->load($match[1])) {
        return new RedirectResponse($webform->toUrl()->setAbsolute(TRUE)->toString());
      }
    }

    $header = [
      $this->t('Title'),
      ['data' => $this->t('Description'), 'class' => [RESPONSIVE_PRIORITY_LOW]],
      ['data' => $this->t('Category'), 'class' => [RESPONSIVE_PRIORITY_LOW]],
      ['data' => $this->t('Operations'), 'colspan' => 2],
    ];

    $webforms = $this->getTemplates($keys, $category);
    $rows = [];
    foreach ($webforms as $webform) {
      $route_parameters = ['webform' => $webform->id()];

      $row['title'] = $webform->toLink();
      $row['description']['data']['#markup'] = $webform->get('description');
      $row['category']['data']['#markup'] = $webform->get('category');
      if ($this->currentUser->hasPermission('create webform')) {
        $row['operations']['data']['select'] = [
          '#type' => 'link',
          '#title' => $this->t('Select'),
          '#url' => Url::fromRoute('entity.webform.duplicate_form', $route_parameters),
          '#attributes' => WebformDialogHelper::getModalDialogAttributes(700, ['button', 'button--primary']),
        ];
      }
      $row['operations']['data']['preview'] = [
        '#type' => 'link',
        '#title' => $this->t('Preview'),
        '#url' => Url::fromRoute('entity.webform.preview', $route_parameters),
        '#attributes' => WebformDialogHelper::getModalDialogAttributes(800, ['button']),
      ];
      $rows[] = $row;
    }

    $build = [];
    $build['filter_form'] = $this->formBuilder->getForm('\Drupal\webform_templates\Form\WebformTemplatesFilterForm', $keys);

    // Display info.
    if ($total = count($rows)) {
      $build['info'] = [
        '#markup' => $this->formatPlural($total, '@total template', '@total templates', ['@total' => $total]),
        '#prefix' => '<div>',
        '#suffix' => '</div>',
      ];
    }

    $build['table'] = [
      '#type' => 'table',
      '#header' => $header,
      '#rows' => $rows,
      '#empty' => $this->t('There are no templates available.'),
      '#cache' => [
        'contexts' => $this->webformStorage->getEntityType()->getListCacheContexts(),
        'tags' => $this->webformStorage->getEntityType()->getListCacheTags(),
      ],
    ];

    // Must preload libraries required by (modal) dialogs.
    WebformDialogHelper::attachLibraries($build);

    return $build;
  }

  /**
   * Returns a webform to add a new submission to a webform.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request.
   * @param \Drupal\webform\WebformInterface $webform
   *   The webform this submission will be added to.
   *
   * @return array|NotFoundHttpException
   *   The webform submission webform.
   */
  public function previewForm(Request $request, WebformInterface $webform) {
    if (!$webform->isTemplate()) {
      return new NotFoundHttpException();
    }

    return $webform->getSubmissionForm([], 'preview');
  }

  /**
   * Get webform templates.
   *
   * @param string $keys
   *   (optional) Filter templates by keyword.
   * @param string $category
   *   (optional) Filter templates by category.
   *
   * @return array|\Drupal\Core\Entity\EntityInterface[]
   *   An array webform entity that are used as templates.
   */
  protected function getTemplates($keys = '', $category = '') {
    $query = $this->webformStorage->getQuery();
    $query->condition('template', TRUE);
    // Filter by key(word).
    if ($keys) {
      $or = $query->orConditionGroup()
        ->condition('title', $keys, 'CONTAINS')
        ->condition('description', $keys, 'CONTAINS')
        ->condition('category', $keys, 'CONTAINS')
        ->condition('elements', $keys, 'CONTAINS');
      $query->condition($or);
    }

    // Filter by category.
    if ($category) {
      $query->condition('category', $category);
    }

    $query->sort('title');

    $entity_ids = $query->execute();
    if (empty($entity_ids)) {
      return [];
    }

    /* @var $entities \Drupal\webform\WebformInterface[] */
    $entities = $this->webformStorage->loadMultiple($entity_ids);

    // If the user is not a webform admin, check view access to each webform.
    if (!$this->isAdmin()) {
      foreach ($entities as $entity_id => $entity) {
        if (!$entity->access('view')) {
          unset($entities[$entity_id]);
        }
      }
    }

    return $entities;

  }

  /**
   * Route preview title callback.
   *
   * @param \Drupal\webform\WebformInterface|null $webform
   *   A webform.
   *
   * @return string
   *   The webform label.
   */
  public function previewTitle(WebformInterface $webform = NULL) {
    return $this->t('Previewing @title template', ['@title' => $webform->label()]);
  }

  /**
   * Is the current user a webform administrator.
   *
   * @return bool
   *   TRUE if the current user has 'administer webform' or 'edit any webform'
   *   permission.
   */
  protected function isAdmin() {
    return ($this->currentUser->hasPermission('administer webform') || $this->currentUser->hasPermission('edit any webform'));
  }

}
