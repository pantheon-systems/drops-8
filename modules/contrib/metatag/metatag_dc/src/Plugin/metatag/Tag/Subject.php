<?php

namespace Drupal\metatag_dc\Plugin\metatag\Tag;

use Drupal\metatag\Plugin\metatag\Tag\MetaNameBase;

/**
 * The Dublin Core "Subject" meta tag.
 *
 * @MetatagTag(
 *   id = "dcterms_subject",
 *   label = @Translation("Subject"),
 *   description = @Translation("The topic of the resource. Typically, the subject will be represented using keywords, key phrases, or classification codes. Recommended best practice is to use a controlled vocabulary. To describe the spatial or temporal topic of the resource, use the Coverage element."),
 *   name = "dcterms.subject",
 *   group = "dublin_core",
 *   weight = 3,
 *   type = "label",
 *   secure = FALSE,
 *   multiple = FALSE
 * )
 */
class Subject extends MetaNameBase {
  // Nothing here yet. Just a placeholder class for a plugin.
}
