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

/**
 * Provides an alias cleaner.
 */
class AliasCleaner implements AliasCleanerInterface {

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
   * @todo Split this up into separate properties.
   *
   * @var array
   */
  protected $cleanStringCache = array();

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
    // this would result in a duplicate blackslash.
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
  public function cleanString($string, array $options = array()) {
    if (empty($this->cleanStringCache)) {
      // Generate and cache variables used in this method.
      $config = $this->configFactory->get('pathauto.settings');
      $this->cleanStringCache = array(
        'separator' => $config->get('separator'),
        'strings' => array(),
        'transliterate' => $config->get('transliterate'),
        'punctuation' => array(),
        'reduce_ascii' => (bool) $config->get('reduce_ascii'),
        'ignore_words_regex' => FALSE,
        'lowercase' => (bool) $config->get('case'),
        'maxlength' => min($config->get('max_component_length'), $this->aliasStorageHelper->getAliasSchemaMaxLength()),
      );

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
      $ignore_words_regex = preg_replace(array('/^[,\s]+|[,\s]+$/', '/[,\s]+/'), array('', '\b|\b'), $ignore_words);
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
      if (Unicode::strlen(trim($words_removed)) > 0) {
        $output = $words_removed;
      }
    }

    // Always replace whitespace with the separator.
    $output = preg_replace('/\s+/', $this->cleanStringCache['separator'], $output);

    // Trim duplicates and remove trailing and leading separators.
    $output = $this->getCleanSeparators($this->getCleanSeparators($output, $this->cleanStringCache['separator']));

    // Optionally convert to lower case.
    if ($this->cleanStringCache['lowercase']) {
      $output = Unicode::strtolower($output);
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
        $punctuation = array();
        $punctuation['double_quotes']      = array('value' => '"', 'name' => t('Double quotation marks'));
        $punctuation['quotes']             = array('value' => '\'', 'name' => t("Single quotation marks (apostrophe)"));
        $punctuation['backtick']           = array('value' => '`', 'name' => t('Back tick'));
        $punctuation['comma']              = array('value' => ',', 'name' => t('Comma'));
        $punctuation['period']             = array('value' => '.', 'name' => t('Period'));
        $punctuation['hyphen']             = array('value' => '-', 'name' => t('Hyphen'));
        $punctuation['underscore']         = array('value' => '_', 'name' => t('Underscore'));
        $punctuation['colon']              = array('value' => ':', 'name' => t('Colon'));
        $punctuation['semicolon']          = array('value' => ';', 'name' => t('Semicolon'));
        $punctuation['pipe']               = array('value' => '|', 'name' => t('Vertical bar (pipe)'));
        $punctuation['left_curly']         = array('value' => '{', 'name' => t('Left curly bracket'));
        $punctuation['left_square']        = array('value' => '[', 'name' => t('Left square bracket'));
        $punctuation['right_curly']        = array('value' => '}', 'name' => t('Right curly bracket'));
        $punctuation['right_square']       = array('value' => ']', 'name' => t('Right square bracket'));
        $punctuation['plus']               = array('value' => '+', 'name' => t('Plus sign'));
        $punctuation['equal']              = array('value' => '=', 'name' => t('Equal sign'));
        $punctuation['asterisk']           = array('value' => '*', 'name' => t('Asterisk'));
        $punctuation['ampersand']          = array('value' => '&', 'name' => t('Ampersand'));
        $punctuation['percent']            = array('value' => '%', 'name' => t('Percent sign'));
        $punctuation['caret']              = array('value' => '^', 'name' => t('Caret'));
        $punctuation['dollar']             = array('value' => '$', 'name' => t('Dollar sign'));
        $punctuation['hash']               = array('value' => '#', 'name' => t('Number sign (pound sign, hash)'));
        $punctuation['at']                 = array('value' => '@', 'name' => t('At sign'));
        $punctuation['exclamation']        = array('value' => '!', 'name' => t('Exclamation mark'));
        $punctuation['tilde']              = array('value' => '~', 'name' => t('Tilde'));
        $punctuation['left_parenthesis']   = array('value' => '(', 'name' => t('Left parenthesis'));
        $punctuation['right_parenthesis']  = array('value' => ')', 'name' => t('Right parenthesis'));
        $punctuation['question_mark']      = array('value' => '?', 'name' => t('Question mark'));
        $punctuation['less_than']          = array('value' => '<', 'name' => t('Less-than sign'));
        $punctuation['greater_than']       = array('value' => '>', 'name' => t('Greater-than sign'));
        $punctuation['slash']              = array('value' => '/', 'name' => t('Slash'));
        $punctuation['back_slash']         = array('value' => '\\', 'name' => t('Backslash'));

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
  public function cleanTokenValues(&$replacements, $data = array(), $options = array()) {
    foreach ($replacements as $token => $value) {
      // Only clean non-path tokens.
      if (!preg_match('/(path|alias|url|url-brief)\]$/', $token)) {
        $replacements[$token] = $this->cleanString($value, $options);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function resetCaches() {
    $this->cleanStringCache = array();
  }

}
