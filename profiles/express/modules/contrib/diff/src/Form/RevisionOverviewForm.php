<?php

namespace Drupal\diff\Form;

use Drupal\Component\Utility\Xss;
use Drupal\Core\Entity\Query\QueryFactory;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Link;
use Drupal\diff\DiffEntityComparison;
use Drupal\diff\DiffLayoutManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Datetime\DateFormatter;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\Core\Render\RendererInterface;

/**
 * Provides a form for revision overview page.
 */
class RevisionOverviewForm extends FormBase {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The current user service.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * The date service.
   *
   * @var \Drupal\Core\Datetime\DateFormatter
   */
  protected $date;

  /**
   * The renderer service.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * Wrapper object for simple configuration from diff.settings.yml.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $config;

  /**
   * The field diff layout plugin manager service.
   *
   * @var \Drupal\diff\DiffLayoutManager
   */
  protected $diffLayoutManager;

  /**
   * The diff entity comparison service.
   *
   * @var \Drupal\diff\DiffEntityComparison
   */
  protected $entityComparison;

  /**
   * The entity query factory service.
   *
   * @var \Drupal\Core\Entity\Query\QueryFactory
   */
  protected $entityQuery;

  /**
   * Constructs a RevisionOverviewForm object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   * @param \Drupal\Core\Datetime\DateFormatter $date
   *   The date service.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer service.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   * @param \Drupal\diff\DiffLayoutManager $diff_layout_manager
   *   The diff layout service.
   * @param \Drupal\diff\DiffEntityComparison $entity_comparison
   *   The diff entity comparison service.
   * @param \Drupal\Core\Entity\Query\QueryFactory $entity_query
   *   The entity query factory.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, AccountInterface $current_user, DateFormatter $date, RendererInterface $renderer, LanguageManagerInterface $language_manager, DiffLayoutManager $diff_layout_manager, DiffEntityComparison $entity_comparison, QueryFactory $entity_query) {
    $this->entityTypeManager = $entity_type_manager;
    $this->currentUser = $current_user;
    $this->date = $date;
    $this->renderer = $renderer;
    $this->languageManager = $language_manager;
    $this->config = $this->config('diff.settings');
    $this->diffLayoutManager = $diff_layout_manager;
    $this->entityComparison = $entity_comparison;
    $this->entityQuery = $entity_query;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('current_user'),
      $container->get('date.formatter'),
      $container->get('renderer'),
      $container->get('language_manager'),
      $container->get('plugin.manager.diff.layout'),
      $container->get('diff.entity_comparison'),
      $container->get('entity.query')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'revision_overview_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $node = NULL) {
    $account = $this->currentUser;
    /** @var \Drupal\node\NodeInterface $node */
    $langcode = $node->language()->getId();
    $langname = $node->language()->getName();
    $languages = $node->getTranslationLanguages();
    $has_translations = (count($languages) > 1);
    $node_storage = $this->entityTypeManager->getStorage('node');
    $type = $node->getType();

    $pagerLimit = $this->config->get('general_settings.revision_pager_limit');

    $query = $this->entityQuery->get('node')
      ->condition($node->getEntityType()->getKey('id'), $node->id())
      ->pager($pagerLimit)
      ->allRevisions()
      ->sort($node->getEntityType()->getKey('revision'), 'DESC')
      ->execute();
    $vids = array_keys($query);

    $revision_count = count($vids);

    $build['#title'] = $has_translations ? $this->t('@langname revisions for %title', [
      '@langname' => $langname,
      '%title' => $node->label(),
    ]) : $this->t('Revisions for %title', [
      '%title' => $node->label(),
    ]);
    $build['nid'] = array(
      '#type' => 'hidden',
      '#value' => $node->id(),
    );

    $table_header = [];
    $table_header['revision'] = $this->t('Revision');

    // Allow comparisons only if there are 2 or more revisions.
    if ($revision_count > 1) {
      $table_header += array(
        'select_column_one' => '',
        'select_column_two' => '',
      );
    }
    $table_header['operations'] = $this->t('Operations');

    $rev_revert_perm = $account->hasPermission("revert $type revisions") ||
      $account->hasPermission('revert all revisions') ||
      $account->hasPermission('administer nodes');
    $rev_delete_perm = $account->hasPermission("delete $type revisions") ||
      $account->hasPermission('delete all revisions') ||
      $account->hasPermission('administer nodes');
    $revert_permission = $rev_revert_perm && $node->access('update');
    $delete_permission = $rev_delete_perm && $node->access('delete');

    // Contains the table listing the revisions.
    $build['node_revisions_table'] = array(
      '#type' => 'table',
      '#header' => $table_header,
      '#attributes' => array('class' => array('diff-revisions')),
    );

    $build['node_revisions_table']['#attached']['library'][] = 'diff/diff.general';
    $build['node_revisions_table']['#attached']['drupalSettings']['diffRevisionRadios'] = $this->config->get('general_settings.radio_behavior');

    $default_revision = $node->getRevisionId();
    // Add rows to the table.
    foreach ($vids as $key => $vid) {
      $previous_revision = NULL;
      if (isset($vids[$key + 1])) {
        $previous_revision = $node_storage->loadRevision($vids[$key + 1]);
      }
      /** @var \Drupal\Core\Entity\ContentEntityInterface $revision */
      if ($revision = $node_storage->loadRevision($vid)) {
        if ($revision->hasTranslation($langcode) && $revision->getTranslation($langcode)->isRevisionTranslationAffected()) {
          $username = array(
            '#theme' => 'username',
            '#account' => $revision->getRevisionAuthor(),
          );
          $revision_date = $this->date->format($revision->getRevisionCreationTime(), 'short');
          // Use revision link to link to revisions that are not active.
          if ($vid != $node->getRevisionId()) {
            $link = Link::fromTextAndUrl($revision_date, new Url('entity.node.revision', ['node' => $node->id(), 'node_revision' => $vid]));
          }
          else {
            $link = $node->toLink($revision_date);
          }

          if ($vid == $default_revision) {
            $row = [
              'revision' => $this->buildRevision($link, $username, $revision, $previous_revision),
            ];

            // Allow comparisons only if there are 2 or more revisions.
            if ($revision_count > 1) {
              $row += [
                'select_column_one' => $this->buildSelectColumn('radios_left', $vid, FALSE),
                'select_column_two' => $this->buildSelectColumn('radios_right', $vid, $vid),
              ];
            }
            $row['operations'] = array(
              '#prefix' => '<em>',
              '#markup' => $this->t('Current revision'),
              '#suffix' => '</em>',
              '#attributes' => array(
                'class' => array('revision-current'),
              ),
            );
            $row['#attributes'] = [
              'class' => ['revision-current'],
            ];
          }
          else {
            $route_params = array(
              'node' => $node->id(),
              'node_revision' => $vid,
              'langcode' => $langcode,
            );
            $links = array();
            if ($revert_permission) {
              $links['revert'] = [
                'title' => $vid < $node->getRevisionId() ? $this->t('Revert') : $this->t('Set as current revision'),
                'url' => $has_translations ?
                  Url::fromRoute('node.revision_revert_translation_confirm', ['node' => $node->id(), 'node_revision' => $vid, 'langcode' => $langcode]) :
                  Url::fromRoute('node.revision_revert_confirm', ['node' => $node->id(), 'node_revision' => $vid]),
              ];
            }
            if ($delete_permission) {
              $links['delete'] = array(
                'title' => $this->t('Delete'),
                'url' => Url::fromRoute('node.revision_delete_confirm', $route_params),
              );
            }

            // Here we don't have to deal with 'only one revision' case because
            // if there's only one revision it will also be the default one,
            // entering on the first branch of this if else statement.
            $row = [
              'revision' => $this->buildRevision($link, $username, $revision, $previous_revision),
              'select_column_one' => $this->buildSelectColumn('radios_left', $vid,
                isset($vids[1]) ? $vids[1] : FALSE),
              'select_column_two' => $this->buildSelectColumn('radios_right', $vid, FALSE),
              'operations' => [
                '#type' => 'operations',
                '#links' => $links,
              ],
            ];
          }
          // Add the row to the table.
          $build['node_revisions_table'][] = $row;
        }
      }
    }

    // Allow comparisons only if there are 2 or more revisions.
    if ($revision_count > 1) {
      $build['submit'] = array(
        '#type' => 'submit',
        '#button_type' => 'primary',
        '#value' => t('Compare selected revisions'),
        '#attributes' => array(
          'class' => array(
            'diff-button',
          ),
        ),
      );
    }
    $build['pager'] = array(
      '#type' => 'pager',
    );
    $build['#attached']['library'][] = 'node/drupal.node.admin';
    return $build;
  }

  /**
   * Set column attributes and return config array.
   *
   * @param string $name
   *   Name attribute.
   * @param string $return_val
   *   Return value attribute.
   * @param string $default_val
   *   Default value attribute.
   *
   * @return array
   *   Configuration array.
   */
  protected function buildSelectColumn($name, $return_val, $default_val) {
    return [
      '#type' => 'radio',
      '#title_display' => 'invisible',
      '#name' => $name,
      '#return_value' => $return_val,
      '#default_value' => $default_val,
    ];
  }

  /**
   * Set and return configuration for revision.
   *
   * @param \Drupal\Core\Link $link
   *   Link attribute.
   * @param string $username
   *   Username attribute.
   * @param \Drupal\Core\Entity\ContentEntityInterface $revision
   *   Revision parameter for getRevisionDescription function.
   * @param \Drupal\Core\Entity\ContentEntityInterface $previous_revision
   *   (optional) Previous revision for getRevisionDescription function.
   *   Defaults to NULL.
   *
   * @return array
   *   Configuration for revision.
   */
  protected function buildRevision(Link $link, $username, ContentEntityInterface $revision, ContentEntityInterface $previous_revision = NULL) {
    return [
      '#type' => 'inline_template',
      '#template' => '{% trans %}{{ date }} by {{ username }}{% endtrans %}{% if message %}<p class="revision-log">{{ message }}</p>{% endif %}',
      '#context' => [
        'date' => $link->toString(),
        'username' => $this->renderer->renderPlain($username),
        'message' => [
          '#markup' => $this->entityComparison->getRevisionDescription($revision, $previous_revision),
          '#allowed_tags' => Xss::getAdminTagList(),
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $input = $form_state->getUserInput();

    if (count($form_state->getValue('node_revisions_table')) <= 1) {
      $form_state->setErrorByName('node_revisions_table', $this->t('Multiple revisions are needed for comparison.'));
    }
    elseif (!isset($input['radios_left']) || !isset($input['radios_right'])) {
      $form_state->setErrorByName('node_revisions_table', $this->t('Select two revisions to compare.'));
    }
    elseif ($input['radios_left'] == $input['radios_right']) {
      // @todo Radio-boxes selection resets if there are errors.
      $form_state->setErrorByName('node_revisions_table', $this->t('Select different revisions to compare.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $input = $form_state->getUserInput();
    $vid_left = $input['radios_left'];
    $vid_right = $input['radios_right'];
    $nid = $input['nid'];

    // Always place the older revision on the left side of the comparison
    // and the newer revision on the right side (however revisions can be
    // compared both ways if we manually change the order of the parameters).
    if ($vid_left > $vid_right) {
      $aux = $vid_left;
      $vid_left = $vid_right;
      $vid_right = $aux;
    }
    // Builds the redirect Url.
    $redirect_url = Url::fromRoute(
      'diff.revisions_diff',
      array(
        'node' => $nid,
        'left_revision' => $vid_left,
        'right_revision' => $vid_right,
        'filter' => $this->diffLayoutManager->getDefaultLayout(),
      )
    );
    $form_state->setRedirectUrl($redirect_url);
  }

}
