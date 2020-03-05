<?php

namespace Drupal\ctools\Controller;

use Drupal\Core\Controller\ControllerResolverInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\ctools\Wizard\WizardFactoryInterface;

/**
 * Wrapping controller for wizard forms that serve as the main page body.
 */
class WizardEntityFormController extends WizardFormController {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * @param \Drupal\Core\Controller\ControllerResolverInterface $controller_resolver
   *   The controller resolver.
   * @param \Drupal\Core\Form\FormBuilderInterface $form_builder
   *   The form builder.
   * @param \Drupal\ctools\Wizard\WizardFactoryInterface $wizard_factory
   *   The wizard factory.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(ControllerResolverInterface $controller_resolver, FormBuilderInterface $form_builder, WizardFactoryInterface $wizard_factory, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($controller_resolver, $form_builder, $wizard_factory);
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  protected function getFormArgument(RouteMatchInterface $route_match) {
    $form_arg = $route_match->getRouteObject()->getDefault('_entity_wizard');
    list($entity_type_id, $operation) = explode('.', $form_arg);
    $definition = $this->entityTypeManager->getDefinition($entity_type_id);
    $handlers = $definition->getHandlerClasses();
    if (empty($handlers['wizard'][$operation])) {
      throw new \Exception(sprintf('Unsupported wizard operation %s', $operation));
    }
    return $handlers['wizard'][$operation];
  }

}
