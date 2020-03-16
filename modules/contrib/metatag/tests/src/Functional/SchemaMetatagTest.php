<?php

namespace Drupal\Tests\metatag\Functional;

use Drupal\Tests\schema_web_page\Functional\SchemaWebPageTest;

/**
 * Wrapper to trigger one of the Schema.org Metatag module's tests.
 *
 * This will help avoid making changes to Metatag that trigger problems for
 * separate submodules.
 *
 * @see https://www.drupal.org/project/metatag/issues/2994979
 *
 * @group metatag
 */
class SchemaMetatagTest extends SchemaWebPageTest {
  // Just run the tests as-is.
}
