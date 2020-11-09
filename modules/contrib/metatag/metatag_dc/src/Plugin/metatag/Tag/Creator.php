<?php

namespace Drupal\metatag_dc\Plugin\metatag\Tag;

use Drupal\metatag\Plugin\metatag\Tag\MetaNameBase;

/**
 * The Dublin Core "Creator" meta tag.
 *
 * @MetatagTag(
 *   id = "dcterms_creator",
 *   label = @Translation("Creator"),
 *   description = @Translation("An entity primarily responsible for making the resource. Examples of a Creator include a person, an organization, or a service. Typically, the name of a Creator should be used to indicate the entity."),
 *   name = "dcterms.creator",
 *   group = "dublin_core",
 *   weight = 2,
 *   type = "label",
 *   secure = FALSE,
 *   multiple = FALSE
 * )
 */
class Creator extends MetaNameBase {
  // Nothing here yet. Just a placeholder class for a plugin.
}
