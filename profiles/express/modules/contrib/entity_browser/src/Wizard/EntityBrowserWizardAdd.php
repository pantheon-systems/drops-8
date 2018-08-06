<?php

namespace Drupal\entity_browser\Wizard;

/**
 * Custom override for create form.
 */
class EntityBrowserWizardAdd extends EntityBrowserWizard {

  /**
   * {@inheritdoc}
   */
  public function getRouteName() {
    return 'entity.entity_browser.edit_form';
  }

}
