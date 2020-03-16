<?php

namespace Drupal\metatag_views\Plugin\views\display_extender;

use Drupal\Core\Form\FormStateInterface;
use Drupal\metatag\MetatagManagerInterface;
use Drupal\metatag\MetatagTagPluginManager;
use Drupal\views\Plugin\views\display_extender\DisplayExtenderPluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

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
   * Constructs the plugin.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\metatag\MetatagTagPluginManager $metatag_plugin_manager
   *   The plugin manager for metatag tags.
   * @param \Drupal\metatag\MetatagManagerInterface $metatag_manager
   *   The metatag manager.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, MetatagTagPluginManager $metatag_plugin_manager, MetatagManagerInterface $metatag_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->metatagTagManager = $metatag_plugin_manager;
    $this->metatagManager = $metatag_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('plugin.manager.metatag.tag'),
      $container->get('metatag.manager')
    );
  }

  /**
   * Provide a form to edit options for this plugin.
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {

    if ($form_state->get('section') == 'metatags') {
      $form['#title'] .= t('The meta tags for this display');
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
      'title' => t('Meta tags'),
      'column' => 'second',
    ];
    $options['metatags'] = [
      'category' => 'metatags',
      'title' => t('Meta tags'),
      'value' => $this->hasMetatags() ? t('Overridden') : t('Using defaults'),
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
