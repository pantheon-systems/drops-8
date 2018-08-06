<?php

namespace Drupal\xmlsitemap\Form;

use Drupal\Core\Entity\EntityConfirmFormBase;
use Drupal\Core\Url;
use Drupal\Core\Form\FormStateInterface;

/**
 * Builds the form to delete a sitemap.
 */
class XmlSitemapDeleteForm extends EntityConfirmFormBase {

  /**
   * {@inheritdoc}
   */
  public function getBaseFormID() {
    return 'xmlsitemap_delete_form';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to delete %name?', array('%name' => $this->entity->label()));
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('xmlsitemap.admin_search_list');
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
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->entity->delete();
    drupal_set_message($this->t('Sitemap %label has been deleted.', array('%label' => $this->entity->label())));
    $form_state->setRedirect('xmlsitemap.admin_search');
  }

}
