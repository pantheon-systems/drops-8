<?php

/**
 * @file
 * Contains \Drupal\linkit\ProfileListBuilder.
 */

namespace Drupal\linkit;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Url;

/**
 * Defines a class to build a listing of profile entities.
 *
 * @see \Drupal\linkit\Entity\Profile
 */
class ProfileListBuilder extends ConfigEntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
      $header['title'] = t('Profile');
      $header['description'] = [
          'data' => t('Description'),
          'class' => [RESPONSIVE_PRIORITY_MEDIUM],
      ];
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /** @var \Drupal\linkit\ProfileInterface $linkitProfile */
    $linkitProfile = $entity;
    $row['label'] = $linkitProfile->label();
    $row['description']['data'] = ['#markup' => $linkitProfile->getDescription()];
    return $row + parent::buildRow($entity);
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultOperations(EntityInterface $entity) {
    $operations = parent::getDefaultOperations($entity);

    if (isset($operations['edit'])) {
      $operations['edit']['title'] = t('Edit profile');
    }

    $operations['matchers'] = [
      'title' => t('Manage matchers'),
      'weight' => 10,
      'url' => Url::fromRoute('linkit.matchers', [
        'linkit_profile' => $entity->id()
      ]),
    ];

    $operations['attributes'] = [
      'title' => t('Manage attributes'),
      'weight' => 20,
      'url' => Url::fromRoute('linkit.attributes', [
        'linkit_profile' => $entity->id()
      ]),
    ];

    return $operations;
  }

}
