<?php

namespace Drupal\webform_ui\Form;

use Drupal\Component\Utility\Xss;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Markup;
use Drupal\Core\Url;
use Drupal\webform\Utility\WebformDialogHelper;
use Drupal\webform\WebformInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Provides an edit webform for a webform element.
 */
class WebformUiElementEditForm extends WebformUiElementFormBase {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, WebformInterface $webform = NULL, $key = NULL) {
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

    // ISSUE:
    // The below delete link with .use-ajax is throwing errors because the modal
    // dialog code is creating a <button> without any parent form.
    // Issue #2879304: Editing Select Other elements produces JavaScript errors
    // @see Drupal.Ajax
    /*
    if ($this->isModalDialog()) {
      $form['actions']['delete'] = [
        '#type' => 'link',
        '#title' => $this->t('Delete'),
        '#url' => new Url(
          'entity.webform_ui.element.delete_form',
          [
            'webform' => $webform->id(),
            'key' => $key,
          ]
        ),
        '#attributes' => WebformDialogHelper::getModalDialogAttributes(700, ['button', 'button--danger']),
      ];
    }
    */

    // WORKAROUND:
    // Create a hidden link that is clicked using jQuery.
    if ($this->isDialog()) {
      $form['delete'] = [
        '#type' => 'link',
        '#title' => $this->t('Delete'),
        '#url' => new Url('entity.webform_ui.element.delete_form', ['webform' => $webform->id(), 'key' => $key]),
        '#attributes' => ['style' => 'display:none'] + WebformDialogHelper::getModalDialogAttributes(700, ['webform-ui-element-delete-link']),
      ];
      $form['actions']['delete'] = [
        '#type' => 'submit',
        '#value' => $this->t('Delete'),
        '#attributes' => [
          'class' => ['button', 'button--danger'],
          'onclick' => "jQuery('.webform-ui-element-delete-link').click(); return false;",
        ],
      ];
    }

    return $form;
  }

}
