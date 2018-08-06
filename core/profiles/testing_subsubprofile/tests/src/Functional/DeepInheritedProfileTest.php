<?php

namespace Drupal\Tests\testing_subsubprofile\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests inherited profiles.
 *
 * @group profiles
 */
class DeepInheritedProfileTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected $profile = 'testing_subsubprofile';

  /**
   * Tests sub-sub-profile inherited installation.
   */
  public function testDeepInheritedProfile() {
    // Check that stable is the default theme enabled in parent profile.
    $this->assertEquals('stable', $this->config('system.theme')->get('default'));

    // page_cache was enabled in main profile, disabled in parent and enabled
    // in this profile.
    $this->assertTrue(\Drupal::moduleHandler()->moduleExists('page_cache'));
    // block was enabled in parent profile.
    $this->assertTrue(\Drupal::moduleHandler()->moduleExists('block'));
    // config was enabled in parent profile and disabled in this.
    $this->assertFalse(\Drupal::moduleHandler()->moduleExists('config'));
  }

}
