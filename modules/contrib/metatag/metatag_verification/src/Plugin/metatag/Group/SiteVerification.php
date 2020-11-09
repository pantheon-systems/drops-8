<?php

namespace Drupal\metatag_verification\Plugin\metatag\Group;

use Drupal\metatag\Plugin\metatag\Group\GroupBase;

/**
 * The Site Verification group.
 *
 * @MetatagGroup(
 *   id = "site_verification",
 *   label = @Translation("Site verification"),
 *   description = @Translation("These meta tags are used to confirm site ownership for search engines and other services."),
 *   weight = 10
 * )
 */
class SiteVerification extends GroupBase {
  // Inherits everything from Base.
}
