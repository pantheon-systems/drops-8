<?php

namespace Drupal\metatag_mobile\Plugin\metatag\Tag;

use Drupal\metatag\Plugin\metatag\Tag\MetaNameBase;

/**
 * The Handheld Friendly for Mobile metatag.
 *
 * @MetatagTag(
 *   id = "handheldfriendly",
 *   label = @Translation("Handheld-Friendly"),
 *   description = @Translation("Some older mobile browsers will expect this meta tag to be set to 'true' to indicate that the site has been designed with mobile browsers in mind."),
 *   name = "HandheldFriendly",
 *   group = "mobile",
 *   weight = 83,
 *   type = "string",
 *   secure = FALSE,
 *   multiple = TRUE
 * )
 */
class HandheldFriendly extends MetaNameBase {
  // Nothing here yet. Just a placeholder class for a plugin.
}
