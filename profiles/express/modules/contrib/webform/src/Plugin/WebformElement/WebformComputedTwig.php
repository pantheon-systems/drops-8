<?php

namespace Drupal\webform\Plugin\WebformElement;

use Drupal\Core\Form\FormStateInterface;
use Drupal\webform\Utility\WebformElementHelper;
use Drupal\webform\Twig\WebformTwigExtension;

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
  protected function defineDefaultProperties() {
    return [
      'whitespace' => '',
    ] + parent::defineDefaultProperties();
  }

  /****************************************************************************/

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $form['computed']['help'] = WebformTwigExtension::buildTwigHelp();
    $form['computed']['template']['#mode'] = 'twig';

    // Set #access so that help is always visible.
    WebformElementHelper::setPropertyRecursive($form['computed']['help'], '#access', TRUE);

    return $form;
  }

}
