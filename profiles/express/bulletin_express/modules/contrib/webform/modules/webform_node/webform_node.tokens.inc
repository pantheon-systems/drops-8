<?php

/**
 * @file
 * Builds placeholder replacement tokens for webform nodes.
 */

use Drupal\Core\Render\BubbleableMetadata;
use Drupal\node\NodeInterface;

/**
 * Implements hook_token_info().
 */
function webform_node_token_info() {
  $types['webform_submission'] = [
    'name' => t('Webform submissions'),
    'description' => t('Tokens related to webform submission.'),
    'needs-data' => 'webform_submission',
  ];
  $webform_submission['node'] = [
    'name' => t('Node'),
    'description' => t("The node that the webform was submitted from."),
    'type' => 'node',
  ];

  return [
    'types' => $types,
    'tokens' => [
      'webform_submission' => $webform_submission,
    ],
  ];
}

/**
 * Implements hook_tokens().
 */
function webform_node_tokens($type, $tokens, array $data, array $options, BubbleableMetadata $bubbleable_metadata) {
  $token_service = \Drupal::token();

  $replacements = [];

  if ($type == 'webform_submission' && !empty($data['webform_submission'])) {
    /** @var \Drupal\webform\WebformSubmissionInterface $webform_submission */
    $webform_submission = $data['webform_submission'];
    $source_entity = $webform_submission->getSourceEntity();
    if (!$source_entity || (!$source_entity instanceof NodeInterface)) {
      return $replacements;
    }

    foreach ($tokens as $name => $original) {
      switch ($name) {
        case 'node':
          $replacements[$original] = $source_entity->label();
          break;
      }
    }

    if ($entity_tokens = $token_service->findWithPrefix($tokens, 'node')) {
      $replacements += $token_service->generate('node', $entity_tokens, ['node' => $source_entity], $options, $bubbleable_metadata);
    }
  }

  return $replacements;
}
