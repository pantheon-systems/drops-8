<?php

namespace Drupal\webform\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\webform\Plugin\WebformHandlerManagerInterface;
use Drupal\webform\WebformInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Provides an add form for webform handler.
 */
class WebformHandlerAddForm extends WebformHandlerFormBase {

  /**
   * The webform handler manager.
   *
   * @var \Drupal\webform\Plugin\WebformHandlerManagerInterface
   */
  protected $webformHandlerManager;

  /**
   * Constructs a WebformHandlerAddForm.
   *
   * @param \Drupal\webform\Plugin\WebformHandlerManagerInterface $webform_handler
   *   The webform handler manager.
   */
  public function __construct(WebformHandlerManagerInterface $webform_handler) {
    $this->webformHandlerManager = $webform_handler;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.webform.handler')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, WebformInterface $webform = NULL, $webform_handler = NULL) {
    $form = parent::buildForm($form, $form_state, $webform, $webform_handler);
    // Throw access denied is handler is excluded.
    if ($this->webformHandler->isExcluded()) {
      throw new AccessDeniedHttpException();
    }

    $form['#title'] = $this->t('Add @label handler', ['@label' => $this->webformHandler->label()]);
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  protected function prepareWebformHandler($webform_handler) {
    /** @var \Drupal\webform\Plugin\WebformHandlerInterface $webform_handler */
    $webform_handler = $this->webformHandlerManager->createInstance($webform_handler);
    // Initialize the handler an pass in the webform.
    $webform_handler->setWebform($this->webform);
    // Set the initial weight so this handler comes last.
    $handlers = $this->webform->getHandlers();
    $weight = 0;
    foreach ($handlers as $handler) {
      if ($weight < $handler->getWeight()) {
        $weight = $handler->getWeight() + 1;
      }
    }
    $webform_handler->setWeight($weight);
    return $webform_handler;
  }

}
