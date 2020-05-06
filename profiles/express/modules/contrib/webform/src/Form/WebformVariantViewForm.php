<?php

namespace Drupal\webform\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\webform\Utility\WebformElementHelper;
use Drupal\webform\WebformInterface;

/**
 * Provides a view and tests form for webform variants.
 */
class WebformVariantViewForm extends FormBase {

  /**
   * The current operation (view or test).
   *
   * @var string
   */
  protected $operation;

  /**
   * The webform.
   *
   * @var \Drupal\webform\WebformInterface
   */
  protected $webform;

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'webform_variant_view_form';
  }

  /**
   * Form constructor.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param \Drupal\webform\WebformInterface $webform
   *   The webform.
   * @param string $operation
   *   The webform operation.
   *
   * @return array
   *   The form structure.
   */
  public function buildForm(array $form, FormStateInterface $form_state, WebformInterface $webform = NULL, $operation = 'view') {
    $this->operation = $operation;
    $this->webform = $webform;

    $t_args = [
      '@operation' => ($operation === 'view') ? $this->t('view') : $this->t('test'),
    ];
    $form['description'] = [
      '#type' => 'container',
      'text' => [
        '#markup' => $this->t('Select variants and then click submit, which will redirect you to the @operation form.', $t_args),
        '#prefix' => '<p>',
        '#suffix' => '</p>',
      ],
    ];

    $element_keys = $webform->getElementsVariant();
    if (isset($element_keys)) {
      $form['variants'] = [
        '#tree' => TRUE,
      ];
      foreach ($element_keys as $element_key) {
        $element = $webform->getElement($element_key);

        $variants = $webform->getVariants(NULL, TRUE, $element_key);
        if (!$variants->count()) {
          continue;
        }
        $variant_options = [];
        foreach ($variants as $variant) {
          $variant_options[$variant->getVariantId()] = $variant->label();
        }

        $form['variants'][$element_key] = [
          '#type' => 'select',
          '#title' => WebformElementHelper::getAdminTitle($element),
          '#options' => $variant_options,
          '#empty_option' => $this->t('- None -'),
        ];
      }
    }

    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => ($operation === 'view') ? $this->t('View') : $this->t('Test'),
      '#button_type' => 'primary',
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $variants = $form_state->getValue('variants');

    // Build query string.
    $query = [];

    $element_keys = $this->webform->getElementsVariant();
    foreach ($element_keys as $element_key) {
      if (empty($variants[$element_key])) {
        continue;
      }

      $variant_id = $variants[$element_key];
      $variant_element = $this->webform->getElement($element_key);

      // If #prepopulate is disabled use '_webform_variant'
      // querystring parameter for view and test operations.
      // @see \Drupal\webform\Entity\Webform::getSubmissionForm
      if (empty($variant_element['#prepopulate'])) {
        $query += ['_webform_variant' => []];
        $query['_webform_variant'][$element_key] = $variant_id;
      }
      else {
        $query[$element_key] = $variant_id;
      }
    }
    $options = ['query' => $query];

    $rel = ($this->operation === 'view') ? 'canonical' : 'test-form';
    $redirect_url = $this->webform->toUrl($rel, $options);
    $form_state->setRedirectUrl($redirect_url);
  }

}
