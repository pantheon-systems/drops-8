<?php

/**
 * @file
 * Contains \Drupal\Tests\redirect\Kernel\Migrate\d7\PathRedirectTest.
 */

namespace Drupal\Tests\redirect\Kernel\Migrate\d7;

use Drupal\redirect\Entity\Redirect;
use Drupal\Tests\migrate_drupal\Kernel\MigrateDrupalTestBase;


/**
 * Tests the d7_path_redirect source plugin.
 *
 * @group redirect
 */
class PathRedirectTest extends MigrateDrupalTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = array('redirect', 'link');

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->installEntitySchema('redirect');
    $this->loadFixture(__DIR__ . '/../../../../fixtures/drupal7.php');

    $this->executeMigration('d7_path_redirect');
  }

  /**
   * Asserts various aspects of a redirect entity.
   *
   * @param int $id
   *   The entity ID in the form ENTITY_TYPE.BUNDLE.FIELD_NAME.
   * @param string $source_url
   *   The expected source url.
   * @param string $redirect_url
   *   The expected redirect url.
   * @param string $status_code
   *   The expected status code.
   */
  protected function assertEntity($id, $source_url, $redirect_url, $status_code) {
    /** @var Redirect $redirect */
    $redirect = Redirect::load($id);
    $this->assertSame($this->getMigration('d7_path_redirect')
      ->getIdMap()
      ->lookupDestinationID([$id]), [$redirect->id()]);
    $this->assertSame($source_url, $redirect->getSourceUrl());
    $this->assertSame($redirect_url, $redirect->getRedirectUrl()
      ->toUriString());
    $this->assertSame($status_code, $redirect->getStatusCode());
  }

  /**
   * Tests the Drupal 7 path redirect to Drupal 8 migration.
   */
  public function testPathRedirect() {
    $this->assertEntity(5, '/test/source/url', 'base:test/redirect/url', '301');
    $this->assertEntity(7, '/test/source/url2', 'http://test/external/redirect/url?foo=bar&biz=buz', '307');
  }
}
