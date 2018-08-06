<?php

namespace Drupal\webform_ui\Form;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Component\Utility\Xss;
use Drupal\Core\Form\FormStateInterface;
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
      $webform_element = $this->getWebformElement();
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
      '@title' => (!empty($this->element['#title'])) ? new FormattableMarkup(Xss::filterAdmin($this->element['#title']), []) : $key,
    ]);

    $this->action = $this->t('updated');

    $form = parent::buildForm($form, $form_state, $webform, $key);

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

    return $form;
  }

}
