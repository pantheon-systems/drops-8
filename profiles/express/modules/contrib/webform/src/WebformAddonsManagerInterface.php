<?php

namespace Drupal\webform;

/**
 * Defines an interface for webform add-ons classes.
 */
interface WebformAddonsManagerInterface {

  /**
   * Get add-on promotions.
   *
   * @return array
   *   An associative array of add-on promotions.
   */
  public function getPromotions();

  /**
   * Get add-on project information.
   *
   * @param string $name
   *   The name of the add-on project.
   *
   * @return array
   *   An associative array containing an add-on project.
   */
  public function getProject($name);

  /**
   * Get add-on projects.
   *
   * @param string|null $category
   *   (optional) Category to filter project by.
   *
   * @return array
   *   An associative array of add-on projects.
   */
  public function getProjects($category = NULL);

  /**
   * Get add-on projects that support third party settings.
   *
   * @return array
   *   An associative array containing add-on projects that support third party
   *   settings.
   */
  public function getThirdPartySettings();

  /**
   * Get add-on categories.
   *
   * @return array
   *   An array of add-on categories.
   */
  public function getCategories();

}
