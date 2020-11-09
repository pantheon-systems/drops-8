<?php

namespace Drupal\metatag_open_graph_products\Plugin\metatag\Tag;

use Drupal\metatag\Plugin\metatag\Tag\MetaPropertyBase;

/**
 * Provides a plugin for the 'product:price:amount' meta tag.
 *
 * @MetatagTag(
 *   id = "product_price_amount",
 *   label = @Translation("Product price amount"),
 *   description = @Translation("The price amount of the product."),
 *   name = "product:price:amount",
 *   group = "open_graph_products",
 *   weight = 1,
 *   type = "string",
 *   secure = FALSE,
 *   multiple = FALSE
 * )
 */
class ProductPriceAmount extends MetaPropertyBase {
  // Nothing here yet. Just a placeholder class for a plugin.
}
