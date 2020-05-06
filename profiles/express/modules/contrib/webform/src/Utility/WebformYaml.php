<?php

namespace Drupal\webform\Utility;

use Drupal\Component\Serialization\SerializationInterface;
use Drupal\Core\Serialization\Yaml;
use Symfony\Component\Yaml\Dumper;
use Symfony\Component\Yaml\Yaml as SymfonyYaml;

/**
 * Provides YAML tidy function.
 */
class WebformYaml implements SerializationInterface {

  /**
   * {@inheritdoc}
   */
  public static function encode($data) {
    // Convert \r\n to \n so that multiline strings are properly formatted.
    // @see \Symfony\Component\Yaml\Dumper::dump
    static::normalize($data);

    $dumper = new Dumper(2);
    $yaml = $dumper->dump($data, PHP_INT_MAX, 0, SymfonyYaml::DUMP_EXCEPTION_ON_INVALID_TYPE | SymfonyYaml::DUMP_MULTI_LINE_LITERAL_BLOCK);

    // Remove return after array delimiter.
    $yaml = preg_replace('#((?:\n|^)[ ]*-)\n[ ]+(\w|[\'"])#', '\1 \2', $yaml);

    return trim($yaml);
  }

  /**
   * {@inheritdoc}
   */
  public static function decode($raw) {
    return Yaml::decode($raw);
  }

  /**
   * {@inheritdoc}
   */
  public static function getFileExtension() {
    return 'yml';
  }

  /**
   * Determine if string is valid YAML.
   *
   * @param string $yaml
   *   A YAML string.
   *
   * @return bool
   *   TRUE if string is valid YAML.
   */
  public static function isValid($yaml) {
    return self::validate($yaml) ? FALSE : TRUE;
  }

  /**
   * Validate YAML string.
   *
   * @param string $yaml
   *   A YAML string.
   *
   * @return null|string
   *   NULL if the YAML string contains no errors, else the parsing exception
   *   message is returned.
   */
  public static function validate($yaml) {
    try {
      Yaml::decode($yaml);
      return NULL;
    }
    catch (\Exception $exception) {
      return $exception->getMessage();
    }
  }

  /**
   * Tidy export YAML includes tweaking array layout and multiline strings.
   *
   * @param string $yaml
   *   The output generated from \Drupal\Core\Serialization\Yaml::encode.
   *
   * @return string
   *   The encoded data.
   */
  public static function tidy($yaml) {
    return self::encode(self::decode($yaml));
  }

  /****************************************************************************/
  // Helper methods.
  /****************************************************************************/

  /**
   * Convert \r\n to \n inside data.
   *
   * @param array $data
   *   Data with all converted \r\n to \n.
   */
  protected static function normalize(array &$data) {
    foreach ($data as $key => &$value) {
      if (is_string($value)) {
        $data[$key] = preg_replace('/\r\n?/', "\n", $value);
      }
      elseif (is_array($value)) {
        static::normalize($value);
      }
    }
  }

}
