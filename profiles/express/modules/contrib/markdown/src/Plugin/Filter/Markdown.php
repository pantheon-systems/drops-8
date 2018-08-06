<?php

namespace Drupal\markdown\Plugin\Filter;

use Drupal\Core\Form\FormStateInterface;
use Drupal\filter\FilterProcessResult;
use Drupal\filter\Plugin\FilterBase;
use Drupal\Core\Url;
use Michelf\MarkdownExtra;
use League\CommonMark\CommonMarkConverter;

/**
 * Provides a filter for markdown.
 *
 * @Filter(
 *   id = "markdown",
 *   module = "markdown",
 *   title = @Translation("Markdown"),
 *   description = @Translation("Allows content to be submitted using Markdown, a simple plain-text syntax that is filtered into valid HTML."),
 *   type = Drupal\filter\Plugin\FilterInterface::TYPE_MARKUP_LANGUAGE,
 *   settings = {
 *     "markdown_library" = "php-markdown"
 *   },
 * )
 */
class Markdown extends FilterBase {

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $libraries_options = array();

    if (class_exists('Michelf\MarkdownExtra')) {
      $libraries_options['php-markdown'] = 'PHP Markdown';
    }
    elseif (\Drupal::moduleHandler()->moduleExists('libraries')) {
      $library = libraries_detect('php-markdown');
      if (!empty($library['installed'])) {
        $libraries_options['php-markdown'] = 'PHP Markdown';
      }
    }

    if (class_exists('League\CommonMark\CommonMarkConverter')) {
      $libraries_options['commonmark'] = 'Commonmark';
    }

    if (!empty($libraries_options)) {
      $form['markdown_library'] = array(
        '#type' => 'select',
        '#title' => $this->t('Markdown library'),
        '#options' => $libraries_options,
        '#default_value' => $this->settings['markdown_library'],
      );
    }
    else {
      $form['markdown_library'] = array(
        '#type' => 'item',
        '#title' => $this->t('No Markdown library found'),
        '#description' => $this->t('You need to use composer to install the <a href=":markdown_link">PHP Markdown Lib</a> and/or the <a href=":commonmark_link">CommonMark Lib</a>. Optionally you can use the Library module and place the PHP Markdown Lib in the root library directory, see more in README.', array(
          ':markdown_link' => 'https://packagist.org/packages/michelf/php-markdown',
          ':commonmark_link' => 'https://packagist.org/packages/league/commonmark',
        )),
      );
    }

    if (isset($library['name'])) {
      $form['markdown_status'] = array(
        '#title' => $this->t('Version'),
        '#theme' => 'item_list',
        '#items' => array(
          $library['name'] . ' ' . $library['version'],
        ),
      );
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function process($text, $langcode) {
    if (!empty($text)) {
      switch ($this->settings['markdown_library']) {
        case 'commonmark':
          $converter = new CommonMarkConverter();
          $text = $converter->convertToHtml($text);
          break;
        case 'php-markdown':
          if (!class_exists('Michelf\MarkdownExtra') && \Drupal::moduleHandler()->moduleExists('libraries')) {
            libraries_load('php-markdown', 'markdown-extra');
          }
          $text = MarkdownExtra::defaultTransform($text);
          break;
      }
    }

    return new FilterProcessResult($text);
  }

  /**
   * {@inheritdoc}
   */
  public function tips($long = FALSE) {
    if ($long) {
      return $this->t('Quick Tips:<ul>
      <li>Two or more spaces at a line\'s end = Line break</li>
      <li>Double returns = Paragraph</li>
      <li>*Single asterisks* or _single underscores_ = <em>Emphasis</em></li>
      <li>**Double** or __double__ = <strong>Strong</strong></li>
      <li>This is [a link](http://the.link.example.com "The optional title text")</li>
      </ul>For complete details on the Markdown syntax, see the <a href="http://daringfireball.net/projects/markdown/syntax">Markdown documentation</a> and <a href="http://michelf.com/projects/php-markdown/extra/">Markdown Extra documentation</a> for tables, footnotes, and more.');
    }
    else {
      return $this->t('You can use <a href="@filter_tips">Markdown syntax</a> to format and style the text. Also see <a href="@markdown_extra">Markdown Extra</a> for tables, footnotes, and more.', array(
        '@filter_tips' => Url::fromRoute('filter.tips_all')->toString(),
        '@markdown_extra' => 'http://michelf.com/projects/php-markdown/extra/',
      ));
    }
  }

}
