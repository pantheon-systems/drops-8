<?php

namespace Drupal\redirect\Form;

use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Language\Language;
use Drupal\Core\Routing\MatchingRouteNotFoundException;
use Drupal\Core\Url;
use Drupal\redirect\Entity\Redirect;
use Drupal\Core\Form\FormStateInterface;

class RedirectForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  protected function prepareEntity() {
    /** @var \Drupal\redirect\Entity\Redirect $redirect */
    $redirect = $this->entity;

    if ($redirect->isNew()) {

      // To pass in the query set parameters into GET as follows:
      // source_query[key1]=value1&source_query[key2]=value2
      $source_query = array();
      if ($this->getRequest()->get('source_query')) {
        $source_query = $this->getRequest()->get('source_query');
      }

      $redirect_options = array();
      $redirect_query = array();
      if ($this->getRequest()->get('redirect_options')) {
        $redirect_options = $this->getRequest()->get('redirect_options');
        if (isset($redirect_options['query'])) {
          $redirect_query = $redirect_options['query'];
          unset($redirect_options['query']);
        }
      }

      $source_url = urldecode($this->getRequest()->get('source'));
      if (!empty($source_url)) {
        $redirect->setSource($source_url, $source_query);
      }

      $redirect_url = urldecode($this->getRequest()->get('redirect'));
      if (!empty($redirect_url)) {
        try {
          $redirect->setRedirect($redirect_url, $redirect_query, $redirect_options);
        }
        catch (MatchingRouteNotFoundException $e) {
          drupal_set_message(t('Invalid redirect URL %url provided.', array('%url' => $redirect_url)), 'warning');
        }
      }

      $redirect->setLanguage($this->getRequest()->get('language') ? $this->getRequest()->get('language') : Language::LANGCODE_NOT_SPECIFIED);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);
    /** @var \Drupal\redirect\Entity\Redirect $redirect */
    $redirect = $this->entity;

    $default_code = $redirect->getStatusCode() ? $redirect->getStatusCode() : \Drupal::config('redirect.settings')->get('default_status_code');

    $form['status_code'] = array(
      '#type' => 'select',
      '#title' => t('Redirect status'),
      '#description' => t('You can find more information about HTTP redirect status codes at <a href="@status-codes">@status-codes</a>.', array('@status-codes' => 'http://en.wikipedia.org/wiki/List_of_HTTP_status_codes#3xx_Redirection')),
      '#default_value' => $default_code,
      '#options' => redirect_status_code_options(),
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
    $source = $form_state->getValue(array('redirect_source', 0));
    $redirect = $form_state->getValue(array('redirect_redirect', 0));

    if ($source['path'] == '<front>') {
      $form_state->setErrorByName('redirect_source', t('It is not allowed to create a redirect from the front page.'));
    }
    if (strpos($source['path'], '#') !== FALSE) {
      $form_state->setErrorByName('redirect_source', t('The anchor fragments are not allowed.'));
    }
    if (strpos($source['path'], '/') === 0) {
      $form_state->setErrorByName('redirect_source', t('The url to redirect from should not start with a forward slash (/).'));
    }

    try {
      $source_url = Url::fromUri('internal:/' . $source['path']);
      $redirect_url = Url::fromUri($redirect['uri']);

      // It is relevant to do this comparison only in case the source path has
      // a valid route. Otherwise the validation will fail on the redirect path
      // being an invalid route.
      if ($source_url->toString() == $redirect_url->toString()) {
        $form_state->setErrorByName('redirect_redirect', t('You are attempting to redirect the page to itself. This will result in an infinite loop.'));
      }
    }
    catch (\InvalidArgumentException $e) {
      // Do nothing, we want to only compare the resulting URLs.
    }

    $parsed_url = UrlHelper::parse(trim($source['path']));
    $path = isset($parsed_url['path']) ? $parsed_url['path'] : NULL;
    $query = isset($parsed_url['query']) ? $parsed_url['query'] : NULL;
    $hash = Redirect::generateHash($path, $query, $form_state->getValue('language')[0]['value']);

    // Search for duplicate.
    $redirects = \Drupal::entityManager()
      ->getStorage('redirect')
      ->loadByProperties(array('hash' => $hash));

    if (!empty($redirects)) {
      $redirect = array_shift($redirects);
      if ($this->entity->isNew() || $redirect->id() != $this->entity->id()) {
        $form_state->setErrorByName('redirect_source', t('The source path %source is already being redirected. Do you want to <a href="@edit-page">edit the existing redirect</a>?',
          array(
            '%source' => $source['path'],
            '@edit-page' => $redirect->url('edit-form'))));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $this->entity->save();
    drupal_set_message(t('The redirect has been saved.'));
    $form_state->setRedirect('redirect.list');
  }
}
