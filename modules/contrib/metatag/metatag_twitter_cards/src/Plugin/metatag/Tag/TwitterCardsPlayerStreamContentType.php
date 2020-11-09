<?php

namespace Drupal\metatag_twitter_cards\Plugin\metatag\Tag;

use Drupal\metatag\Plugin\metatag\Tag\MetaNameBase;

/**
 * The Twitter Cards player stream content-type metatag.
 *
 * @MetatagTag(
 *   id = "twitter_cards_player_stream_content_type",
 *   label = @Translation("MP4 media stream MIME-type"),
 *   description = @Translation("The MIME type for the media contained in the stream URL, as defined by <a href=':url'>RFC 4337</a>.", arguments = { ":url" = "https://tools.ietf.org/rfc/rfc4337.txt" }),
 *   name = "twitter:player:stream:content_type",
 *   group = "twitter_cards",
 *   weight = 404,
 *   type = "string",
 *   secure = FALSE,
 *   multiple = FALSE
 * )
 */
class TwitterCardsPlayerStreamContentType extends MetaNameBase {
}
