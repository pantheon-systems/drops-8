<?php

namespace Drupal\Tests\pathauto\Functional;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Language\Language;
use Drupal\Core\Render\BubbleableMetadata;
use Drupal\pathauto\Entity\PathautoPattern;
use Drupal\pathauto\PathautoPatternInterface;
use Drupal\taxonomy\VocabularyInterface;
use Drupal\taxonomy\Entity\Vocabulary;
use Drupal\taxonomy\Entity\Term;

/**
 * Helper test class with some added functions for testing.
 */
trait PathautoTestHelperTrait {

  /**
   * Creates a pathauto pattern.
   *
   * @param string $entity_type_id
   *   The entity type.
   * @param string $pattern
   *   The path pattern.
   * @param int $weight
   *   (optional) The pattern weight.
   *
   * @return \Drupal\pathauto\PathautoPatternInterface
   *   The created pattern.
   */
  protected function createPattern($entity_type_id, $pattern, $weight = 10) {
    $type = ($entity_type_id == 'forum') ? 'forum' : 'canonical_entities:' . $entity_type_id;

    $pattern = PathautoPattern::create([
      'id' => mb_strtolower($this->randomMachineName()),
      'type' => $type,
      'pattern' => $pattern,
      'weight' => $weight,
    ]);
    $pattern->save();
    return $pattern;
  }

  /**
   * Add a bundle condition to a pathauto pattern.
   *
   * @param \Drupal\pathauto\PathautoPatternInterface $pattern
   *   The pattern.
   * @param string $entity_type
   *   The entity type ID.
   * @param string $bundle
   *   The bundle.
   */
  protected function addBundleCondition(PathautoPatternInterface $pattern, $entity_type, $bundle) {
    $plugin_id = $entity_type == 'node' ? 'node_type' : 'entity_bundle:' . $entity_type;

    $pattern->addSelectionCondition(
      [
        'id' => $plugin_id,
        'bundles' => [
          $bundle => $bundle,
        ],
        'negate' => FALSE,
        'context_mapping' => [
          $entity_type => $entity_type,
        ]
      ]
    );
  }

  public function assertToken($type, $object, $token, $expected) {
    $bubbleable_metadata = new BubbleableMetadata();
    $tokens = \Drupal::token()->generate($type, [$token => $token], [$type => $object], [], $bubbleable_metadata);
    $tokens += [$token => ''];
    $this->assertSame($tokens[$token], $expected, t("Token value for [@type:@token] was '@actual', expected value '@expected'.", ['@type' => $type, '@token' => $token, '@actual' => $tokens[$token], '@expected' => $expected]));
  }

  public function saveAlias($source, $alias, $langcode = Language::LANGCODE_NOT_SPECIFIED) {
    \Drupal::service('path.alias_storage')->delete(['source' => $source, 'langcode' => $langcode]);
    return \Drupal::service('path.alias_storage')->save($source, $alias, $langcode);
  }

  public function saveEntityAlias(EntityInterface $entity, $alias, $langcode = NULL) {
    // By default, use the entity language.
    if (!$langcode) {
      $langcode = $entity->language()->getId();
    }
    return $this->saveAlias('/' . $entity->toUrl()->getInternalPath(), $alias, $langcode);
  }

  public function assertEntityAlias(EntityInterface $entity, $expected_alias, $langcode = NULL) {
    // By default, use the entity language.
    if (!$langcode) {
      $langcode = $entity->language()->getId();
    }
    $this->assertAlias('/' . $entity->toUrl()->getInternalPath(), $expected_alias, $langcode);
  }

  public function assertEntityAliasExists(EntityInterface $entity) {
    return $this->assertAliasExists(['source' => '/' . $entity->toUrl()->getInternalPath()]);
  }

  public function assertNoEntityAlias(EntityInterface $entity, $langcode = NULL) {
    // By default, use the entity language.
    if (!$langcode) {
      $langcode = $entity->language()->getId();
    }
    $this->assertEntityAlias($entity, '/' . $entity->toUrl()->getInternalPath(), $langcode);
  }

  public function assertNoEntityAliasExists(EntityInterface $entity, $alias = NULL) {
    $path = ['source' => '/' . $entity->toUrl()->getInternalPath()];
    if (!empty($alias)) {
      $path['alias'] = $alias;
    }
    $this->assertNoAliasExists($path);
  }

  public function assertAlias($source, $expected_alias, $langcode = Language::LANGCODE_NOT_SPECIFIED) {
    \Drupal::service('path.alias_manager')->cacheClear($source);
    $entity_type_manager = \Drupal::entityTypeManager();
    if ($entity_type_manager->hasDefinition('path_alias')) {
      $entity_type_manager->getStorage('path_alias')->resetCache();
    }
    $this->assertEquals($expected_alias, \Drupal::service('path.alias_manager')->getAliasByPath($source, $langcode), t("Alias for %source with language '@language' is correct.",
      ['%source' => $source, '@language' => $langcode]));
  }

  public function assertAliasExists($conditions) {
    $path = \Drupal::service('path.alias_storage')->load($conditions);
    $this->assertTrue($path, t('Alias with conditions @conditions found.', ['@conditions' => var_export($conditions, TRUE)]));
    return $path;
  }

  public function assertNoAliasExists($conditions) {
    $alias = \Drupal::service('path.alias_storage')->load($conditions);
    $this->assertFalse($alias, t('Alias with conditions @conditions not found.', ['@conditions' => var_export($conditions, TRUE)]));
  }

  public function deleteAllAliases() {
    \Drupal::service('pathauto.alias_storage_helper')->deleteAll();
    \Drupal::service('path.alias_manager')->cacheClear();
  }

  /**
   * @param array $values
   *
   * @return \Drupal\taxonomy\VocabularyInterface
   */
  public function addVocabulary(array $values = []) {
    $name = mb_strtolower($this->randomMachineName(5));
    $values += [
      'name' => $name,
      'vid' => $name,
    ];
    $vocabulary = Vocabulary::create($values);
    $vocabulary->save();

    return $vocabulary;
  }

  public function addTerm(VocabularyInterface $vocabulary, array $values = []) {
    $values += [
      'name' => mb_strtolower($this->randomMachineName(5)),
      'vid' => $vocabulary->id(),
    ];

    $term = Term::create($values);
    $term->save();
    return $term;
  }

  public function assertEntityPattern($entity_type, $bundle, $langcode = Language::LANGCODE_NOT_SPECIFIED, $expected) {

    $values = [
      'langcode' => $langcode,
      \Drupal::entityTypeManager()->getDefinition($entity_type)->getKey('bundle') => $bundle,
    ];
    $entity = \Drupal::entityTypeManager()->getStorage($entity_type)->create($values);

    $pattern = \Drupal::service('pathauto.generator')->getPatternByEntity($entity);
    $this->assertSame($expected, $pattern->getPattern());
  }

  public function drupalGetTermByName($name, $reset = FALSE) {
    if ($reset) {
      // @todo - implement cache reset.
    }
    $terms = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadByProperties(['name' => $name]);
    return !empty($terms) ? reset($terms) : FALSE;
  }

}
