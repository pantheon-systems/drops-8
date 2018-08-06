<?php

namespace Drupal\entity_browser\Plugin\EntityBrowser\Display;

use Drupal\Component\Uuid\UuidInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\KeyValueStore\KeyValueStoreExpirableInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Url;
use Drupal\entity_browser\DisplayBase;
use Drupal\entity_browser\DisplayRouterInterface;
use Drupal\entity_browser\Events\Events;
use Drupal\entity_browser\Events\RegisterJSCallbacks;
use Drupal\entity_browser\Events\AlterEntityBrowserDisplayData;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Drupal\Core\Path\CurrentPathStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpFoundation\Request;

/**
 * Presents entity browser in an iFrame.
 *
 * @EntityBrowserDisplay(
 *   id = "iframe",
 *   label = @Translation("iFrame"),
 *   description = @Translation("Displays the entity browser in an iFrame container embedded into the main page."),
 *   uses_route = TRUE
 * )
 */
class IFrame extends DisplayBase implements DisplayRouterInterface {

  /**
   * Current route match service.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $currentRouteMatch;

  /**
   * Current path.
   *
   * @var \Drupal\Core\Path\CurrentPathStack
   */
  protected $currentPath;

  /**
   * Current request.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected $request;

  /**
   * Constructs display plugin.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $event_dispatcher
   *   Event dispatcher service.
   * @param \Drupal\Component\Uuid\UuidInterface $uuid
   *   UUID generator interface.
   * @param \Drupal\Core\KeyValueStore\KeyValueStoreExpirableInterface $selection_storage
   *   The selection storage.
   * @param \Drupal\Core\Routing\RouteMatchInterface $current_route_match
   *   The currently active route match object.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   Current request.
   * @param \Drupal\Core\Path\CurrentPathStack $current_path
   *   The current path.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EventDispatcherInterface $event_dispatcher, UuidInterface $uuid, KeyValueStoreExpirableInterface $selection_storage, RouteMatchInterface $current_route_match, Request $request, CurrentPathStack $current_path) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $event_dispatcher, $uuid, $selection_storage);
    $this->currentRouteMatch = $current_route_match;
    $this->request = $request;
    $this->currentPath = $current_path;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('event_dispatcher'),
      $container->get('uuid'),
      $container->get('entity_browser.selection_storage'),
      $container->get('current_route_match'),
      $container->get('request_stack')->getCurrentRequest(),
      $container->get('path.current')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'width' => '650',
      'height' => '500',
      'link_text' => $this->t('Select entities'),
      'auto_open' => FALSE,
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function displayEntityBrowser(array $element, FormStateInterface $form_state, array &$complete_form, array $persistent_data = []) {
    parent::displayEntityBrowser($element, $form_state, $complete_form, $persistent_data);
    /** @var \Drupal\entity_browser\Events\RegisterJSCallbacks $event */
    $js_event_object = new RegisterJSCallbacks($this->configuration['entity_browser_id'], $this->getUuid());
    $js_event_object->registerCallback('Drupal.entityBrowser.selectionCompleted');
    $callback_event = $this->eventDispatcher->dispatch(Events::REGISTER_JS_CALLBACKS, $js_event_object);
    $original_path = $this->currentPath->getPath();

    $data = [
      'query_parameters' => [
        'query' => [
          'uuid' => $this->getUuid(),
          'original_path' => $original_path,
        ],
      ],
      'attributes' => [
        'href' => '#browser',
        'class' => ['entity-browser-handle', 'entity-browser-iframe'],
        'data-uuid' => $this->getUuid(),
        'data-original-path' => $original_path,
      ],
    ];
    $event_object = new AlterEntityBrowserDisplayData($this->configuration['entity_browser_id'], $this->getUuid(), $this->getPluginDefinition(), $form_state, $data);
    $event = $this->eventDispatcher->dispatch(Events::ALTER_BROWSER_DISPLAY_DATA, $event_object);
    $data = $event->getData();
    return [
      '#theme_wrappers' => ['container'],
      '#attributes' => [
        'class' => [
          'entity-browser-iframe-container',
        ],
      ],
      'link' => [
        '#type' => 'html_tag',
        '#tag' => 'a',
        '#value' => $this->configuration['link_text'],
        '#attributes' => $data['attributes'],
        '#attached' => [
          'library' => ['entity_browser/iframe'],
          'drupalSettings' => [
            'entity_browser' => [
              'iframe' => [
                $this->getUuid() => [
                  'src' => Url::fromRoute('entity_browser.' . $this->configuration['entity_browser_id'], [], $data['query_parameters'])
                    ->toString(),
                  'width' => $this->configuration['width'],
                  'height' => $this->configuration['height'],
                  'js_callbacks' => $callback_event->getCallbacks(),
                  'entity_browser_id' => $this->configuration['entity_browser_id'],
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
   * KernelEvents::RESPONSE listener.
   *
   * Intercepts default response and injects response that will trigger JS to
   * propagate selected entities upstream.
   *
   * @param FilterResponseEvent $event
   *   Response event.
   */
  public function propagateSelection(FilterResponseEvent $event) {
    $render = [
      'labels' => [
        '#markup' => 'Labels: ' . implode(', ', array_map(function (EntityInterface $item) {
          return $item->label();
        }, $this->entities)),
        '#attached' => [
          'library' => ['entity_browser/'. $this->pluginDefinition['id'] . '_selection'],
          'drupalSettings' => [
            'entity_browser' => [
              $this->pluginDefinition['id'] => [
                'entities' => array_map(function (EntityInterface $item) {
                  return [$item->id(), $item->uuid(), $item->getEntityTypeId()];
                }, $this->entities),
                'uuid' => $this->request->query->get('uuid'),
              ],
            ],
          ],
        ],
      ],
    ];

    $event->setResponse(new Response(\Drupal::service('bare_html_page_renderer')->renderBarePage($render, 'Entity browser', 'page')));
  }

  /**
   * {@inheritdoc}
   */
  public function path() {
    return '/entity-browser/' . $this->pluginDefinition['id'] . '/' . $this->configuration['entity_browser_id'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $configuration = $this->getConfiguration();
    $form['width'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Width of the iFrame'),
      '#default_value' => $configuration['width'],
      '#description' => $this->t('Positive integer for absolute size or a relative size in percentages.'),
    ];

    $form['height'] = [
      '#type' => 'number',
      '#title' => $this->t('Height of the iFrame'),
      '#min' => 1,
      '#default_value' => $configuration['height'],
    ];

    $form['link_text'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Link text'),
      '#default_value' => $configuration['link_text'],
    ];

    $form['auto_open'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Auto open entity browser'),
      '#default_value' => $configuration['auto_open'],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    // We want all positive integers, or percentages between 1% and 100%.
    $pattern = '/^([1-9][0-9]*|([2-9][0-9]{0,1}%)|(1[0-9]{0,2}%))$/';
    if (preg_match($pattern, $form_state->getValue('width')) == 0) {
      $form_state->setError($form['width'], $this->t('Width must be a number greater than 0, or a percentage between 1% and 100%.'));
    }

    if ($form_state->getValue('height') <= 0) {
      $form_state->setError($form['height'], $this->t('Height must be greater than 0.'));
    }
  }

}
