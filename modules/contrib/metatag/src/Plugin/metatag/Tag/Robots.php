<?php

namespace Drupal\metatag\Plugin\metatag\Tag;

/**
 * The basic "Robots" meta tag.
 *
 * @MetatagTag(
 *   id = "robots",
 *   label = @Translation("Robots"),
 *   description = @Translation("Provides search engines with specific directions for what to do when this page is indexed."),
 *   name = "robots",
 *   group = "advanced",
 *   weight = 1,
 *   type = "string",
 *   secure = FALSE,
 *   multiple = FALSE
 * )
 */
class Robots extends MetaNameBase {

  /**
   * Sets the value of this tag.
   *
   * @param string|array $value
   *   The value to set to this tag.
   *   It can be an array if it comes from a form submission or from field
   *   defaults, in which case
   *   we transform it to a comma-separated string.
   */
  public function setValue($value) {
    if (is_array($value)) {
      $value = array_filter($value);
      $value = implode(', ', array_keys($value));
    }
    $this->value = $value;
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $element = []) {
    // Prepare the default value as it is stored as a string.
    $default_value = [];
    if (!empty($this->value)) {
      $default_value = explode(', ', $this->value);
    }

    $form = [
      '#type' => 'checkboxes',
      '#title' => $this->label(),
      '#description' => $this->description(),
      '#options' => [
        'index' => t('index - Allow search engines to index this page (assumed).'),
        'follow' => t('follow - Allow search engines to follow links on this page (assumed).'),
        'noindex' => t('noindex - Prevents search engines from indexing this page.'),
        'nofollow' => t('nofollow - Prevents search engines from following links on this page.'),
        'noarchive' => t('noarchive - Prevents cached copies of this page from appearing in search results.'),
        'nosnippet' => t('nosnippet - Prevents descriptions from appearing in search results, and prevents page caching.'),
        'noodp' => t('noodp - Blocks the <a href=":opendirectory">Open Directory Project</a> description from appearing in search results.', [':opendirectory' => 'http://www.dmoz.org/']),
        'noydir' => t('noydir - Prevents Yahoo! from listing this page in the <a href=":ydir">Yahoo! Directory</a>.', [':ydir' => 'http://dir.yahoo.com/']),
        'noimageindex' => t('noimageindex - Prevent search engines from indexing images on this page.'),
        'notranslate' => t('notranslate - Prevent search engines from offering to translate this page in search results.'),
      ],
      '#default_value' => $default_value,
      '#required' => isset($element['#required']) ? $element['#required'] : FALSE,
      '#element_validate' => [[get_class($this), 'validateTag']],
    ];

    return $form;
  }

}
