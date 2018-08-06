<?php

namespace Drupal\webform\Utility;

use Drupal\Core\Serialization\Yaml;
use Symfony\Component\Yaml\Unescaper;

/**
 * Provides YAML tidy function.
 */
class WebformYaml {

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
    static $unescaper;
    if (!isset($unescaper)) {
      $unescaper = new Unescaper();
    }

    // Remove return after array delimiter.
    $yaml = preg_replace('#(\n[ ]+-)\n[ ]+#', '\1 ', $yaml);

    // Support YAML newlines preserved syntax via pipe (|).
    $lines = explode(PHP_EOL, $yaml);
    foreach ($lines as $index => $line) {
      if (empty($line) || strpos($line, '\n') === FALSE) {
        continue;
      }

      if (preg_match('/^([ ]*(?:- )?)([a-z_]+|\'[^\']+\'|"[^"]+"): (\'|")(.+)\3$/', $line, $match)) {
        $prefix = $match[1];
        $indent = str_repeat(' ', strlen($prefix));
        $name = $match[2];
        $quote = $match[3];
        $value = $match[4];

        if ($quote == "'") {
          $value = rtrim($unescaper->unescapeSingleQuotedString($value));
        }
        else {
          $value = rtrim($unescaper->unescapeDoubleQuotedString($value));
        }

        if (strpos($value, '<') === FALSE) {
          $lines[$index] = $prefix . $name . ": |\n$indent  " . str_replace(PHP_EOL, "\n$indent  ", $value);
        }
        else {
          $value = preg_replace('~\R~u', PHP_EOL, $value);
          $value = preg_replace('#\s*</p>#', '</p>', $value);
          $value = str_replace(PHP_EOL, "\n$indent  ", $value);
          $lines[$index] = $prefix . $name . ": |\n$indent  " . $value;
        }
      }
    }
    $yaml = implode(PHP_EOL, $lines);
    return trim($yaml);
  }

}
