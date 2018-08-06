<?php

namespace Drupal\libraries_ui;


/**
 * Class LibrariesUiService.
 *
 * @package Drupal\libraries_ui
 */
class LibrariesUiService {

public function getAllLibraries(){
    $modules = \Drupal::service('module_handler')->getModuleList();
    $themes = \Drupal::service('theme_handler')->rebuildThemeData();
    $libraryDiscovery = \Drupal::service('library.discovery');
    $extensions = array_merge($modules, $themes);
    $root = \Drupal::root();
    foreach ($extensions as $extension_name => $extension) {
        $library_file = $extension->getPath() . '/' . $extension_name . '.libraries.yml';
        if (is_file($root . '/' . $library_file)) {
            $libraries[$extension_name] = $libraryDiscovery->getLibrariesByExtension($extension_name);
        }
    }
    return $libraries;
 }
}
