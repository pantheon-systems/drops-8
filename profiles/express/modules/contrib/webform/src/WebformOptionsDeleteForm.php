<?php

namespace Drupal\webform;

use Drupal\webform\Form\WebformConfigEntityDeleteFormBase;

/**
 * Provides a delete webform options form.
 */
class WebformOptionsDeleteForm extends WebformConfigEntityDeleteFormBase {

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
          $this->t('Affect any elements which use these options'),
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getDetails() {
    /** @var \Drupal\webform\WebformOptionsInterface $webform_options */
    $webform_options = $this->entity;

    /** @var \Drupal\webform\WebformOptionsStorageInterface $webform_options_storage */
    $webform_options_storage = $this->entityTypeManager->getStorage('webform_options');

    $t_args = [
      '%label' => $this->getEntity()->label(),
      '@entity-type' => $this->getEntity()->getEntityType()->getLowercaseLabel(),
    ];

    $details = [];
    if ($used_by_elements = $webform_options_storage->getUsedByCompositeElements($webform_options)) {
      $details['elements'] = [
        'title' => [
          '#markup' => $this->t('%label is used by the below composite element(s).', $t_args),
        ],
        'list' => [
          '#theme' => 'item_list',
          '#items' => $used_by_elements,
        ],
      ];
    }
    if ($used_by_webforms = $webform_options_storage->getUsedByWebforms($webform_options)) {
      $details['webform'] = [
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
