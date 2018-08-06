<?php

namespace Drupal\media_entity\Plugin\DevelGenerate;

use Drupal\Core\Datetime\DateFormatter;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Routing\UrlGeneratorInterface;
use Drupal\devel_generate\DevelGenerateBase;
use Drupal\media_entity\MediaStorageInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a MediaDevelGenerate plugin.
 *
 * @DevelGenerate(
 *   id = "media",
 *   label = @Translation("media"),
 *   description = @Translation("Generate a given number of media entities."),
 *   url = "media",
 *   permission = "administer devel_generate",
 *   settings = {
 *     "num" = 50,
 *     "kill" = FALSE,
 *     "name_length" = 4
 *   }
 * )
 */
class MediaDevelGenerate extends DevelGenerateBase implements ContainerFactoryPluginInterface {

  /**
   * The media storage.
   *
   * @var \Drupal\media_entity\MediaStorageInterface
   */
  protected $mediaStorage;

  /**
   * The media bundle storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $mediaBundleStorage;

  /**
   * The user storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $userStorage;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * The url generator service.
   *
   * @var \Drupal\Core\Routing\UrlGeneratorInterface
   */
  protected $urlGenerator;

  /**
   * The date formatter service.
   *
   * @var \Drupal\Core\Datetime\DateFormatter
   */
  protected $dateFormatter;

  /**
   * Database connection.
   *
   * @var Connection
   */
  protected $database;

  /**
   * Constructs MediaDevelGenerate class.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param array $plugin_definition
   *   The plugin definition.
   * @param \Drupal\media_entity\MediaStorageInterface $media_storage
   *   The media storage.
   * @param \Drupal\Core\Entity\EntityStorageInterface $user_storage
   *   The user storage.
   * @param \Drupal\Core\Entity\EntityStorageInterface $media_bundle_storage
   *   The media bundle storage.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   * @param \Drupal\Core\Routing\UrlGeneratorInterface $url_generator
   *   The url generator service.
   * @param \Drupal\Core\Datetime\DateFormatter $date_formatter
   *   The date formatter service.
   */
  public function __construct(array $configuration, $plugin_id, array $plugin_definition, MediaStorageInterface $media_storage, EntityStorageInterface $user_storage, EntityStorageInterface $media_bundle_storage, ModuleHandlerInterface $module_handler, LanguageManagerInterface $language_manager, UrlGeneratorInterface $url_generator, DateFormatter $date_formatter) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->moduleHandler = $module_handler;
    $this->mediaStorage = $media_storage;
    $this->mediaBundleStorage = $media_bundle_storage;
    $this->userStorage = $user_storage;
    $this->languageManager = $language_manager;
    $this->urlGenerator = $url_generator;
    $this->dateFormatter = $date_formatter;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $entity_manager = $container->get('entity.manager');
    return new static(
      $configuration, $plugin_id, $plugin_definition,
      $entity_manager->getStorage('media'),
      $entity_manager->getStorage('user'),
      $entity_manager->getStorage('media_bundle'),
      $container->get('module_handler'),
      $container->get('language_manager'),
      $container->get('url_generator'),
      $container->get('date.formatter')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $bundles = $this->mediaBundleStorage->loadMultiple();

    if (empty($bundles)) {
      $create_url = $this->urlGenerator->generateFromRoute('media.bundle_add');
      $this->setMessage($this->t('You do not have any media bundles that can be generated. <a href="@create-bundle">Go create a new media bundle</a>', ['@create-bundle' => $create_url]), 'error', FALSE);
      return [];
    }

    $options = [];
    foreach ($bundles as $bundle) {
      $options[$bundle->id()] = ['bundle' => ['#markup' => $bundle->label()]];
    }

    $form['media_bundles'] = [
      '#type' => 'tableselect',
      '#header' => ['bundle' => $this->t('Media bundle')],
      '#options' => $options,
    ];

    $form['kill'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('<strong>Delete all media</strong> in these bundles before generating new media.'),
      '#default_value' => $this->getSetting('kill'),
    ];
    $form['num'] = [
      '#type' => 'number',
      '#title' => $this->t('How many media items would you like to generate?'),
      '#default_value' => $this->getSetting('num'),
      '#required' => TRUE,
      '#min' => 0,
    ];

    $options = [1 => $this->t('Now')];
    foreach ([3600, 86400, 604800, 2592000, 31536000] as $interval) {
      $options[$interval] = $this->dateFormatter->formatInterval($interval, 1) . ' ' . $this->t('ago');
    }
    $form['time_range'] = [
      '#type' => 'select',
      '#title' => $this->t('How far back in time should the media be dated?'),
      '#description' => $this->t('Media creation dates will be distributed randomly from the current time, back to the selected time.'),
      '#options' => $options,
      '#default_value' => 604800,
    ];

    $form['name_length'] = [
      '#type' => 'number',
      '#title' => $this->t('Maximum number of words in names'),
      '#default_value' => $this->getSetting('name_length'),
      '#required' => TRUE,
      '#min' => 1,
      '#max' => 255,
    ];

    $options = [];
    // We always need a language.
    $languages = $this->languageManager->getLanguages(LanguageInterface::STATE_ALL);
    foreach ($languages as $langcode => $language) {
      $options[$langcode] = $language->getName();
    }

    $form['add_language'] = [
      '#type' => 'select',
      '#title' => $this->t('Set language on media'),
      '#multiple' => TRUE,
      '#description' => $this->t('Requires locale.module'),
      '#options' => $options,
      '#default_value' => [
        $this->languageManager->getDefaultLanguage()->getId(),
      ],
    ];

    $form['#redirect'] = FALSE;

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  protected function generateElements(array $values) {
    if ($values['num'] <= 50) {
      $this->generateMedia($values);
    }
    else {
      $this->generateBatchMedia($values);
    }
  }

  /**
   * Method for creating media when number of elements is less than 50.
   *
   * @param array $values
   *   Array of values submitted through a form.
   */
  private function generateMedia($values) {
    $values['media_bundles'] = array_filter($values['media_bundles']);
    if (!empty($values['kill']) && $values['media_bundles']) {
      $this->mediaKill($values);
    }

    if (!empty($values['media_bundles'])) {
      // Generate media.
      $this->preGenerate($values);
      $start = time();
      for ($i = 1; $i <= $values['num']; $i++) {
        $this->createMediaItem($values);
        if (function_exists('drush_log') && $i % drush_get_option('feedback', 1000) == 0) {
          $now = time();
          drush_log(dt('Completed !feedback media items (!rate media/min)', [
            '!feedback' => drush_get_option('feedback', 1000),
            '!rate' => (drush_get_option('feedback', 1000) * 60) / ($now - $start),
          ]), 'ok');
          $start = $now;
        }
      }
    }
    $this->setMessage($this->formatPlural($values['num'], '1 media created.', 'Finished creating @count media items.'));
  }

  /**
   * Method for creating media when number of elements is greater than 50.
   *
   * @param array $values
   *   The input values from the settings form.
   */
  private function generateBatchMedia($values) {
    // Setup the batch operations and save the variables.
    $operations[] = [
      'devel_generate_operation',
      [$this, 'batchPreGenerate', $values],
    ];

    // Add the kill operation.
    if ($values['kill']) {
      $operations[] = [
        'devel_generate_operation',
        [$this, 'batchMediaKill', $values],
      ];
    }

    // Add the operations to create the media.
    for ($num = 0; $num < $values['num']; $num++) {
      $operations[] = [
        'devel_generate_operation',
        [$this, 'batchCreateMediaItem', $values],
      ];
    }

    // Start the batch.
    $batch = [
      'title' => $this->t('Generating media'),
      'operations' => $operations,
      'finished' => 'devel_generate_batch_finished',
      'file' => drupal_get_path('module', 'devel_generate') . '/devel_generate.batch.inc',
    ];
    batch_set($batch);
  }

  /**
   * Batch version of preGenerate().
   *
   * @param array $vars
   *   The input values from the settings form.
   * @param array $context
   *   Batch job context.
   */
  public function batchPreGenerate($vars, &$context) {
    $context['results'] = $vars;
    $context['results']['num'] = 0;
    $this->preGenerate($context['results']);
  }

  /**
   * Batch version of createMediaItem().
   *
   * @param array $vars
   *   The input values from the settings form.
   * @param array $context
   *   Batch job context.
   */
  public function batchCreateMediaItem($vars, &$context) {
    $this->createMediaItem($context['results']);
    $context['results']['num']++;
  }

  /**
   * Batch version of mediaKill().
   *
   * @param array $vars
   *   The input values from the settings form.
   * @param array $context
   *   Batch job context.
   */
  public function batchMediaKill($vars, &$context) {
    $this->mediaKill($context['results']);
  }

  /**
   * {@inheritdoc}
   */
  public function validateDrushParams($args) {
    $add_language = drush_get_option('languages');
    if (!empty($add_language)) {
      $add_language = explode(',', str_replace(' ', '', $add_language));
      // Intersect with the enabled languages to make sure the language args
      // passed are actually enabled.
      $values['values']['add_language'] = array_intersect($add_language, array_keys($this->languageManager->getLanguages(LanguageInterface::STATE_ALL)));
    }

    $values['kill'] = drush_get_option('kill');
    $values['name_length'] = drush_get_option('name_length', 6);
    $values['num'] = array_shift($args);
    $selected_bundles = _convert_csv_to_array(drush_get_option('bundles', []));

    if (empty($selected_bundles)) {
      return drush_set_error('DEVEL_GENERATE_NO_MEDIA_BUNDLES', dt('No media bundles available'));
    }

    $values['media_bundles'] = array_combine($selected_bundles, $selected_bundles);

    return $values;
  }

  /**
   * Deletes all media of given media bundles.
   *
   * @param array $values
   *   The input values from the settings form.
   */
  protected function mediaKill($values) {
    $mids = $this->mediaStorage->getQuery()
      ->condition('bundle', $values['media_bundles'], 'IN')
      ->execute();

    if (!empty($mids)) {
      $media = $this->mediaStorage->loadMultiple($mids);
      $this->mediaStorage->delete($media);
      $this->setMessage($this->t('Deleted %count media items.', ['%count' => count($mids)]));
    }
  }

  /**
   * Code to be run before generating items.
   *
   * Returns the same array passed in as parameter, but with an array of uids
   * for the key 'users'.
   *
   * @param array $results
   *   The input values from the settings form.
   */
  protected function preGenerate(&$results) {
    // Get user id.
    $users = $this->userStorage->getQuery()
      ->range(0, 50)
      ->execute();
    $users = array_merge($users, ['0']);
    $results['users'] = $users;
  }

  /**
   * Create one media item. Used by both batch and non-batch code branches.
   *
   * @param array $results
   *   The input values from the settings form.
   */
  protected function createMediaItem(&$results) {
    if (!isset($results['time_range'])) {
      $results['time_range'] = 0;
    }
    $users = $results['users'];

    $bundle = array_rand(array_filter($results['media_bundles']));
    $uid = $users[array_rand($users)];

    $media = $this->mediaStorage->create([
      'bundle' => $bundle,
      'name' => $this->getRandom()->sentences(mt_rand(1, $results['name_length']), TRUE),
      'uid' => $uid,
      'revision' => mt_rand(0, 1),
      'status' => TRUE,
      'created' => REQUEST_TIME - mt_rand(0, $results['time_range']),
      'langcode' => $this->getLangcode($results),
    ]);

    // A flag to let hook implementations know that this is a generated item.
    $media->devel_generate = $results;

    // Populate all fields with sample values.
    $this->populateFields($media);

    $media->save();
  }

  /**
   * Determine language based on $results.
   *
   * @param array $results
   *   The input values from the settings form.
   */
  protected function getLangcode($results) {
    if (isset($results['add_language'])) {
      $langcodes = $results['add_language'];
      $langcode = $langcodes[array_rand($langcodes)];
    }
    else {
      $langcode = $this->languageManager->getDefaultLanguage()->getId();
    }
    return $langcode;
  }

}
