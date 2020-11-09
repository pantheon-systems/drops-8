<?php

namespace Drupal\metatag\Normalizer;

use Drupal\serialization\Normalizer\NormalizerBase;

/**
 * Normalizes metatag into the viewed entity.
 */
class MetatagNormalizer extends NormalizerBase {

  /**
   * {@inheritdoc}
   */
  protected $supportedInterfaceOrClass = 'Drupal\metatag\Plugin\Field\MetatagEntityFieldItemList';

  /**
   * {@inheritdoc}
   */
  public function normalize($field_item, $format = NULL, array $context = []) {
    // @see metatag_get_tags_from_route()
    $entity = $field_item->getEntity();

    $tags = metatag_get_tags_from_route($entity);

    $normalized['value'] = [];
    if (isset($tags['#attached']['html_head'])) {
      foreach ($tags['#attached']['html_head'] as $tag) {
        // @todo Work out a proper, long-term fix for this.
        if (isset($tag[0]['#attributes']['content'])) {
          $normalized['value'][$tag[1]] = $tag[0]['#attributes']['content'];
        }
        elseif (isset($tag[0]['#attributes']['href'])) {
          $normalized['value'][$tag[1]] = $tag[0]['#attributes']['href'];
        }
      }
    }

    if (isset($context['langcode'])) {
      $normalized['lang'] = $context['langcode'];
    }

    return $normalized;
  }

  /**
   * {@inheritdoc}
   */
  public function supportsDenormalization($data, $type, $format = NULL) {
    return FALSE;
  }

}
