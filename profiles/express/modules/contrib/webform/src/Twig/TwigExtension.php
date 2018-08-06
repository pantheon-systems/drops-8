<?php

namespace Drupal\webform\Twig;

use Drupal\Component\Utility\Html;
use Drupal\Core\Entity\EntityInterface;
use Drupal\webform\Utility\WebformHtmlHelper;

/**
 * Twig extension with some useful functions and filters.
 */
class TwigExtension extends \Twig_Extension {

  /**
   * {@inheritdoc}
   */
  public function getFunctions() {
    return [
      new \Twig_SimpleFunction('webform_token', [$this, 'webformToken']),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getName() {
    return 'webform';
  }

  /**
   * Replace tokens in text.
   *
   * @param string|array $token
   *   A string of text that may contain tokens.
   * @param \Drupal\Core\Entity\EntityInterface|null $entity
   *   A Webform or Webform submission entity.
   * @param array $data
   *   (optional) An array of keyed objects.
   * @param array $options
   *   (optional) A keyed array of settings and flags to control the token
   *   replacement process.
   *
   * @return string|array
   *   Text or array with tokens replaced.
   *
   * @see \Drupal\Core\Utility\Token::replace
   */
  public function webformToken($token, EntityInterface $entity = NULL, array $data = [], array $options = []) {
    // Allow the webform_token function to be tested during validation without
    // a valid entity.
    if (!$entity) {
      return $token;
    }

    // IMPORTANT: We are not injecting the WebformTokenManager to prevent
    // errors being thrown when updating the Webform.module.
    // ISSUE. This TwigExtension is loaded on every page load, even when a
    // website is in maintenance mode.
    // @see https://www.drupal.org/node/2907960
    /** @var \Drupal\webform\WebformTokenManagerInterface $value */
    $value = \Drupal::service('webform.token_manager')->replace($token, $entity, $data, $options);

    // Must decode HTML entities which are going to re-encoded.
    $value = Html::decodeEntities($value);

    return (WebformHtmlHelper::containsHtml($value)) ? ['#markup' => $value] : $value;
  }

}
