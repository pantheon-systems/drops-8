<?php

namespace Drupal\diff;

use Drupal\Core\Diff\DiffFormatter as CoreDiffFormatterBase;
use Drupal\Core\Config\ConfigFactoryInterface;

/**
 * Diff formatter which returns output that can be rendered to a table.
 */
class DiffFormatter extends CoreDiffFormatterBase {

  /**
   * Creates a DiffFormatter to render diffs in a table.
   *
   * We need to extend the constructor of the diff formatter used by the
   * core config system in order to provide our own settings.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   */
  public function __construct(ConfigFactoryInterface $config_factory) {
    parent::__construct($config_factory);

    $config = $config_factory->get('diff.settings');
    $this->leading_context_lines = $config->get('general_settings.context_lines_leading');
    $this->trailing_context_lines = $config->get('general_settings.context_lines_trailing');
  }

}
