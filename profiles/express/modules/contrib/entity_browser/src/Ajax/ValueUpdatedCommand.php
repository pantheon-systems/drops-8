<?php

namespace Drupal\entity_browser\Ajax;

use Drupal\Core\Ajax\CommandInterface;

/**
 * AJAX command to refresh an entity_browser_entity_reference field widget.
 */
class ValueUpdatedCommand implements CommandInterface {

  /**
   * The ID for the details element.
   *
   * @var string
   */
  protected $details_id;

  /**
   * Constructor.
   *
   * @param string $details_id
   *   The ID for the details element.
   */
  public function __construct($details_id) {
    $this->details_id = $details_id;
  }

  /**
   * {@inheritdoc}
   */
  public function render() {
    return [
      'command' => 'entity_browser_value_updated',
      'details_id' => $this->details_id,
    ];
  }

}
