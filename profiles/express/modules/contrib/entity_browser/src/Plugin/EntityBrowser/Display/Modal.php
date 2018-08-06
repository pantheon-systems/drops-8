<?php

namespace Drupal\entity_browser\Plugin\EntityBrowser\Display;

use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Ajax\OpenDialogCommand;
use Drupal\Core\Url;
use Drupal\entity_browser\DisplayBase;
use Drupal\entity_browser\Events\Events;
use Drupal\entity_browser\Events\RegisterJSCallbacks;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Form\FormStateInterface;
use Drupal\entity_browser\Events\AlterEntityBrowserDisplayData;

/**
 * Presents entity browser in an Modal.
 *
 * @EntityBrowserDisplay(
 *   id = "modal",
 *   label = @Translation("Modal"),
 *   description = @Translation("Displays the entity browser in a modal window."),
 *   uses_route = TRUE
 * )
 */
class Modal extends IFrame {

  /**
   * {@inheritdoc}
   */
  public function displayEntityBrowser(array $element, FormStateInterface $form_state, array &$complete_form, array $persistent_data = []) {
    DisplayBase::displayEntityBrowser($element, $form_state, $complete_form, $persistent_data);
    $js_event_object = new RegisterJSCallbacks($this->configuration['entity_browser_id'], $this->getUuid());
    $js_event_object->registerCallback('Drupal.entityBrowser.selectionCompleted');
    $js_event = $this->eventDispatcher->dispatch(Events::REGISTER_JS_CALLBACKS, $js_event_object);
    $original_path = $this->currentPath->getPath();

    $data = [
      'query_parameters' => [
        'query' => [
          'uuid' => $this->getUuid(),
          'original_path' => $original_path,
        ],
      ],
      'attributes' => [
        'data-uuid' => $this->getUuid(),
      ],
    ];
    $event_object = new AlterEntityBrowserDisplayData($this->configuration['entity_browser_id'], $this->getUuid(), $this->getPluginDefinition(), $form_state, $data);
    $event = $this->eventDispatcher->dispatch(Events::ALTER_BROWSER_DISPLAY_DATA, $event_object);
    $data = $event->getData();
    return [
      '#theme_wrappers' => ['container'],
      'path' => [
        '#type' => 'hidden',
        '#value' => Url::fromRoute('entity_browser.' . $this->configuration['entity_browser_id'], [], $data['query_parameters'])->toString(),
      ],
      'open_modal' => [
        '#type' => 'submit',
        '#value' => $this->configuration['link_text'],
        '#limit_validation_errors' => [],
        '#submit' => [],
        '#name' => implode('_', $element['#eb_parents']),
        '#ajax' => [
          'callback' => [$this, 'openModal'],
          'event' => 'click',
        ],
        '#executes_submit_callback' => FALSE,
        '#attributes' => $data['attributes'],
        '#attached' => [
          'library' => ['core/drupal.dialog.ajax', 'entity_browser/modal'],
          'drupalSettings' => [
            'entity_browser' => [
              'modal' => [
                $this->getUuid() => [
                  'uuid' => $this->getUuid(),
                  'js_callbacks' => $js_event->getCallbacks(),
                  'original_path' => $original_path,
                  'auto_open' => $this->configuration['auto_open'],
                ],
              ],
            ],
          ],
        ],
      ],
    ];
  }

  /**
   * Generates the content and opens the modal.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state object.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   An ajax response.
   */
  public function openModal(array &$form, FormStateInterface $form_state) {
    $triggering_element = $form_state->getTriggeringElement();
    $parents = $triggering_element['#parents'];
    array_pop($parents);
    $parents = array_merge($parents, ['path']);
    $input = $form_state->getUserInput();
    $src = NestedArray::getValue($input, $parents);

    $field_name = $triggering_element['#parents'][0];
    $element_name = $this->configuration['entity_browser_id'];
    $name = 'entity_browser_iframe_' . $element_name;
    $content = [
      '#prefix' => '<div class="ajax-progress-throbber"></div>',
      '#type' => 'html_tag',
      '#tag' => 'iframe',
      '#attributes' => [
        'src' => $src,
        'class' => 'entity-browser-modal-iframe',
        'width' => '100%',
        'height' => $this->configuration['height'] - 90,
        'frameborder' => 0,
        'style' => 'padding:0; position:relative; z-index:10002;',
        'name' => $name,
        'id' => $name,
      ],
    ];
    $html = drupal_render($content);

    $response = new AjaxResponse();
    $response->addCommand(new OpenDialogCommand('#' . Html::getUniqueId($field_name . '-' . $element_name . '-dialog'), $this->configuration['link_text'], $html, [
      'width' => 'auto',
      'height' => 'auto',
      'modal' => TRUE,
      'maxWidth' => $this->configuration['width'],
      'maxHeight' => $this->configuration['height'],
      'fluid' => 1,
      'autoResize' => 0,
      'resizable' => 0,
    ]));
    return $response;
  }

  /**
   * {@inheritdoc}
   */
  public function __sleep() {
    return ['configuration'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $configuration = $this->getConfiguration();

    $form = parent::buildConfigurationForm($form, $form_state);

    $form['width'] = [
      '#type' => 'number',
      '#title' => $this->t('Width of the modal'),
      '#default_value' => $configuration['width'],
      '#description' => $this->t('Empty value for responsive width.'),
    ];
    $form['height'] = [
      '#type' => 'number',
      '#title' => $this->t('Height of the modal'),
      '#default_value' => $configuration['height'],
      '#description' => $this->t('Empty value for responsive height.'),
    ];
    $form['auto_open']['#description'] = $this->t('Will open Entity browser modal as soon as page is loaded, which might cause unwanted results. Should be used only in very specific cases such as Inline entity form integration. It is also advised not to use Entity browsers with this option enabled more than once per page.');

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {}

}
