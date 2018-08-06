<?php

namespace Drupal\cu_site_info\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Block\BlockPluginInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Component\Utility\Xss;
use Drupal\Core\Url;
use Drupal\Core\Link;


/**
 * Provides the CU Site Info Block.
 *
 * @Block(
 *   id = "CU Site Info",
 *   admin_label = @Translation("CU Site Info"),
 * )
 */
class SiteInfoBlock extends BlockBase implements BlockPluginInterface {

  /**
   * {@inheritdoc}
   */
  public function build() {
    global $base_url;

    $config = $this->getConfiguration();

    $contact_info = Xss::filterAdmin($config['contact_info']);

    $config_site_name = \Drupal::config('system.site');
    $site_name = $config_site_name->get('name');

    $link = Link::fromTextAndUrl(t($site_name), Url::fromUri($base_url,array()))->toString();

    return array(
      '#markup' => $contact_info,
      '#attached' => array(
        'library' => array(
          'cu_site_info/cu_site_info_styles',
        ),
      ),
      '#title' => $link,
    );
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form = parent::blockForm($form, $form_state);

    $config = $this->getConfiguration();

    $form['cu_site_info_contact_info'] = array(
      '#type' => 'textarea',
      '#title' => $this->t('CU Site Info Contact Information'),
      '#description' => $this->t('This will display as contact information for your visitors.'),
      '#default_value' => isset($config['contact_info']) ? $config['contact_info'] : '',
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->configuration['contact_info'] = $form_state->getValue('cu_site_info_contact_info');
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    $default_config = \Drupal::config('cu_site_info.settings');
    return array(
      'contact_info' => $default_config->get('cu_site_info.contact_info'),
    );
  }

}
