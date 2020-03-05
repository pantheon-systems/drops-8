<?php

namespace Drupal\pathauto;

use Drupal\Component\Render\PlainTextOutput;
use Drupal\Component\Transliteration\TransliterationInterface;
use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\Unicode;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Provides an alias cleaner.
 */
class AliasCleaner implements AliasCleanerInterface {

  use StringTranslationTrait;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The alias storage helper.
   *
   * @var AliasStorageHelperInterface
   */
  protected $aliasStorageHelper;

  /**
   * Language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * Cache backend.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cacheBackend;

  /**
   * Calculated settings cache.
   *
   * @var array
   *
   * @todo Split this up into separate properties.
   */
  protected $cleanStringCache = [];

  /**
   * Transliteration service.
   *
   * @var \Drupal\Component\Transliteration\TransliterationInterface
   */
  protected $transliteration;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Creates a new AliasCleaner.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\pathauto\AliasStorageHelperInterface $alias_storage_helper
   *   The alias storage helper.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   The cache backend.
   * @param \Drupal\Component\Transliteration\TransliterationInterface $transliteration
   *   The transliteration service.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   */
  public function __construct(ConfigFactoryInterface $config_factory, AliasStorageHelperInterface $alias_storage_helper, LanguageManagerInterface $language_manager, CacheBackendInterface $cache_backend, TransliterationInterface $transliteration, ModuleHandlerInterface $module_handler) {
    $this->configFactory = $config_factory;
    $this->aliasStorageHelper = $alias_storage_helper;
    $this->languageManager = $language_manager;
    $this->cacheBackend = $cache_backend;
    $this->transliteration = $transliteration;
    $this->moduleHandler = $module_handler;
  }

  /**
   * {@inheritdoc}
   */
  public function cleanAlias($alias) {
    $config = $this->configFactory->get('pathauto.settings');
    $alias_max_length = min($config->get('max_length'), $this->aliasStorageHelper->getAliasSchemaMaxLength());

    $output = $alias;

    // Trim duplicate, leading, and trailing separators. Do this before cleaning
    // backslashes since a pattern like "[token1]/[token2]-[token3]/[token4]"
    // could end up like "value1/-/value2" and if backslashes were cleaned first
    // this would result in a duplicate backslash.
    $output = $this->getCleanSeparators($output);

    // Trim duplicate, leading, and trailing backslashes.
    $output = $this->getCleanSeparators($output, '/');

    // Shorten to a logical place based on word boundaries.
    $output = Unicode::truncate($output, $alias_max_length, TRUE);

    return $output;
  }

  /**
   * {@inheritdoc}
   */
  public function getCleanSeparators($string, $separator = NULL) {
    $config = $this->configFactory->get('pathauto.settings');

    if (!isset($separator)) {
      $separator = $config->get('separator');
    }

    $output = $string;

    if (strlen($separator)) {
      // Trim any leading or trailing separators.
      $output = trim($output, $separator);

      // Escape the separator for use in regular expressions.
      $seppattern = preg_quote($separator, '/');

      // Replace multiple separators with a single one.
      $output = preg_replace("/$seppattern+/", $separator, $output);

      // Replace trailing separators around slashes.
      if ($separator !== '/') {
        $output = preg_replace("/\/+$seppattern\/+|$seppattern\/+|\/+$seppattern/", "/", $output);
      }
      else {
        // If the separator is a slash, we need to re-add the leading slash
        // dropped by the trim function.
        $output = '/' . $output;
      }
    }

    return $output;
  }

  /**
   * {@inheritdoc}
   */
  public function cleanString($string, array $options = []) {
    if (empty($this->cleanStringCache)) {
      // Generate and cache variables used in this method.
      $config = $this->configFactory->get('pathauto.settings');
      $this->cleanStringCache = [
        'separator' => $config->get('separator'),
        'strings' => [],
        'transliterate' => $config->get('transliterate'),
        'punctuation' => [],
        'reduce_ascii' => (bool) $config->get('reduce_ascii'),
        'ignore_words_regex' => FALSE,
        'lowercase' => (bool) $config->get('case'),
        'maxlength' => min($config->get('max_component_length'), $this->aliasStorageHelper->getAliasSchemaMaxLength()),
      ];

      // Generate and cache the punctuation replacements for strtr().
      $punctuation = $this->getPunctuationCharacters();
      foreach ($punctuation as $name => $details) {
        $action = $config->get('punctuation.' . $name);
        switch ($action) {
          case PathautoGeneratorInterface::PUNCTUATION_REMOVE:
            $this->cleanStringCache['punctuation'][$details['value']] = '';
            break;

          case PathautoGeneratorInterface::PUNCTUATION_REPLACE:
            $this->cleanStringCache['punctuation'][$details['value']] = $this->cleanStringCache['separator'];
            break;

          case PathautoGeneratorInterface::PUNCTUATION_DO_NOTHING:
            // Literally do nothing.
            break;
        }
      }

      // Generate and cache the ignored words regular expression.
      $ignore_words = $config->get('ignore_words');
      $ignore_words_regex = preg_replace(['/^[,\s]+|[,\s]+$/', '/[,\s]+/'], ['', '\b|\b'], $ignore_words);
      if ($ignore_words_regex) {
        $this->cleanStringCache['ignore_words_regex'] = '\b' . $ignore_words_regex . '\b';
        if (function_exists('mb_eregi_replace')) {
          mb_regex_encoding('UTF-8');
          $this->cleanStringCache['ignore_words_callback'] = 'mb_eregi_replace';
        }
        else {
          $this->cleanStringCache['ignore_words_callback'] = 'preg_replace';
          $this->cleanStringCache['ignore_words_regex'] = '/' . $this->cleanStringCache['ignore_words_regex'] . '/i';
        }
      }
    }

    // Empty strings do not need any processing.
    if ($string === '' || $string === NULL) {
      return '';
    }

    $langcode = NULL;
    if (!empty($options['language'])) {
      $langcode = $options['language']->getId();
    }
    elseif (!empty($options['langcode'])) {
      $langcode = $options['langcode'];
    }

    // Check if the string has already been processed, and if so return the
    // cached result.
    if (isset($this->cleanStringCache['strings'][$langcode][(string) $string])) {
      return $this->cleanStringCache['strings'][$langcode][(string) $string];
    }

    // Remove all HTML tags from the string.
    $output = Html::decodeEntities($string);
    $output = PlainTextOutput::renderFromHtml($output);

    // Optionally transliterate.
    if ($this->cleanStringCache['transliterate']) {
      // If the reduce strings to letters and numbers is enabled, don't bother
      // replacing unknown characters with a question mark. Use an empty string
      // instead.
      $output = $this->transliteration->transliterate($output, $langcode, $this->cleanStringCache['reduce_ascii'] ? '' : '?');
    }

    // Replace or drop punctuation based on user settings.
    $output = strtr($output, $this->cleanStringCache['punctuation']);

    // Reduce strings to letters and numbers.
    if ($this->cleanStringCache['reduce_ascii']) {
      $output = preg_replace('/[^a-zA-Z0-9\/]+/', $this->cleanStringCache['separator'], $output);
    }

    // Get rid of words that are on the ignore list.
    if ($this->cleanStringCache['ignore_words_regex']) {
      $words_removed = $this->cleanStringCache['ignore_words_callback']($this->cleanStringCache['ignore_words_regex'], '', $output);
      if (mb_strlen(trim($words_removed)) > 0) {
        $output = $words_removed;
      }
    }

    // Always replace whitespace with the separator.
    $output = preg_replace('/\s+/', $this->cleanStringCache['separator'], $output);

    // Trim duplicates and remove trailing and leading separators.
    $output = $this->getCleanSeparators($this->getCleanSeparators($output, $this->cleanStringCache['separator']));

    // Optionally convert to lower case.
    if ($this->cleanStringCache['lowercase']) {
      $output = mb_strtolower($output);
    }

    // Shorten to a logical place based on word boundaries.
    $output = Unicode::truncate($output, $this->cleanStringCache['maxlength'], TRUE);

    // Cache this result in the static array.
    $this->cleanStringCache['strings'][$langcode][(string) $string] = $output;

    return $output;
  }

  /**
   * {@inheritdoc}
   */
  public function getPunctuationCharacters() {
    if (empty($this->punctuationCharacters)) {
      $langcode = $this->languageManager->getCurrentLanguage()->getId();

      $cid = 'pathauto:punctuation:' . $langcode;
      if ($cache = $this->cacheBackend->get($cid)) {
        $this->punctuationCharacters = $cache->data;
      }
      else {
        $punctuation                      = [];
        $punctuation['double_quotes']     = ['value' => '"', 'name' => $this->t('Double quotation marks')];
        $punctuation['quotes']            = ['value' => '\'', 'name' => $this->t("Single quotation marks (apostrophe)")];
        $punctuation['backtick']          = ['value' => '`', 'name' => $this->t('Back tick')];
        $punctuation['comma']             = ['value' => ',', 'name' => $this->t('Comma')];
        $punctuation['period']            = ['value' => '.', 'name' => $this->t('Period')];
        $punctuation['hyphen']            = ['value' => '-', 'name' => $this->t('Hyphen')];
        $punctuation['underscore']        = ['value' => '_', 'name' => $this->t('Underscore')];
        $punctuation['colon']             = ['value' => ':', 'name' => $this->t('Colon')];
        $punctuation['semicolon']         = ['value' => ';', 'name' => $this->t('Semicolon')];
        $punctuation['pipe']              = ['value' => '|', 'name' => $this->t('Vertical bar (pipe)')];
        $punctuation['left_curly']        = ['value' => '{', 'name' => $this->t('Left curly bracket')];
        $punctuation['left_square']       = ['value' => '[', 'name' => $this->t('Left square bracket')];
        $punctuation['right_curly']       = ['value' => '}', 'name' => $this->t('Right curly bracket')];
        $punctuation['right_square']      = ['value' => ']', 'name' => $this->t('Right square bracket')];
        $punctuation['plus']              = ['value' => '+', 'name' => $this->t('Plus sign')];
        $punctuation['equal']             = ['value' => '=', 'name' => $this->t('Equal sign')];
        $punctuation['asterisk']          = ['value' => '*', 'name' => $this->t('Asterisk')];
        $punctuation['ampersand']         = ['value' => '&', 'name' => $this->t('Ampersand')];
        $punctuation['percent']           = ['value' => '%', 'name' => $this->t('Percent sign')];
        $punctuation['caret']             = ['value' => '^', 'name' => $this->t('Caret')];
        $punctuation['dollar']            = ['value' => '$', 'name' => $this->t('Dollar sign')];
        $punctuation['hash']              = ['value' => '#', 'name' => $this->t('Number sign (pound sign, hash)')];
        $punctuation['at']                = ['value' => '@', 'name' => $this->t('At sign')];
        $punctuation['exclamation']       = ['value' => '!', 'name' => $this->t('Exclamation mark')];
        $punctuation['tilde']             = ['value' => '~', 'name' => $this->t('Tilde')];
        $punctuation['left_parenthesis']  = ['value' => '(', 'name' => $this->t('Left parenthesis')];
        $punctuation['right_parenthesis'] = ['value' => ')', 'name' => $this->t('Right parenthesis')];
        $punctuation['question_mark']     = ['value' => '?', 'name' => $this->t('Question mark')];
        $punctuation['less_than']         = ['value' => '<', 'name' => $this->t('Less-than sign')];
        $punctuation['greater_than']      = ['value' => '>', 'name' => $this->t('Greater-than sign')];
        $punctuation['slash']             = ['value' => '/', 'name' => $this->t('Slash')];
        $punctuation['back_slash']        = ['value' => '\\', 'name' => $this->t('Backslash')];

        // Allow modules to alter the punctuation list and cache the result.
        $this->moduleHandler->alter('pathauto_punctuation_chars', $punctuation);
        $this->cacheBackend->set($cid, $punctuation);
        $this->punctuationCharacters = $punctuation;
      }
    }

    return $this->punctuationCharacters;
  }

  /**
   * {@inheritdoc}
   */
  public function cleanTokenValues(&$replacements, $data = [], $options = []) {
    foreach ($replacements as $token => $value) {
      // Only clean non-path tokens.
      $config = $this->configFactory->get('pathauto.settings');
      $safe_tokens = implode('|', (array) $config->get('safe_tokens'));
      if (!preg_match('/(\[|\:)(' . $safe_tokens . ')(:|\]$)/', $token)) {
        $replacements[$token] = $this->cleanString($value, $options);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function resetCaches() {
    $this->cleanStringCache = [];
  }

}
