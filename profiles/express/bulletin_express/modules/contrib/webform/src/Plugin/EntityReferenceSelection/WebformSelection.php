<?php

namespace Drupal\webform\Plugin\EntityReferenceSelection;

use Drupal\Core\Entity\Plugin\EntityReferenceSelection\DefaultSelection;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides specific access control for the webform entity type.
 *
 * @EntityReferenceSelection(
 *   id = "default:webform",
 *   label = @Translation("Webform selection"),
 *   entity_types = {"webform"},
 *   group = "default",
 *   weight = 1
 * )
 */
class WebformSelection extends DefaultSelection {

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    // Disable autocreate.
    $form['auto_create']['#access'] = FALSE;

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  protected function buildEntityQuery($match = NULL, $match_operator = 'CONTAINS') {
    $query = parent::buildEntityQuery($match, $match_operator);
    // Exclude templates.
    $query->condition('template', FALSE);
    return $query;
  }

}
