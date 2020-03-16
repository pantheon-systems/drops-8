<?php

namespace Drupal\metatag_views\Form;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\metatag\MetatagManagerInterface;
use Drupal\metatag\MetatagTagPluginManager;
use Drupal\metatag\MetatagToken;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\language\ConfigurableLanguageManagerInterface;
use Drupal\metatag_views\MetatagViewsValuesCleanerTrait;

/**
 * Class MetatagViewsEditForm.
 *
 * @package Drupal\metatag_views\Form
 */
class MetatagViewsTranslationForm extends FormBase {

  use MetatagViewsValuesCleanerTrait;

  /**
   * Drupal\metatag\MetatagManager definition.
   *
   * @var \Drupal\metatag\MetatagManager
   */
  protected $metatagManager;

  /**
   * The language manager.
   *
   * @var \Drupal\language\ConfigurableLanguageManagerInterface
   */
  protected $languageManager;

  /**
   * The Views manager.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $viewsManager;

  /**
   * The Metatag token service.
   *
   * @var \Drupal\metatag\MetatagToken
   */
  protected $tokenService;

  /**
   * The Metatag tag plugin manager.
   *
   * @var \Drupal\metatag\MetatagTagPluginManager
   */
  protected $tagPluginManager;

  /**
   * The View entity object.
   *
   * @var \Drupal\views\ViewEntityInterface
   */
  protected $view;

  /**
   * View ID.
   *
   * @var string
   */
  protected $viewId;

  /**
   * View display ID.
   *
   * @var string
   */
  protected $displayId = 'default';

  /**
   * The language of the translation.
   *
   * @var \Drupal\Core\Language\LanguageInterface
   */
  protected $language;

  /**
   * The language of the translation source.
   *
   * @var \Drupal\Core\Language\LanguageInterface
   */
  protected $sourceLanguage;

  /**
   * An array of base language data.
   *
   * @var array
   */
  protected $baseData = [];

  /**
   * {@inheritdoc}
   */
  public function __construct(MetatagManagerInterface $metatag_manager, EntityTypeManagerInterface $entity_manager, MetatagToken $token, MetatagTagPluginManager $tagPluginManager, ConfigurableLanguageManagerInterface $language_manager) {
    $this->metatagManager = $metatag_manager;
    $this->viewsManager = $entity_manager->getStorage('view');
    $this->tokenService = $token;
    $this->tagPluginManager = $tagPluginManager;
    $this->languageManager = $language_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('metatag.manager'),
      $container->get('entity_type.manager'),
      $container->get('metatag.token'),
      $container->get('plugin.manager.metatag.tag'),
      $container->get('language_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'metatag_views_translate_form';
  }

  /**
   * Gets the translated values while storing a copy of the original values.
   */
  protected function prepareValues() {
    $config_name = $this->view->getConfigDependencyName();
    $config_path = 'display.' . $this->displayId . '.display_options.display_extenders.metatag_display_extender.metatags';

    $configuration = \Drupal::service('config.factory')->get($config_name);
    $this->baseData = $configuration->getOriginal($config_path, FALSE);

    // Set the translation target language on the configuration factory.
    $original_language = $this->languageManager->getConfigOverrideLanguage();
    $this->languageManager->setConfigOverrideLanguage($this->language);

    // Read in translated values.
    $configuration = \Drupal::service('config.factory')->get($config_name);
    $translated_values = $configuration->get($config_path);

    // Set the configuration language back.
    $this->languageManager->setConfigOverrideLanguage($original_language);

    return $translated_values;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Get the parameters from request.
    $this->viewId = \Drupal::request()->get('view_id');
    $this->displayId = \Drupal::request()->get('display_id');
    $langcode = \Drupal::request()->get('langcode');

    $this->view = $this->viewsManager->load($this->viewId);
    $this->language = $this->languageManager->getLanguage($langcode);
    $this->sourceLanguage = $this->view->language();

    // Get meta tags from the view entity.
    $form['#tree'] = TRUE;
    $form['#attached']['library'][] = 'config_translation/drupal.config_translation.admin';

    $form['#title'] = $this->t('Edit @language translation for %view: %display metatags', [
      '%view' => $this->view->label(),
      '%display' => $this->view->getDisplay($this->displayId)['display_title'],
      '@language' => $this->language->getName(),
    ]);

    $form['metatags'] = $this->form($form, $this->prepareValues());
    $form['metatags']['#title'] = t('Metatags');
    $form['metatags']['#type'] = 'fieldset';

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => t('Submit'),
    ];

    return $form;
  }

  /**
   * Add the translation form element for meta tags available in the source.
   */
  public function form(array $element, array $translated_values) {
    $translated_values = $this->clearMetatagViewsDisallowedValues($translated_values);
    // Only offer form elements for tags present in the source language.
    $source_values = $this->removeEmptyTags($this->baseData);

    // Add the outer fieldset.
    $element += [
      '#type' => 'details',
    ];
    $element += $this->tokenService->tokenBrowser(['view']);

    foreach ($source_values as $tag_id => $value) {
      $tag = $this->tagPluginManager->createInstance($tag_id);
      $tag->setValue($translated_values[$tag_id]);

      $form_element = $tag->form($element);
      $element[$tag_id] = [
        '#theme' => 'config_translation_manage_form_element',
        'source' => [
          '#type' => 'item',
          '#title' => $form_element['#title'],
          '#markup' => $value,
        ],
        'translation' => $form_element,
      ];
    }

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Get the values of metatags.
    $values = $form_state->getValue('metatags');
    $translated_values = array_combine(array_keys($values), array_column($values, 'translation'));

    $config_name = $this->view->getConfigDependencyName();
    $config_path = 'display.' . $this->displayId . '.display_options.display_extenders.metatag_display_extender.metatags';

    // Set configuration values based on form submission and source values.
    $base_config = $this->configFactory()->getEditable($config_name);
    $config_translation = $this->languageManager->getLanguageConfigOverride($this->language->getId(), $config_name);

    // Save the configuration values, if they are different from the source
    // values in the base configuration. Otherwise remove the override.
    $source_values = $this->removeEmptyTags($base_config->get($config_path));
    if ($source_values !== $translated_values) {
      $config_translation->set($config_path, $translated_values);
    }
    else {
      $config_translation->clear($config_path);
    }

    // If no overrides, delete language specific configuration file.
    $saved_config = $config_translation->get();
    if (empty($saved_config)) {
      $config_translation->delete();
    }
    else {
      $config_translation->save();
    }

    // Redirect back to the views list.
    $form_state->setRedirect('metatag_views.metatags.translate_overview', [
      'view_id' => $this->viewId,
      'display_id' => $this->displayId,
    ]);

    $this->messenger()->addMessage($this->t('Successfully updated @language translation.', [
      '@language' => $this->language->getName(),
    ]));
  }

}
