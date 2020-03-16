<?php

namespace Drupal\metatag_dc_advanced\Plugin\metatag\Tag;

use Drupal\metatag\Plugin\metatag\Tag\MetaNameBase;

/**
 * The Dublin Core "instructionalMethod" meta tag.
 *
 * @MetatagTag(
 *   id = "dcterms_instructional_method",
 *   label = @Translation("Instructional Method"),
 *   description = @Translation("A process, used to engender knowledge, attitudes and skills, that the described resource is designed to support. Instructional Method will typically include ways of presenting instructional materials or conducting instructional activities, patterns of learner-to-learner and learner-to-instructor interactions, and mechanisms by which group and individual levels of learning are measured. Instructional methods include all aspects of the instruction and learning processes from planning and implementation through evaluation and feedback."),
 *   name = "dcterms.instructionalMethod",
 *   group = "dublin_core_advanced",
 *   weight = 6,
 *   type = "label",
 *   secure = FALSE,
 *   multiple = FALSE
 * )
 */
class InstructionalMethod extends MetaNameBase {
  // Nothing here yet. Just a placeholder class for a plugin.
}
