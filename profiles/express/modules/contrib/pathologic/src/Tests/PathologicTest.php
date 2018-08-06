<?php

/**
 * @file
 * Definition of Drupal\pathologic\Tests\PathologicTest.
 */

namespace Drupal\pathologic\Tests;

use Drupal\Component\Utility\SafeMarkup;
use Drupal\simpletest\WebTestBase;
use Drupal\pathologic\Plugin\Filter\FilterPathologic;
use Drupal\Core\Language\Language;
use Drupal\Core\Url;

/**
 * Tests Pathologic functionality.
 *
 * @group filter
 */
class PathologicTest extends WebTestBase {

  public static $modules = array('filter', 'pathologic', 'pathologic_test');

  function testPathologic() {
    global $script_path;

    // Start by testing our function to build protocol-relative URLs
    $this->assertEqual(
      _pathologic_url_to_protocol_relative('http://example.com/foo/bar'),
      '//example.com/foo/bar',
      t('Protocol-relative URL creation with http:// URL')
    );
    $this->assertEqual(
      _pathologic_url_to_protocol_relative('https://example.org/baz'),
      '//example.org/baz',
      t('Protocol-relative URL creation with https:// URL')
    );

    // Build some paths to check against
    $test_paths = array(
      'foo' => array(
        'path' => 'foo',
        'opts' => array()
      ),
      'foo/bar' => array(
        'path' => 'foo/bar',
        'opts' => array()
      ),
      'foo/bar?baz' => array(
        'path' => 'foo/bar',
        'opts' => array('query' => array('baz' => NULL))
      ),
      'foo/bar?baz=qux' => array(
        'path' => 'foo/bar',
        'opts' => array('query' => array('baz' => 'qux'))
      ),
      'foo/bar#baz' => array(
        'path' => 'foo/bar',
        'opts' => array('fragment' => 'baz'),
      ),
      'foo/bar?baz=qux&amp;quux=quuux#quuuux' => array(
        'path' => 'foo/bar',
        'opts' => array(
          'query' => array('baz' => 'qux', 'quux' => 'quuux'),
          'fragment' => 'quuuux',
        ),
      ),
      'foo%20bar?baz=qux%26quux' => array(
        'path' => 'foo bar',
        'opts' => array(
          'query' => array('baz' => 'qux&quux'),
        ),
      ),
      '/' => array(
        'path' => '<front>',
        'opts' => array(),
      ),
    );

    foreach (array('full', 'proto-rel', 'path') as $protocol_style) {
      $format_id = _pathologic_build_format(['settings_source' => 'local', 'local_settings' => ['protocol_style' => $protocol_style]]);
      $paths = array();
      foreach ($test_paths as $path => $args) {
        $args['opts']['absolute'] = $protocol_style !== 'path';
        $paths[$path] = _pathologic_content_url($args['path'], $args['opts']);
        if ($protocol_style === 'proto-rel') {
          $paths[$path] = _pathologic_url_to_protocol_relative($paths[$path]);
        }
      }
      $t10ns = array(
        '@clean' => empty($script_path) ? t('Yes') : t('No'),
        '@ps' => $protocol_style,
      );

      $this->assertEqual(
        check_markup('<a href="foo"><img src="foo/bar" /></a>', $format_id),
        '<a href="' . $paths['foo'] . '"><img src="' . $paths['foo/bar'] . '" /></a>',
        t('Simple paths. Clean URLs: @clean; protocol style: @ps.', $t10ns)
      );
      $this->assertEqual(
        check_markup('<a href="index.php?q=foo"></a><a href="index.php?q=foo/bar&baz=qux"></a>', $format_id),
        '<a href="' . $paths['foo'] . '"></a><a href="' . $paths['foo/bar?baz=qux'] . '"></a>',
        t('D7 and earlier-style non-clean URLs. Clean URLs: @clean; protocol style: @ps.', $t10ns)
      );
      $this->assertEqual(
        check_markup('<a href="index.php/foo"></a><a href="index.php/foo/bar?baz=qux"></a>', $format_id),
        '<a href="' . $paths['foo'] . '"></a><a href="' . $paths['foo/bar?baz=qux'] . '"></a>',
        t('D8-style non-clean URLs. Clean URLs: @clean; protocol style: @ps.', $t10ns)
      );
      $this->assertEqual(
        check_markup('<form action="foo/bar?baz"><IMG LONGDESC="foo/bar?baz=qux" /></a>', $format_id),
        '<form action="' . $paths['foo/bar?baz'] . '"><IMG LONGDESC="' . $paths['foo/bar?baz=qux'] . '" /></a>',
        t('Paths with query string. Clean URLs: @clean; protocol style: @ps.', $t10ns)
      );
      $this->assertEqual(
        check_markup('<a href="foo/bar#baz">', $format_id),
        '<a href="' . $paths['foo/bar#baz'] . '">',
        t('Path with fragment. Clean URLs: @clean; protocol style: @ps.', $t10ns)
      );
      $this->assertEqual(
        check_markup('<a href="#foo">', $format_id),
        '<a href="#foo">',
        t('Fragment-only href. Clean URLs: @clean; protocol style: @ps.', $t10ns)
      );
      // @see https://drupal.org/node/2208223
      $this->assertEqual(
        check_markup('<a href="#">', $format_id),
        '<a href="#">',
        t('Hash-only href. Clean URLs: @clean; protocol style: @ps.', $t10ns)
      );
      $this->assertEqual(
        check_markup('<a href="foo/bar?baz=qux&amp;quux=quuux#quuuux">', $format_id),
        '<a href="' . $paths['foo/bar?baz=qux&amp;quux=quuux#quuuux'] . '">',
        t('Path with query string and fragment. Clean URLs: @clean; protocol style: @ps.', $t10ns)
      );
      $this->assertEqual(
        check_markup('<a href="foo%20bar?baz=qux%26quux">', $format_id),
        '<a href="' . $paths['foo%20bar?baz=qux%26quux'] . '">',
        t('Path with URL encoded parts. Clean URLs: @clean; protocol style: @ps.', $t10ns)
      );
      $this->assertEqual(
        check_markup('<a href="/"></a>', $format_id),
        '<a href="' . $paths['/'] . '"></a>',
        t('Path with just slash. Clean URLs: @clean; protocol style: @ps', $t10ns)
      );
    }

    global $base_path;
    $this->assertEqual(
      check_markup('<a href="' . $base_path . 'foo">bar</a>', $format_id),
      '<a href="' . _pathologic_content_url('foo', array('absolute' => FALSE)) .'">bar</a>',
      t('Paths beginning with $base_path (like WYSIWYG editors like to make)')
    );
    global $base_url;
    $this->assertEqual(
      check_markup('<a href="' . $base_url . '/foo">bar</a>', $format_id),
      '<a href="' . _pathologic_content_url('foo', array('absolute' => FALSE)) .'">bar</a>',
      t('Paths beginning with $base_url')
    );

    // @see http://drupal.org/node/1617944
    $this->assertEqual(
      check_markup('<a href="//example.com/foo">bar</a>', $format_id),
      '<a href="//example.com/foo">bar</a>',
      t('Off-site schemeless URLs (//example.com/foo) ignored')
    );

    // Test internal: and all base paths
    $format_id = _pathologic_build_format([
      'settings_source' => 'local',
      'local_settings' => [
        'local_paths' => "http://example.com/qux\nhttp://example.org\n/bananas",
        'protocol_style' => 'full',
      ],
    ]);

    // @see https://drupal.org/node/2030789
    $this->assertEqual(
      check_markup('<a href="//example.org/foo">bar</a>', $format_id),
      '<a href="' . _pathologic_content_url('foo', array('absolute' => TRUE)) . '">bar</a>',
      t('On-site schemeless URLs processed')
    );
    $this->assertEqual(
      check_markup('<a href="internal:foo">', $format_id),
      '<a href="' . _pathologic_content_url('foo', array('absolute' => TRUE)) . '">',
      t('Path Filter compatibility (internal:)')
    );
    $this->assertEqual(
      check_markup('<a href="files:image.jpeg">look</a>', $format_id),
      '<a href="' . _pathologic_content_url(file_create_url(file_default_scheme() . '://image.jpeg'), array('absolute' => TRUE)) . '">look</a>',
      t('Path Filter compatibility (files:)')
    );
    $this->assertEqual(
      check_markup('<a href="http://example.com/qux/foo"><img src="http://example.org/bar.jpeg" longdesc="/bananas/baz" /></a>', $format_id),
      '<a href="' . _pathologic_content_url('foo', array('absolute' => TRUE)) . '"><img src="' . _pathologic_content_url('bar.jpeg', array('absolute' => TRUE)) . '" longdesc="' . _pathologic_content_url('baz', array('absolute' => TRUE)) . '" /></a>',
      t('"All base paths for this site" functionality')
    );
    $this->assertEqual(
      check_markup('<a href="webcal:foo">bar</a>', $format_id),
      '<a href="webcal:foo">bar</a>',
      t('URLs with likely protocols are ignored')
    );
    // Test hook_pathologic_alter() implementation.
    $this->assertEqual(
      check_markup('<a href="foo?test=add_foo_qpart">', $format_id),
      '<a href="' . _pathologic_content_url('foo', array('absolute' => TRUE, 'query' => array('test' => 'add_foo_qpart', 'foo' => 'bar'))) . '">',
      t('hook_pathologic_alter(): Alter $url_params')
    );
    $this->assertEqual(
      check_markup('<a href="bar?test=use_original">', $format_id),
      '<a href="bar?test=use_original">',
      t('hook_pathologic_alter(): Passthrough with use_original option')
    );

    // Test paths to existing files when clean URLs are disabled.
    // @see http://drupal.org/node/1672430
    $script_path = '';
    $filtered_tag = check_markup('<img src="misc/druplicon.png" />', $format_id);
    $this->assertTrue(
      strpos($filtered_tag, 'q=') === FALSE,
      t('Paths to files don\'t have ?q= when clean URLs are off')
    );

    $format_id = _pathologic_build_format([
      'settings_source' => 'global',
      'local_settings' => [
        'protocol_style' => 'rel',
      ],
    ]);
    $this->config('pathologic.settings')
      ->set('protocol_style', 'proto-rel')
      ->set('local_paths', 'http://example.com/')
      ->save();
    $this->assertEqual(
      check_markup('<img src="http://example.com/foo.jpeg" />', $format_id),
      '<img src="' . _pathologic_url_to_protocol_relative(_pathologic_content_url('foo.jpeg', array('absolute' => TRUE))) . '" />',
      t('Use global settings when so configured on the format')
    );
  }

}

/**
 * Wrapper around url() which does HTML entity decoding and encoding.
 *
 * Since Pathologic works with paths in content, it needs to decode paths which
 * have been HTML-encoded, and re-encode them when done. This is a wrapper
 * around url() which does the same thing so that we can expect the results
 * from it and from Pathologic to still match in our tests.
 *
 * @see url()
 * @see http://drupal.org/node/1672932
 * @see http://www.w3.org/TR/xhtml1/guidelines.html#C_12
 */
function _pathologic_content_url($path, $options) {
  // If we should pretend this is a path to a file, make url() behave like clean
  // URLs are enabled.
  // @see _pathologic_replace()
  // @see http://drupal.org/node/1672430
  if (!empty($options['is_file'])) {
    $options['script_path'] = '';
  }

  if (parse_url($path, PHP_URL_SCHEME) === NULL) {
    if ($path == '<front>') {
      return SafeMarkup::checkPlain(Url::fromRoute('<front>', [], $options)->toString());
    }
    $path = 'base://' . $path;
  }
  return SafeMarkup::checkPlain(Url::fromUri(htmlspecialchars_decode($path), $options)->toString());
}


/**
 * Build a text format with Pathologic configured a certain way.
 *
 * @param $settings
 *   An array of settings for the Pathologic instance on the format.
 * @return
 *   A format machine name (consisting of random characters) for the format.
 */
function _pathologic_build_format($settings) {
  $format_id = user_password();
  $format = entity_create('filter_format', [
    'format' => $format_id,
    'name' => $format_id,
  ]);
  $format->setFilterConfig('filter_pathologic', [
    'status' => 1,
    'settings' => $settings,
  ]);
  $format->save();
  return $format_id;
}
