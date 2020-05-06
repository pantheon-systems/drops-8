<?php

namespace Drupal\webform_ui\Form;

use Drupal\Component\Utility\Xss;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Markup;
use Drupal\Core\Url;
use Drupal\webform\WebformInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Provides an edit webform for a webform element.
 */
class WebformUiElementEditForm extends WebformUiElementFormBase {

  /**
   * {@inheritdoc}
   */
  protected $operation = 'update';

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, WebformInterface $webform = NULL, $key = NULL, $parent_key = NULL, $type = NULL) {
    $this->element = $webform->getElementDecoded($key);
    if ($this->element === NULL) {
      throw new NotFoundHttpException();
    }

    // Handler changing element type.
    if ($type = $this->getRequest()->get('type')) {
      $webform_element = $this->getWebformElementPlugin();
      $related_types = $webform_element->getRelatedTypes($this->element);
      if (!isset($related_types[$type])) {
        throw new NotFoundHttpException();
      }
      $this->originalType = $this->element['#type'];
      $this->element['#type'] = $type;
    }

    // Issue: #title is display as modal dialog's title and can't be escaped.
    // Workaround: Filter and define @title as safe markup.
    $form['#title'] = $this->t('Edit @title element', [
      '@title' => (!empty($this->element['#title'])) ? Markup::create(Xss::filterAdmin($this->element['#title'])) : $key,
    ]);

    $this->action = $this->t('updated');

    $form = parent::buildForm($form, $form_state, $webform, $key);

    // Delete action.
    if (!$form_state->get('default_value_element')) {
      $url = new Url('entity.webform_ui.element.delete_form', ['webform' => $webform->id(), 'key' => $key]);
      $this->buildDialogDeleteAction($form, $form_state, $url);
    }

    return $form;
  }

}
