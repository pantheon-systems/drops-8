<?php

namespace Drupal\Tests\token\Kernel;

use Drupal\taxonomy\Entity\Term;
use Drupal\taxonomy\Entity\Vocabulary;
use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\Core\Url;

/**
 * Tests taxonomy tokens.
 *
 * @group token
 */
class TaxonomyTest extends KernelTestBase {

  protected $vocab;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['taxonomy', 'text', 'language'];

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    $this->installEntitySchema('taxonomy_term');

    // Create the default tags vocabulary.
    $vocabulary = Vocabulary::create([
      'name' => 'Tags',
      'vid' => 'tags',
    ]);
    $vocabulary->save();
    $this->vocab = $vocabulary;
  }

  /**
   * Test the additional taxonomy term tokens.
   */
  function testTaxonomyTokens() {
    $root_term = $this->addTerm($this->vocab, ['name' => 'Root term', 'path' => ['alias' => '/root-term']]);
    $tokens = [
      'url' => Url::fromRoute('entity.taxonomy_term.canonical', ['taxonomy_term' => $root_term->id()], ['absolute' => TRUE])->toString(),
      'url:absolute' => Url::fromRoute('entity.taxonomy_term.canonical', ['taxonomy_term' => $root_term->id()], ['absolute' => TRUE])->toString(),
      'url:relative' => Url::fromRoute('entity.taxonomy_term.canonical', ['taxonomy_term' => $root_term->id()], ['absolute' => FALSE])->toString(),
      'url:path' => '/root-term',
      'url:unaliased:path' => "/taxonomy/term/{$root_term->id()}",
      'edit-url' => Url::fromRoute('entity.taxonomy_term.edit_form', ['taxonomy_term' => $root_term->id()], ['absolute' => TRUE])->toString(),
      'parents' => NULL,
      'parents:count' => NULL,
      'parents:keys' => NULL,
      'root' => NULL,
      // Deprecated tokens
      'url:alias' => '/root-term',
    ];
    $this->assertTokens('term', ['term' => $root_term], $tokens);

    $parent_term = $this->addTerm($this->vocab, ['name' => 'Parent term', 'parent' => $root_term->id()]);
    $tokens = [
      'url' => Url::fromRoute('entity.taxonomy_term.canonical', ['taxonomy_term' => $parent_term->id()], ['absolute' => TRUE])->toString(),
      'url:absolute' => Url::fromRoute('entity.taxonomy_term.canonical', ['taxonomy_term' => $parent_term->id()], ['absolute' => TRUE])->toString(),
      'url:relative' => Url::fromRoute('entity.taxonomy_term.canonical', ['taxonomy_term' => $parent_term->id()], ['absolute' => FALSE])->toString(),
      'url:path' => "/taxonomy/term/{$parent_term->id()}",
      'url:unaliased:path' => "/taxonomy/term/{$parent_term->id()}",
      'edit-url' => Url::fromRoute('entity.taxonomy_term.edit_form', ['taxonomy_term' => $parent_term->id()], ['absolute' => TRUE])->toString(),
      'parents' => 'Root term',
      'parents:count' => 1,
      'parents:keys' => $root_term->id(),
      'root' => $root_term->label(),
      'root:tid' => $root_term->id(),
      // Deprecated tokens
      'url:alias' => "/taxonomy/term/{$parent_term->id()}",
    ];
    $this->assertTokens('term', ['term' => $parent_term], $tokens);

    $term = $this->addTerm($this->vocab, ['name' => 'Test term', 'parent' => $parent_term->id()]);
    $tokens = [
      'parents' => 'Root term, Parent term',
      'parents:count' => 2,
      'parents:keys' => implode(', ', [$root_term->id(), $parent_term->id()]),
    ];
    $this->assertTokens('term', ['term' => $term], $tokens);
  }

  /**
   * Test the additional vocabulary tokens.
   */
  function testVocabularyTokens() {
    $vocabulary = $this->vocab;
    $tokens = [
      'machine-name' => 'tags',
      'edit-url' => Url::fromRoute('entity.taxonomy_vocabulary.edit_form', ['taxonomy_vocabulary' => $vocabulary->id()], ['absolute' => TRUE])->toString(),
    ];
    $this->assertTokens('vocabulary', ['vocabulary' => $vocabulary], $tokens);
  }

  function addVocabulary(array $vocabulary = []) {
    $vocabulary += [
      'name' => mb_strtolower($this->randomMachineName(5)),
      'nodes' => ['article' => 'article'],
    ];
    $vocabulary = Vocabulary::create($vocabulary)->save();
    return $vocabulary;
  }

  function addTerm($vocabulary, array $term = []) {
    $term += [
      'name' => mb_strtolower($this->randomMachineName(5)),
      'vid' => $vocabulary->id(),
    ];
    $term = Term::create($term);
    $term->save();
    return $term;
  }

  /**
   * Test the multilingual terms.
   */
  function testMultilingualTerms() {
    // Add a second language.
    $language = ConfigurableLanguage::createFromLangcode('de');
    $language->save();

    // Create an english parent term and add a german translation for it.
    $parent_term = $this->addTerm($this->vocab, [
      'name' => 'english-parent-term',
      'langcode' => 'en',
    ]);
    $parent_term->addTranslation('de', [
      'name' => 'german-parent-term',
    ])->save();

    // Create a term related to the parent term.
    $child_term = $this->addTerm($this->vocab, [
      'name' => 'english-child-term',
      'langcode' => 'en',
      'parent' => $parent_term->id(),
    ]);
    $child_term->addTranslation('de', [
      'name' => 'german-child-term',
    ])->save();

    // Expect the parent term to be in the specified language.
    $this->assertTokens('term', ['term' => $child_term], ['parents' => 'german-parent-term'], ['langcode' => 'de']);
    $this->assertTokens('term', ['term' => $child_term], ['root' => 'german-parent-term'], ['langcode' => 'de']);
  }

}
