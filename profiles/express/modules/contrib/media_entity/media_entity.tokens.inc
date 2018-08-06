<?php

/**
 * @file
 * Builds placeholder replacement tokens for media_entity-related data.
 */

use Drupal\Component\Utility\Html;
use Drupal\Core\Datetime\Entity\DateFormat;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Render\BubbleableMetadata;
use Drupal\media_entity\Entity\MediaBundle;

/**
 * Implements hook_token_info().
 */
function media_entity_token_info() {
  $type = [
    'name' => t('Media'),
    'description' => t('Tokens related to individual media items.'),
    'needs-data' => 'media',
  ];

  // Core tokens for media.
  $media['mid'] = [
    'name' => t("Media ID"),
    'description' => t('The unique ID of the media item.'),
  ];
  $media['uuid'] = [
    'name' => t("Media UUID"),
    'description' => t('The unique UUID of the media item.'),
  ];
  $media['vid'] = [
    'name' => t("Revision ID"),
    'description' => t("The unique ID of the media's latest revision."),
  ];
  $media['bundle'] = [
    'name' => t("Media bundle"),
  ];
  $media['bundle-name'] = [
    'name' => t("Media bundle name"),
    'description' => t("The human-readable name of the media bundle."),
  ];
  $media['langcode'] = [
    'name' => t('Language code'),
    'description' => t('The language code of the language the media is written in.'),
  ];
  $media['name'] = [
    'name' => t('Name'),
    'description' => t('The name of this media.'),
  ];
  $media['type'] = [
    'name' => t("Type"),
    'description' => t("The type of this media."),
  ];
  $node['author'] = [
    'name' => t("Author"),
    'type' => 'user',
  ];
  $media['url'] = [
    'name' => t("URL"),
    'description' => t("The URL of the media."),
  ];
  $media['edit-url'] = [
    'name' => t("Edit URL"),
    'description' => t("The URL of the media's edit page."),
  ];

  // Chained tokens for media.
  $media['created'] = [
    'name' => t("Date created"),
    'type' => 'date',
  ];
  $media['changed'] = [
    'name' => t("Date changed"),
    'description' => t("The date the media was most recently updated."),
    'type' => 'date',
  ];

  return [
    'types' => ['media' => $type],
    'tokens' => ['media' => $media],
  ];
}

/**
 * Implements hook_tokens().
 */
function media_entity_tokens($type, $tokens, array $data, array $options, BubbleableMetadata $bubbleable_metadata) {
  $token_service = \Drupal::token();

  $url_options = ['absolute' => TRUE];
  if (isset($options['langcode'])) {
    $url_options['language'] = \Drupal::languageManager()->getLanguage($options['langcode']);
    $langcode = $options['langcode'];
  }
  else {
    $langcode = LanguageInterface::LANGCODE_DEFAULT;
  }
  $sanitize = !empty($options['sanitize']);

  $replacements = [];

  if ($type == 'media' && !empty($data['media'])) {
    /** @var \Drupal\media_entity\MediaInterface $media */
    $media = \Drupal::service('entity.repository')->getTranslationFromContext($data['media'], $langcode, ['operation' => 'media_entity_tokens']);

    foreach ($tokens as $name => $original) {
      switch ($name) {
        // Simple key values on the media_entity.
        case 'mid':
          $replacements[$original] = $media->id();
          break;

        case 'uuid':
          $replacements[$original] = $media->uuid();
          break;

        case 'vid':
          $replacements[$original] = $media->getRevisionId();
          break;

        case 'bundle':
          $replacements[$original] = $sanitize ? Html::escape($media->bundle()) : $media->bundle();
          break;

        case 'bundle-name':
          $bundle_name = MediaBundle::load($media->bundle())->label();
          $replacements[$original] = $sanitize ? Html::escape($bundle_name) : $bundle_name;
          break;

        case 'langcode':
          $replacements[$original] = $sanitize ? Html::escape($media->language()->getId()) : $media->language()->getId();
          break;

        case 'name':
          $media_name = $media->get('name')->value;
          $replacements[$original] = $sanitize ? Html::escape($media_name) : $media_name;
          break;

        case 'type':
          $media_type = $media->get('name')->value;
          $replacements[$original] = $sanitize ? Html::escape($media_type) : $media_type;
          break;

        case 'url':
          $replacements[$original] = $media->toUrl('canonical', $url_options);
          break;

        case 'edit-url':
          $replacements[$original] = $media->toUrl('edit-form', $url_options);
          break;

        // Default values for the chained tokens handled below.
        case 'author':
          /** @var \Drupal\user\UserInterface $account */
          $account = $media->get('uid')->entity;
          $bubbleable_metadata->addCacheableDependency($account);
          $replacements[$original] = $sanitize ? Html::escape($account->label()) : $account->label();
          break;

        case 'created':
          $date_format = DateFormat::load('medium');
          $bubbleable_metadata->addCacheableDependency($date_format);
          $replacements[$original] = \Drupal::service('date.formatter')
            ->format($media->getCreatedTime(), 'medium', '', NULL, $langcode);
          break;

        case 'changed':
          $date_format = DateFormat::load('medium');
          $bubbleable_metadata->addCacheableDependency($date_format);
          $replacements[$original] = \Drupal::service('date.formatter')
            ->format($media->getChangedTime(), 'medium', '', NULL, $langcode);
          break;
      }
    }

    if ($author_tokens = $token_service->findWithPrefix($tokens, 'author')) {
      $account = $media->get('uid')->entity;
      $replacements += $token_service->generate('user', $author_tokens, ['user' => $account], $options, $bubbleable_metadata);
    }

    if ($created_tokens = $token_service->findWithPrefix($tokens, 'created')) {
      $replacements += $token_service->generate('date', $created_tokens, ['date' => $media->getCreatedTime()], $options, $bubbleable_metadata);
    }

    if ($changed_tokens = $token_service->findWithPrefix($tokens, 'changed')) {
      $replacements += $token_service->generate('date', $changed_tokens, ['date' => $media->getChangedTime()], $options, $bubbleable_metadata);
    }
  }

  return $replacements;
}
