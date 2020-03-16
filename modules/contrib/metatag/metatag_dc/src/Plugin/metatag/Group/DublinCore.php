<?php

namespace Drupal\metatag_dc\Plugin\metatag\Group;

use Drupal\metatag\Plugin\metatag\Group\GroupBase;

/**
 * The DublinCore group.
 *
 * @MetatagGroup(
 *   id = "dublin_core",
 *   label = @Translation("Dublin Core"),
 *   description = @Translation("Provides the fifteen <a href=':docs'>Dublin Core Metadata Element Set 1.1</a> meta tags from the <a href=':link'>Dublin Core Metadata Institute</a>", arguments = { ":docs" = "http://dublincore.org/documents/dces/", ":link" = "http://dublincore.org/"}),
 *   weight = 4
 * )
 */
class DublinCore extends GroupBase {
  // Inherits everything from Base.
}
