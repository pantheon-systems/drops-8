<?php

namespace Drupal\Core\Extension;

/**
 * Implementation of the profile handler usable before any working system.
 */
class FallbackProfileHandler implements ProfileHandlerInterface {

  /**
   * The app root.
   *
   * @var string
   */
  protected $root;

  /**
   * Creates a new FallbackProfileHandler instance.
   *
   * @param string $root
   *   The app root.
   */
  public function __construct($root) {
    $this->root = $root;
  }

  /**
   * The stored profile info.
   *
   * @var array[]
   */
  protected $profileInfo = [];

  /**
   * {@inheritdoc}
   */
  public function getProfileInfo($profile) {
    if (isset($this->profileInfo[$profile])) {
      return $this->profileInfo[$profile];
    }
    else {
      throw new \InvalidArgumentException('The profile name is invalid.');
    }
  }

  /**
   * {@inheritdoc}
   */
  public function setProfileInfo($profile, array $info) {
    $this->profileInfo[$profile] = $info;
  }

  /**
   * {@inheritdoc}
   */
  public function clearCache() {
    unset($this->profileInfo);
  }

  /**
   * {@inheritdoc}
   */
  public function getProfileInheritance($profile = NULL) {
    $profile_path = drupal_get_path('profile', $profile);
    return [
      $profile => new Extension($this->root, 'profile', $profile_path),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function selectDistribution(array $profile_list) {
    return NULL;
  }

}
