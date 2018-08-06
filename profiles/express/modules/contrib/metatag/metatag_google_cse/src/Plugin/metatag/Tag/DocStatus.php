<?php

namespace Drupal\metatag_google_cse\Plugin\metatag\Tag;

use \Drupal\metatag\Plugin\metatag\Tag\MetaNameBase;

/**
 * Provides a plugin for the 'doc_status' meta tag.
 *
 * @MetatagTag(
 *   id = "doc_status",
 *   label = @Translation("Document status"),
 *   description = @Translation("The document status, e.g. ""draft""."),
 *   name = "doc_status",
 *   group = "google_cse",
 *   weight = 0,
 *   type = "string",
 *   secure = FALSE,
 *   multiple = FALSE
 * )
 */
class DocStatus extends MetaNameBase {
  // Nothing here yet. Just a placeholder class for a plugin.
}
