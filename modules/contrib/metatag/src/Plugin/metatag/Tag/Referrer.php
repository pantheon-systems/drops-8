<?php

namespace Drupal\metatag\Plugin\metatag\Tag;

use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * The basic "Referrer policy" meta tag.
 *
 * @MetatagTag(
 *   id = "referrer",
 *   label = @Translation("Referrer policy"),
 *   description = @Translation("Indicate to search engines and other page scrapers whether or not links should be followed. See <a href='https://w3c.github.io/webappsec/specs/referrer-policy/'>the W3C specifications</a> for further details."),
 *   name = "referrer",
 *   group = "advanced",
 *   weight = 5,
 *   type = "label",
 *   secure = FALSE,
 *   multiple = FALSE
 * )
 */
class Referrer extends MetaNameBase {

  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public function form(array $element = []) {
    $form = [
      '#type' => 'select',
      '#title' => $this->label(),
      '#description' => $this->description(),
      '#options' => [
        'no-referrer' => $this->t('No Referrer'),
        'no-referrer-when-downgrade' => $this->t('No Referrer When Downgrade'),
        'origin' => $this->t('Origin'),
        'origin-when-cross-origin' => $this->t('Origin When Cross-Origin'),
        'same-origin' => $this->t('Same Origin'),
        'strict-origin' => $this->t('Strict Origin'),
        'strict-origin-when-cross-origin' => $this->t('Strict Origin When Cross-Origin'),
        'unsafe-url' => $this->t('Unsafe URL'),
      ],
      '#empty_option' => $this->t('- None -'),
      '#empty_value' => '',
      '#default_value' => $this->value(),
      '#required' => isset($element['#required']) ? $element['#required'] : FALSE,
      '#element_validate' => [[get_class($this), 'validateTag']],
    ];

    return $form;
  }

}
