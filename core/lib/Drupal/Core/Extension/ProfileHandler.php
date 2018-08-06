<?php

namespace Drupal\Core\Extension;

/**
 * Class that manages profiles in a Drupal installation.
 */
class ProfileHandler implements ProfileHandlerInterface {

  /**
   * Stores profiles with their parents for caching purposes.
   *
   * This cache stores the profile extensions keyed by base profile.
   *
   * @var \Drupal\Core\Extension\Extension[][]
   */
  protected $profilesWithParentsCache = [];

  /**
   * Cache for processing info files.
   *
   * @var array
   */
  protected $infoCache = [];

  /**
   * Whether we have primed the filename cache.
   *
   * @var bool
   */
  protected $scanCache = FALSE;

  /**
   * The app root.
   *
   * @var string
   */
  protected $root;

  /**
   * The info parser to parse the profile info.yml files.
   *
   * @var \Drupal\Core\Extension\InfoParserInterface
   */
  protected $infoParser;

  /**
   * An extension discovery instance.
   *
   * @var \Drupal\Core\Extension\ExtensionDiscovery
   */
  protected $extensionDiscovery;

  /**
   * Local variable used to set profile weights
   *
   * @var int
   */
  protected $weight;

  /**
   * Constructs a new ProfileHandler.
   *
   * @param string $root
   *   The app root.
   * @param \Drupal\Core\Extension\InfoParserInterface $info_parser
   *   The info parser to parse the profile.info.yml files.
   * @param \Drupal\Core\Extension\ExtensionDiscovery $extension_discovery
   *   (optional) A extension discovery instance (for unit tests).
   */
  public function __construct($root, InfoParserInterface $info_parser, ExtensionDiscovery $extension_discovery = NULL) {
    $this->root = $root;
    $this->infoParser = $info_parser;
    $this->extensionDiscovery = $extension_discovery ?: new ExtensionDiscovery($root, TRUE, NULL, NULL, $this);
  }

  /**
   * Return the full path to a profile.
   *
   * Wrapper around drupal_get_path. If profile path is not available yet we
   * call scan('profile') and prime the cache.
   *
   * @param string $profile
   *   The name of the profile.
   *
   * @return string
   *   The full path to the profile.
   */
  protected function getProfilePath($profile) {
    // Check to see if system_rebuild_module_data cache is primed.
    // @todo Remove as part of https://www.drupal.org/node/2186491.
    $modules_cache = &drupal_static('system_rebuild_module_data');
    if (!$this->scanCache && !isset($modules_cache)) {
      // Find installation profiles. This needs to happen before performing a
      // module scan as the module scan requires knowing what the active profile
      // is.
      // @todo Remove as part of https://www.drupal.org/node/2186491.
      $profiles = $this->extensionDiscovery->scan('profile');
      foreach ($profiles as $profile_name => $extension) {
        // Prime the drupal_get_filename() static cache with the profile info
        // file location so we can use drupal_get_path() on the active profile
        // during the module scan.
        // @todo Remove as part of https://www.drupal.org/node/2186491.
        drupal_get_filename('profile', $profile_name, $extension->getPathname());
      }
      $this->scanCache = TRUE;
    }
    return drupal_get_path('profile', $profile);
  }

  /**
   * {@inheritdoc}
   */
  public function getProfileInfo($profile) {
    // Even though info_parser caches the info array, we need to also cache
    // this since it is recursive.
    if (!isset($this->infoCache[$profile])) {
      // Set defaults for profile info.
      $defaults = [
        'dependencies' => [],
        'themes' => ['stark'],
        'description' => '',
        'version' => NULL,
        'hidden' => FALSE,
        'php' => DRUPAL_MINIMUM_PHP,
        'base profile' => [
          'name' => '',
          'excluded_dependencies' => [],
          'excluded_themes' => [],
        ],
      ];

      $profile_path = $this->getProfilePath($profile);
      $profile_file = $profile_path . "/$profile.info.yml";
      $info = $this->infoParser->parse($profile_file) + $defaults;

      // Normalize any base profile info.
      if (is_string($info['base profile'])) {
        $info['base profile'] = [
          'name' => $info['base profile'],
          'excluded_dependencies' => [],
          'excluded_themes' => [],
        ];
      }

      $profile_list = [];
      // Get the base profile dependencies.
      if ($base_profile_name = $info['base profile']['name']) {
        $base_info = $this->getProfileInfo($base_profile_name);
        $profile_list += $base_info['profile_list'];

        // Ensure all dependencies are cleanly merged.
        $info['dependencies'] = array_merge($info['dependencies'], $base_info['dependencies']);

        if (isset($info['base profile']['excluded_dependencies'])) {
          // Apply excluded dependencies.
          $info['dependencies'] = array_diff($info['dependencies'], $info['base profile']['excluded_dependencies']);
        }
        // Ensure there's no circular dependency.
        $info['dependencies'] = array_diff($info['dependencies'], [$profile]);

        // Ensure all themes are cleanly merged.
        $info['themes'] = array_unique(array_merge($info['themes'], $base_info['themes']));
        if (isset($info['base profile']['excluded_themes'])) {
          // Apply excluded themes.
          $info['themes'] = array_diff($info['themes'], $info['base profile']['excluded_themes']);
        }
        // Ensure each theme is listed only once.
        $info['themes'] = array_unique($info['themes']);

      }
      $profile_list[$profile] = $profile;
      $info['profile_list'] = $profile_list;

      // Ensure the same dependency notation as in modules can be used.
      array_walk($info['dependencies'], function(&$dependency) {
        $dependency = ModuleHandler::parseDependency($dependency)['name'];
      });

      // Installation profiles are hidden by default, unless explicitly
      // specified otherwise in the .info.yml file.
      $info['hidden'] = isset($info['hidden']) ? $info['hidden'] : TRUE;

      $this->infoCache[$profile] = $info;
    }
    return $this->infoCache[$profile];
  }

  /**
   * {@inheritdoc}
   */
  public function setProfileInfo($profile, array $info) {
    $this->infoCache[$profile] = $info;
    // Also unset the cached profile extension so the updated info will
    // be picked up.
    unset($this->profilesWithParentsCache[$profile]);
  }

  /**
   * {@inheritdoc}
   */
  public function clearCache() {
    $this->profilesWithParentsCache = [];
    $this->infoCache = [];
  }

  /**
   * Create an Extension object for a profile.
   *
   * @param string $profile
   *   The name of the profile.
   *
   * @return \Drupal\Core\Extension\Extension
   *   The extension object for the profile
   *   Properties added to extension:
   *     info: The parsed info.yml data.
   *     origin: The directory origin as used in ExtensionDiscovery.
   */
  protected function getProfileExtension($profile) {
    $profile_info = $this->getProfileInfo($profile);

    $type = $profile_info['type'];
    $profile_path = $this->getProfilePath($profile);
    $profile_file = $profile_path . "/$profile.info.yml";
    $filename = file_exists($profile_path . "/$profile.$type") ? "$profile.$type" : NULL;
    $extension = new Extension($this->root, $type, $profile_file, $filename);

    $extension->info = $profile_info;
    $extension->origin = '';

    return $extension;
  }

  /**
   * Get a list of dependent profile names.
   *
   * @param string $profile
   *   Name of profile.
   *
   * @return string[]
   *   An associative array of profile names, keyed by profile name
   *   in descending order of their dependencies (parent profiles first, main
   *   profile last).
   */
  protected function getProfileList($profile) {
    $profile_info = $this->getProfileInfo($profile);
    return $profile_info['profile_list'];
  }

  /**
   * {@inheritdoc}
   */
  public function getProfileInheritance($profile = NULL) {
    if (empty($profile)) {
      $profile = drupal_get_profile();
    }
    if (!isset($this->profilesWithParentsCache[$profile])) {
      $profiles = [];
      // Check if a valid profile name was given.
      if (!empty($profile)) {
        $list = $this->getProfileList($profile);

        // Starting weight for profiles ensures their hooks run last.
        $weight = 1000;

        // Loop through profile list and create Extension objects.
        $profiles = [];
        foreach ($list as $profile_name) {
          $extension = $this->getProfileExtension($profile_name);
          $extension->weight = $weight;
          $weight++;
          $profiles[$profile_name] = $extension;
        }
      }
      $this->profilesWithParentsCache[$profile] = $profiles;
    }
    return $this->profilesWithParentsCache[$profile];
  }

  /**
   * {@inheritdoc}
   */
  public function selectDistribution(array $profile_list) {
    // First, find all profiles marked as distributions.
    $distributions = [];
    foreach ($profile_list as $profile_name) {
      $profile_info = $this->getProfileInfo($profile_name);
      if (!empty($profile_info['distribution'])) {
        $distributions[$profile_name] = $profile_name;
      }
    }
    // Remove any base profiles.
    foreach ($profile_list as $profile_name) {
      $profile_info = $this->getProfileInfo($profile_name);
      if ($base_profile = $profile_info['base profile']['name']) {
        unset($distributions[$base_profile]);
      }
    }
    return !empty($distributions) ? current($distributions) : NULL;
  }

}
