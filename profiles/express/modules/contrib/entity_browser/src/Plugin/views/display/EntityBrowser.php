<?php

namespace Drupal\entity_browser\Plugin\views\display;

use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\display\DisplayPluginBase;

/**
 * The plugin that handles entity browser display.
 *
 * "entity_browser_display" is a custom property, used with
 * \Drupal\views\Views::getApplicableViews() to retrieve all views with a
 * 'Entity Browser' display.
 *
 * @ingroup views_display_plugins
 *
 * @ViewsDisplay(
 *   id = "entity_browser",
 *   title = @Translation("Entity browser"),
 *   help = @Translation("Displays a view as Entity browser widget."),
 *   theme = "views_view",
 *   admin = @Translation("Entity browser"),
 *   entity_browser_display = TRUE
 * )
 */
class EntityBrowser extends DisplayPluginBase {

  /**
   * {@inheritdoc}
   */
  public function execute() {
    parent::execute();
    $render = ['view' => $this->view->render()];
    $this->handleForm($render);
    return $render;
  }

  /**
   * {@inheritdoc}
   */
  public function ajaxEnabled() {
    // Force AJAX as this Display Plugin will almost always be embedded inside
    // EntityBrowserForm, which breaks normal exposed form submits.
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function getOption($option) {
    // @todo remove upon resolution of https://www.drupal.org/node/2904798
    // This overrides getOption() instead of ajaxEnabled() because
    // \Drupal\views\Controller\ViewAjaxController::ajaxView() currently calls
    // that directly.
    if ($option == 'use_ajax') {
      return TRUE;
    }
    else {
      return parent::getOption($option);
    }
  }


  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['use_ajax']['default'] = TRUE;
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function optionsSummary(&$categories, &$options) {
    parent::optionsSummary($categories, $options);
    if (isset($options['use_ajax'])) {
      $options['use_ajax']['value'] = $this->t('Yes (Forced)');
    }
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);
    // Disable the ability to toggle AJAX support, as we forcibly enable AJAX
    // in our ajaxEnabled() implementation.
    if (isset($form['use_ajax'])) {
      $form['use_ajax'] = [
        '#description' => $this->t('Entity Browser requires Views to use AJAX.'),
        '#type' => 'checkbox',
        '#title' => $this->t('Use AJAX'),
        '#default_value' => 1,
        '#disabled' => TRUE,
      ];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function preview() {
    return $this->execute();
  }

  /**
   * {@inheritdoc}
   *
   * Pre render callback for a view.
   *
   * Based on DisplayPluginBase::elementPreRender() except that we removed form
   * part which need to handle by our own.
   */
  public function elementPreRender(array $element) {
    $view = $element['#view'];
    $empty = empty($view->result);

    // Force a render array so CSS/JS can be attached.
    if (!is_array($element['#rows'])) {
      $element['#rows'] = ['#markup' => $element['#rows']];
    }

    $element['#header'] = $view->display_handler->renderArea('header', $empty);
    $element['#footer'] = $view->display_handler->renderArea('footer', $empty);
    $element['#empty'] = $empty ? $view->display_handler->renderArea('empty', $empty) : [];
    $element['#exposed'] = !empty($view->exposed_widgets) ? $view->exposed_widgets : [];
    $element['#more'] = $view->display_handler->renderMoreLink();
    $element['#feed_icons'] = !empty($view->feedIcons) ? $view->feedIcons : [];

    if ($view->display_handler->renderPager()) {
      $exposed_input = isset($view->exposed_raw_input) ? $view->exposed_raw_input : NULL;
      $element['#pager'] = $view->renderPager($exposed_input);
    }

    if (!empty($view->attachment_before)) {
      $element['#attachment_before'] = $view->attachment_before;
    }
    if (!empty($view->attachment_after)) {
      $element['#attachment_after'] = $view->attachment_after;
    }

    return $element;
  }

  /**
   * Handles form elements on a view.
   *
   * @param array $render
   *   Rendered content.
   */
  protected function handleForm(array &$render) {
    if (!empty($this->view->field['entity_browser_select'])) {

      /** @var \Drupal\entity_browser\Plugin\views\field\SelectForm $select */
      $select = $this->view->field['entity_browser_select'];
      $select->viewsForm($render);

      $render['#post_render'][] = [get_class($this), 'postRender'];
      $substitutions = [];
      foreach ($this->view->result as $row) {
        $form_element_row_id = $select->getRowId($row);

        $substitutions[] = [
          'placeholder' => '<!--form-item-entity_browser_select--' . $form_element_row_id . '-->',
          'field_name' => 'entity_browser_select',
          'row_id' => $form_element_row_id,
        ];
      }

      $render['#substitutions'] = [
        '#type' => 'value',
        '#value' => $substitutions,
      ];
    }
  }

  /**
   * Post render callback that moves form elements into the view.
   *
   * Form elements need to be added out of view to be correctly detected by Form
   * API and then added into the view afterwards. Views use the same approach
   * for bulk operations.
   *
   * @param string $content
   *   Rendered content.
   * @param array $element
   *   Render array.
   *
   * @return string
   *   Rendered content.
   */
  public static function postRender($content, array $element) {
    // Placeholders and their substitutions (usually rendered form elements).
    $search = $replace = [];

    // Add in substitutions provided by the form.
    foreach ($element['#substitutions']['#value'] as $substitution) {
      $field_name = $substitution['field_name'];
      $row_id = $substitution['row_id'];

      $search[] = $substitution['placeholder'];
      $replace[] = isset($element[$field_name][$row_id]) ? \Drupal::service('renderer')->render($element[$field_name][$row_id]) : '';
    }
    // Add in substitutions from hook_views_form_substitutions().
    $substitutions = \Drupal::moduleHandler()->invokeAll('views_form_substitutions');
    foreach ($substitutions as $placeholder => $substitution) {
      $search[] = $placeholder;
      $replace[] = $substitution;
    }

    // We cannot render exposed form within the View, as nested forms are not
    // standard and will break entity selection.
    $search[] = '<form';
    $replace[] = '<div';
    $search[] = '</form>';
    $replace[] = '</div>';

    $content = str_replace($search, $replace, $content);

    return $content;
  }

}
