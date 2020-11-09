<?php

namespace Drupal\metatag_views\Plugin\views\display_extender;

use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\display_extender\DisplayExtenderPluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Metatag display extender plugin.
 *
 * @ingroup views_display_extender_plugins
 *
 * @ViewsDisplayExtender(
 *   id = "metatag_display_extender",
 *   title = @Translation("Metatag display extender"),
 *   help = @Translation("Metatag settings for this view."),
 *   no_ui = FALSE
 * )
 */
class MetatagDisplayExtender extends DisplayExtenderPluginBase {

  use StringTranslationTrait;

  /**
   * The metatag manager.
   *
   * @var \Drupal\metatag\MetatagManagerInterface
   */
  protected $metatagManager;

  /**
   * The plugin manager for metatag tags.
   *
   * @var \Drupal\metatag\MetatagTagPluginManager
   */
  protected $metatagTagManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    /** @var \Drupal\metatag_views\Plugin\views\display_extender\MetatagDisplayExtender */
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->metatagTagManager = $container->get('plugin.manager.metatag.tag');
    $instance->metatagManager = $container->get('metatag.manager');

    return $instance;
  }

  /**
   * Provide a form to edit options for this plugin.
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {

    if ($form_state->get('section') == 'metatags') {
      $form['#title'] .= $this->t('The meta tags for this display');
      $metatags = $this->getMetatags();

      // Build/inject the Metatag form.
      $form['metatags'] = $this->metatagManager->form($metatags, $form, ['view']);
    }
  }

  /**
   * Validate the options form.
   */
  public function validateOptionsForm(&$form, FormStateInterface $form_state) {
  }

  /**
   * Handle any special handling on the validate form.
   */
  public function submitOptionsForm(&$form, FormStateInterface $form_state) {
    if ($form_state->get('section') == 'metatags') {
      // Process submitted metatag values and remove empty tags.
      $tag_values = [];
      $metatags = $form_state->cleanValues()->getValues();
      foreach ($metatags as $tag_id => $tag_value) {
        // Some plugins need to process form input before storing it.
        // Hence, we set it and then get it.
        $tag = $this->metatagTagManager->createInstance($tag_id);
        $tag->setValue($tag_value);
        if (!empty($tag->value())) {
          $tag_values[$tag_id] = $tag->value();
        }
      }
      $this->options['metatags'] = $tag_values;
    }
  }

  /**
   * Set up any variables on the view prior to execution.
   */
  public function preExecute() {
  }

  /**
   * Inject anything into the query that the display_extender handler needs.
   */
  public function query() {
  }

  /**
   * Provide the default summary for options in the views UI.
   *
   * This output is returned as an array.
   */
  public function optionsSummary(&$categories, &$options) {
    $categories['metatags'] = [
      'title' => $this->t('Meta tags'),
      'column' => 'second',
    ];
    $options['metatags'] = [
      'category' => 'metatags',
      'title' => $this->t('Meta tags'),
      'value' => $this->hasMetatags() ? $this->t('Overridden') : $this->t('Using defaults'),
    ];
  }

  /**
   * Lists defaultable sections and items contained in each section.
   */
  public function defaultableSections(&$sections, $section = NULL) {
  }

  /**
   * Identify whether or not the current display has custom meta tags defined.
   *
   * @return bool
   *   Whether or not the view has overridden metatags.
   */
  protected function hasMetatags() {
    $metatags = $this->getMetatags();
    return !empty($metatags);

  }

  /**
   * Get the Metatag configuration for this display.
   *
   * @return array
   *   The meta tag values.
   */
  public function getMetatags() {
    $metatags = [];

    if (!empty($this->options['metatags'])) {
      $metatags = $this->options['metatags'];
    }

    return $metatags;
  }

  /**
   * Sets the meta tags for the given view.
   *
   * @param array $metatags
   *   Metatag arrays as suitable for storage.
   */
  public function setMetatags(array $metatags) {
    $this->options['metatags'] = $metatags;
  }

}
