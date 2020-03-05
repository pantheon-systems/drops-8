<?php

namespace Drupal\pathauto;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityMalformedException;
use Drupal\Core\Entity\Exception\UndefinedLinkTemplateException;
use Drupal\Core\Entity\RevisionableInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Messenger\MessengerTrait;
use Drupal\Core\Render\BubbleableMetadata;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\Core\Utility\Token;
use Drupal\token\TokenEntityMapperInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Provides methods for generating path aliases.
 */
class PathautoGenerator implements PathautoGeneratorInterface {

  use MessengerTrait;
  use StringTranslationTrait;

  /**
   * Config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Token service.
   *
   * @var \Drupal\Core\Utility\Token
   */
  protected $token;

  /**
   * Calculated pattern for a specific entity.
   *
   * @var array
   */
  protected $patterns = [];

  /**
   * Available patterns per entity type ID.
   *
   * @var array
   */
  protected $patternsByEntityType = [];

  /**
   * The alias cleaner.
   *
   * @var \Drupal\pathauto\AliasCleanerInterface
   */
  protected $aliasCleaner;

  /**
   * The alias storage helper.
   *
   * @var \Drupal\pathauto\AliasStorageHelperInterface
   */
  protected $aliasStorageHelper;

  /**
   * The alias uniquifier.
   *
   * @var \Drupal\pathauto\AliasUniquifierInterface
   */
  protected $aliasUniquifier;

  /**
   * The messenger service.
   *
   * @var \Drupal\pathauto\MessengerInterface
   */
  protected $pathautoMessenger;

  /**
   * The token entity mapper.
   *
   * @var \Drupal\token\TokenEntityMapperInterface
   */
  protected $tokenEntityMapper;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Manages pathauto alias type plugins.
   *
   * @var \Drupal\pathauto\AliasTypeManager
   */
  protected $aliasTypeManager;

  /**
   * Creates a new Pathauto manager.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\Core\Utility\Token $token
   *   The token utility.
   * @param \Drupal\pathauto\AliasCleanerInterface $alias_cleaner
   *   The alias cleaner.
   * @param \Drupal\pathauto\AliasStorageHelperInterface $alias_storage_helper
   *   The alias storage helper.
   * @param AliasUniquifierInterface $alias_uniquifier
   *   The alias uniquifier.
   * @param \Drupal\pathauto\MessengerInterface $pathauto_messenger
   *   The messenger service.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
   *   The string translation service.
   * @param \Drupal\token\TokenEntityMapperInterface $token_entity_mapper
   *   The token entity mapper.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\pathauto\AliasTypeManager $alias_type_manager
   *   Manages pathauto alias type plugins.
   */
  public function __construct(ConfigFactoryInterface $config_factory, ModuleHandlerInterface $module_handler, Token $token, AliasCleanerInterface $alias_cleaner, AliasStorageHelperInterface $alias_storage_helper, AliasUniquifierInterface $alias_uniquifier, MessengerInterface $pathauto_messenger, TranslationInterface $string_translation, TokenEntityMapperInterface $token_entity_mapper, EntityTypeManagerInterface $entity_type_manager, AliasTypeManager $alias_type_manager = NULL) {
    $this->configFactory = $config_factory;
    $this->moduleHandler = $module_handler;
    $this->token = $token;
    $this->aliasCleaner = $alias_cleaner;
    $this->aliasStorageHelper = $alias_storage_helper;
    $this->aliasUniquifier = $alias_uniquifier;
    $this->pathautoMessenger = $pathauto_messenger;
    $this->stringTranslation = $string_translation;
    $this->tokenEntityMapper = $token_entity_mapper;
    $this->entityTypeManager = $entity_type_manager;
    $this->aliasTypeManager = $alias_type_manager ?: \Drupal::service('plugin.manager.alias_type');
  }

  /**
   * {@inheritdoc}
   */
  public function createEntityAlias(EntityInterface $entity, $op) {
    // Retrieve and apply the pattern for this content type.
    $pattern = $this->getPatternByEntity($entity);
    if (empty($pattern)) {
      // No pattern? Do nothing (otherwise we may blow away existing aliases...)
      return NULL;
    }

    try {
      $internalPath = $entity->toUrl()->getInternalPath();
    }
    // @todo convert to multi-exception handling in PHP 7.1.
    catch (EntityMalformedException $exception) {
      return NULL;
    }
    catch (UndefinedLinkTemplateException $exception) {
      return NULL;
    }
    catch (\UnexpectedValueException $exception) {
      return NULL;
    }

    $source = '/' . $internalPath;
    $config = $this->configFactory->get('pathauto.settings');
    $langcode = $entity->language()->getId();

    // Core does not handle aliases with language Not Applicable.
    if ($langcode == LanguageInterface::LANGCODE_NOT_APPLICABLE) {
      $langcode = LanguageInterface::LANGCODE_NOT_SPECIFIED;
    }

    // Build token data.
    $data = [
      $this->tokenEntityMapper->getTokenTypeForEntityType($entity->getEntityTypeId()) => $entity,
    ];

    // Allow other modules to alter the pattern.
    $context = [
      'module' => $entity->getEntityType()->getProvider(),
      'op' => $op,
      'source' => $source,
      'data' => $data,
      'bundle' => $entity->bundle(),
      'language' => &$langcode,
    ];
    $pattern_original = $pattern->getPattern();
    $this->moduleHandler->alter('pathauto_pattern', $pattern, $context);
    $pattern_altered = $pattern->getPattern();

    // Special handling when updating an item which is already aliased.
    $existing_alias = NULL;
    if ($op == 'update' || $op == 'bulkupdate') {
      if ($existing_alias = $this->aliasStorageHelper->loadBySource($source, $langcode)) {
        switch ($config->get('update_action')) {
          case PathautoGeneratorInterface::UPDATE_ACTION_NO_NEW:
            // If an alias already exists,
            // and the update action is set to do nothing,
            // then gosh-darn it, do nothing.
            return NULL;
        }
      }
    }

    // Replace any tokens in the pattern.
    // Uses callback option to clean replacements. No sanitization.
    // Pass empty BubbleableMetadata object to explicitly ignore cacheablity,
    // as the result is never rendered.
    $alias = $this->token->replace($pattern->getPattern(), $data, [
      'clear' => TRUE,
      'callback' => [$this->aliasCleaner, 'cleanTokenValues'],
      'langcode' => $langcode,
      'pathauto' => TRUE,
    ], new BubbleableMetadata());

    // Check if the token replacement has not actually replaced any values. If
    // that is the case, then stop because we should not generate an alias.
    // @see token_scan()
    $pattern_tokens_removed = preg_replace('/\[[^\s\]:]*:[^\s\]]*\]/', '', $pattern->getPattern());
    if ($alias === $pattern_tokens_removed) {
      return NULL;
    }

    $alias = $this->aliasCleaner->cleanAlias($alias);

    // Allow other modules to alter the alias.
    $context['source'] = &$source;
    $context['pattern'] = $pattern;
    $this->moduleHandler->alter('pathauto_alias', $alias, $context);

    // If we have arrived at an empty string, discontinue.
    if (!mb_strlen($alias)) {
      return NULL;
    }

    // If the alias already exists, generate a new, hopefully unique, variant.
    $original_alias = $alias;
    $this->aliasUniquifier->uniquify($alias, $source, $langcode);
    if ($original_alias != $alias) {
      // Alert the user why this happened.
      $this->pathautoMessenger->addMessage($this->t('The automatically generated alias %original_alias conflicted with an existing alias. Alias changed to %alias.', [
        '%original_alias' => $original_alias,
        '%alias' => $alias,
      ]), $op);
    }

    // Return the generated alias if requested.
    if ($op == 'return') {
      return $alias;
    }

    // Build the new path alias array and send it off to be created.
    $path = [
      'source' => $source,
      'alias' => $alias,
      'language' => $langcode,
    ];

    $return = $this->aliasStorageHelper->save($path, $existing_alias, $op);

    // Because there is no way to set an altered pattern to not be cached,
    // change it back to the original value.
    if ($pattern_altered !== $pattern_original) {
      $pattern->setPattern($pattern_original);
    }

    return $return;
  }

  /**
   * Loads pathauto patterns for a given entity type ID.
   *
   * @param string $entity_type_id
   *   An entity type ID.
   *
   * @return \Drupal\pathauto\PathautoPatternInterface[]
   *   A list of patterns, sorted by weight.
   */
  protected function getPatternByEntityType($entity_type_id) {
    if (!isset($this->patternsByEntityType[$entity_type_id])) {

      $ids = $this->entityTypeManager->getStorage('pathauto_pattern')
        ->getQuery()
        ->condition('type', array_keys(
          $this->aliasTypeManager
            ->getPluginDefinitionByType($this->tokenEntityMapper->getTokenTypeForEntityType($entity_type_id))))
        ->condition('status', 1)
        ->sort('weight')
        ->execute();

      $this->patternsByEntityType[$entity_type_id] = $this->entityTypeManager
        ->getStorage('pathauto_pattern')
        ->loadMultiple($ids);
    }

    return $this->patternsByEntityType[$entity_type_id];
  }

  /**
   * {@inheritdoc}
   */
  public function getPatternByEntity(EntityInterface $entity) {
    $langcode = $entity->language()->getId();
    if (!isset($this->patterns[$entity->getEntityTypeId()][$entity->id()][$langcode])) {
      foreach ($this->getPatternByEntityType($entity->getEntityTypeId()) as $pattern) {
        if ($pattern->applies($entity)) {
          $this->patterns[$entity->getEntityTypeId()][$entity->id()][$langcode] = $pattern;
          break;
        }
      }
      // If still not set.
      if (!isset($this->patterns[$entity->getEntityTypeId()][$entity->id()][$langcode])) {
        $this->patterns[$entity->getEntityTypeId()][$entity->id()][$langcode] = NULL;
      }
    }
    return $this->patterns[$entity->getEntityTypeId()][$entity->id()][$langcode];
  }

  /**
   * {@inheritdoc}
   */
  public function resetCaches() {
    $this->patterns = [];
    $this->patternsByEntityType = [];
    $this->aliasCleaner->resetCaches();
  }

  /**
   * {@inheritdoc}
   */
  public function updateEntityAlias(EntityInterface $entity, $op, array $options = []) {
    // Skip if the entity does not have the path field.
    if (!($entity instanceof ContentEntityInterface) || !$entity->hasField('path')) {
      return NULL;
    }

    // Skip if pathauto processing is disabled.
    if ($entity->path->pathauto != PathautoState::CREATE && empty($options['force'])) {
      return NULL;
    }

    // Only act if this is the default revision.
    if ($entity instanceof RevisionableInterface && !$entity->isDefaultRevision()) {
      return NULL;
    }

    $options += ['language' => $entity->language()->getId()];
    $type = $entity->getEntityTypeId();

    // Skip processing if the entity has no pattern.
    if (!$this->getPatternByEntity($entity)) {
      return NULL;
    }

    // Deal with taxonomy specific logic.
    // @todo Update and test forum related code.
    if ($type == 'taxonomy_term') {

      $config_forum = $this->configFactory->get('forum.settings');
      if ($entity->bundle() == $config_forum->get('vocabulary')) {
        $type = 'forum';
      }
    }

    try {
      $result = $this->createEntityAlias($entity, $op);
    }
    catch (\InvalidArgumentException $e) {
      $this->messenger()->addError($e->getMessage());
      return NULL;
    }

    // @todo Move this to a method on the pattern plugin.
    if ($type == 'taxonomy_term') {
      foreach ($this->loadTermChildren($entity->id()) as $subterm) {
        $this->updateEntityAlias($subterm, $op, $options);
      }
    }

    return $result;
  }

  /**
   * Finds all children of a term ID.
   *
   * @param int $tid
   *   Term ID to retrieve parents for.
   *
   * @return \Drupal\taxonomy\TermInterface[]
   *   An array of term objects that are the children of the term $tid.
   */
  protected function loadTermChildren($tid) {
    return $this->entityTypeManager->getStorage('taxonomy_term')->loadChildren($tid);
  }

}
