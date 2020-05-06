<?php

namespace Drupal\webform\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\webform\Plugin\WebformVariantManagerInterface;
use Drupal\webform\WebformInterface;
use Drupal\webform\WebformTokenManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Provides an add form for webform variant.
 */
class WebformVariantAddForm extends WebformVariantFormBase {

  /**
   * The webform variant manager.
   *
   * @var \Drupal\webform\Plugin\WebformVariantManagerInterface
   */
  protected $webformVariantManager;

  /**
   * Constructs a WebformVariantAddForm.
   *
   * @param \Drupal\webform\WebformTokenManagerInterface $token_manager
   *   The webform token manager.
   * @param \Drupal\webform\Plugin\WebformVariantManagerInterface $webform_variant
   *   The webform variant manager.
   */
  public function __construct(WebformTokenManagerInterface $token_manager, WebformVariantManagerInterface $webform_variant) {
    parent::__construct($token_manager);
    $this->webformVariantManager = $webform_variant;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('webform.token_manager'),
      $container->get('plugin.manager.webform.variant')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, WebformInterface $webform = NULL, $webform_variant = NULL) {
    $form = parent::buildForm($form, $form_state, $webform, $webform_variant);
    // Throw access denied is variant is excluded.
    if ($this->webformVariant->isExcluded()) {
      throw new AccessDeniedHttpException();
    }

    $form['#title'] = $this->t('Add @label variant', ['@label' => $this->webformVariant->label()]);
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  protected function prepareWebformVariant($webform_variant) {
    /** @var \Drupal\webform\Plugin\WebformVariantInterface $webform_variant */
    $webform_variant = $this->webformVariantManager->createInstance($webform_variant);
    // Initialize the variant an pass in the webform.
    $webform_variant->setWebform($this->webform);
    // Set the initial weight so this variant comes last.
    $variants = $this->webform->getVariants();
    $weight = 0;
    foreach ($variants as $variant) {
      if ($weight < $variant->getWeight()) {
        $weight = $variant->getWeight() + 1;
      }
    }
    $webform_variant->setWeight($weight);
    return $webform_variant;
  }

}
