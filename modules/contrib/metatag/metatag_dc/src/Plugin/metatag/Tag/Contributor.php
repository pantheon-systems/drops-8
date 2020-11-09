<?php

namespace Drupal\metatag_dc\Plugin\metatag\Tag;

use Drupal\metatag\Plugin\metatag\Tag\MetaNameBase;

/**
 * The Dublin Core "Contributor" meta tag.
 *
 * @MetatagTag(
 *   id = "dcterms_contributor",
 *   label = @Translation("Contributor"),
 *   description = @Translation("An entity responsible for making contributions to the resource. Examples of a Contributor include a person, an organization, or a service. Typically, the name of a Contributor should be used to indicate the entity."),
 *   name = "dcterms.contributor",
 *   group = "dublin_core",
 *   weight = 6,
 *   type = "label",
 *   secure = FALSE,
 *   multiple = FALSE
 * )
 */
class Contributor extends MetaNameBase {
  // Nothing here yet. Just a placeholder class for a plugin.
}
