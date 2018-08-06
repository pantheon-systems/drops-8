<?php

namespace Drupal\media_entity_document\Plugin\EntityBrowser\Widget;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\entity_browser\Plugin\EntityBrowser\Widget\Upload as FileUpload;
use Drupal\media_entity\MediaInterface;

/**
 * Uses upload to create media entity documents.
 *
 * @EntityBrowserWidget(
 *   id = "media_entity_document_upload",
 *   label = @Translation("Upload documents"),
 *   description = @Translation("Upload widget that creates media entity documents.")
 * )
 */
class Upload extends FileUpload {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'extensions' => 'txt, pdf',
      'media_bundle' => NULL,
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function getForm(array &$original_form, FormStateInterface $form_state, array $aditional_widget_parameters) {
    /** @var \Drupal\media_entity\MediaBundleInterface $bundle */
    if (!$this->configuration['media_bundle'] || !($bundle = $this->entityTypeManager->getStorage('media_bundle')->load($this->configuration['media_bundle']))) {
      return ['#markup' => $this->t('The media bundle is not configured correctly.')];
    }

    if ($bundle->getType()->getPluginId() != 'document') {
      return ['#markup' => $this->t('The configured bundle is not using document plugin.')];
    }

    $form = parent::getForm($original_form, $form_state, $aditional_widget_parameters);
    $form['upload']['#upload_validators']['file_validate_extensions'] = [$this->configuration['extensions']];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  protected function prepareEntities(array $form, FormStateInterface $form_state) {
    $files = parent::prepareEntities($form, $form_state);

    /** @var \Drupal\media_entity\MediaBundleInterface $bundle */
    $bundle = $this->entityTypeManager
      ->getStorage('media_bundle')
      ->load($this->configuration['media_bundle']);

    $documents = [];
    foreach ($files as $file) {
      /** @var \Drupal\media_entity\MediaInterface $document */
      $document = $this->entityTypeManager->getStorage('media')->create([
        'bundle' => $bundle->id(),
        $bundle->getTypeConfiguration()['source_field'] => $file,
      ]);

      $documents[] = $document;
    }

    return $documents;
  }

  /**
   * {@inheritdoc}
   */
  public function submit(array &$element, array &$form, FormStateInterface $form_state) {
    if (!empty($form_state->getTriggeringElement()['#eb_widget_main_submit'])) {
      $documents = $this->prepareEntities($form, $form_state);
      array_walk(
        $documents,
        function (MediaInterface $media) { $media->save(); }
      );

      $this->selectEntities($documents, $form_state);
      $this->clearFormValues($element, $form_state);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $bundle_options = [];
    $form = parent::buildConfigurationForm($form, $form_state);

    $form['extensions'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Allowed extensions'),
      '#default_value' => $this->configuration['extensions'],
      '#required' => TRUE,
    ];

    $bundles = $this->entityTypeManager
      ->getStorage('media_bundle')
      ->loadByProperties(['type' => 'document']);

    /** @var \Drupal\media_entity\MediaBundleInterface $bundle */
    foreach ($bundles as $bundle) {
      $bundle_options[$bundle->id()] = $bundle->label();
    }

    if (empty($bundle_options)) {
      $url = Url::fromRoute('media.bundle_add')->toString();
      $form['media_bundle'] = [
        '#markup' => $this->t("You don't have media bundle of the Document type. You should <a href='!link'>create one</a>", ['!link' => $url]),
      ];
    }
    else {
      $form['media_bundle'] = [
        '#type' => 'select',
        '#title' => $this->t('Media bundle'),
        '#default_value' => $this->configuration['media_bundle'],
        '#options' => $bundle_options,
      ];
    }

    return $form;
  }

}
