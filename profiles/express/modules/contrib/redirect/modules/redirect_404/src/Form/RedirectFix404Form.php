<?php

namespace Drupal\redirect_404\Form;

use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Url;
use Drupal\redirect_404\SqlRedirectNotFoundStorage;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a form that lists all 404 error paths and no redirect assigned yet.
 *
 * This is a fallback for the provided default view.
 */
class RedirectFix404Form extends FormBase {

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * The redirect storage.
   *
   * @var \Drupal\redirect_404\SqlRedirectNotFoundStorage
   */
  protected $redirectStorage;

  /**
   * The date formatter service.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatter;

  /**
   * The entity manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a RedirectFix404Form.
   *
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   * @param \Drupal\redirect_404\SqlRedirectNotFoundStorage $redirect_storage
   *   The redirect storage.
   * @param \Drupal\Core\Datetime\DateFormatterInterface $date_formatter
   *   The date Formatter service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity manager.
   */
  public function __construct(LanguageManagerInterface $language_manager, SqlRedirectNotFoundStorage $redirect_storage, DateFormatterInterface $date_formatter, EntityTypeManagerInterface $entity_type_manager) {
    $this->languageManager = $language_manager;
    $this->redirectStorage = $redirect_storage;
    $this->dateFormatter = $date_formatter;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('language_manager'),
      $container->get('redirect.not_found_storage'),
      $container->get('date.formatter'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'redirect_fix_404_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $destination = $this->getDestinationArray();

    $search = $this->getRequest()->get('search');
    $form['#attributes'] = ['class' => ['search-form']];

    $form['basic'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Filter 404s'),
      '#attributes' => ['class' => ['container-inline']],
    ];
    $form['basic']['filter'] = [
      '#type' => 'textfield',
      '#title' => '',
      '#default_value' => $search,
      '#maxlength' => 128,
      '#size' => 25,
    ];
    $form['basic']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Filter'),
      '#action' => 'filter',
    ];
    if ($search) {
      $form['basic']['reset'] = [
        '#type' => 'submit',
        '#value' => $this->t('Reset'),
        '#action' => 'reset',
      ];
    }

    $languages = $this->languageManager->getLanguages(LanguageInterface::STATE_ALL);
    $multilingual = $this->languageManager->isMultilingual();

    $header = [
      ['data' => $this->t('Path'), 'field' => 'source'],
      ['data' => $this->t('Count'), 'field' => 'count', 'sort' => 'desc'],
      ['data' => $this->t('Last accessed'), 'field' => 'timestamp'],
    ];
    if ($multilingual) {
      $header[] = ['data' => $this->t('Language'), 'field' => 'language'];
    }
    $header[] = ['data' => $this->t('Operations')];

    $rows = [];
    $results = $this->redirectStorage->listRequests($header, $search);
    foreach ($results as $result) {
      $path = ltrim($result->path, '/');

      $row = [];
      $row['source'] = $path;
      $row['count'] = $result->count;
      $row['timestamp'] = $this->dateFormatter->format($result->timestamp, 'short');
      if ($multilingual) {
        if (isset($languages[$result->langcode])) {
          $row['language'] = $languages[$result->langcode]->getName();
        }
        else {
          $row['language'] = $this->t('Undefined @langcode', ['@langcode' => $result->langcode]);
        }
      }

      $operations = [];
      if ($this->entityTypeManager->getAccessControlHandler('redirect')->createAccess()) {
        $operations['add'] = [
          'title' => $this->t('Add redirect'),
          'url' => Url::fromRoute('redirect.add', [], ['query' => ['source' => $path, 'language' => $result->langcode] + $destination]),
        ];
      }
      $row['operations'] = [
        'data' => [
          '#type' => 'operations',
          '#links' => $operations,
        ],
      ];

      $rows[] = $row;
    }

    $form['redirect_404_table']  = [
      '#theme' => 'table',
      '#header' => $header,
      '#rows' => $rows,
      '#empty' => $this->t('There are no 404 errors to fix.'),
    ];
    $form['redirect_404_pager'] = ['#type' => 'pager'];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    if ($form_state->getTriggeringElement()['#action'] == 'filter') {
      $form_state->setRedirect('redirect_404.fix_404', [], ['query' => ['search' => trim($form_state->getValue('filter'))]]);
    }
    else {
      $form_state->setRedirect('redirect_404.fix_404');
    }
  }

}
