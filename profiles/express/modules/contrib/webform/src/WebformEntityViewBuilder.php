<?php

namespace Drupal\webform;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityViewBuilder;

/**
 * Render controller for webform.
 */
class WebformEntityViewBuilder extends EntityViewBuilder {

  /**
   * {@inheritdoc}
   */
  public function view(EntityInterface $entity, $view_mode = 'full', $langcode = NULL) {
    // TODO: Determine if webform can be cached.
    /* @var $entity \Drupal\webform\WebformInterface */
    return [
      '#cache' => [
        'max-age' => 0,
      ],
    ] + $entity->getSubmissionForm();
  }

}
