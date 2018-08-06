<?php

/**
 * @file
 * Contains \Drupal\linkit\Tests\Matchers\TermMatcherTest.
 */

namespace Drupal\linkit\Tests\Matchers;

use Drupal\Component\Utility\Unicode;
use Drupal\Core\Language\LanguageInterface;
use Drupal\linkit\Tests\LinkitTestBase;
use Drupal\taxonomy\Entity\Vocabulary;

/**
 * Tests term matcher.
 *
 * @group linkit
 */
class TermMatcherTest extends LinkitTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['taxonomy'];

  /**
   * The matcher manager.
   *
   * @var \Drupal\linkit\MatcherManager
   */
  protected $manager;

  /**
   * Creates and saves a vocabulary.
   *
   * @param string $name
   *   The vocabulary name.
   *
   * @return Vocabulary The new vocabulary object.
   * The new vocabulary object.
   */
  private function createVocabulary($name) {
    $vocabularyStorage = \Drupal::entityTypeManager()->getStorage('taxonomy_vocabulary');
    $vocabulary = $vocabularyStorage->create([
      'name' => $name,
      'description' => $name,
      'vid' => Unicode::strtolower($name),
      'langcode' => LanguageInterface::LANGCODE_NOT_SPECIFIED,
    ]);
    $vocabulary->save();
    return $vocabulary;
  }

  /**
   * Creates and saves a new term with in vocabulary $vid.
   *
   * @param \Drupal\taxonomy\Entity\Vocabulary $vocabulary
   *   The vocabulary object.
   * @param array $values
   *   (optional) An array of values to set, keyed by property name. If the
   *   entity type has bundles, the bundle key has to be specified.
   *
   * @return \Drupal\taxonomy\Entity\Term
   *   The new taxonomy term object.
   */
  private function createTerm(Vocabulary $vocabulary, $values = array()) {
    $filter_formats = filter_formats();
    $format = array_pop($filter_formats);

    $termStorage = \Drupal::entityTypeManager()->getStorage('taxonomy_term');
    $term = $termStorage->create($values + array(
        'name' => $this->randomMachineName(),
        'description' => array(
          'value' => $this->randomMachineName(),
          // Use the first available text format.
          'format' => $format->id(),
        ),
        'vid' => $vocabulary->id(),
        'langcode' => LanguageInterface::LANGCODE_NOT_SPECIFIED,
      ));
    $term->save();
    return $term;
  }

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->drupalLogin($this->adminUser);
    $this->manager = $this->container->get('plugin.manager.linkit.matcher');

    $testing_vocabulary_1 = $this->createVocabulary('testing_vocabulary_1');
    $testing_vocabulary_2 = $this->createVocabulary('testing_vocabulary_2');

    $this->createTerm($testing_vocabulary_1, ['name' => 'foo_bar']);
    $this->createTerm($testing_vocabulary_1, ['name' => 'foo_baz']);
    $this->createTerm($testing_vocabulary_1, ['name' => 'foo_foo']);
    $this->createTerm($testing_vocabulary_1, ['name' => 'bar']);
    $this->createTerm($testing_vocabulary_2, ['name' => 'foo_bar']);
    $this->createTerm($testing_vocabulary_2, ['name' => 'foo_baz']);
  }

  /**
   * Tests term matcher with default configuration.
   */
  function testTermMatcherWidthDefaultConfiguration() {
    /** @var \Drupal\linkit\MatcherInterface $plugin */
    $plugin = $this->manager->createInstance('entity:taxonomy_term', []);
    $matches = $plugin->getMatches('foo');
    $this->assertEqual(5, count($matches), 'Correct number of matches');
  }

  /**
   * Tests term matcher with bundle filer.
   */
  function testTermMatcherWidthBundleFiler() {
    /** @var \Drupal\linkit\MatcherInterface $plugin */
    $plugin = $this->manager->createInstance('entity:taxonomy_term', [
      'settings' => [
        'bundles' => [
          'testing_vocabulary_1' => 'testing_vocabulary_1'
        ],
      ],
    ]);

    $matches = $plugin->getMatches('foo');
    $this->assertEqual(3, count($matches), 'Correct number of matches');
  }

}
