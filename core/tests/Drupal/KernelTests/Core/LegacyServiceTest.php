<?php

namespace Drupal\KernelTests\Core;

use Drupal\KernelTests\KernelTestBase;

/**
 * Tests deprecated core services.
 *
 * @group Core
 * @group legacy
 */
class LegacyServiceTest extends KernelTestBase {

  /**
   * Tests the site.path service.
   *
   * @expectedDeprecation The "site.path" service is deprecated in drupal:9.0.0 and is removed from drupal:10.0.0. Use the site.path parameter instead. See https://www.drupal.org/node/3080612
   * @expectedDeprecation The "site.path.factory" service is deprecated in drupal:9.0.0 and is removed from drupal:10.0.0. Use the site.path parameter instead. See https://www.drupal.org/node/3080612
   */
  public function testSitePath() {
    $this->assertSame($this->container->get('site.path'), (string) $this->container->getParameter('site.path'));
  }

  /**
   * Tests the app.root service.
   *
   * @expectedDeprecation The "app.root" service is deprecated in drupal:9.0.0 and is removed from drupal:10.0.0. Use the app.root parameter instead. See https://www.drupal.org/node/3080612
   * @expectedDeprecation The "app.root.factory" service is deprecated in drupal:9.0.0 and is removed from drupal:10.0.0. Use the app.root parameter instead. See https://www.drupal.org/node/3080612
   */
  public function testAppRoot() {
    $this->assertSame($this->container->get('app.root'), (string) $this->container->getParameter('app.root'));
  }

}
