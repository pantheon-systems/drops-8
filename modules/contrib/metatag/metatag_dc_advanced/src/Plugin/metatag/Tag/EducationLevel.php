<?php

namespace Drupal\metatag_dc_advanced\Plugin\metatag\Tag;

use Drupal\metatag\Plugin\metatag\Tag\MetaNameBase;

/**
 * The Dublin Core "educationLevel" meta tag.
 *
 * @MetatagTag(
 *   id = "dcterms_education_level",
 *   label = @Translation("Audience Education Level"),
 *   description = @Translation("A class of entity, defined in terms of progression through an educational or training context, for which the described resource is intended."),
 *   name = "dcterms.educationLevel",
 *   group = "dublin_core_advanced",
 *   weight = 6,
 *   type = "label",
 *   secure = FALSE,
 *   multiple = FALSE
 * )
 */
class EducationLevel extends MetaNameBase {
  // Nothing here yet. Just a placeholder class for a plugin.
}
