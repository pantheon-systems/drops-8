<?php

namespace Drupal\metatag_views\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\metatag_views\Controller\MetatagViewsController;
use Drupal\views\Views;

/**
 * Class MetatagViewsAddForm.
 *
 * @package Drupal\metatag_views\Form
 */
class MetatagViewsAddForm extends MetatagViewsEditForm {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'metatag_views_add_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);

    // Add a view select to the edit form.
    $views = Views::getViewsAsOptions(FALSE, 'enabled', NULL, TRUE, TRUE);
    // Get only the views that do not have the meta tags set yet.
    $in_use = MetatagViewsController::getTaggedViews();
    foreach ($in_use as $view_id => $displays) {
      foreach (array_keys($displays) as $display_id) {
        unset($views[$view_id][$view_id . ':' . $display_id]);
      }
    }
    $views = array_filter($views);

    // Need to create that AFTER the $form['metatags'] as the whole form
    // is passed to the $metatagManager->form() which causes duplicated field.
    $form['view']['#type'] = 'select';
    $form['view']['#options'] = $views;
    $form['view']['#empty_option'] = $this->t('- Select a view -');

    return $form;
  }

}
