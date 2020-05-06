<?php

namespace Drupal\webform_image_select;

use Drupal\webform\Form\WebformConfigEntityDeleteFormBase;

/**
 * Provides a delete webform images select images form.
 */
class WebformImageSelectImagesDeleteForm extends WebformConfigEntityDeleteFormBase {

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
          $this->t('Affect any elements which use these images'),
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getDetails() {
    /** @var \Drupal\webform_image_select\WebformImageSelectImagesInterface $webform_images */
    $webform_images = $this->entity;

    /** @var \Drupal\webform_image_select\WebformImageSelectImagesStorageInterface $webform_images_storage */
    $webform_images_storage = $this->entityTypeManager->getStorage('webform_image_select_images');

    $t_args = [
      '%label' => $this->getEntity()->label(),
      '@entity-type' => $this->getEntity()->getEntityType()->getLowercaseLabel(),
    ];

    $details = [];
    if ($used_by_webforms = $webform_images_storage->getUsedByWebforms($webform_images)) {
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
