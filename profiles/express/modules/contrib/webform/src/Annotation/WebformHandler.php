<?php

namespace Drupal\webform\Annotation;

use Drupal\Component\Annotation\Plugin;
use Drupal\webform\Plugin\WebformHandlerInterface;

/**
 * Defines a webform handler annotation object.
 *
 * Plugin Namespace: Plugin\WebformHandler.
 *
 * For a working example, see
 * \Drupal\webform\Plugin\WebformHandler\EmailWebformHandler
 *
 * @see hook_webform_handler_info_alter()
 * @see \Drupal\webform\Plugin\WebformHandlerInterface
 * @see \Drupal\webform\Plugin\WebformHandlerBase
 * @see \Drupal\webform\Plugin\WebformHandlerManager
 * @see plugin_api
 *
 * @Annotation
 */
class WebformHandler extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The human-readable name of the webform handler.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $label;

  /**
   * The category in the admin UI where the block will be listed.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $category = '';

  /**
   * A brief description of the webform handler.
   *
   * This will be shown when adding or configuring this webform handler.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $description = '';

  /**
   * The maximum number of instances allowed for this webform handler.
   *
   * Possible values are positive integers or
   * \Drupal\webform\Plugin\WebformHandlerInterface::CARDINALITY_UNLIMITED or
   * \Drupal\webform\Plugin\WebformHandlerInterface::CARDINALITY_SINGLE.
   *
   * @var int
   */
  public $cardinality = WebformHandlerInterface::CARDINALITY_UNLIMITED;

  /**
   * Notifies the webform that this handler processes results.
   *
   * When set to TRUE, 'Disable saving of submissions.' can be set.
   *
   * @var bool
   */
  public $results = WebformHandlerInterface::RESULTS_IGNORED;

  /**
   * Indicated whether handler supports condition logic.
   *
   * Most handlers will support conditional logic, this flat allows custom
   * handlers and custom modules to easily disabled conditional logic for
   * a handler.
   *
   * @var bool
   */
  public $conditions = TRUE;

  /**
   * Indicated whether handler supports tokens.
   *
   * @var bool
   */
  public $tokens = FALSE;

  /**
   * Indicated whether submission must be stored in the database for this handler processes results.
   *
   * @var bool
   */
  public $submission = WebformHandlerInterface::SUBMISSION_OPTIONAL;

}
