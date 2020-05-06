<?php

namespace Drupal\webform\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\webform\WebformInterface;

/**
 * Form for deleting a webform variant.
 */
class WebformVariantDeleteForm extends WebformDeleteFormBase {

  /**
   * The webform containing the webform variant to be deleted.
   *
   * @var \Drupal\webform\WebformInterface
   */
  protected $webform;

  /**
   * The webform variant to be deleted.
   *
   * @var \Drupal\webform\Plugin\WebformVariantInterface
   */
  protected $webformVariant;

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    if ($this->isDialog()) {
      $t_args = [
        '@title' => $this->webformVariant->label(),
      ];
      return $this->t("Delete the '@title' variant?", $t_args);
    }
    else {
      $t_args = [
        '%webform' => $this->webform->label(),
        '%title' => $this->webformVariant->label(),
      ];
      return $this->t('Delete the %title variant from the %webform webform?', $t_args);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getWarning() {
    $t_args = ['%title' => $this->webformVariant->label()];
    return [
      '#type' => 'webform_message',
      '#message_type' => 'warning',
      '#message_message' => $this->t('Are you sure you want to delete the %title variant?', $t_args) . '<br/>' .
        '<strong>' . $this->t('This action cannot be undone.') . '</strong>',
    ];
  }

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
          $this->t('Remove this variant'),
        ],
      ],
    ];
  }

  /****************************************************************************/
  // Form methods.
  /****************************************************************************/

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return $this->webform->toUrl('variants');
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'webform_variant_delete_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, WebformInterface $webform = NULL, $webform_variant = NULL) {
    $this->webform = $webform;
    $this->webformVariant = $this->webform->getVariant($webform_variant);

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->webform->deleteWebformVariant($this->webformVariant);
    $this->messenger()->addStatus($this->t('The webform variant %name has been deleted.', ['%name' => $this->webformVariant->label()]));
    $form_state->setRedirectUrl($this->webform->toUrl('variants'));
  }

}
