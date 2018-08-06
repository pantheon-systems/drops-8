<?php

namespace Drupal\xmlsitemap\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\State\StateInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configure xmlsitemap settings for this site.
 */
class XmlSitemapRebuildForm extends ConfigFormBase {

  /**
   * The state store.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * Constructs a new XmlSitemapRebuildForm object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\Core\State\StateInterface $state
   *   The state service.
   */
  public function __construct(ConfigFactoryInterface $config_factory, StateInterface $state) {
    parent::__construct($config_factory);

    $this->state = $state;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('state')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'xmlsitemap_admin_rebuild';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['xmlsitemap.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $request = $this->getRequest();
    if (!$request->request && !$this->state->get('xmlsitemap_rebuild_needed')) {
      if (!$this->state->get('xmlsitemap_regenerate_needed')) {
        drupal_set_message(t('Your sitemap is up to date and does not need to be rebuilt.'), 'error');
      }
      else {
        $request->query->set('destination', 'admin/config/search/xmlsitemap');
        drupal_set_message(t('A rebuild is not necessary. If you are just wanting to regenerate the XML sitemap files, you can <a href="@link-cron">run cron manually</a>.', array('@link-cron' => Url::fromRoute('system.run_cron', [], array('query' => drupal_get_destination())))), 'warning');
        $this->setRequest($request);
      }
    }

    // Build a list of rebuildable link types.
    $rebuild_types = xmlsitemap_get_rebuildable_link_types();
    $rebuild_types = array_combine($rebuild_types, $rebuild_types);
    $form['entity_type_ids'] = array(
      '#type' => 'select',
      '#title' => t('Select which link types you would like to rebuild'),
      '#description' => t('If no link types are selected, the sitemap files will just be regenerated.'),
      '#multiple' => TRUE,
      '#options' => $rebuild_types,
      '#default_value' => $this->state->get('xmlsitemap_rebuild_needed') || !$this->state->get('xmlsitemap_developer_mode') ? $rebuild_types : array(),
      '#access' => $this->state->get('xmlsitemap_developer_mode'),
    );
    $form['save_custom'] = array(
      '#type' => 'checkbox',
      '#title' => t('Save and restore any custom inclusion and priority links.'),
      '#default_value' => TRUE,
    );
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Save any changes to the frontpage link.
    $entity_type_ids = $form_state->getValue('entity_type_ids');
    $save_custom = $form_state->getValue('save_custom');
    $batch = xmlsitemap_rebuild_batch($entity_type_ids, $save_custom);
    batch_set($batch);

    $form_state->setRedirect('xmlsitemap.admin_search');
    parent::submitForm($form, $form_state);
  }

}
