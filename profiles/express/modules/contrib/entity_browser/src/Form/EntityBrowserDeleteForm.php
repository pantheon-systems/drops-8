<?php

namespace Drupal\entity_browser\Form;

use Drupal\Core\Entity\EntityDeleteForm;
use Drupal\Core\Url;

/**
 * Delete confirm form for entity browsers.
 */
class EntityBrowserDeleteForm extends EntityDeleteForm {

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t(
      'Are you sure you want to delete entity browser %label?',
      ['%label' => $this->entity->label()]
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Delete Entity Browser');
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('entity.entity_browser.collection');
  }

  /**
   * {@inheritdoc}
   */
  protected function getDeletionMessage() {
    return $this->t(
      'Entity browser %label was deleted.',
      ['%label' => $this->entity->label()]
    );
  }

}
