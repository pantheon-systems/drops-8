<?php

namespace Drupal\webform\Plugin\WebformHandler;

use Drupal\Core\Serialization\Yaml;
use Drupal\Core\Form\FormStateInterface;
use Drupal\webform\Utility\WebformYaml;
use Drupal\webform\Plugin\WebformHandlerBase;
use Drupal\webform\WebformSubmissionInterface;

/**
 * Webform submission debug handler.
 *
 * @WebformHandler(
 *   id = "debug",
 *   label = @Translation("Debug"),
 *   category = @Translation("Development"),
 *   description = @Translation("Debug webform submission."),
 *   cardinality = \Drupal\webform\Plugin\WebformHandlerInterface::CARDINALITY_SINGLE,
 *   results = \Drupal\webform\Plugin\WebformHandlerInterface::RESULTS_PROCESSED,
 *   submission = \Drupal\webform\Plugin\WebformHandlerInterface::SUBMISSION_OPTIONAL,
 * )
 */
class DebugWebformHandler extends WebformHandlerBase {

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state, WebformSubmissionInterface $webform_submission) {
    $build = ['#markup' => 'Submitted values are:<pre>' . WebformYaml::tidy(Yaml::encode($webform_submission->getData())) . '</pre>'];
    drupal_set_message(\Drupal::service('renderer')->renderPlain($build), 'warning');
  }

}
