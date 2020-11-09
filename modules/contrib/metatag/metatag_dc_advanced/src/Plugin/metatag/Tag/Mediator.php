<?php

namespace Drupal\metatag_dc_advanced\Plugin\metatag\Tag;

use Drupal\metatag\Plugin\metatag\Tag\MetaNameBase;

/**
 * The Dublin Core "mediator" meta tag.
 *
 * @MetatagTag(
 *   id = "dcterms_mediator",
 *   label = @Translation("Mediator"),
 *   description = @Translation("An entity that mediates access to the resource and for whom the resource is intended or useful. In an educational context, a mediator might be a parent, teacher, teaching assistant, or care-giver."),
 *   name = "dcterms.mediator",
 *   group = "dublin_core_advanced",
 *   weight = 6,
 *   type = "label",
 *   secure = FALSE,
 *   multiple = FALSE
 * )
 */
class Mediator extends MetaNameBase {
  // Nothing here yet. Just a placeholder class for a plugin.
}
