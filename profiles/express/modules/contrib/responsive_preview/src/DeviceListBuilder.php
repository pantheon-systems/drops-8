<?php

namespace Drupal\responsive_preview;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Config\Entity\DraggableListBuilder;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Provides a listing of Device entities.
 */
class DeviceListBuilder extends DraggableListBuilder {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'responsive_preview_device';
  }

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['label'] = $this->t('Name');
    $header['status'] = $this->t('Show in list');
    $header['dimensions'] = $this->t('Dimensions');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $row['label'] = $entity->label();

    $row['status'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show %title in list', ['%title' => $entity->label()]),
      '#title_display' => 'invisible',
      '#default_value' => $entity->status(),
    ];

    $dimensions = $entity->getDimensions();

    $row['dimensions']['#markup'] = new FormattableMarkup('@widthx@height (@dppx dppx)', [
      '@width' => $dimensions['width'],
      '@height' => $dimensions['height'],
      '@dppx' => $dimensions['dppx'],
    ]);

    return $row + parent::buildRow($entity);
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);
    if (empty($this->entities)) {
      $form['actions']['#access'] = FALSE;
    }
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $entities = $form_state->getValue($this->entitiesKey);
    if (!empty($entities)) {
      parent::submitForm($form, $form_state);

      foreach ($entities as $id => $value) {
        if (isset($this->entities[$id]) && $this->entities[$id]->status() !== $value['status']) {
          $this->entities[$id]->setStatus($value['status']);
          $this->entities[$id]->save();
        }
      }

      drupal_set_message($this->t('The device settings have been updated.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function render() {
    $build = parent::render();
    $build[$this->entitiesKey]['#empty'] = $this->t('No devices available. <a href=":link">Add devices</a>.', [
      ':link' => Url::fromRoute('entity.responsive_preview_device.add_form')->toString(),
    ]);
    return $build;
  }

}
