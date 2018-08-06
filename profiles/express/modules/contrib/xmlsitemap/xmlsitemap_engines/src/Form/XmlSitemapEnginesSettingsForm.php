<?php

namespace Drupal\xmlsitemap_engines\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\State\StateInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Datetime\DateFormatter;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure xmlsitemap engines settings for this site.
 */
class XmlSitemapEnginesSettingsForm extends ConfigFormBase {

  /**
   * The state service.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * The date formatter service.
   *
   * @var \Drupal\Core\Datetime\DateFormatter
   */
  protected $date;

  /**
   * Constructs a new XmlSitemapCustomAddForm object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\Core\Datetime\DateFormatter $date
   *   The date service.
   * @param \Drupal\Core\State\StateInterface $state
   *   The state store service.
   */
  public function __construct(ConfigFactoryInterface $config_factory, DateFormatter $date, StateInterface $state) {
    parent::__construct($config_factory);

    $this->date = $date;
    $this->state = $state;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('date.formatter'),
      $container->get('state')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'xmlsitemap_engines_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'xmlsitemap_engines.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Build the list of support engines for the checkboxes options.
    $engines = xmlsitemap_engines_get_engine_info();
    $engine_options = array();
    foreach ($engines as $engine => $engine_info) {
      $engine_options[$engine] = $engine_info['name'];
    }
    asort($engine_options);

    $form['engines'] = array(
      '#type' => 'checkboxes',
      '#title' => $this->t('Submit the sitemap to the following engines'),
      '#default_value' => $this->config('xmlsitemap_engines.settings')->get('engines'),
      '#options' => $engine_options,
    );
    $lifetimes = array(3600, 10800, 21600, 32400, 43200, 86400, 172800, 259200, 604800, 604800 * 2, 604800 * 4);
    $lifetimes = array_combine($lifetimes, $lifetimes);
    $format_lifetimes = array();
    foreach ($lifetimes as $value) {
      $format_lifetimes[$value] = $this->date->formatInterval($value);
    }
    $form['minimum_lifetime'] = array(
      '#type' => 'select',
      '#title' => $this->t('Do not submit more often than every'),
      '#options' => $format_lifetimes,
      '#default_value' => $this->config('xmlsitemap_engines.settings')->get('minimum_lifetime'),
    );
    $form['xmlsitemap_engines_submit_updated'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Only submit if the sitemap has been updated since the last submission.'),
      '#default_value' => $this->state->get('xmlsitemap_engines_submit_updated'),
    );
    $form['custom_urls'] = array(
      '#type' => 'textarea',
      '#title' => $this->t('Custom submission URLs'),
      '#description' => $this->t('Enter one URL per line. The token [sitemap] will be replaced with the URL to your sitemap. For example: %example-before would become %example-after.', array('%example-before' => 'http://example.com/ping?[sitemap]', '%example-after' => xmlsitemap_engines_prepare_url('http://example.com/ping?[sitemap]', Url::fromRoute('xmlsitemap.sitemap_xml', [], ['absolute' => TRUE])->toString()))),
      '#default_value' => $this->config('xmlsitemap_engines.settings')->get('custom_urls'),
      '#rows' => 2,
      '#wysiwyg' => FALSE,
    );

    // Ensure the xmlsitemap_engines variable gets filtered to a simple array.
    $form['array_filter'] = array('#type' => 'value', '#value' => TRUE);
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
    $custom_urls = $form_state->getValue('custom_urls');
    $custom_urls = preg_split('/[\r\n]+/', $custom_urls, -1, PREG_SPLIT_NO_EMPTY);
    foreach ($custom_urls as $custom_url) {
      $url = xmlsitemap_engines_prepare_url($custom_url, '');
      if (!UrlHelper::isValid($url, TRUE)) {
        $form_state->setErrorByName($custom_url, $this->t('Invalid URL %url.', array('%url' => $custom_url)));
      }
    }
    $custom_urls = implode("\n", $custom_urls);
    $form_state->setValue('custom_urls', $custom_urls);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
    $state_variables = xmlsitemap_engines_state_variables();
    $keys = array(
      'engines',
      'minimum_lifetime',
      'xmlsitemap_engines_submit_updated',
      'custom_urls',
    );
    $config = $this->config('xmlsitemap_engines.settings');
    $values = $form_state->getValues();
    foreach ($keys as $key) {
      if (isset($state_variables[$key])) {
        $this->state->set($key, $values[$key]);
      }
      else {
        $config->set($key, $values[$key]);
      }
    }
    $config->save();
  }

}
