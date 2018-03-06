<?php

namespace Drupal\KernelTests\Core\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\entity_test\Entity\EntityTestMulRev;
use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\user\Entity\User;

/**
 * Test decoupled translation revisions.
 *
 * @group entity
 *
 * @coversDefaultClass \Drupal\Core\Entity\ContentEntityStorageBase
 */
class EntityDecoupledTranslationRevisionsTest extends EntityKernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'system',
    'entity_test',
    'language',
  ];

  /**
   * The entity type bundle info service.
   *
   * @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface
   */
  protected $bundleInfo;

  /**
   * The entity storage.
   *
   * @var \Drupal\Core\Entity\ContentEntityStorageInterface
   */
  protected $storage;

  /**
   * The translations of the test entity.
   *
   * @var \Drupal\Core\Entity\ContentEntityInterface[]
   */
  protected $translations;

  /**
   * The previous revision identifiers for the various revision translations.
   *
   * @var int[]
   */
  protected $previousRevisionId = [];

  /**
   * The previous unstranslatable field value.
   *
   * @var string[]
   */
  protected $previousUntranslatableFieldValue;

  /**
   * The current edit sequence step index.
   *
   * @var int
   */
  protected $stepIndex;

  /**
   * The current edit sequence step info.
   *
   * @var array
   */
  protected $stepInfo;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $entity_type_id = 'entity_test_mulrev';
    $this->installEntitySchema($entity_type_id);
    $this->storage = $this->container->get('entity_type.manager')
      ->getStorage($entity_type_id);

    $this->installConfig(['language']);
    $langcodes = ['it', 'fr'];
    foreach ($langcodes as $langcode) {
      ConfigurableLanguage::createFromLangcode($langcode)->save();
    }

    $values = [
      'name' => $this->randomString(),
      'status' => 1,
    ];
    User::create($values)->save();

    // Make sure entity bundles are translatable.
    $this->state->set('entity_test.translation', TRUE);
    $this->bundleInfo = \Drupal::service('entity_type.bundle.info');
    $this->bundleInfo->clearCachedBundles();
  }

  /**
   * Data provider for ::testDecoupledDefaultRevisions.
   */
  public function dataTestDecoupledPendingRevisions() {
    $sets = [];

    $sets['Intermixed languages - No initial default translation'][] = [
      ['en', TRUE],
      ['en', FALSE],
      ['it', FALSE],
      ['en', FALSE],
      ['it', FALSE],
      ['en', TRUE],
      ['it', TRUE],
    ];

    $sets['Intermixed languages - With initial default translation'][] = [
      ['en', TRUE],
      ['it', TRUE],
      ['en', FALSE],
      ['it', FALSE],
      ['en', TRUE],
      ['it', TRUE],
    ];

    $sets['Alternate languages - No initial default translation'][] = [
      ['en', TRUE],
      ['en', FALSE],
      ['en', FALSE],
      ['en', TRUE],
      ['it', FALSE],
      ['en', TRUE],
      ['it', FALSE],
      ['it', FALSE],
      ['it', TRUE],
    ];

    $sets['Alternate languages - With initial default translation'][] = [
      ['en', TRUE],
      ['it', TRUE],
      ['en', TRUE],
      ['en', FALSE],
      ['en', FALSE],
      ['en', TRUE],
      ['it', TRUE],
      ['it', FALSE],
      ['it', FALSE],
      ['it', TRUE],
    ];

    $sets['Multiple languages - No initial default translation'][] = [
      ['en', TRUE],
      ['it', FALSE],
      ['fr', FALSE],
      ['en', FALSE],
      ['en', TRUE],
      ['it', TRUE],
      ['fr', FALSE],
      ['en', FALSE],
      ['it', FALSE],
      ['en', TRUE],
      ['fr', TRUE],
      ['it', TRUE],
      ['fr', TRUE],
    ];

    $sets['Multiple languages - With initial default translation'][] = [
      ['en', TRUE],
      ['it', TRUE],
      ['fr', TRUE],
      ['en', FALSE],
      ['it', FALSE],
      ['en', TRUE],
      ['it', TRUE],
      ['fr', FALSE],
      ['en', FALSE],
      ['it', FALSE],
      ['en', TRUE],
      ['fr', TRUE],
      ['it', TRUE],
      ['fr', TRUE],
    ];

    return $sets;
  }

  /**
   * Test decoupled default revisions.
   *
   * @param array[] $sequence
   *   An array with arrays of arguments for the ::doSaveNewRevision() method as
   *   values. Every child array corresponds to a method invocation.
   *
   * @covers ::createRevision
   *
   * @dataProvider dataTestDecoupledPendingRevisions
   */
  public function testDecoupledPendingRevisions($sequence) {
    $revision_id = $this->doTestEditSequence($sequence);
    $this->assertEquals(count($sequence), $revision_id);
  }

  /**
   * Data provider for ::testUntranslatableFields.
   */
  public function dataTestUntranslatableFields() {
    $sets = [];

    $sets['Default behavior - Untranslatable fields affect all revisions'] = [
      [
        ['en', TRUE, TRUE],
        ['it', TRUE, TRUE],
        ['en', FALSE],
        ['it', FALSE],
        ['en', TRUE],
        ['it', TRUE],
      ],
    ];

    return $sets;
  }

  /**
   * Tests that untranslatable fields are handled correctly.
   *
   * @param array[] $sequence
   *   An array with arrays of arguments for the ::doSaveNewRevision() method as
   *   values. Every child array corresponds to a method invocation.
   *
   * @covers ::createRevision
   *
   * @dataProvider dataTestUntranslatableFields
   */
  public function testUntranslatableFields($sequence) {
    // Test that a new entity is always valid.
    $entity = EntityTestMulRev::create();
    $entity->set('non_mul_field', 0);
    $violations = $entity->validate();
    $this->assertEmpty($violations);

    // Test the specified sequence.
    $this->doTestEditSequence($sequence);
  }

  /**
   * Actually tests an edit step sequence.
   *
   * @param array[] $sequence
   *   An array of sequence steps.
   *
   * @return int
   *   The latest saved revision id.
   */
  protected function doTestEditSequence($sequence) {
    $revision_id = NULL;
    foreach ($sequence as $index => $step) {
      $this->stepIndex = $index;
      $revision_id = call_user_func_array([$this, 'doEditStep'], $step);
    }
    return $revision_id;
  }

  /**
   * Saves a new revision of the test entity.
   *
   * @param string $active_langcode
   *   The language of the translation for which a new revision will be saved.
   * @param bool $default_revision
   *   Whether the revision should be flagged as the default revision.
   * @param bool $untranslatable_update
   *   (optional) Whether an untranslatable field update should be performed.
   *   Defaults to FALSE.
   * @param bool $valid
   *   (optional) Whether entity validation is expected to succeed. Defaults to
   *   TRUE.
   *
   * @return int
   *   The new revision identifier.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  protected function doEditStep($active_langcode, $default_revision, $untranslatable_update = FALSE, $valid = TRUE) {
    $this->stepInfo = [$active_langcode, $default_revision, $untranslatable_update, $valid];

    // Initialize previous data tracking.
    if (!isset($this->translations)) {
      $this->translations[$active_langcode] = EntityTestMulRev::create();
      $this->previousRevisionId[$active_langcode] = 0;
    }
    if (!isset($this->translations[$active_langcode])) {
      $this->translations[$active_langcode] = reset($this->translations)->addTranslation($active_langcode);
      $this->previousRevisionId[$active_langcode] = 0;
    }

    // We want to update previous data only if we expect a valid result,
    // otherwise we would be just polluting it with invalid values.
    if ($valid) {
      $entity = &$this->translations[$active_langcode];
      $previous_revision_id = &$this->previousRevisionId[$active_langcode];
    }
    else {
      $entity = clone $this->translations[$active_langcode];
      $previous_revision_id = $this->previousRevisionId[$active_langcode];
    }

    // Check that after instantiating a new revision for the specified
    // translation, we are resuming work from where we left the last time. If
    // that is the case, the label generated for the previous revision should
    // match the stored one.
    if (!$entity->isNew()) {
      $previous_label = NULL;
      if (!$entity->isNewTranslation()) {
        $previous_label = $this->generateNewEntityLabel($entity, $previous_revision_id);
      }
      $previous_revision_id = (int) $entity->getLoadedRevisionId();
      $latest_affected_revision_id = $this->storage->getLatestTranslationAffectedRevisionId($entity->id(), $entity->language()->getId());
      /** @var \Drupal\Core\Entity\ContentEntityInterface $latest_affected_revision */
      $latest_affected_revision = isset($latest_affected_revision_id) ?
        $this->storage->loadRevision($latest_affected_revision_id) : $this->storage->load($entity->id());
      $translation = $latest_affected_revision->hasTranslation($active_langcode) ?
        $latest_affected_revision->getTranslation($active_langcode) : $latest_affected_revision->addTranslation($active_langcode);
      $entity = $this->storage->createRevision($translation, $default_revision);
      $this->assertEquals($default_revision, $entity->isDefaultRevision());
      $this->assertEquals($translation->getLoadedRevisionId(), $entity->getLoadedRevisionId());
      $this->assertEquals($previous_label, $entity->label(), $this->formatMessage('Loaded translatable field value does not match the previous one.'));
    }

    $value = $entity->get('non_mul_field')->value;
    if (isset($previous_untranslatable_field_value)) {
      $this->assertEquals($previous_untranslatable_field_value, $value, $this->formatMessage('Loaded untranslatable field value does not match the previous one.'));
    }

    // Perform a change and store it.
    $label = $this->generateNewEntityLabel($entity, $previous_revision_id, TRUE);
    $entity->set('name', $label);
    if ($untranslatable_update) {
      // Store the revision ID of the previous untranslatable fields update in
      // the new value, besides the upcoming revision ID. Useful to analyze test
      // failures.
      $prev = 0;
      if (isset($value)) {
        preg_match('/^\d+ -> (\d+)$/', $value, $matches);
        $prev = $matches[1];
      }
      $value = $prev . ' -> ' . ($entity->getLoadedRevisionId() + 1);
      $entity->set('non_mul_field', $value);
    }

    $violations = $entity->validate();
    $messages = [];
    foreach ($violations as $violation) {
      /** \Symfony\Component\Validator\ConstraintViolationInterface */
      $messages[] = $violation->getMessage();
    }
    $this->assertEquals($valid, !$violations->count(), $this->formatMessage('Validation does not match the expected result: %s', implode(', ', $messages)));

    if ($valid) {
      $entity->save();

      // Reload the current revision translation and the default revision to
      // make sure data was stored correctly.
      /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */
      $entity = $this->storage->loadRevision($entity->getRevisionId());
      $entity = $entity->getTranslation($active_langcode);
      /** @var \Drupal\Core\Entity\ContentEntityInterface $default_entity */
      $default_entity = $this->storage->loadUnchanged($entity->id());

      // Verify that the values for the current revision translation match the
      // expected ones, while for the other translations they match the default
      // revision. We also need to verify that only the current revision
      // translation was marked as affected.
      foreach ($entity->getTranslationLanguages() as $langcode => $language) {
        $translation = $entity->getTranslation($langcode);
        $rta_expected = $langcode == $active_langcode || $untranslatable_update;
        $this->assertEquals($rta_expected, $translation->isRevisionTranslationAffected(), $this->formatMessage("'$langcode' translation incorrectly affected"));
        $label_expected = $label;
        if ($langcode !== $active_langcode) {
          $default_translation = $default_entity->hasTranslation($langcode) ? $default_entity->getTranslation($langcode) : $default_entity;
          $label_expected = $default_translation->label();
        }
        $this->assertEquals($label_expected, $translation->label(), $this->formatMessage("Incorrect '$langcode' translation label"));
      }
    }

    return $entity->getRevisionId();
  }

  /**
   * Generates a new label for the specified revision.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $revision
   *   An entity object.
   * @param int $previous_revision_id
   *   The previous revision identifier for this revision translation.
   * @param bool $next
   *   (optional) Whether the label describes the current revision or the one
   *   to be created. Defaults to FALSE.
   *
   * @return string
   *   A revision label.
   */
  protected function generateNewEntityLabel(ContentEntityInterface $revision, $previous_revision_id, $next = FALSE) {
    $language_label = $revision->language()->getName();
    $revision_type = $revision->isDefaultRevision() ? 'Default' : 'Pending';
    $revision_id = $next ? $this->storage->getLatestRevisionId($revision->id()) + 1 : $revision->getLoadedRevisionId();
    return sprintf('%s (%s %d -> %d)', $language_label, $revision_type, $previous_revision_id, $revision_id);
  }

  /**
   * Formats an assertion message.
   *
   * @param string $message
   *   The human-readable message.
   *
   * @return string
   *   The formatted message.
   */
  protected function formatMessage($message) {
    $args = func_get_args();
    array_shift($args);
    $params = array_merge($args, $this->stepInfo);
    array_unshift($params, $this->stepIndex + 1);
    array_unshift($params, '[Step %d] ' . $message . ' (langcode: %s, default_revision: %d, untranslatable_update: %d, valid: %d)');
    return call_user_func_array('sprintf', $params);
  }

  /**
   * Tests that internal properties are preserved while creating a new revision.
   */
  public function testInternalProperties() {
    $entity = EntityTestMulRev::create();
    $this->doTestInternalProperties($entity);

    $entity = EntityTestMulRev::create();
    $entity->save();
    $this->doTestInternalProperties($entity);

    /** @var \Drupal\entity_test\Entity\EntityTestMulRev $translation */
    $translation = EntityTestMulRev::create()->addTranslation('it');
    $translation->save();
    $this->doTestInternalProperties($translation);
  }

  /**
   * Checks that internal properties are preserved for the specified entity.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   An entity object.
   */
  protected function doTestInternalProperties(ContentEntityInterface $entity) {
    $this->assertFalse($entity->isValidationRequired());
    $entity->setValidationRequired(TRUE);
    $this->assertTrue($entity->isValidationRequired());
    $new_revision = $this->storage->createRevision($entity);
    $this->assertTrue($new_revision->isValidationRequired());
  }

}
