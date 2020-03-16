<?php

namespace Drupal\metatag_dc_advanced\Plugin\metatag\Tag;

use Drupal\metatag\Plugin\metatag\Tag\MetaNameBase;

/**
 * The Dublin Core "dateAccepted" meta tag.
 *
 * @MetatagTag(
 *   id = "dcterms_date_accepted",
 *   label = @Translation("Date Accepted"),
 *   description = @Translation("Date of acceptance of the resource. Examples of resources to which a Date Accepted may be relevant are a thesis (accepted by a university department) or an article (accepted by a journal)."),
 *   name = "dcterms.dateAccepted",
 *   group = "dublin_core_advanced",
 *   weight = 6,
 *   type = "label",
 *   secure = FALSE,
 *   multiple = FALSE
 * )
 */
class DateAccepted extends MetaNameBase {
  // Nothing here yet. Just a placeholder class for a plugin.
}
