<?php

namespace Drupal\metatag_dc_advanced\Plugin\metatag\Tag;

use Drupal\metatag\Plugin\metatag\Tag\MetaNameBase;

/**
 * The Dublin Core "accrualPolicy" meta tag.
 *
 * @MetatagTag(
 *   id = "dcterms_accrual_policy",
 *   label = @Translation("Accrual Policy"),
 *   description = @Translation("The policy governing the addition of items to a collection."),
 *   name = "dcterms.accrualPolicy",
 *   group = "dublin_core_advanced",
 *   weight = 6,
 *   type = "label",
 *   secure = FALSE,
 *   multiple = FALSE
 * )
 */
class AccrualPolicy extends MetaNameBase {
  // Nothing here yet. Just a placeholder class for a plugin.
}
