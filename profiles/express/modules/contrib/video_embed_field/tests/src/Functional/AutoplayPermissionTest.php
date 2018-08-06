<?php

namespace Drupal\Tests\video_embed_field\Functional;
use Drupal\Tests\BrowserTestBase;

/**
 * Test the autoplay permission works.
 *
 * @group video_embed_field
 */
class AutoplayPermissionTest extends BrowserTestBase {

  use EntityDisplaySetupTrait;

  public static $modules = [
    'video_embed_field',
    'node',
  ];

  /**
   * Test the autoplay permission works.
   */
  public function testAutoplay() {
    $this->setupEntityDisplays();
    $node = $this->createVideoNode('https://vimeo.com/80896303');
    $this->setDisplayComponentSettings('video_embed_field_video', [
      'autoplay' => TRUE,
    ]);
    $bypass_autoplay_user = $this->drupalCreateUser(['never autoplay videos']);
    // Assert a user with the permission doesn't get autoplay.
    $this->drupalLogin($bypass_autoplay_user);
    $this->drupalGet('node/' . $node->id());
    $this->assertSession()->elementAttributeContains('css', 'iframe', 'src', 'autoplay=0');
    // Ensure an anonymous user gets autoplay.
    $this->drupalLogout();
    $this->drupalGet('node/' . $node->id());
    $this->assertSession()->elementAttributeContains('css', 'iframe', 'src', 'autoplay=1');
  }

}
