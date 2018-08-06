<?php

namespace Drupal\webform;

use Drupal\Core\Entity\BundleEntityFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\webform\Form\WebformDialogFormTrait;

/**
 * Provides a webform add form.
 */
class WebformEntityAddForm extends BundleEntityFormBase {

  use WebformDialogFormTrait;

  /**
   * {@inheritdoc}
   */
  protected function prepareEntity() {
    if ($this->operation == 'duplicate') {
      $this->setEntity($this->getEntity()->createDuplicate());
    }
    parent::prepareEntity();
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\webform\WebformInterface $webform */
    $webform = $this->getEntity();

    // Customize title for duplicate webform.
    if ($this->operation == 'duplicate') {
      // Display custom title.
      $form['#title'] = $this->t("Duplicate '@label' form", ['@label' => $webform->label()]);
    }

    $form = parent::buildForm($form, $form_state);

    return $this->buildDialogForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\webform\WebformInterface $webform */
    $webform = $this->getEntity();

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $webform->id(),
      '#machine_name' => [
        'exists' => '\Drupal\webform\Entity\Webform::load',
        'source' => ['title'],
      ],
      '#maxlength' => 32,
      '#disabled' => (bool) $webform->id() && $this->operation != 'duplicate',
      '#required' => TRUE,
    ];
    $form['title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Title'),
      '#maxlength' => 255,
      '#default_value' => $webform->label(),
      '#required' => TRUE,
      '#id' => 'title',
      '#attributes' => [
        'autofocus' => 'autofocus',
      ],
    ];
    $form['description'] = [
      '#type' => 'webform_html_editor',
      '#title' => $this->t('Administrative description'),
      '#default_value' => $webform->get('description'),
    ];
    /** @var \Drupal\webform\WebformEntityStorageInterface $webform_storage */
    $webform_storage = $this->entityTypeManager->getStorage('webform');
    $form['category'] = [
      '#type' => 'webform_select_other',
      '#title' => $this->t('Category'),
      '#options' => $webform_storage->getCategories(),
      '#empty_option' => '<' . $this->t('None') . '>',
      '#default_value' => $webform->get('category'),
    ];
    $form = $this->protectBundleIdElement($form);

    return parent::form($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    // Poormans duplication of translated webform configuration.
    // This completely bypasses the config translation system and just
    // duplicates any translated webform config stored in the database.
    if ($this->operation == 'duplicate') {
      $result = \Drupal::database()->select('config', 'c')
        ->fields('c', ['collection', 'name', 'data'])
        ->condition('c.name', 'webform.webform.' . \Drupal::routeMatch()->getRawParameter('webform'))
        ->condition('c.collection', 'language.%', 'LIKE')
        ->execute();
      while ($record = $result->fetchAssoc()) {
        $record['name'] = 'webform.webform.' . $this->entity->id();
        \Drupal::database()->insert('config')
          ->fields(['collection', 'name', 'data'])
          ->values($record)
          ->execute();
      }
    }

    $form_state->setRedirectUrl(Url::fromRoute('entity.webform.edit_form', ['webform' => $this->getEntity()->id()]));
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\webform\WebformInterface $webform */
    $webform = $this->getEntity();

    $webform->save();

    $context = [
      '@label' => $webform->label(),
      'link' => $webform->toLink($this->t('Edit'), 'settings')->toString()
    ];
    $t_args = ['%label' => $webform->label()];
    $this->logger('webform')->notice('Webform @label created.', $context);
    drupal_set_message($this->t('Webform %label created.', $t_args));
  }

}
