<?php

namespace Drupal\metatag_verification\Plugin\metatag\Tag;

use Drupal\metatag\Plugin\metatag\Tag\MetaNameBase;

/**
 * Provides a plugin for the 'zoom-domain-verification' meta tag.
 *
 * @MetatagTag(
 *   id = "zoom_domain_verification",
 *   label = @Translation("Zoom"),
 *   description = @Translation("A string provided by <a href=':zoom'>Zoom</a>, full details are available from the <a href=':help'>Zoom online help</a>.", arguments = { ":zoom" = "https://zoom.us/", ":help" = "https://support.zoom.us/hc/en-us/articles/203395207-What-is-Managed-Domain" }),
 *   name = "zoom-domain-verification",
 *   group = "site_verification",
 *   weight = 9,
 *   type = "label",
 *   secure = FALSE,
 *   multiple = FALSE
 * )
 */
class ZoomDomainVerification extends MetaNameBase {
}
