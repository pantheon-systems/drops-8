<?php

namespace Drupal\entity_browser\Form;

use Drupal\entity_browser\EntityBrowserInterface;

/**
 * Selection display configuration step in entity browser form wizard.
 */
class SelectionDisplayConfig extends PluginConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'entity_browser_selection_display_config_form';
  }

  /**
   * {@inheritdoc}
   */
  public function getPlugin(EntityBrowserInterface $entity_browser) {
    return $entity_browser->getSelectionDisplay();
  }

}
