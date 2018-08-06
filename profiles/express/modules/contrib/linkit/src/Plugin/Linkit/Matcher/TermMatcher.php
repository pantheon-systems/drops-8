<?php

/**
 * @file
 * Contains \Drupal\linkit\Plugin\Linkit\Matcher\TermMatcher.
 */

namespace Drupal\linkit\Plugin\Linkit\Matcher;

use Drupal\Core\Form\FormStateInterface;
use Drupal\linkit\Utility\LinkitXss;

/**
 * @Matcher(
 *   id = "entity:taxonomy_term",
 *   target_entity = "taxonomy_term",
 *   label = @Translation("Taxonomy term"),
 *   provider = "taxonomy"
 * )
 */
class TermMatcher extends EntityMatcher {

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    return parent::calculateDependencies() + [
      'module' => ['taxonomy'],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);
    $this->insertTokenList($form, ['term']);
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  protected function buildDescription($entity) {
    $description = \Drupal::token()->replace($this->configuration['result_description'], ['term' => $entity], []);
    return LinkitXss::descriptionFilter($description);
  }

}
