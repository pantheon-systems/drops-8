<?php

namespace Drupal\responsive_preview\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\responsive_preview\Entity\Device;

/**
 * Form handler for the device forms.
 */
class DeviceForm extends EntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $device = $this->entity;

    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $device->label(),
      '#description' => $this->t('Label for the Device.'),
      '#required' => TRUE,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $device->id(),
      '#disabled' => !$device->isNew(),
      '#machine_name' => [
        'exists' => [Device::class, 'load'],
      ],
    ];

    $form['status'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show device in list'),
      '#default_value' => $device->status(),
    ];

    $dimensions = $device->getDimensions();

    $form['dimensions'] = [
      '#type' => 'container',
      '#tree' => TRUE,
    ];

    $form['dimensions']['width'] = [
      '#type' => 'number',
      '#title' => $this->t('Width'),
      '#default_value' => $dimensions['width'],
      '#field_suffix' => $this->t('px'),
      '#required' => TRUE,
      '#min' => 1,
    ];

    $form['dimensions']['height'] = [
      '#type' => 'number',
      '#title' => $this->t('Height'),
      '#default_value' => $dimensions['height'],
      '#field_suffix' => $this->t('px'),
      '#required' => TRUE,
      '#min' => 1,
    ];

    $form['dimensions']['dppx'] = [
      '#type' => 'number',
      '#title' => $this->t('Dots per pixel (dppx)'),
      '#default_value' => $dimensions['dppx'],
      '#field_suffix' => $this->t('px'),
      '#description' => $this->t('Size of a single dot in graphical representation. Classic desktop displays have 1dppx, typical modern smartphones and laptops have 2dppx or higher. For example Google Nexus 4 and iPhone 5 has 2dppx, while Google Nexus 7 has 1.325dppx and Samsung Galaxy S4 has 3dppx.'),
      '#required' => TRUE,
      '#min' => 1,
      '#step' => 'any',
    ];

    $form['orientation'] = [
      '#type' => 'select',
      '#title' => $this->t('Default orientation'),
      '#options' => [
        'portrait' => $this->t('Portrait'),
        'landscape' => $this->t('Landscape'),
      ],
      '#default_value' => $device->label(),
      '#required' => TRUE,
      '#min' => 1,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $device = $this->entity;
    $status = $device->save();

    if ($status === SAVED_NEW) {
      drupal_set_message($this->t('Device %label has been added.', ['%label' => $device->label()]));
    }
    else {
      drupal_set_message($this->t('Device %label has been updated.', ['%label' => $device->label()]));
    }

    $form_state->setRedirectUrl($device->toUrl('collection'));
  }

}
