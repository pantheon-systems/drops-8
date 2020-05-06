<?php

namespace Drupal\webform_options_custom;

use Drupal\webform\Form\WebformConfigEntityDeleteFormBase;

/**
 * Provides a delete webform custom options form.
 */
class WebformOptionsCustomDeleteForm extends WebformConfigEntityDeleteFormBase {

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return [
      'title' => [
        '#markup' => $this->t('This action will…'),
      ],
      'list' => [
        '#theme' => 'item_list',
        '#items' => [
          $this->t('Remove configuration'),
          $this->t('Affect any elements which use these custom options'),
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getDetails() {
    /** @var \Drupal\webform_options_custom\WebformOptionsCustomInterface $webform_options_custom */
    $webform_options_custom = $this->entity;

    /** @var \Drupal\webform_options_custom\WebformOptionsCustomStorageInterface $webform_options_custom_storage */
    $webform_options_custom_storage = $this->entityTypeManager->getStorage('webform_options_custom');

    $t_args = [
      '%label' => $this->getEntity()->label(),
      '@entity-type' => $this->getEntity()->getEntityType()->getLowercaseLabel(),
    ];

    $details = [];
    if ($used_by_webforms = $webform_options_custom_storage->getUsedByWebforms($webform_options_custom)) {
      $details['used_by_composite_elements'] = [
        'title' => [
          '#markup' => $this->t('%label is used by the below webform(s).', $t_args),
        ],
        'list' => [
          '#theme' => 'item_list',
          '#items' => $used_by_webforms,
        ],
      ];
    }

    if ($details) {
      return [
        '#type' => 'details',
        '#title' => $this->t('Webforms affected'),
      ] + $details;
    }
    else {
      return [];
    }
  }

}
