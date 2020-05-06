<?php

namespace Drupal\webform\Twig;

use Drupal\Core\Entity\EntityInterface;
use Drupal\webform\Element\WebformMessage;
use Drupal\webform\Utility\WebformElementHelper;
use Drupal\webform\Utility\WebformHtmlHelper;
use Drupal\webform\Utility\WebformLogicHelper;
use Drupal\webform\Utility\WebformYaml;
use Drupal\webform\WebformSubmissionInterface;

/**
 * Twig extension with some useful functions and filters.
 */
class WebformTwigExtension extends \Twig_Extension {

  protected static $options = [
    'html' => 'webform_token_options_html',
    'email' => 'webform_token_options_email',
  ];

  /**
   * {@inheritdoc}
   */
  public function getFunctions() {
    return [
      new \Twig_SimpleFunction('webform_debug', [$this, 'webformDebug']),
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
   * Debug data by outputting YAML.
   *
   * @param mixed $data
   *   Data to be outputted.
   *
   * @return string
   *   Data serialized to YAML.
   */
  public function webformDebug($data) {
    try {
      if (is_array($data)) {
        WebformElementHelper::convertRenderMarkupToStrings($data);
        return WebformYaml::encode($data);
      }
      else {
        return $data;
      }
    }
    catch (\Exception $exception) {
      return $exception->getMessage();
    }
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

    $original_token = $token;
    if (WebformLogicHelper::startRecursionTracking($token) === FALSE) {
      return '';
    }

    // Parse options included in the token.
    // @see \Drupal\webform\Twig\TwigExtension::renderTwigTemplate
    foreach (static::$options as $option_name => $option_setting) {
      if (strpos($token, ":$option_setting")) {
        $options[$option_name] = TRUE;
        $token = str_replace(":$option_setting", '', $token);
      }
    }

    // IMPORTANT: We are not injecting the WebformTokenManager to prevent
    // errors being thrown when updating the Webform.module.
    // ISSUE. This TwigExtension is loaded on every page load, even when a
    // website is in maintenance mode.
    // @see https://www.drupal.org/node/2907960
    /** @var \Drupal\webform\WebformTokenManagerInterface $token_manager */
    $token_manager = \Drupal::service('webform.token_manager');
    $value = $token_manager->replace($token, $entity, $data, $options);

    if (WebformLogicHelper::stopRecursionTracking($original_token) === FALSE) {
      return '';
    }

    return (WebformHtmlHelper::containsHtml($value)) ? ['#markup' => $value] : $value;
  }

  /****************************************************************************/
  // Token methods used by the 'WebformComputedTwig' and 'EmailWebformHandler'.
  //
  // @see \Drupal\webform\Plugin\WebformElement\WebformComputedTwig
  // @see \Drupal\webform\Plugin\WebformHandler\EmailWebformHandler
  /****************************************************************************/

  /**
   * Build reusable Twig help.
   *
   * @param array $variables
   *   An array of available variable names.
   *
   * @return array
   *   A renderable array container Twig help.
   */
  public static function buildTwigHelp(array $variables = []) {
    /** @var \Drupal\webform\WebformSubmissionStorageInterface $submission_storage */
    $submission_storage = \Drupal::entityTypeManager()->getStorage('webform_submission');
    $field_definitions = $submission_storage->getFieldDefinitions();

    // Bold all the passed variable names.
    foreach ($variables as $index => $item) {
      $variables[$index] = ['#markup' => $item, '#prefix' => '<strong>', '#suffix' => '</strong>'];
    }

    $variables = array_merge($variables, [
      '{{ data.element_key }}',
      '{{ data[\'element_key\'] }}',
      '{{ data.element_key.delta }}',
      '{{ data[\'element_key\'][\'delta\'] }}',
      '{{ data.composite_element_key.subelement_key }}',
      '{{ data.composite_element_key.delta.subelement_key }}',
      '{{ original_data }}',
      '{{ elements }}',
      '{{ elements_flattened }}',
    ]);
    foreach (array_keys($field_definitions) as $field_name) {
      $variables[] = "{{ $field_name }}";
    }
    $variables = array_merge($variables, [
      '{{ webform }}',
      '{{ webform_submission }}',
    ]);

    $t_args = [
      ':twig_href' => 'https://twig.sensiolabs.org/',
      ':drupal_href' => 'https://www.drupal.org/docs/8/theming/twig',
    ];
    $output = [];
    $output[] = [
      '#markup' => '<p>' . t('Learn about <a href=":twig_href">Twig</a> and how it is used in <a href=":drupal_href">Drupal</a>.', $t_args) . '</p>',
    ];
    $output[] = [
      '#markup' => '<p>' . t("The following variables are available:") . '</p>',
    ];
    $output[] = [
      '#theme' => 'item_list',
      '#items' => $variables,
    ];
    $output[] = [
      '#markup' => '<p>' . t("You can also output tokens using the <code>webform_token()</code> function.") . '</p>',
    ];
    $output[] = [
      '#markup' => "<pre>{{ webform_token('[webform_submission:values:element_value]', webform_submission, [], options) }}</pre>",
    ];
    $output[] = [
      '#markup' => '<p>' . t("You can debug data using the <code>webform_debug()</code> function.") . '</p>',
    ];
    $output[] = [
      '#markup' => "<pre>{{ webform_debug(data) }}</pre>",
    ];
    if (\Drupal::currentUser()->hasPermission('administer modules') && !\Drupal::moduleHandler()->moduleExists('twig_tweak')) {
      $t_args = [
        ':module_href' => 'https://www.drupal.org/project/twig_tweak',
        ':documentation_href' => 'https://www.drupal.org/docs/8/modules/twig-tweak/cheat-sheet-8x-2x',
      ];
      $output[] = [
        '#type' => 'webform_message',
        '#message_type' => 'info',
        '#message_message' => t('Install the <a href=":module_href">Twig tweak</a> module, which provides a Twig extension with some <a href=":documentation_href">useful functions and filters</a> that can improve development experience.', $t_args),
        '#message_close' => TRUE,
        '#storage' => WebformMessage::STORAGE_SESSION,
      ];
    }
    return [
      '#type' => 'details',
      '#title' => t('Help using Twig'),
      'description' => $output,
    ];
  }

  /**
   * Render a Twig template with a webform submission.
   *
   * @param \Drupal\webform\WebformSubmissionInterface $webform_submission
   *   A webform submission.
   * @param string $template
   *   A inline Twig template.
   * @param array $options
   *   (optional) Template and token options.
   * @param array $context
   *   (optional) Context to be passed to inline Twig template.
   *
   * @return string
   *   The fully rendered Twig template.
   *
   * @see \Drupal\webform\Element\WebformComputedTwig::computeValue
   * @see \Drupal\webform\Plugin\WebformHandler\EmailWebformHandler::getMessage
   */
  public static function renderTwigTemplate(WebformSubmissionInterface $webform_submission, $template, array $options = [], array $context = []) {
    try {
      $build = self::buildTwigTemplate($webform_submission, $template, $options, $context);
      return \Drupal::service('renderer')->renderPlain($build);
    }
    catch (\Exception $exception) {
      if ($webform_submission->getWebform()->access('update')) {
        \Drupal::messenger()->addError($exception->getMessage());
      }
      \Drupal::logger('webform')->error($exception->getMessage());
      return '';
    }
  }

  /**
   * Build a Twig template with a webform submission.
   *
   * @param \Drupal\webform\WebformSubmissionInterface $webform_submission
   *   A webform submission.
   * @param string $template
   *   A inline Twig template.
   * @param array $options
   *   (optional) Template, token options, and context.
   * @param array $context
   *   (optional) Context to be passed to inline Twig template.
   *
   * @return array
   *   A renderable containing an inline twig template.
   */
  public static function buildTwigTemplate(WebformSubmissionInterface $webform_submission, $template, array $options = [], array $context = []) {
    $options += [
      'html' => FALSE,
      'email' => FALSE,
    ];

    // Include 'html' and 'email' options in the token.
    // @see \Drupal\webform\Twig\TwigExtension::renderTwigTemplate
    foreach (static::$options as $option_name => $option_setting) {
      if ($options[$option_name]) {
        $template = preg_replace('/\[(webform_submission:values:?[^]]*)\]/', '[\1:' . $option_setting . ']', $template);
      }
    }

    // If the template does NOT use the webform_token() function, but contains
    // simple tokens, convert the simple tokens to use
    // the webform_token() function.
    if (strpos($template, 'webform_token(') === FALSE
      && strpos($template, '[webform') !== FALSE) {
      $template = preg_replace('#([^"\']|^)(\[[^]]+\])([^"\']|$)#', '\1{{ webform_token(\'\2\', webform_submission) }}\3', $template);
    }

    $context += [
      'webform_submission' => $webform_submission,
      'webform' => $webform_submission->getWebform(),
      'elements' => $webform_submission->getWebform()->getElementsDecoded(),
      'elements_flattened' => $webform_submission->getWebform()->getElementsDecodedAndFlattened(),
      'options' => $options,
      'original_data' => $webform_submission->getOriginalData(),
    ] + $webform_submission->toArray(TRUE);

    return [
      '#type' => 'inline_template',
      '#template' => $template,
      '#context' => $context,
    ];
  }

  /**
   * Determine if the current user can edit Twig templates.
   *
   * @return bool
   *   TRUE if the current user can edit Twig templates.
   */
  public static function hasEditTwigAccess() {
    return (\Drupal::currentUser()->hasPermission('edit webform twig') || \Drupal::currentUser()->hasPermission('administer webform'));
  }

}
