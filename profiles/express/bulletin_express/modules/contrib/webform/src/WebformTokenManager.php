<?php

namespace Drupal\webform;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Utility\Token;

/**
 * Defines a class to manage token replacement.
 */
class WebformTokenManager implements WebformTokenManagerInterface {

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The token service.
   *
   * @var \Drupal\Core\Utility\Token
   */
  protected $token;

  /**
   * Constructs a WebformTokenManager object.
   *
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\Core\Utility\Token $token
   *   The token service.
   */
  public function __construct(ModuleHandlerInterface $module_handler, Token $token) {
    $this->moduleHandler = $module_handler;
    $this->token = $token;
  }

  /**
   * {@inheritdoc}
   */
  public function replace($text, EntityInterface $entity = NULL, array $data = [], array $options = []) {
    // Replace tokens within an array.
    if (is_array($text)) {
      foreach ($text as $key => $value) {
        $text[$key] = $this->replace($value, $entity);
      }
      return $text;
    }

    // Most strings won't contain tokens so let's check and return ASAP.
    if (!is_string($text) || strpos($text, '[') === FALSE) {
      return $text;
    }

    // Set token options.
    $options += ['clear' => TRUE];

    // Replace @deprecated [webform-submission] with [webform_submission].
    $text = str_replace('[webform-submission:', '[webform_submission:', $text);

    // Set token data based on entity type.
    $this->setTokenData($data, $entity);

    return $this->token->replace($text, $data, $options);
  }

  /**
   * {@inheritdoc}
   */
  public function buildTreeLink(array $token_types = ['webform', 'webform_submission']) {
    if ($this->moduleHandler->moduleExists('token')) {
      // @todo Issue #2235581: Make Token Dialog support inserting in WYSIWYGs.
      return [
        '#theme' => 'token_tree_link',
        '#token_types' => $token_types,
        '#click_insert' => FALSE,
        '#dialog' => TRUE,
      ];
    }
    else {
      return [];
    }
  }

  /**
   * Get token data based on an entity's type.
   *
   * @param array $token_data
   *   An array of token data.
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   A Webform or Webform submission entity.
   */
  protected function setTokenData(array &$token_data, EntityInterface $entity) {
    if ($entity instanceof WebformSubmissionInterface) {
      $token_data['webform_submission'] = $entity;
      $token_data['webform'] = $entity->getWebform();
    }
    elseif ($entity instanceof WebformInterface) {
      $token_data['webform'] = $entity;
    }
  }

}
