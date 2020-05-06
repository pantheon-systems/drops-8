<?php

namespace Drupal\webform_submission_export_import\Routing;

use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * Remove webform submission import export routes.
 */
class WebformSubmissionExportImportRouteSubscriber extends RouteSubscriberBase {

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Constructs a WebformSubmissionLogRouteSubscriber object.
   *
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   */
  public function __construct(ModuleHandlerInterface $module_handler) {
    $this->moduleHandler = $module_handler;
  }

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    if (!$this->moduleHandler->moduleExists('webform_node')) {
      $collection->remove('entity.node.webform_submission_export_import.results_import');
      $collection->remove('entity.node.webform_submission_export_import.results_import.example.download');
      $collection->remove('entity.node.webform_submission_export_import.results_import.example.view');
    }
  }

}
