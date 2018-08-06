<?php

namespace Drupal\responsive_preview;

/**
 * Interface for ResponsivePreview service.
 */
interface ResponsivePreviewInterface {

  /**
   * Returns the url to a page, that should be shown in the preview.
   */
  public function getUrl();

  /**
   * Returns an array of enabled devices, suitable for rendering.
   *
   * @return array
   *   A render array of enabled devices.
   */
  public function getRenderableDevicesList();

}
