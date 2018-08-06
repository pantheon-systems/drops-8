<?php

namespace Drupal\Tests\testing_inherited\Functional;

use Drupal\block\BlockInterface;
use Drupal\block\Entity\Block;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests inherited profiles.
 *
 * @group profiles
 */
class InheritedProfileTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected $profile = 'testing_inherited';

  /**
   * Tests inherited installation profile.
   */
  public function testInheritedProfile() {
    // Check that the stable_login block exists.
    $this->assertInstanceOf(BlockInterface::class, Block::load('stable_login'));

    // Check that stable is the default theme.
    $this->assertEquals('stable', $this->config('system.theme')->get('default'));

    // Check the excluded_dependencies flag on installation profiles.
    $this->assertTrue(\Drupal::moduleHandler()->moduleExists('config'));
    $this->assertFalse(\Drupal::moduleHandler()->moduleExists('page_cache'));

    // Check that all themes were installed, except excluded ones.
    $this->assertTrue(\Drupal::service('theme_handler')->themeExists('stable'));
    $this->assertFalse(\Drupal::service('theme_handler')->themeExists('classy'));
  }

}
