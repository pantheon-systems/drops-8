<?php
/**
 * @file
 * Contains \Drupal\bootstrap\Plugin\Alter\LibraryInfo.
 */

namespace Drupal\bootstrap\Plugin\Alter;

use Drupal\bootstrap\Annotation\BootstrapAlter;
use Drupal\bootstrap\Bootstrap;
use Drupal\bootstrap\Plugin\PluginBase;
use Drupal\Component\Utility\NestedArray;

/**
 * Implements hook_library_info_alter().
 *
 * @ingroup plugins_alter
 *
 * @BootstrapAlter("library_info")
 */
class LibraryInfo extends PluginBase implements AlterInterface {

  /**
   * {@inheritdoc}
   */
  public function alter(&$libraries, &$extension = NULL, &$context2 = NULL) {
    $livereload = $this->theme->livereloadUrl();

    // Disable preprocess on all CSS/JS if "livereload" is enabled.
    if ($livereload) {
      $this->processLibrary($libraries, function (&$info, &$key, $type) {
        if ($type === 'css' || $type === 'js') {
          $info['preprocess'] = FALSE;
        }
      });
    }

    if ($extension === 'bootstrap') {
      // Alter the "livereload.js" placeholder with the correct URL.
      if ($livereload) {
        $libraries['livereload']['js'][$livereload] = $libraries['livereload']['js']['livereload.js'];
        unset($libraries['livereload']['js']['livereload.js']);
      }

      // Retrieve the theme's CDN provider and assets.
      $provider = $this->theme->getProvider();
      $assets = $provider ? $provider->getAssets() : [];

      // Immediately return if there is no provider or assets.
      if (!$provider || !$assets) {
        return;
      }

      // Merge the assets into the library info.
      $libraries['theme'] = NestedArray::mergeDeepArray([$assets, $libraries['theme']], TRUE);

      // Add a specific version and theme CSS overrides file.
      // @todo This should be retrieved by the Provider API.
      $version = $this->theme->getSetting('cdn_' . $provider->getPluginId() . '_version') ?: Bootstrap::FRAMEWORK_VERSION;
      $libraries['theme']['version'] = $version;
      $provider_theme = $this->theme->getSetting('cdn_' . $provider->getPluginId() . '_theme') ?: 'bootstrap';
      $provider_theme = $provider_theme === 'bootstrap' || $provider_theme === 'bootstrap_theme' ? '' : "-$provider_theme";

      foreach ($this->theme->getAncestry(TRUE) as $ancestor) {
        $overrides = $ancestor->getPath() . "/css/$version/overrides$provider_theme.min.css";
        if (file_exists($overrides)) {
          // Since this uses a relative path to the ancestor from DRUPAL_ROOT,
          // we must prepend the entire path with forward slash (/) so it
          // doesn't prepend the active theme's path.
          $overrides = "/$overrides";

          // The overrides file must also be stored in the "base" category so
          // it isn't added after any potential sub-theme's "theme" category.
          // There's no weight, so it will be added after the provider's assets.
          // @see https://www.drupal.org/node/2770613
          $libraries['theme']['css']['base'][$overrides] = [];
          break;
        }
      }
    }
    // Core replacements.
    elseif ($extension === 'core') {
      // Replace core dialog/jQuery UI implementations with Bootstrap Modals.
      if ($this->theme->getSetting('modal_enabled')) {
        $libraries['drupal.dialog']['override'] = 'bootstrap/drupal.dialog';
        $libraries['drupal.dialog.ajax']['override'] = 'bootstrap/drupal.dialog.ajax';
      }
    }
  }

  /**
   * Processes library definitions.
   *
   * @param array $libraries
   *   The libraries array, passed by reference.
   * @param callable $callback
   *   The callback to perform processing on the library.
   */
  public function processLibrary(&$libraries, callable $callback) {
    foreach ($libraries as &$library) {
      foreach ($library as $type => $definition) {
        if (is_array($definition)) {
          $modified = [];
          // CSS needs special handling since it contains grouping.
          if ($type === 'css') {
            foreach ($definition as $group => $files) {
              foreach ($files as $key => $info) {
                call_user_func_array($callback, [&$info, &$key, $type]);
                $modified[$group][$key] = $info;
              }
            }
          }
          else {
            foreach ($definition as $key => $info) {
              call_user_func_array($callback, [&$info, &$key, $type]);
              $modified[$key] = $info;
            }
          }
          $library[$type] = $modified;
        }
      }
    }
  }

}
