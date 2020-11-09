<?php

namespace Drupal\metatag\Plugin\GraphQL\Scalars;

use Drupal\graphql\Plugin\GraphQL\Scalars\Internal\StringScalar;

/**
 * Metatag module dummy type.
 *
 * Metatag module defines a custom data type that essentially is a string, but
 * not called "string", which causes the GraphQL type system chokes.
 *
 * @GraphQLScalar(
 *   id = "metatag",
 *   name = "metatag",
 *   type = "string"
 * )
 */
class MetatagScalar extends StringScalar {
}
