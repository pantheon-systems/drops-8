<?php

namespace Drupal\media_entity\Form;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\user\PrivateTempStoreFactory;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Provides a media deletion confirmation form.
 */
class DeleteMultiple extends ConfirmFormBase {

  /**
   * The array of media entities to delete.
   *
   * @var string[][]
   */
  protected $entityInfo = [];

  /**
   * The tempstore factory.
   *
   * @var \Drupal\user\PrivateTempStoreFactory
   */
  protected $tempStoreFactory;

  /**
   * The entity storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $storage;

  /**
   * Constructs a DeleteMultiple form object.
   *
   * @param \Drupal\user\PrivateTempStoreFactory $temp_store_factory
   *   The tempstore factory.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $manager
   *   The entity manager.
   */
  public function __construct(PrivateTempStoreFactory $temp_store_factory, EntityTypeManagerInterface $manager) {
    $this->tempStoreFactory = $temp_store_factory;
    $this->storage = $manager->getStorage('media');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('user.private_tempstore'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'media_multiple_delete_confirm';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->formatPlural(count($this->entityInfo), 'Are you sure you want to delete this item?', 'Are you sure you want to delete these items?');
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('system.admin_content');
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Delete');
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $this->entityInfo = $this->tempStoreFactory->get('media_multiple_delete_confirm')->get(\Drupal::currentUser()->id());
    if (empty($this->entityInfo)) {
      return new RedirectResponse($this->getCancelUrl()->setAbsolute()->toString());
    }
    /** @var \Drupal\media_entity\MediaInterface[] $entities */
    $entities = $this->storage->loadMultiple(array_keys($this->entityInfo));

    $items = [];
    foreach ($this->entityInfo as $id => $langcodes) {
      foreach ($langcodes as $langcode) {
        $entity = $entities[$id]->getTranslation($langcode);
        $key = $id . ':' . $langcode;
        $default_key = $id . ':' . $entity->getUntranslated()->language()->getId();

        // If we have a translated entity we build a nested list of translations
        // that will be deleted.
        $languages = $entity->getTranslationLanguages();
        if (count($languages) > 1 && $entity->isDefaultTranslation()) {
          $names = [];
          foreach ($languages as $translation_langcode => $language) {
            $names[] = $language->getName();
            unset($items[$id . ':' . $translation_langcode]);
          }
          $items[$default_key] = [
            'label' => [
              '#markup' => $this->t('@label (Original translation) - <em>The following media translations will be deleted:</em>', ['@label' => $entity->label()]),
            ],
            'deleted_translations' => [
              '#theme' => 'item_list',
              '#items' => $names,
            ],
          ];
        }
        elseif (!isset($items[$default_key])) {
          $items[$key] = $entity->label();
        }
      }
    }

    $form['entities'] = [
      '#theme' => 'item_list',
      '#items' => $items,
    ];
    $form = parent::buildForm($form, $form_state);

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    if ($form_state->getValue('confirm') && !empty($this->entityInfo)) {
      $total_count = 0;
      $delete_entities = [];
      /** @var \Drupal\Core\Entity\ContentEntityInterface[][] $delete_translations */
      $delete_translations = [];
      /** @var \Drupal\media_entity\MediaInterface[] $entities */
      $entities = $this->storage->loadMultiple(array_keys($this->entityInfo));

      foreach ($this->entityInfo as $id => $langcodes) {
        foreach ($langcodes as $langcode) {
          $entity = $entities[$id]->getTranslation($langcode);
          if ($entity->isDefaultTranslation()) {
            $delete_entities[$id] = $entity;
            unset($delete_translations[$id]);
            $total_count += count($entity->getTranslationLanguages());
          }
          elseif (!isset($delete_entities[$id])) {
            $delete_translations[$id][] = $entity;
          }
        }
      }

      if ($delete_entities) {
        $this->storage->delete($delete_entities);
        $this->logger('media_entity')->notice('Deleted @count media entities.', ['@count' => count($delete_entities)]);
      }

      if ($delete_translations) {
        $count = 0;
        foreach ($delete_translations as $id => $translations) {
          $entity = $entities[$id]->getUntranslated();
          foreach ($translations as $translation) {
            $entity->removeTranslation($translation->language()->getId());
          }
          $entity->save();
          $count += count($translations);
        }
        if ($count) {
          $total_count += $count;
          $this->logger('media_entity')->notice('Deleted @count media translations.', ['@count' => $count]);
        }
      }

      if ($total_count) {
        drupal_set_message($this->formatPlural($total_count, 'Deleted 1 media entity.', 'Deleted @count media entities.'));
      }

      $this->tempStoreFactory->get('media_multiple_delete_confirm')->delete(\Drupal::currentUser()->id());
    }

    $form_state->setRedirect('system.admin_content');
  }

}
