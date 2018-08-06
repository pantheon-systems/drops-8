<?php

namespace Drupal\entity_browser\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\entity_browser\EntityBrowserInterface;

/**
 * Base class for steps in entity browser form wizard.
 */
abstract class PluginConfigFormBase extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\entity_browser\EntityBrowserInterface $entity_browser */
    $entity_browser = $form_state->getTemporaryValue('wizard')['entity_browser'];
    $form = $this->getPlugin($entity_browser)->buildConfigurationForm($form, $form_state);

    $fields = Element::children($form);
    if (empty($fields)) {
      $form['no_options'] = [
        '#prefix' => '<p>',
        '#suffix' => '</p>',
        '#markup' => $this->t('This plugin has no configuration options.'),
      ];
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    /** @var \Drupal\entity_browser\EntityBrowserInterface $entity_browser */
    $entity_browser = $form_state->getTemporaryValue('wizard')['entity_browser'];
    $this->getPlugin($entity_browser)->validateConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    /** @var \Drupal\entity_browser\EntityBrowserInterface $entity_browser */
    $entity_browser = $form_state->getTemporaryValue('wizard')['entity_browser'];
    $this->getPlugin($entity_browser)->submitConfigurationForm($form, $form_state);
  }

  /**
   * Gets plugin that form operates with.
   *
   * @return \Drupal\Core\Plugin\PluginFormInterface|\Drupal\Component\Plugin\PluginInspectionInterface
   *   Plugin instance.
   */
  abstract public function getPlugin(EntityBrowserInterface $entity_browser);

}
