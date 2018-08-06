<?php

namespace Drupal\entity_browser\Form;

use Drupal\entity_browser\EntityBrowserInterface;

/**
 * Widget selector configuration step in entity browser form wizard.
 */
class WidgetSelectorConfig extends PluginConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'entity_browser_widget_selector_config_form';
  }

  /**
   * {@inheritdoc}
   */
  public function getPlugin(EntityBrowserInterface $entity_browser) {
    return $entity_browser->getWidgetSelector();
  }

}
