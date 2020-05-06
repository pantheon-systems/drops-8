<?php

/**
 * @file
 * Hooks related to Webform Image Select module.
 */

/**
 * @addtogroup hooks
 * @{
 */

/**
 * Alter webform image select images by id.
 *
 * @param array $images
 *   An associative array of images.
 * @param array $element
 *   The webform element that the images is for.
 * @param string $images_id
 *   The webform image select images id. Set to NULL if the images are custom.
 */
function hook_webform_image_select_images_alter(array &$images, array &$element, $images_id = NULL) {

}

/**
 * Alter the webform image select images by id.
 *
 * @param array $images
 *   An associative array of images.
 * @param array $element
 *   The webform element that the images is for.
 */
function hook_webform_image_select_images_WEBFORM_IMAGE_SELECT_IMAGES_ID_alter(array &$images, array &$element) {

}

/**
 * @} End of "addtogroup hooks".
 */
