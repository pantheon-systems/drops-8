<?php

namespace Drupal\webform;

use Drupal\Core\Routing\RouteMatchInterface;

/**
 * Defines an interface for help classes.
 */
interface WebformHelpManagerInterface {

  /**
   * Get group.
   *
   * @param string|null $id
   *   (optional) Group name.
   *
   * @return array|mixed
   *   A single group item or all groups.
   */
  public function getGroup($id = NULL);

  /**
   * Get help.
   *
   * @param string|null $id
   *   (optional) Help id.
   *
   * @return array|mixed
   *   A single help item or all help.
   */
  public function getHelp($id = NULL);

  /**
   * Get video.
   *
   * @param string|null $id
   *   (optional) Video id.
   *
   * @return array|mixed
   *   A single help item or all videos.
   */
  public function getVideo($id = NULL);

  /**
   * Get video links.
   *
   * @param string $id
   *   Video id.
   *
   * @return array
   *   An array of links.
   */
  public function getVideoLinks($id);

  /**
   * Sets a notification to be displayed to webform administrators.
   *
   * @param string $id
   *   The notification id.
   * @param string|\Drupal\Component\Render\MarkupInterface|array $message
   *   The notification to be displayed to webform administrators.
   * @param string $type
   *   (optional) The message's type. Defaults to 'status'. These values are
   *   supported: 'info', 'status', 'warning', 'error'.
   *
   * @internal
   *   Currently being used to display notifications related to updates.
   */
  public function addNotification($id, $message, $type = 'status');

  /**
   * Get notifications.
   *
   * @param string $type
   *   (optional) The message's type. These values are
   *   supported: 'info', 'status', 'warning', 'error'.
   *
   * @return array
   *   An array of messages for specified message type or
   *   all notifications grouped by type.
   *
   * @internal
   *   Currently being used to display notifications related to updates.
   */
  public function getNotifications($type = NULL);

  /**
   * Delete a notification by id.
   *
   * @param string $id
   *   The notification id.
   *
   * @internal
   *   Currently being used to display notifications related to updates.
   */
  public function deleteNotification($id);

  /**
   * Build help for specific route.
   *
   * @param string $route_name
   *   The route for which to find help.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The route match object from which to find help.
   *
   * @return array
   *   An render array containing help for specific route.
   */
  public function buildHelp($route_name, RouteMatchInterface $route_match);

  /**
   * Build the main help page for the Webform module.
   *
   * @return array
   *   An render array containing help for the Webform module.
   */
  public function buildIndex();

  /**
   * Build the videos section.
   *
   * @param bool $docs
   *   Set to TRUE to build exportable HTML documentation.
   *
   * @return array
   *   An render array containing the videos section.
   */
  public function buildVideos($docs = FALSE);

  /**
   * Build video link.
   *
   * @param string $video_id
   *   Video id.
   * @param string|null $video_display
   *   Video displa type.
   * @param string|null $title
   *   Link title.
   * @param array $options
   *   Link options.
   *
   * @return array
   *   A renderable array containing a link to a video.
   */
  public function buildVideoLink($video_id, $video_display = NULL, $title = NULL, array $options = []);

  /**
   * Build the add-ons section.
   *
   * @param bool $docs
   *   Set to TRUE to build exportable HTML documentation.
   *
   * @return array
   *   An render array containing the add-ons section.
   */
  public function buildAddOns($docs = FALSE);

  /**
   * Build the libraries section.
   *
   * @param bool $docs
   *   Set to TRUE to build exportable HTML documentation.
   *
   * @return array
   *   An render array containing the libraries section.
   */
  public function buildLibraries($docs = FALSE);

  /**
   * Build the comparison section.
   *
   * @param bool $docs
   *   Set to TRUE to build exportable HTML documentation.
   *
   * @return array
   *   An render array containing the comparison section.
   */
  public function buildComparison($docs = FALSE);

}
