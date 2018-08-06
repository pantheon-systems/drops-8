<?php

namespace Drupal\webform_bootstrap_test_theme\Plugin\Preprocess;

use Drupal\bootstrap\Utility\Variables;
use Drupal\bootstrap\Plugin\Preprocess\PreprocessBase;
use Drupal\bootstrap\Plugin\Preprocess\PreprocessInterface;

if (class_exists('\Drupal\bootstrap\Plugin\Preprocess\PreprocessBase')) {
  /**
   * Pre-processes variables for the "region" theme hook.
   *
   * @ingroup plugins_preprocess
   *
   * @BootstrapPreprocess("region")
   */
  class Region extends PreprocessBase implements PreprocessInterface {

    /**
     * {@inheritdoc}
     */
    public function preprocessVariables(Variables $variables) {
      $region = $variables['elements']['#region'];
      $variables['region'] = $region;
      $variables['content'] = $variables['elements']['#children'];

      // Support for "well" classes in regions.
      static $region_wells;
      if (!isset($region_wells)) {
        $region_wells = $this->theme->getSetting('region_wells');
      }
      if (!empty($region_wells[$region])) {
        $variables->addClass($region_wells[$region]);
      }
    }

  }

}
else {
  class Region {}
}
