<?php

namespace Drupal\Tests\metatag\Functional;

use Drupal\taxonomy\Entity\Term;
use Drupal\taxonomy\Entity\Vocabulary;
use Drupal\Tests\BrowserTestBase;

/**
 * Ensures that meta tags output by core are removed if we are overriding them.
 *
 * @group metatag
 */
class RemoveCoreMetaTags extends BrowserTestBase {

  // Contains helper methods.
  use MetatagHelperTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'token',
    'metatag',
    'taxonomy',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Tests core tags are removed on taxonomy term pages.
   */
  public function testTaxonomyPage() {
    $this->loginUser1();

    // Set up a vocabulary.
    $vocabulary = Vocabulary::create([
      'vid' => 'metatag_vocab',
      'name' => $this->randomString(),
    ]);
    $vocabulary->save();
    $term = Term::create([
      'vid' => $vocabulary->id(),
      'name' => $this->randomString(),
    ]);
    $term->save();

    // Set up meta tags for taxonomy.
    $edit = [
      'canonical_url' => '[current-page:url:unaliased]',
    ];
    $this->drupalPostForm('admin/config/search/metatag/taxonomy_term', $edit, 'Save');

    // Ensure there is only 1 canonical metatag.
    $this->drupalGet('taxonomy/term/' . $term->id());
    $xpath = $this->xpath("//link[@rel='canonical']");
    $this->assertEquals(1, count($xpath), 'Exactly one canonical rel meta tag found.');
  }

}
