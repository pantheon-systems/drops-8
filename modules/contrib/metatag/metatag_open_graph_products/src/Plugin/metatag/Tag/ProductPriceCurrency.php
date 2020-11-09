<?php

namespace Drupal\metatag_open_graph_products\Plugin\metatag\Tag;

use Drupal\metatag\Plugin\metatag\Tag\MetaPropertyBase;

/**
 * Provides a plugin for the 'product:price:currency' meta tag.
 *
 * @MetatagTag(
 *   id = "product_price_currency",
 *   label = @Translation("Product price currency"),
 *   description = @Translation("The price currency of the product."),
 *   name = "product:price:currency",
 *   group = "open_graph_products",
 *   weight = 2,
 *   type = "string",
 *   secure = FALSE,
 *   multiple = FALSE
 * )
 */
class ProductPriceCurrency extends MetaPropertyBase {
  // Nothing here yet. Just a placeholder class for a plugin.
}
