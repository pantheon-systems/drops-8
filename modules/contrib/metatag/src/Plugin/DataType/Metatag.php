<?php

namespace Drupal\metatag\Plugin\DataType;

use Drupal\Core\TypedData\Plugin\DataType\StringData;

/**
 * The metatag data type.
 *
 * The plain value of a metatag is a serialized object represented as a string.
 *
 * @DataType(
 *  id = "metatag",
 *  label = @Translation("Metatag")
 * )
 */
class Metatag extends StringData implements MetatagInterface {

}
