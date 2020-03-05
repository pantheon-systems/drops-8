<?php

namespace Drupal\Tests\token\Functional;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Language\Language;
use Drupal\Core\Render\BubbleableMetadata;

/**
 * Helper test trait with some added functions for testing.
 */
trait TokenTestTrait {

  function assertToken($type, array $data, $token, $expected, array $options = []) {
    return $this->assertTokens($type, $data, [$token => $expected], $options);
  }

  function assertTokens($type, array $data, array $tokens, array $options = []) {
    $input = $this->mapTokenNames($type, array_keys($tokens));
    $bubbleable_metadata = new BubbleableMetadata();
    $replacements = \Drupal::token()->generate($type, $input, $data, $options, $bubbleable_metadata);
    foreach ($tokens as $name => $expected) {
      $token = $input[$name];
      if (!isset($expected)) {
        $this->assertTrue(!isset($replacements[$token]), t("Token value for @token was not generated.", ['@type' => $type, '@token' => $token]));
      }
      elseif (!isset($replacements[$token])) {
        $this->fail(t("Token value for @token was not generated.", ['@type' => $type, '@token' => $token]));
      }
      elseif (!empty($options['regex'])) {
        $this->assertTrue(preg_match('/^' . $expected . '$/', $replacements[$token]), t("Token value for @token was '@actual', matching regular expression pattern '@expected'.", ['@type' => $type, '@token' => $token, '@actual' => $replacements[$token], '@expected' => $expected]));
      }
      else {
        $this->assertEquals($expected, $replacements[$token], t("Token value for @token was '@actual', expected value '@expected'.", ['@type' => $type, '@token' => $token, '@actual' => $replacements[$token], '@expected' => $expected]));
      }
    }

    return $replacements;
  }

  function mapTokenNames($type, array $tokens = []) {
    $return = [];
    foreach ($tokens as $token) {
      $return[$token] = "[$type:$token]";
    }
    return $return;
  }

  function assertNoTokens($type, array $data, array $tokens, array $options = []) {
    $input = $this->mapTokenNames($type, $tokens);
    $bubbleable_metadata = new BubbleableMetadata();
    $replacements = \Drupal::token()->generate($type, $input, $data, $options, $bubbleable_metadata);
    foreach ($tokens as $name) {
      $token = $input[$name];
      $this->assertTrue(!isset($replacements[$token]), t("Token value for @token was not generated.", ['@type' => $type, '@token' => $token]));
    }
  }

  function saveAlias($source, $alias, $language = Language::LANGCODE_NOT_SPECIFIED) {
    $alias = [
      'source' => $source,
      'alias' => $alias,
      'language' => $language,
    ];
    \Drupal::service('path.alias_storage')->save($alias['source'], $alias['alias']);
    return $alias;
  }

  function saveEntityAlias($entity_type, EntityInterface $entity, $alias, $language = Language::LANGCODE_NOT_SPECIFIED) {
    $uri = $entity->toUrl()->toArray();
    return $this->saveAlias($uri['path'], $alias, $language);
  }

  /**
   * Make a page request and test for token generation.
   */
  function assertPageTokens($url, array $tokens, array $data = [], array $options = []) {
    if (empty($tokens)) {
      return TRUE;
    }

    $token_page_tokens = [
      'tokens' => $tokens,
      'data' => $data,
      'options' => $options,
    ];
    \Drupal::state()->set('token_page_tokens', $token_page_tokens);

    $options += ['url_options' => []];
    $this->drupalGet($url, $options['url_options']);
    $this->refreshVariables();
    $result = \Drupal::state()->get('token_page_tokens', []);

    if (!isset($result['values']) || !is_array($result['values'])) {
      return $this->fail('Failed to generate tokens.');
    }

    foreach ($tokens as $token => $expected) {
      if (!isset($expected)) {
        $this->assertTrue(!isset($result['values'][$token]) || $result['values'][$token] === $token, t("Token value for @token was not generated.", ['@token' => $token]));
      }
      elseif (!isset($result['values'][$token])) {
        $this->fail(t('Failed to generate token @token.', ['@token' => $token]));
      }
      else {
        $this->assertIdentical($result['values'][$token], (string) $expected, t("Token value for @token was '@actual', expected value '@expected'.", ['@token' => $token, '@actual' => $result['values'][$token], '@expected' => $expected]));
      }
    }
  }

}
