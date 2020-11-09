<?php

namespace Drupal\metatag_dc_advanced\Plugin\metatag\Tag;

use Drupal\metatag\Plugin\metatag\Tag\MetaNameBase;

/**
 * The Dublin Core "dateCopyrighted" meta tag.
 *
 * @MetatagTag(
 *   id = "dcterms_date_copyrighted",
 *   label = @Translation("Date Copyrighted"),
 *   description = @Translation("Date of copyright."),
 *   name = "dcterms.dateCopyrighted",
 *   group = "dublin_core_advanced",
 *   weight = 6,
 *   type = "label",
 *   secure = FALSE,
 *   multiple = FALSE
 * )
 */
class DateCopyrighted extends MetaNameBase {
  // Nothing here yet. Just a placeholder class for a plugin.
}
