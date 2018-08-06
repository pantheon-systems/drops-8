<?php

namespace Drupal\pathauto;

use Drupal\Core\Config\Entity\DraggableListBuilder;
use Drupal\Core\Entity\EntityInterface;

/**
 * Provides a listing of Pathauto pattern entities.
 */
class PathautoPatternListBuilder extends DraggableListBuilder {

  /**
   * {@inheritdoc}
   */
  protected $limit = FALSE;

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'pathauto_pattern_list';
  }

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['label'] = $this->t('Label');
    $header['pattern'] = $this->t('Pattern');
    $header['type'] = $this->t('Pattern type');
    $header['conditions'] = $this->t('Conditions');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /* @var \Drupal\pathauto\PathautoPatternInterface $entity */
    $row['label'] = $entity->label();
    $row['patern']['#markup'] = $entity->getPattern();
    $row['type']['#markup'] = $entity->getAliasType()->getLabel();
    $row['conditions']['#theme'] = 'item_list';
    foreach ($entity->getSelectionConditions() as $condition) {
      $row['conditions']['#items'][] = $condition->summary();
    }
    return $row + parent::buildRow($entity);
  }

}
