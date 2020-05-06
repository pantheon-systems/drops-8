<?php

namespace Drupal\webform\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\webform\Utility\WebformElementHelper;
use Drupal\webform\WebformInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Form for apply a webform variant.
 */
class WebformVariantApplyForm extends WebformDeleteFormBase {

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
   * Track if webform has multiple variants.
   *
   * @var bool
   */
  protected $hasMultipleVariants;

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    if ($this->isDialog()) {
      return ($this->hasMultipleVariants)
        ? $this->t("Apply variants?")
        : $this->t("Apply variant?");
    }
    else {
      $t_args = ['%webform' => $this->webform->label()];
      return ($this->hasMultipleVariants)
        ? $this->t('Apply the selected variants to the %webform webform?', $t_args)
        : $this->t('Apply variant to the %webform webform?', $t_args);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getWarning() {
    if (!$this->hasMultipleVariants && $this->webformVariant) {
      $t_args = ['%title' => $this->webformVariant->label()];
      return [
        '#type' => 'webform_message',
        '#message_type' => 'warning',
        '#message_message' => $this->t('Are you sure you want to apply the %title variant?', $t_args) . '<br/>' .
          '<strong>' . $this->t('This action cannot be undone.') . '</strong>',
      ];
    }
    else {
      return [
        '#type' => 'webform_message',
        '#message_type' => 'warning',
        '#message_message' => $this->t('Are you sure you want to apply the selected variants?') . '<br/>' .
          '<strong>' . $this->t('This action cannot be undone.') . '</strong>',
      ];
    }
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
          ($this->hasMultipleVariants)
            ? $this->t('Replace existing settings, elements, and handler with selected variants.')
            : $this->t('Replace existing settings, elements, and handler with this variant.'),
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
    return 'webform_variant_apply_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, WebformInterface $webform = NULL) {
    $this->webform = $webform;

    $variant_id = $this->getRequest()->query->get('variant_id');
    if ($variant_id) {
      // Throw access denied exception is the variant does not exist.
      if (!$webform->getVariants()->has($variant_id)) {
        throw new AccessDeniedHttpException();
      }
      $this->webformVariant = $webform->getVariant($variant_id);
    }

    $this->hasMultipleVariants = (count($this->webform->getElementsVariant()) > 1) ? TRUE : FALSE;

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmInput() {
    $webform = $this->webform;

    $build = [];

    if ($this->hasMultipleVariants || empty($this->webformVariant)) {
      $build['variants'] = ['#tree' => TRUE];
      $element_keys = $webform->getElementsVariant();
      foreach ($element_keys as $element_key) {
        $element = $webform->getElement($element_key);
        $variants = $webform->getVariants(NULL, NULL, $element_key);
        if (!$variants->count()) {
          continue;
        }
        $variant_options = [];
        foreach ($variants as $variant) {
          $variant_options[$variant->getVariantId()] = $variant->label();
        }

        $build['variants'][$element_key] = [
          '#type' => 'select',
          '#title' => WebformElementHelper::getAdminTitle($element),
          '#options' => $variant_options,
          '#empty_option' => $this->t('- None -'),
          '#default_value' => ($this->webformVariant) ? $this->webformVariant->getVariantId() : '',
        ];
      }
    }

    $build['delete'] = [
      '#type' => 'radios',
      '#title' => ($this->hasMultipleVariants)
        ? $this->t('After selected variants are applied…')
        : $this->t('After this variant is applied…'),
      '#options' => [
        'selected' => ($this->hasMultipleVariants)
          ? $this->t('Delete the selected variants')
          : $this->t('Delete this variant'),
        'all' => $this->t('Delete all variants'),
        'none' => $this->t('Do not delete any variants'),
      ],
      '#required' => TRUE,
    ];

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Apply');
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $variants = array_filter($form_state->getValue('variants') ?: []);
    if ($this->hasMultipleVariants && empty($variants)) {
      $form_state->setErrorByName('variants', $this->t('Please select variants to be applied to the webform.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $webform = $this->webform;

    // Apply variant(s).
    $variants = array_filter($form_state->getValue('variants') ?: []);
    if (empty($variants) && $this->webformVariant) {
      $element_key = $this->webformVariant->getElementKey();
      $variant_id = $this->webformVariant->getVariantId();
      $variants = [$element_key => $variant_id];
    }
    $webform->applyVariants(NULL, $variants, TRUE);
    $webform->setOverride(FALSE);
    $webform->save();

    $variant_plugin = $this->webformVariant;
    if (!$this->hasMultipleVariants && empty($variant_plugin)) {
      $variant_plugin = $webform->getVariant(reset($variants));
    }

    // Delete variant and display a status message.
    $t_args = ['%title' => $variant_plugin->label()];
    switch ($form_state->getValue('delete')) {
      case 'selected':
        foreach ($variants as $element_key => $variant_id) {
          $variant_plugin = $webform->getVariant($variant_id);
          $this->webform->deleteWebformVariant($variant_plugin);
        }
        ($this->hasMultipleVariants)
          ? $this->messenger()->addStatus($this->t('The selected webform variants have been applied and deleted.'))
          : $this->messenger()->addStatus($this->t('The webform variant %title has been applied and deleted.', $t_args));
        break;

      case 'all':
        $variant_plugins = $webform->getVariants();
        foreach ($variant_plugins as $variant_plugin) {
          $this->webform->deleteWebformVariant($variant_plugin);
        }
        ($this->hasMultipleVariants)
          ? $this->messenger()->addStatus($this->t('The selected webform variants have been applied and all variants have been deleted.'))
          : $this->messenger()->addStatus($this->t('The webform variant %title has been applied and all variants have been deleted.', $t_args));
        break;

      case 'none':
      default:
        ($this->hasMultipleVariants)
          ? $this->messenger()->addStatus($this->t('The selected webform variants have been applied.'))
          : $this->messenger()->addStatus($this->t('The webform variant %title has been applied.', $t_args));
        break;
    }

    $form_state->setRedirectUrl($this->webform->toUrl());
  }

}
