<?php

namespace Drupal\metatag_dc_advanced\Plugin\metatag\Tag;

use Drupal\metatag\Plugin\metatag\Tag\MetaNameBase;

/**
 * The Dublin Core "dateSubmitted" meta tag.
 *
 * @MetatagTag(
 *   id = "dcterms_date_submitted",
 *   label = @Translation("Date Submitted"),
 *   description = @Translation("Date of submission of the resource. Examples of resources to which a Date Submitted may be relevant are a thesis (submitted to a university department) or an article (submitted to a journal)."),
 *   name = "dcterms.dateSubmitted",
 *   group = "dublin_core_advanced",
 *   weight = 6,
 *   type = "label",
 *   secure = FALSE,
 *   multiple = FALSE
 * )
 */
class DateSubmitted extends MetaNameBase {
  // Nothing here yet. Just a placeholder class for a plugin.
}
