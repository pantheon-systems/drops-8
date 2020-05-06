<?php

namespace Drupal\webform\Plugin\WebformElement;

use Drupal\Core\Form\FormStateInterface;
use Drupal\webform\WebformSubmissionInterface;

/**
 * Provides a 'section' element.
 *
 * @WebformElement(
 *   id = "webform_section",
 *   label = @Translation("Section"),
 *   description = @Translation("Provides an element for a section/group of form elements."),
 *   category = @Translation("Containers"),
 * )
 */
class WebformSection extends ContainerBase {

  /**
   * {@inheritdoc}
   */
  protected function defineDefaultProperties() {
    return [
      // Description/Help.
      'help' => '',
      'help_title' => '',
      'description' => '',
      'more' => '',
      'more_title' => '',
      // Title.
      'title_tag' => \Drupal::config('webform.settings')->get('element.default_section_title_tag'),
      'title_display' => '',
      'help_display' => '',
    ] + parent::defineDefaultProperties();
  }

  /****************************************************************************/

  /**
   * {@inheritdoc}
   */
  public function prepare(array &$element, WebformSubmissionInterface $webform_submission = NULL) {
    parent::prepare($element, $webform_submission);

    if (empty($element['#title_tag'])) {
      $element['#title_tag'] = $this->getDefaultProperty('title_tag');
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getItemDefaultFormat() {
    return 'header';
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $form['form']['title_tag'] = [
      '#type' => 'webform_select_other',
      '#title' => $this->t('Title tag'),
      '#description' => $this->t("The section's title HTML tag."),
      '#options' => [
        'h1' => $this->t('Header 1 (h1)'),
        'h2' => $this->t('Header 2 (h2)'),
        'h3' => $this->t('Header 3 (h3)'),
        'h4' => $this->t('Header 4 (h4)'),
        'h5' => $this->t('Header 5 (h5)'),
        'h6' => $this->t('Header 6 (h6)'),
        'label' => $this->t('Label (label)'),
      ],
    ];

    return $form;
  }

}
