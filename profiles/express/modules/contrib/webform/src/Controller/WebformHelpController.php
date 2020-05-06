<?php

namespace Drupal\webform\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\webform\WebformHelpManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides route responses for Webform help.
 */
class WebformHelpController extends ControllerBase implements ContainerInjectionInterface {

  /**
   * The webform help manager.
   *
   * @var \Drupal\webform\WebformHelpManagerInterface
   */
  protected $help;

  /**
   * Constructs a WebformHelpController object.
   *
   * @param \Drupal\webform\WebformHelpManagerInterface $help
   *   The webform help manager.
   */
  public function __construct(WebformHelpManagerInterface $help) {
    $this->help = $help;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('webform.help_manager')
    );
  }

  /**
   * Returns the Webform help page.
   *
   * @return array
   *   The webform submission webform.
   */
  public function index() {
    return $this->help->buildIndex();
  }

}
