<?php

/**
 * @file
 * Contains \Drupal\smart_trim\Plugin\Field\FieldFormatter\SmartTrimFormatter.
 */

namespace Drupal\smart_trim\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Component\Utility\Unicode;
use Drupal\smart_trim\Truncate\TruncateHTML;

/**
 * Plugin implementation of the 'smart_trim' formatter.
 *
 * @FieldFormatter(
 *   id = "smart_trim",
 *   label = @Translation("Smart trimmed"),
 *   field_types = {
 *     "text",
 *     "text_long",
 *     "text_with_summary"
 *   },
 *   settings = {
 *     "trim_length" = "300",
 *     "trim_type" = "chars",
 *     "trim_suffix" = "...",
 *     "more_link" = FALSE,
 *     "more_text" = "Read more",
 *     "summary_handler" = "full",
 *     "trim_options" = ""
 *   }
 * )
 */
class SmartTrimFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return array(
      'trim_length' => '600',
      'trim_type' => 'chars',
      'trim_suffix' => '',
      'more_link' => 0,
      'more_class' => 'more-link',
      'more_text' => 'More',
      'summary_handler' => 'full',
      'trim_options' => array(),
    ) + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $element = parent::settingsForm($form, $form_state);

    $element['trim_length'] = array(
      '#title' => t('Trim length'),
      '#type' => 'textfield',
      '#size' => 10,
      '#default_value' => $this->getSetting('trim_length'),
      '#min' => 0,
      '#required' => TRUE,
    );

    $element['trim_type'] = array(
      '#title' => t('Trim units'),
      '#type' => 'select',
      '#options' => array(
        'chars' => t("Characters"),
        'words' => t("Words"),
      ),
      '#default_value' => $this->getSetting('trim_type'),
    );

    $element['trim_suffix'] = array(
      '#title' => t('Suffix'),
      '#type' => 'textfield',
      '#size' => 10,
      '#default_value' => $this->getSetting('trim_suffix'),
    );

    $element['more_link'] = array(
      '#title' => t('Display more link?'),
      '#type' => 'select',
      '#options' => array(
        0 => t("No"),
        1 => t("Yes"),
      ),
      '#default_value' => $this->getSetting('more_link'),
      '#description' => t('Displays a link to the entity (if one exists)'),
    );

    $element['more_text'] = array(
      '#title' => t('More link text'),
      '#type' => 'textfield',
      '#size' => 20,
      '#default_value' => $this->getSetting('more_text'),
      '#description' => t('If displaying more link, enter the text for the link.'),
    );

    if ($this->fieldDefinition->getType() == 'text_with_summary') {
      $element['summary_handler'] = array(
        '#title' => t('Summary'),
        '#type' => 'select',
        '#options' => array(
          'full' => t("Use summary if present, and do not trim"),
          'trim' => t("Use summary if present, honor trim settings"),
          'ignore' => t("Do not use summary"),
        ),
        '#default_value' => $this->getSetting('summary_handler'),
      );
    }

    $trim_options_value = $this->getSetting('trim_options');
    $element['trim_options'] = array(
      '#title' => t('Additional options'),
      '#type' => 'checkboxes',
      '#options' => array(
        'text' => t('Strip HTML'),
      ),
      '#default_value' => empty($trim_options_value) ? array() : $trim_options_value,
    );

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $unicode = new Unicode();
    $summary = array();
    $type = t('words');
    if ($this->getSetting('trim_type') == 'chars') {
      $type = t('characters');
    }
    $trim_string = $this->getSetting('trim_length') . ' ' . $type;

    if ($unicode->strlen((trim($this->getSetting('trim_suffix'))))) {
      $trim_string .= " " . t("with suffix");
    }
    if ($this->getSetting('more_link')) {
      $trim_string .= ", " . t("with more link");
    }
    $summary[] = $trim_string;

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode = NULL) {

    $element = array();
    $setting_trim_options = $this->getSetting('trim_options');
    $settings_summary_handler = $this->getSetting('summary_handler');
    $entity = $items->getEntity();

    foreach ($items as $delta => $item) {
      if ($settings_summary_handler != 'ignore' && !empty($item->summary)) {
        $output = $item->summary_processed;
      }
      else {
        $output = $item->processed;
      }

      // Process additional options (currently only HTML on/off).
      if (!empty($setting_trim_options)) {
        if (!empty($setting_trim_options['text'])) {
          // Strip tags.
          $output = strip_tags(str_replace('<', ' <', $output));

          // Strip out line breaks.
          $output = preg_replace('/\n|\r|\t/m', ' ', $output);

          // Strip out non-breaking spaces.
          $output = str_replace('&nbsp;', ' ', $output);
          $output = str_replace("\xc2\xa0", ' ', $output);

          // Strip out extra spaces.
          $output = trim(preg_replace('/\s\s+/', ' ', $output));
        }
      }

      // Make the trim, provided we're not showing a full summary.
      if ($this->getSetting('summary_handler') != 'full' || empty($item->summary)) {
        $truncate = new TruncateHTML();
        $length = $this->getSetting('trim_length');
        $ellipse = $this->getSetting('trim_suffix');
        if ($this->getSetting('trim_type') == 'words') {
          $output = $truncate->truncateWords($output, $length, $ellipse);
        }
        else {
          $output = $truncate->truncateChars($output, $length, $ellipse);
        }
      }

      // Add the link, if there is one!
      $link = '';
      $uri = $entity->toUrl();
      // But wait! Don't add a more link if the field ends in <!--break-->.
      if ($uri && $this->getSetting('more_link') && strpos(strrev($output), strrev('<!--break-->')) !== 0) {
        $more = $this->getSetting('more_text');
        $class = $this->getSetting('more_text');

        $project_link = Link::fromTextAndUrl($more, $uri);
        $project_link = $project_link->toRenderable();
        $project_link['#attributes'] = array(
          'class' => array(
            $class,
          ),
        );
        $link = render($project_link);
      }
      $output .= $link;
      $element[$delta] = array('#markup' => $output);
    }
    return $element;
  }

}
