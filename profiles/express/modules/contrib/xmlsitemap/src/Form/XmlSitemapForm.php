<?php

namespace Drupal\xmlsitemap\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a form for creating and editing xmlsitemap entities.
 */
class XmlSitemapForm extends EntityForm {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'xmlsitemap_sitemap_edit_form';
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);
    if ($this->entity->getContext() == NULL) {
      $this->entity->context = array();
      $this->entity->setOriginalId(NULL);
    }
    $xmlsitemap = $this->entity;
    $form['#entity'] = $xmlsitemap;
    $form['label'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $xmlsitemap->label(),
      '#description' => $this->t('Label for the Example.'),
      '#required' => TRUE,
    );
    $form['context'] = array(
      '#tree' => TRUE,
    );
    $visible_children = Element::getVisibleChildren($form['context']);
    if (empty($visible_children)) {
      $form['context']['empty'] = array(
        '#type' => 'markup',
        '#markup' => '<p>' . t('There are currently no XML sitemap contexts available.') . '</p>',
      );
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    if (!$form_state->hasValue('context')) {
      $form_state->setValue('context', xmlsitemap_get_current_context());
    }
    if ($form_state->hasValue(['context', 'language'])) {
      $language = $form_state->getValue(['context', 'language']);
      if ($language == LanguageInterface::LANGCODE_NOT_SPECIFIED) {
        $form_state->unsetValue(['context', 'language']);
      }
    }
    $context = $form_state->getValue('context');
    $this->entity->context = $context;
    $this->entity->label = $form_state->getValue('label');
    $this->entity->id = xmlsitemap_sitemap_get_context_hash($context);

    try {
      $status = $this->entity->save();
      if ($status == SAVED_NEW) {
        drupal_set_message($this->t('Saved the %label sitemap.', array(
              '%label' => $this->entity->label(),
        )));
      }
      else if ($status == SAVED_UPDATED) {
        drupal_set_message($this->t('Updated the %label sitemap.', array(
              '%label' => $this->entity->label(),
        )));
      }
    }
    catch (EntityStorageException $ex) {
      drupal_set_message($this->t('There is another sitemap saved with the same context.'), 'error');
    }

    $form_state->setRedirect('xmlsitemap.admin_search');
  }

  /**
   * {@inheritdoc}
   */
  public function delete(array $form, FormStateInterface $form_state) {
    $destination = array();
    $request = $this->getRequest();
    if ($request->query->has('destination')) {
      $destination = drupal_get_destination();
      $request->query->remove('destination');
    }
    $form_state->setRedirect('xmlsitemap.admin_delete', array('xmlsitemap' => $this->entity->id()));
  }

}
