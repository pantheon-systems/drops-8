<?php

namespace Drupal\metatag_dc_advanced\Plugin\metatag\Tag;

use Drupal\metatag\Plugin\metatag\Tag\MetaNameBase;

/**
 * The Dublin Core "accrualMethod" meta tag.
 *
 * @MetatagTag(
 *   id = "dcterms_accrual_method",
 *   label = @Translation("Accrual Method"),
 *   description = @Translation("The method by which items are added to a collection."),
 *   name = "dcterms.accrualMethod",
 *   group = "dublin_core_advanced",
 *   weight = 6,
 *   type = "label",
 *   secure = FALSE,
 *   multiple = FALSE
 * )
 */
class AccrualMethod extends MetaNameBase {
  // Nothing here yet. Just a placeholder class for a plugin.
}
