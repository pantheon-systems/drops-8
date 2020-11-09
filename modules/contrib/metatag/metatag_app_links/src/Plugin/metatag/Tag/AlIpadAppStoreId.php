<?php

namespace Drupal\metatag_app_links\Plugin\metatag\Tag;

use Drupal\metatag\Plugin\metatag\Tag\MetaPropertyBase;

/**
 * The AppLinks iPad app store ID meta tag.
 *
 * @MetatagTag(
 *   id = "al_ipad_app_store_id",
 *   label = @Translation("iPad app store ID"),
 *   description = @Translation("The app ID for the app store."),
 *   name = "al:ipad:app_store_id",
 *   group = "app_links",
 *   weight = 1,
 *   type = "string",
 *   secure = FALSE,
 *   multiple = FALSE
 * )
 */
class AlIpadAppStoreId extends MetaPropertyBase {
  // Nothing here yet. Just a placeholder class for a plugin.
}
