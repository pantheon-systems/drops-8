<?php

namespace Drupal\pathauto\Form;

use Drupal\Component\Utility\Html;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Url;
use Drupal\pathauto\AliasTypeManager;
use Drupal\pathauto\PathautoGeneratorInterface;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configure file system settings for this site.
 */
class PathautoSettingsForm extends ConfigFormBase {

  /**
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * @var \Drupal\pathauto\AliasTypeManager
   */
  protected $aliasTypeManager;

  /**
   * {@inheritDoc}
   */
  public function __construct(ConfigFactoryInterface $config_factory, EntityTypeManagerInterface $entity_type_manager, EntityFieldManagerInterface $entity_field_manager, AliasTypeManager $alias_type_manager) {
    parent::__construct($config_factory);
    $this->entityTypeManager = $entity_type_manager;
    $this->entityFieldManager = $entity_field_manager;
    $this->aliasTypeManager = $alias_type_manager;
  }

  /**
   * {@inheritDoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('entity_type.manager'),
      $container->get('entity_field.manager'),
      $container->get('plugin.manager.alias_type')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'pathauto_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['pathauto.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('pathauto.settings');

    $form['enabled_entity_types'] = [
      '#type' => 'details',
      '#open' => TRUE,
      '#title' => $this->t('Enabled entity types'),
      '#description' => $this->t('Enable to add a path field and allow to define alias patterns for the given type. Disabled types already define a path field themselves or currently have a pattern.'),
      '#tree' => TRUE,
    ];

    // Get all applicable entity types.
    foreach ($this->entityTypeManager->getDefinitions() as $entity_type_id => $entity_type) {
      // Disable a checkbox if it already exists and if the entity type has
      // patterns currently defined or if it isn't defined by us.
      $patterns_count = \Drupal::entityQuery('pathauto_pattern')
        ->condition('type', 'canonical_entities:' . $entity_type_id)
        ->count()
        ->execute();

      if (is_subclass_of($entity_type->getClass(), FieldableEntityInterface::class) && $entity_type->hasLinkTemplate('canonical')) {
        $field_definitions = $this->entityFieldManager->getBaseFieldDefinitions($entity_type_id);
        $form['enabled_entity_types'][$entity_type_id] = [
          '#type' => 'checkbox',
          '#title' => $entity_type->getLabel(),
          '#default_value' => isset($field_definitions['path']) || in_array($entity_type_id, $config->get('enabled_entity_types')),
          '#disabled' => isset($field_definitions['path']) && ($field_definitions['path']->getProvider() != 'pathauto' || $patterns_count),
        ];
      }
    }

    $form['verbose'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Verbose'),
      '#default_value' => $config->get('verbose'),
      '#description' => $this->t('Display alias changes (except during bulk updates).'),
    );

    $form['separator'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Separator'),
      '#size' => 1,
      '#maxlength' => 1,
      '#default_value' => $config->get('separator'),
      '#description' => $this->t('Character used to separate words in titles. This will replace any spaces and punctuation characters. Using a space or + character can cause unexpected results.'),
    );

    $form['case'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Character case'),
      '#default_value' => $config->get('case'),
      '#description' => $this->t('Convert token values to lowercase.'),
    );

    $max_length = \Drupal::service('pathauto.alias_storage_helper')->getAliasSchemaMaxlength();

    $help_link = '';
    if (\Drupal::moduleHandler()->moduleExists('help')) {
      $help_link = ' ' . $this->t('See <a href=":pathauto-help">Pathauto help</a> for details.', [':pathauto-help' => Url::fromRoute('help.page', ['name' => 'pathauto'])->toString()]);
    }

    $form['max_length'] = array(
      '#type' => 'number',
      '#title' => $this->t('Maximum alias length'),
      '#size' => 3,
      '#maxlength' => 3,
      '#default_value' => $config->get('max_length'),
      '#min' => 1,
      '#max' => $max_length,
      '#description' => $this->t('Maximum length of aliases to generate. 100 is the recommended length. @max is the maximum possible length.', array('@max' => $max_length)) . $help_link,
    );

    $form['max_component_length'] = array(
      '#type' => 'number',
      '#title' => $this->t('Maximum component length'),
      '#size' => 3,
      '#maxlength' => 3,
      '#default_value' => $config->get('max_component_length'),
      '#min' => 1,
      '#max' => $max_length,
      '#description' => $this->t('Maximum text length of any component in the alias (e.g., [title]). 100 is the recommended length. @max is the maximum possible length.', ['@max' => $max_length]) . $help_link,
    );

    $description = $this->t('What should Pathauto do when updating an existing content item which already has an alias?');
    if (\Drupal::moduleHandler()->moduleExists('redirect')) {
      $description .= ' ' . $this->t('The <a href=":url">Redirect module settings</a> affect whether a redirect is created when an alias is deleted.', array(':url' => Url::fromRoute('redirect.settings')->toString()));
    }
    else {
      $description .= ' ' . $this->t('Considering installing the <a href=":url">Redirect module</a> to get redirects when your aliases change.', array(':url' => 'http://drupal.org/project/redirect'));
    }

    $form['update_action'] = array(
      '#type' => 'radios',
      '#title' => $this->t('Update action'),
      '#default_value' => $config->get('update_action'),
      '#options' => array(
        PathautoGeneratorInterface::UPDATE_ACTION_NO_NEW => $this->t('Do nothing. Leave the old alias intact.'),
        PathautoGeneratorInterface::UPDATE_ACTION_LEAVE => $this->t('Create a new alias. Leave the existing alias functioning.'),
        PathautoGeneratorInterface::UPDATE_ACTION_DELETE => $this->t('Create a new alias. Delete the old alias.'),
      ),
      '#description' => $description,
    );

    $form['transliterate'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Transliterate prior to creating alias'),
      '#default_value' => $config->get('transliterate'),
      '#description' => $this->t('When a pattern includes certain characters (such as those with accents) should Pathauto attempt to transliterate them into the US-ASCII alphabet?'),
    );

    $form['reduce_ascii'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Reduce strings to letters and numbers'),
      '#default_value' => $config->get('reduce_ascii'),
      '#description' => $this->t('Filters the new alias to only letters and numbers found in the ASCII-96 set.'),
    );

    $form['ignore_words'] = array(
      '#type' => 'textarea',
      '#title' => $this->t('Strings to Remove'),
      '#default_value' => $config->get('ignore_words'),
      '#description' => $this->t('Words to strip out of the URL alias, separated by commas. Do not use this to remove punctuation.'),
      '#wysiwyg' => FALSE,
    );

    $form['punctuation'] = array(
      '#type' => 'fieldset',
      '#title' => $this->t('Punctuation'),
      '#collapsible' => TRUE,
      '#collapsed' => TRUE,
      '#tree' => TRUE,
    );

    $punctuation = \Drupal::service('pathauto.alias_cleaner')->getPunctuationCharacters();

    foreach ($punctuation as $name => $details) {
      // Use the value from config if it exists.
      if ($config->get('punctuation.' . $name) !== NULL) {
        $details['default'] = $config->get('punctuation.' . $name);
      }
      else {
        // Otherwise use the correct default.
        $details['default'] = $details['value'] == $config->get('separator') ? PathautoGeneratorInterface::PUNCTUATION_REPLACE : PathautoGeneratorInterface::PUNCTUATION_REMOVE;
      }
      $form['punctuation'][$name] = array(
        '#type' => 'select',
        '#title' => $details['name'] . ' (<code>' . Html::escape($details['value']) . '</code>)',
        '#default_value' => $details['default'],
        '#options' => array(
          PathautoGeneratorInterface::PUNCTUATION_REMOVE => $this->t('Remove'),
          PathautoGeneratorInterface::PUNCTUATION_REPLACE => $this->t('Replace by separator'),
          PathautoGeneratorInterface::PUNCTUATION_DO_NOTHING => $this->t('No action (do not replace)'),
        ),
      );
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    $config = $this->config('pathauto.settings');

    $form_state->cleanValues();

    $original_entity_types = $config->get('enabled_entity_types');
    foreach ($form_state->getValues() as $key => $value) {
      if ($key == 'enabled_entity_types') {
        $enabled_entity_types = [];
        foreach ($value as $entity_type_id => $enabled) {
          $field_definitions = $this->entityFieldManager->getBaseFieldDefinitions($entity_type_id);
          // Verify that the entity type is enabled and that it is not defined
          // or defined by us before adding it to the configuration, so that
          // we do not store an entity type that cannot be enabled or disabled.
          if ($enabled && (!isset($field_definitions['path']) || ($field_definitions['path']->getProvider() === 'pathauto'))) {
            $enabled_entity_types[] = $entity_type_id;
          }
        }
        $value = $enabled_entity_types;
      }
      $config->set($key, $value);
    }
    $config->save();

    parent::submitForm($form, $form_state);
  }

}
