<?php

/**
 * @file
 * Contains \Drupal\linkit_test\Plugin\Linkit\Matcher\ConfigurableDummyMatcher.
 */

namespace Drupal\linkit_test\Plugin\Linkit\Matcher;

use Drupal\Core\Form\FormStateInterface;
use Drupal\linkit\ConfigurableMatcherBase;
use Symfony\Component\DependencyInjection\ContainerInterface;


/**
 * @Matcher(
 *   id = "configurable_dummy_matcher",
 *   label = @Translation("Configurable Dummy Matcher"),
 * )
 */
class ConfigurableDummyMatcher extends ConfigurableMatcherBase {

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return parent::defaultConfiguration() + [
      'dummy_setting' => FALSE,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form['dummy_setting'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Dummy setting'),
      '#default_value' => $this->configuration['dummy_setting'],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->configuration['dummy_setting'] = $form_state->getValue('dummy_setting');
  }

  /**
   * {@inheritdoc}
   */
  public function getMatches($string) {
    $matches[] = [
      'title' => 'Configurable Dummy Matcher title',
      'description' => 'Configurable Dummy Matcher description',
      'path' => 'http://example.com',
      'group' => 'Configurable Dummy Matcher',
    ];

    return $matches;
  }

}
