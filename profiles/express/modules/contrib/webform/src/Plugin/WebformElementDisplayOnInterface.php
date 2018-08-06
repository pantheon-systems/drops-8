<?php

namespace Drupal\webform\Plugin;

/**
 * Provides a 'display_on' interface hide/show element on form and/or view display.
 */
interface WebformElementDisplayOnInterface {

  /**
   * Denotes display on both form and view displays.
   *
   * @var string
   */
  const DISPLAY_ON_BOTH = 'both';

  /**
   * Denotes display on form display only.
   *
   * @var string
   */
  const DISPLAY_ON_FORM = 'form';

  /**
   * Denotes display on view display only.
   *
   * @var string
   */
  const DISPLAY_ON_VIEW = 'view';

  /**
   * Denotes never display the element.
   *
   * @var string
   */
  const DISPLAY_ON_NONE = 'none';

}
