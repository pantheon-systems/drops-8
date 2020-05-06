<?php

namespace Drupal\webform\Plugin\WebformElement;

use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Entity\View as ViewEntity;
use Drupal\webform\Element\WebformMessage as WebformMessageElement;

/**
 * Provides a hidden 'view' element.
 *
 * @WebformElement(
 *   id = "view",
 *   label = @Translation("View"),
 *   description = @Translation("Provides a view embed element. Only users who can 'Administer views' or 'Edit webform source code' can create and update this element."),
 *   category = @Translation("Markup elements"),
 *   states_wrapper = TRUE,
 * )
 */
class View extends WebformMarkupBase {

  /**
   * {@inheritdoc}
   */
  protected function defineDefaultProperties() {
    return [
      'name' => '',
      'display_id' => '',
      'arguments' => [],
      'display_on' => static::DISPLAY_ON_BOTH,
    ] + parent::defineDefaultProperties();
  }

  /****************************************************************************/

  /**
   * {@inheritdoc}
   */
  public function isHidden() {
    // Only users who can 'Administer views' or 'Edit webform source code''
    // can add the 'View' element.
    return !$this->currentUser->hasPermission('edit webform source')
      && !$this->currentUser->hasPermission('administer views');
  }

  /**
   * {@inheritdoc}
   */
  public function preview() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);
    $form['view'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('View settings'),
      '#open' => TRUE,
    ];

    $form['view']['view_message'] = [
      '#type' => 'webform_message',
      '#message_type' => 'warning',
      '#access' => TRUE,
      '#message_message' => $this->t("View displays with exposed filters are not supported because exposed filters nest a &lt;form&gt; within a &lt;form&gt; and this breaks the webform."),
      '#message_close' => TRUE,
      '#message_storage' => WebformMessageElement::STORAGE_SESSION,
      '#states' => [
        'visible' => [
          ':input[name="properties[display_on]"]' => ['!value' => static::DISPLAY_ON_VIEW],
        ],
      ],
    ];

    // Move display on from markup.
    $form['view']['display_on'] = $form['markup']['display_on'];

    $form['view']['name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('View name'),
      '#required' => TRUE,
    ];
    $form['view']['display_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('View display id'),
      '#required' => TRUE,
    ];
    $form['view']['arguments'] = [
      '#type' => 'webform_multiple',
      '#title' => $this->t('View arguments'),
    ];

    // If the view element is hidden, don't allow the view settings
    // to be updated.
    if ($this->isHidden()) {
      /** @var \Drupal\webform_ui\Form\WebformUiElementEditForm $form_object */
      $form_object = $form_state->getFormObject();
      $element = $form_object->getElement();

      // Display message.
      $form['view']['view_message'] = [
        '#type' => 'webform_message',
        '#message_type' => 'info',
        '#message_message' => $this->t("Only users who can 'Administer views' or 'Edit webform source code' can update the view name, display id, and arguments."),
        '#message_close' => TRUE,
        '#message_storage' => WebformMessageElement::STORAGE_SESSION,
        '#access' => TRUE,
      ];

      // Hide input and display values as items.
      $view_properties = ['name', 'display_id', 'arguments'];
      foreach ($view_properties as $view_property) {
        $form['view'][$view_property]['#access'] = FALSE;
        if (!empty($element['#' . $view_property])) {
          $form['view'][$view_property . '_item'] = [
            '#type' => 'item',
            '#title' => $form['view'][$view_property]['#title'],
            '#markup' => (is_array($element['#' . $view_property]))
              ? implode('/', $element['#' . $view_property])
              : $element['#' . $view_property],
            '#access' => TRUE,
          ];
        }
      }
    }

    unset($form['markup']);

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    $properties = $this->getConfigurationFormProperties($form, $form_state);

    // Check view name.
    /** @var \Drupal\views\ViewEntityInterface $view */
    $view = ViewEntity::load($properties['#name']);
    if (!$view) {
      $form_state->setErrorByName('name', t('View %name does not exist.', ['%name' => $properties['#name']]));
      return;
    }

    // Check display id.
    $display = $view->getDisplay($properties['#display_id']);
    if (!$display) {
      $form_state->setErrorByName('display_id', t('View display %display_id does not exist.', ['%display_id' => $properties['#display_id']]));
      return;
    }

    // Check exposed filters is display on a form.
    $display_on = (!empty($properties['#display_on']))
      ? $properties['#display_on']
      : $this->getDefaultProperty('display_on');
    if (in_array($display_on, [static::DISPLAY_ON_BOTH, static::DISPLAY_ON_FORM])) {
      if (isset($display['display_options']['filters'])) {
        $filters = $display['display_options']['filters'];
      }
      else {
        $default_display = $view->getDisplay('default');
        $filters = $default_display['display_options']['filters'];
      }
      foreach ($filters as $filter) {
        if (!empty($filter['exposed'])) {
          $form_state->setErrorByName('display_id', t('View display %display_id has exposed filters which will break the webform.', ['%display_id' => $properties['#display_id']]));
          break;
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getElementSelectorOptions(array $element) {
    return [];
  }

}
