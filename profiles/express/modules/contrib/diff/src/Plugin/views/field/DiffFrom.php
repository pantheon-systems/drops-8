<?php

namespace Drupal\diff\Plugin\views\field;

use Drupal\Core\Entity\RevisionableInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Routing\RedirectDestinationTrait;
use Drupal\node\NodeInterface;

/**
 * Provides View field diff from plugin.
 *
 * @ViewsField("diff__from")
 */
class DiffFrom extends DiffPluginBase {

  use RedirectDestinationTrait;

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();

    $options['label']['default'] = t('From');
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function viewsForm(array &$form, FormStateInterface $form_state) {
    // Replace the form submit button label.
    $form['actions']['submit']['#value'] = $this->t('Compare');
    parent::viewsForm($form, $form_state);
  }

  /**
   * Returns the diff_to field ID.
   *
   * @return string|null
   *   The diff_to field ID, or null if the field was not found on the view.
   */
  protected function getToFieldId() {
    foreach ($this->view->field as $id => $field) {
      if ($field instanceof DiffTo) {
        return $id;
      }
    }
  }

  /**
   * Submit handler for the bulk form.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException
   *   Thrown when the user tried to access an action without access to it.
   */
  public function viewsFormSubmit(array &$form, FormStateInterface $form_state) {
    if ($form_state->get('step') == 'views_form_views_form') {
      $diff_from = $form_state->getValue($this->options['id']);
      $diff_from_entity = $this->loadEntityFromDiffFormKey($diff_from);

      $diff_to = $form_state->getValue($this->getToFieldId());
      $diff_to_entity = $this->loadEntityFromDiffFormKey($diff_to);

      $options = array(
        'query' => $this->getDestinationArray(),
      );
      $entity_type_id = $diff_from_entity->getEntityTypeId();

      $filter = \Drupal::service('plugin.manager.diff.layout')->getDefaultLayout();
      if ($diff_from_entity instanceof NodeInterface && $diff_to_entity instanceof NodeInterface) {
        $form_state->setRedirect('diff.revisions_diff', [
          $entity_type_id => $diff_from_entity->id(),
          'left_revision' => $diff_from_entity->getRevisionId(),
          'right_revision' => $diff_to_entity->getRevisionId(),
          'filter' => $filter,
        ], $options);
      }
      elseif ($diff_from_entity instanceof RevisionableInterface && $diff_to_entity instanceof RevisionableInterface) {
        $route_name = 'entity.' . $entity_type_id . '.revisions_diff';
        $form_state->setRedirect($route_name, [
          $entity_type_id => $diff_from_entity->id(),
          'left_revision' => $diff_from_entity->getRevisionId(),
          'right_revision' => $diff_to_entity->getRevisionId(),
          'filter' => $filter,
        ], $options);
      }
    }
  }

}
