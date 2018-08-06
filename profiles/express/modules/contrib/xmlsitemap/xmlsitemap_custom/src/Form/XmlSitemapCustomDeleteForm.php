<?php

namespace Drupal\xmlsitemap_custom\Form;

use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Url;
use Drupal\Core\Form\FormStateInterface;
use Drupal\xmlsitemap\XmlSitemapLinkStorageInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Provides a form for deleting a custom link.
 */
class XmlSitemapCustomDeleteForm extends ConfirmFormBase {

  /**
   * The xmlsitemap link storage handler.
   *
   * @var \Drupal\xmlsitemap\XmlSitemapLinkStorageInterface
   */
  protected $linkStorage;

  /**
   * The path of the custom link.
   *
   * @var string
   */
  protected $custom_link;

  /**
   * Constructs a new XmlSitemapCustomEditForm object.
   *
   * @param \Drupal\xmlsitemap\XmlSitemapLinkStorageInterface $link_storage
   *   The xmlsitemap link storage service.
   */
  public function __construct(XmlSitemapLinkStorageInterface $link_storage) {
    $this->linkStorage = $link_storage;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('xmlsitemap.link_storage')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'xmlsitemap_custom_delete';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $link = '') {
    if (!$custom_link = $this->linkStorage->load('custom', $link)) {
      throw new NotFoundHttpException();
    }
    else {
      $this->custom_link = $custom_link;
    }
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Delete');
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('xmlsitemap_custom.list');
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to delete %link?', array('%link' => $this->custom_link['loc']));
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->linkStorage->delete('custom', $this->custom_link['id']);
    $this->logger('xmlsitemap')->debug('The custom link for %loc has been deleted.', array('%loc' => $this->custom_link['loc']));
    drupal_set_message($this->t('The custom link for %loc has been deleted.', array('%loc' => $this->custom_link['loc'])));

    $form_state->setRedirectUrl($this->getCancelUrl());
  }

}
