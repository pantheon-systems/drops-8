<?php

namespace Drupal\entity_browser\Element;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element\FormElement;

/**
 * Provides an Entity Browser pager form element.
 *
 * Properties:
 * - #total_pages: Total number of pages. This is optional with default
 *   value set on NULL. With default value pager can't calculate last page
 *   correctly and "next" will be available even on last page. For
 *   correct functionality #total_pages must be set up.
 *
 * Example:
 * @code
 *   $form['pager'] = [
 *     '#type' => 'entity_browser_pager',
 *     '#total_pages' => 12,
 *   ];
 * @endcode
 *
 * Number of the current page is stored in the form state. In order to get it
 * the provided helper function needs to be utilized:
 *
 * @code
 *   $page = EntityBrowserPagerElement::getCurrentPage($form_state);
 * @endcode
 *
 * @see ::getCurrentPage($form_state).
 *
 * @FormElement("entity_browser_pager")
 */
class EntityBrowserPagerElement extends FormElement {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $class = get_class($this);
    return [
      '#process' => [[$class, 'processEntityBrowserPager']],
      '#theme_wrappers' => ['form_element'],
      '#total_pages' => NULL,
      '#attached' => [
        'library' => ['entity_browser/pager'],
      ],
    ];
  }

  /**
   * Process Entity browser pager element.
   */
  public static function processEntityBrowserPager(&$element, FormStateInterface $form_state, &$complete_form) {
    $page = static::getCurrentPage($form_state);

    $element['previous'] = [
      '#type' => 'submit',
      '#submit' => [[static::class, 'submitPager']],
      '#value' => t('‹ Previous'),
      '#name' => 'prev_page',
      '#disabled' => $page === 1,
      '#attributes' => ['class' => ['prev']],
      '#limit_validation_errors' => [array_merge($element['#parents'], ['previous'])],
    ];
    $element['current'] = [
      '#type' => 'html_tag',
      '#tag' => 'span',
      '#value' => t('Page @page', ['@page' => $page]),
      '#attributes' => ['class' => ['current']],
    ];
    $element['next'] = [
      '#type' => 'submit',
      '#submit' => [[static::class, 'submitPager']],
      '#value' => t('Next ›'),
      '#name' => 'next_page',
      '#disabled' => $element['#total_pages'] == $page,
      '#attributes' => ['class' => ['next']],
      '#limit_validation_errors' => [array_merge($element['#parents'], ['next'])],
    ];

    return $element;
  }

  /**
   * Submit handler for next and previous buttons.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state.
   */
  public static function submitPager($form, FormStateInterface $form_state) {
    $page = static::getCurrentPage($form_state);

    $triggering_element = $form_state->getTriggeringElement();
    if ($triggering_element['#name'] == 'prev_page') {
      $page--;
    }
    elseif ($triggering_element['#name'] == 'next_page') {
      $page++;
    }

    $form_state->set('page', $page);
    $form_state->setRebuild();
  }

  /**
   * Gets current page from the form state.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state.
   *
   * @return int
   *   Current page.
   */
  public static function getCurrentPage(FormStateInterface $form_state) {
    return !empty($form_state->get('page')) ? $form_state->get('page') : 1;
  }

  /**
   * Sets current page.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state.
   * @param int $page
   *   (Optional) Page to set as current. Pager will be reset to the first page
   *   if omitted.
   */
  public static function setCurrentPage(FormStateInterface $form_state, $page = 1) {
    $form_state->set('page', $page);
  }
}
