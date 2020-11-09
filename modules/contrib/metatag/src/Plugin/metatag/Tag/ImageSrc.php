<?php

namespace Drupal\metatag\Plugin\metatag\Tag;

/**
 * The advanced "Image" meta tag.
 *
 * @MetatagTag(
 *   id = "image_src",
 *   label = @Translation("Image"),
 *   description = @Translation("An image associated with this page, for use as a thumbnail in social networks and other services."),
 *   name = "image_src",
 *   group = "advanced",
 *   weight = 4,
 *   type = "image",
 *   secure = FALSE,
 *   multiple = FALSE
 * )
 */
class ImageSrc extends LinkRelBase {
  // Nothing here yet. Just a placeholder class for a plugin.
}
