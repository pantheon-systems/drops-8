<?php

namespace Drupal\express_site_info\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Block\BlockPluginInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Component\Utility\Xss;
use Drupal\Core\Url;
use Drupal\Core\Link;


/**
 * Provides the Express Site Info Block.
 *
 * @Block(
 *   id = "Express Site Info",
 *   admin_label = @Translation("Express Site Info"),
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
          'express_site_info/express_site_info_styles',
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

    $form['express_site_info_contact_info'] = array(
      '#type' => 'textarea',
      '#title' => $this->t('Express Site Info Contact Information'),
      '#description' => $this->t('This will display as contact information for your visitors.'),
      '#default_value' => isset($config['contact_info']) ? $config['contact_info'] : '',
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->configuration['contact_info'] = $form_state->getValue('express_site_info_contact_info');
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    $default_config = \Drupal::config('express_site_info.settings');
    return array(
      'contact_info' => $default_config->get('express_site_info.contact_info'),
    );
  }

}
