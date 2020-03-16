<?php

namespace Drupal\metatag_dc\Plugin\metatag\Tag;

use Drupal\metatag\Plugin\metatag\Tag\MetaNameBase;

/**
 * The Dublin Core "Date" meta tag.
 *
 * @MetatagTag(
 *   id = "dcterms_date",
 *   label = @Translation("Date"),
 *   description = @Translation("A point or period of time associated with an event in the lifecycle of the resource. Date may be used to express temporal information at any level of granularity.  Recommended best practice is to use an encoding scheme, such as the W3CDTF profile of ISO 8601 [W3CDTF]."),
 *   name = "dcterms.date",
 *   group = "dublin_core",
 *   weight = 7,
 *   type = "label",
 *   secure = FALSE,
 *   multiple = FALSE
 * )
 */
class Date extends MetaNameBase {
  // Nothing here yet. Just a placeholder class for a plugin.
}
