<?php

namespace Drupal\metatag_dc_advanced\Plugin\metatag\Tag;

use Drupal\metatag\Plugin\metatag\Tag\MetaNameBase;

/**
 * The Dublin Core "accessRights" meta tag.
 *
 * @MetatagTag(
 *   id = "dcterms_access_rights",
 *   label = @Translation("Access Rights"),
 *   description = @Translation("Information about who can access the resource or an indication of its security status. Access Rights may include information regarding access or restrictions based on privacy, security, or other policies."),
 *   name = "dcterms.accessRights",
 *   group = "dublin_core_advanced",
 *   weight = 6,
 *   type = "label",
 *   secure = FALSE,
 *   multiple = FALSE
 * )
 */
class AccessRights extends MetaNameBase {
  // Nothing here yet. Just a placeholder class for a plugin.
}
