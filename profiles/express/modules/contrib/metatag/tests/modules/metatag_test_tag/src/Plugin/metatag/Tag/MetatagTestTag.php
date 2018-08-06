<?php

namespace Drupal\metatag_test_tag\Plugin\metatag\Tag;

use Drupal\metatag\Plugin\metatag\Tag\MetaNameBase;

/**
 * A metatag tag for testing.
 *
 * @MetatagTag(
 *   id = "metatag_test",
 *   label = @Translation("Metatag Test"),
 *   description = @Translation("A metatag tag for testing."),
 *   name = "metatag_test",
 *   group = "basic",
 *   weight = 3,
 *   type = "label",
 *   secure = FALSE,
 *   multiple = FALSE
 * )
 */
class MetatagTestTag extends MetaNameBase {
}
