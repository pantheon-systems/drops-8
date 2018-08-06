<?php

namespace Drupal\webform\Plugin\WebformElement;

use Drupal\Component\Utility\Html;
use Drupal\Core\Form\FormStateInterface;
use Drupal\webform\Utility\WebformElementHelper;

/**
 * Provides a 'webform_computed_twig' element.
 *
 * @WebformElement(
 *   id = "webform_computed_twig",
 *   label = @Translation("Computed Twig"),
 *   description = @Translation("Provides an item to display computed webform submission values using Twig."),
 *   category = @Translation("Computed Elements"),
 * )
 */
class WebformComputedTwig extends WebformComputedBase {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    /** @var \Drupal\webform\WebformSubmissionStorageInterface $submission_storage */
    $submission_storage = \Drupal::entityTypeManager()->getStorage('webform_submission');
    $field_definitions = $submission_storage->getFieldDefinitions();
    $items = [
      '{{ webform }}',
      '{{ webform_submission }}',
      '{{ elements }}',
      '{{ elements_flattened }}',
      // @todo Dynamically generate examples for all elements.
      // This could be overkill.
      '{{ data.element_key }}',
      '{{ data.element_key.delta }}',
      '{{ data.composite_element_key.subelement_key }}',
      '{{ data.composite_element_key.delta.subelement_key }}',
    ];
    foreach (array_keys($field_definitions) as $field_name) {
      $items[] = "{{ $field_name }}";
    }

    $t_args = [
      ':twig_href' => 'https://twig.sensiolabs.org/',
      ':drupal_href' => 'https://www.drupal.org/docs/8/theming/twig',
    ];
    $output = [];
    $output[] = [
      '#markup' => '<p>' . $this->t('Learn about <a href=":twig_href">Twig</a> and how it is used in <a href=":drupal_href">Drupal</a>.', $t_args) . '</p>',
    ];
    $output[] = [
      '#markup' => '<p>' . $this->t("The following variables are available:") . '</p>',
    ];
    $output[] = [
      '#theme' => 'item_list',
      '#items' => $items,
    ];
    $output[] = [
      '#markup' => '<p>' . $this->t("You can also output tokens using the <code>webform_token()</code> function.") . '</p>',
    ];
    $output[] = [
      '#markup' => "<pre>{{ webform_token('[webform_submission:values:element_value]', webform_submission) }}</pre>",
    ];
    $form['computed']['help'] = [
      '#type' => 'details',
      '#title' => $this->t('Help using Twig'),
      'description' => $output,
    ];
    $form['computed']['value']['#mode'] = 'twig';
    // Set #access so that help is always visible.
    WebformElementHelper::setPropertyRecursive($form['computed']['help'], '#access', TRUE);

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::validateConfigurationForm($form, $form_state);

    // Validate Twig markup with no context.
    try {
      $build = [
        '#type' => 'inline_template',
        '#template' => $form_state->getValue('value'),
        '#context' => [],
      ];
      \Drupal::service('renderer')->renderPlain($build);
    }
    catch (\Exception $exception) {
      $form_state->setErrorByName('markup', [
        'message' => ['#markup' => $this->t('Failed to render computed Twig value due to error.'), '#suffix' => '<br /><br />'],
        'error' => ['#markup' => Html::escape($exception->getMessage()), '#prefix' => '<pre>', '#suffix' => '</pre>'],
      ]);
    }
  }

}
