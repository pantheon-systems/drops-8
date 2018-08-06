<?php

namespace Drupal\xmlsitemap_custom\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Language\LanguageInterface;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Path\AliasManagerInterface;
use Drupal\xmlsitemap\XmlSitemapLinkStorageInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Provides a form for editing a custom link.
 */
class XmlSitemapCustomEditForm extends FormBase {

  /**
   * The path of the custom link.
   *
   * @var string
   */
  protected $custom_link;

  /**
   * The language manager.
   *
   * @var \Drupal\language\ConfigurableLanguageManagerInterface
   */
  protected $languageManager;

  /**
   * The alias manager service.
   *
   * @var \Drupal\Core\Path\AliasManagerInterface
   */
  protected $aliasManager;

  /**
   * The xmlsitemap link storage handler.
   *
   * @var \Drupal\xmlsitemap\XmlSitemapLinkStorageInterface
   */
  protected $linkStorage;

  /**
   * Constructs a new XmlSitemapCustomEditForm object.
   *
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager service.
   * @param \Drupal\Core\Path\AliasManagerInterface $alias_manager
   *   The path alias manager service.
   * @param \Drupal\xmlsitemap\XmlSitemapLinkStorageInterface $link_storage
   *   The xmlsitemap link storage service.
   */
  public function __construct(LanguageManagerInterface $language_manager, AliasManagerInterface $alias_manager, XmlSitemapLinkStorageInterface $link_storage) {
    $this->languageManager = $language_manager;
    $this->aliasManager = $alias_manager;
    $this->linkStorage = $link_storage;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('language_manager'),
      $container->get('path.alias_manager'),
      $container->get('xmlsitemap.link_storage')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'xmlsitemap_custom_edit';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $link = '') {
    if (!$custom_link = $this->linkStorage->load('custom', $link)) {
      drupal_set_message($this->t('No valid custom link specified.'), 'error');
      $this->redirect('xmlsitemap_custom.list');
    }
    else {
      $this->custom_link = $custom_link;
    }

    $form['type'] = array(
      '#type' => 'value',
      '#value' => 'custom',
    );
    $form['subtype'] = array(
      '#type' => 'value',
      '#value' => '',
    );
    $form['id'] = array(
      '#type' => 'value',
      '#value' => $this->custom_link['id'],
    );
    $form['loc'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Path to link'),
      '#field_prefix' => Url::fromRoute('<front>', [], array('absolute' => TRUE)),
      '#default_value' => $this->custom_link['loc'],
      '#required' => TRUE,
      '#size' => 30,
    );
    $form['priority'] = array(
      '#type' => 'select',
      '#title' => $this->t('Priority'),
      '#options' => xmlsitemap_get_priority_options(),
      '#default_value' => number_format($this->custom_link['priority'], 1),
      '#description' => $this->t('The priority of this URL relative to other URLs on your site.'),
    );
    $form['changefreq'] = array(
      '#type' => 'select',
      '#title' => $this->t('Change frequency'),
      '#options' => array(0 => $this->t('None')) + xmlsitemap_get_changefreq_options(),
      '#default_value' => $this->custom_link['changefreq'],
      '#description' => $this->t('How frequently the page is likely to change. This value provides general information to search engines and may not correlate exactly to how often they crawl the page.'),
    );
    $form['language'] = array(
      '#type' => 'language_select',
      '#title' => $this->t('Language'),
      '#languages' => LanguageInterface::STATE_ALL,
      '#default_value' => $this->custom_link['language'],
    );

    $form['actions'] = array(
      '#type' => 'actions',
    );
    $form['actions']['submit'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Save'),
      '#weight' => 5,
      '#button_type' => 'primary',
    );
    $form['actions']['cancel'] = [
      '#type' => 'link',
      '#title' => $this->t('Cancel'),
      '#url' => Url::fromRoute('xmlsitemap_custom.list'),
      '#weight' => 10,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $link = $form_state->getValues();
    $link['loc'] = trim($link['loc']);
    $link['loc'] = $this->aliasManager->getPathByAlias($link['loc'], $link['language']);
    $form_state->setValue('loc', $link['loc']);
    try {
      $client = new Client();
      $client->get(Url::fromRoute('<front>', [], array('absolute' => TRUE))->toString() . $link['loc']);
    }
    catch (ClientException $e) {
      $form_state->setErrorByName('loc', $this->t('The custom link @link is either invalid or it cannot be accessed by anonymous users.', array('@link' => $link['loc'])));
    }
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $form_state->cleanValues();
    $link = $form_state->getValues();
    $this->linkStorage->save($link);
    drupal_set_message($this->t('The custom link for %loc was saved.', array('%loc' => $link['loc'])));

    $form_state->setRedirect('xmlsitemap_custom.list');
  }

}
