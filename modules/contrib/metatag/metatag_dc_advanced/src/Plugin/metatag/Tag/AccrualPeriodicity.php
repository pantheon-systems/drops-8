<?php

namespace Drupal\metatag_dc_advanced\Plugin\metatag\Tag;

use Drupal\metatag\Plugin\metatag\Tag\MetaNameBase;

/**
 * The Dublin Core "accrualPeriodicity" meta tag.
 *
 * @MetatagTag(
 *   id = "dcterms_accrual_periodicity",
 *   label = @Translation("Accrual Periodicity"),
 *   description = @Translation("The frequency with which items are added to a collection."),
 *   name = "dcterms.accrualPeriodicity",
 *   group = "dublin_core_advanced",
 *   weight = 6,
 *   type = "label",
 *   secure = FALSE,
 *   multiple = FALSE
 * )
 */
class AccrualPeriodicity extends MetaNameBase {
  // Nothing here yet. Just a placeholder class for a plugin.
}
