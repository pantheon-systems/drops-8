<?php

namespace Drupal\metatag_dc\Plugin\metatag\Tag;

use Drupal\metatag\Plugin\metatag\Tag\MetaNameBase;

/**
 * The Dublin Core "Rights" meta tag.
 *
 * @MetatagTag(
 *   id = "dcterms_rights",
 *   label = @Translation("Rights"),
 *   description = @Translation("Information about rights held in and over the resource. Typically, rights information includes a statement about various property rights associated with the resource, including intellectual property rights."),
 *   name = "dcterms.rights",
 *   group = "dublin_core",
 *   weight = 15,
 *   type = "label",
 *   secure = FALSE,
 *   multiple = FALSE
 * )
 */
class Rights extends MetaNameBase {
  // Nothing here yet. Just a placeholder class for a plugin.
}
