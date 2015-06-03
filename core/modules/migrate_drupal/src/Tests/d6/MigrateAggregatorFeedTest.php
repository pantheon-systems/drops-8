<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Tests\d6\MigrateAggregatorFeedTest.
 */

namespace Drupal\migrate_drupal\Tests\d6;

use Drupal\aggregator\Entity\Feed;
use Drupal\migrate\MigrateExecutable;
use Drupal\migrate_drupal\Tests\d6\MigrateDrupal6TestBase;

/**
 * Upgrade variables to aggregator_feed entities.
 *
 * @group migrate_drupal
 */
class MigrateAggregatorFeedTest extends MigrateDrupal6TestBase {

  static $modules = array('aggregator');

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->installEntitySchema('aggregator_feed');

    $migration = entity_load('migration', 'd6_aggregator_feed');
    $dumps = array(
      $this->getDumpDirectory() . '/AggregatorFeed.php',
    );
    $this->prepare($migration, $dumps);
    $executable = new MigrateExecutable($migration, $this);
    $executable->import();
  }

  /**
   * Tests migration of aggregator feeds.
   */
  public function testAggregatorFeedImport() {
    /** @var Feed $feed */
    $feed = Feed::load(5);
    $this->assertNotNull($feed->uuid());
    $this->assertIdentical('Know Your Meme', $feed->title->value);
    $this->assertIdentical('en', $feed->language()->getId());
    $this->assertIdentical('http://knowyourmeme.com/newsfeed.rss', $feed->url->value);
    $this->assertIdentical('900', $feed->refresh->value);
    $this->assertIdentical('1387659487', $feed->checked->value);
    $this->assertIdentical('0', $feed->queued->value);
    $this->assertIdentical('http://knowyourmeme.com', $feed->link->value);
    $this->assertIdentical('New items added to the News Feed', $feed->description->value);
    $this->assertIdentical('http://b.thumbs.redditmedia.com/harEHsUUZVajabtC.png', $feed->image->value);
    $this->assertIdentical('"213cc1365b96c310e92053c5551f0504"', $feed->etag->value);
    $this->assertIdentical('0', $feed->modified->value);
  }
}
