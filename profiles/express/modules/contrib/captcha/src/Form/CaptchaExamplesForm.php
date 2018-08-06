<?php

namespace Drupal\captcha\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;

/**
 * Displays the captcha settings form.
 */
class CaptchaExamplesForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'captcha_examples';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $module = NULL, $challenge = NULL) {
    module_load_include('inc', 'captcha', 'captcha.admin');

    $form = [];
    if ($module && $challenge) {
      // Generate 10 example challenges.
      for ($i = 0; $i < 10; $i++) {
        $form["challenge_{$i}"] = _captcha_generate_example_challenge($module, $challenge);
      }
    }
    else {
      // Generate a list with examples of the available CAPTCHA types.
      $form['info'] = [
        '#markup' => $this->t('This page gives an overview of all available challenge types, generated with their current settings.'),
      ];

      $modules_list = \Drupal::moduleHandler()->getImplementations('captcha');
      foreach ($modules_list as $mkey => $module) {
        $challenges = call_user_func_array($module . '_captcha', ['list']);

        if ($challenges) {
          foreach ($challenges as $ckey => $challenge) {
            $form["captcha_{$mkey}_{$ckey}"] = [
              '#type' => 'details',
              '#title' => $this->t('Challenge %challenge by module %module', [
                '%challenge' => $challenge,
                '%module' => $module,
              ]),
              'challenge' => _captcha_generate_example_challenge($module, $challenge),
              'more_examples' => [
                '#markup' => Link::fromTextAndUrl($this->t('10 more examples of this challenge.'), Url::fromRoute('captcha_examples', [
                  'module' => $module,
                  'challenge' => $challenge,
                ]))->toString(),
              ],
            ];
          }
        }
      }
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

  }

}
