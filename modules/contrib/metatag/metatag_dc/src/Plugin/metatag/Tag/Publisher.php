<?php

namespace Drupal\metatag_dc\Plugin\metatag\Tag;

use Drupal\metatag\Plugin\metatag\Tag\MetaNameBase;

/**
 * The Dublin Core "Publisher" meta tag.
 *
 * @MetatagTag(
 *   id = "dcterms_publisher",
 *   label = @Translation("Publisher"),
 *   description = @Translation("An entity responsible for making the resource available. Examples of a Publisher include a person, an organization, or a service. Typically, the name of a Publisher should be used to indicate the entity."),
 *   name = "dcterms.publisher",
 *   group = "dublin_core",
 *   weight = 5,
 *   type = "label",
 *   secure = FALSE,
 *   multiple = FALSE
 * )
 */
class Publisher extends MetaNameBase {
  // Nothing here yet. Just a placeholder class for a plugin.
}
