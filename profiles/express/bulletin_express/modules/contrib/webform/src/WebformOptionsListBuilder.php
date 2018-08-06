<?php

namespace Drupal\webform;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\OptGroup;
use Drupal\webform\Entity\WebformOptions;

/**
 * Defines a class to build a listing of webform options entities.
 *
 * @see \Drupal\webform\Entity\WebformOption
 */
class WebformOptionsListBuilder extends ConfigEntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function render() {
    $build = [];

    // Display info.
    if ($total = $this->getStorage()->getQuery()->count()->execute()) {
      $t_args = [
        '@total' => $total,
        '@results' => $this->formatPlural($total, $this->t('option'), $this->t('options')),
      ];
      $build['info'] = [
        '#markup' => $this->t('@total @results', $t_args),
        '#prefix' => '<div>',
        '#suffix' => '</div>',
      ];
    }

    $build += parent::render();

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['label'] = $this->t('Label');
    $header['id'] = $this->t('ID');
    $header['category'] = $this->t('Category');
    $header['options'] = [
      'data' => $this->t('Options'),
      'class' => [RESPONSIVE_PRIORITY_LOW],
    ];
    $header['alter'] = [
      'data' => $this->t('Altered'),
      'class' => [RESPONSIVE_PRIORITY_LOW],
    ];
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /** @var \Drupal\webform\WebformOptionsInterface $entity */
    $row['label'] = $entity->toLink($entity->label(), 'edit-form');
    $row['id'] = $entity->id();
    $row['category'] = $entity->get('category');

    $options = WebformOptions::getElementOptions(['#options' => $entity->id()]);
    $options = OptGroup::flattenOptions($options);
    foreach ($options as $key => &$value) {
      if ($key != $value) {
        $value .= ' (' . $key . ')';
      }
    }
    $row['options'] = implode('; ', array_slice($options, 0, 12)) . (count($options) > 12 ? '; ...' : '');

    $row['alter'] = $entity->hasAlterHooks() ? $this->t('Yes') : $this->t('No');
    return $row + parent::buildRow($entity);
  }

}
