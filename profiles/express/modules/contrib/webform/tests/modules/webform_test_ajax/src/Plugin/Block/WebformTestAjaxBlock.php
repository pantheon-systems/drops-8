<?php

namespace Drupal\webform_test_ajax\Plugin\Block;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Routing\RedirectDestinationInterface;
use Drupal\webform\Entity\Webform;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'webform_test_block_context' block.
 *
 * @Block(
 *   id = "webform_test_ajax_block",
 *   admin_label = @Translation("Webform Ajax"),
 *   category = @Translation("Webform Test")
 * )
 */
class WebformTestAjaxBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The redirect destination service.
   *
   * @var \Drupal\Core\Routing\RedirectDestinationInterface
   */
  protected $redirectDestination;


  /**
   * Creates a WebformBlock instance.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\webform\WebformTokenManagerInterface $token_manager
   *   The webform token manager.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, RedirectDestinationInterface $redirect_destination) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->redirectDestination = $redirect_destination;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('redirect.destination')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $webforms = Webform::loadMultiple();

    $links = [];
    foreach ($webforms as $webform_id => $webform) {
      if (strpos($webform_id, 'test_ajax') !== 0 && $webform_id != 'test_form_wizard_long_100') {
        continue;
      }

      if (!in_array($webform_id, ['test_ajax_confirmation_page', 'test_ajax_confirmation_url', 'test_ajax_confirmation_url_msg'])) {
        // Add destination to Ajax webform that don't redirect to confirmation page or URL.
        $route_options = ['query' => $this->redirectDestination->getAsArray()];
      }
      else {
        $route_options = [];
      }

      $links[$webform_id] = [
        'title' => $this->t('Open @webform_id', ['@webform_id' => $webform_id]),
        'url' => $webform->toUrl('canonical', $route_options),
        'attributes' => [
          'data-dialog-type' => 'modal',
          'data-dialog-options' => Json::encode([
            'width' => 800,
          ]),
          'class' => [
            'use-ajax',
          ],
        ],
      ];
    }

    return [
      '#theme' => 'links',
      '#links' => $links,
      '#attached' => ['library' => ['core/drupal.ajax']],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheMaxAge() {
    return 0;
  }

}
