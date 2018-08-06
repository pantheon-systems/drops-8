<?php

/**
 * @file
 * Contains \Drupal\linkit\Form\Attribute\AddForm.
 */

namespace Drupal\linkit\Form\Attribute;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\linkit\AttributeManager;
use Drupal\linkit\ConfigurableAttributeInterface;
use Drupal\linkit\ProfileInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a form to apply attributes to a profile.
 */
class AddForm extends FormBase {

  /**
   * The profiles to which the attributes will be applied.
   *
   * @var \Drupal\linkit\ProfileInterface
   */
  protected $linkitProfile;

  /**
   * The attribute manager.
   *
   * @var \Drupal\linkit\AttributeManager
   */
  protected $manager;

  /**
   * Constructs a new AddForm.
   *
   * @param \Drupal\linkit\AttributeManager $manager
   *   The attribute manager.
   */
  public function __construct(AttributeManager $manager) {
    $this->manager = $manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.linkit.attribute')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return "linkit_attribute_add_form";
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, ProfileInterface $linkit_profile = NULL) {
    $this->linkitProfile = $linkit_profile;

    $form['#attached']['library'][] = 'linkit/linkit.admin';
    $header = [
      'label' => $this->t('Attributes'),
      'description' => $this->t('Description'),
    ];

    $form['plugin'] = [
      '#type' => 'tableselect',
      '#header' => $header,
      '#options' => $this->buildRows(),
      '#empty' => $this->t('No attributes available.'),
      '#multiple' => FALSE,
    ];

    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save and continue'),
      '#submit' => ['::submitForm'],
      '#tableselect' => TRUE,
      '#button_type' => 'primary',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    if (empty($form_state->getValue('plugin'))) {
      $form_state->setErrorByName('plugin', $this->t('No attribute selected.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $form_state->cleanValues();

    /** @var \Drupal\linkit\AttributeInterface $plugin */
    $plugin = $this->manager->createInstance($form_state->getValue('plugin'));
    $plugin_id = $this->linkitProfile->addAttribute($plugin->getConfiguration());
    $this->linkitProfile->save();

    $this->logger('linkit')->notice('Added %label attribute to the @profile profile.', [
      '%label' => $this->linkitProfile->getAttribute($plugin_id)->getLabel(),
      '@profile' => $this->linkitProfile->label(),
    ]);

    $is_configurable = $plugin instanceof ConfigurableAttributeInterface;
    if ($is_configurable) {
      $form_state->setRedirect('linkit.attribute.edit', [
        'linkit_profile' => $this->linkitProfile->id(),
        'plugin_instance_id' => $plugin_id,
      ]);
    }
    else {
      drupal_set_message($this->t('Added %label attribute.', ['%label' => $plugin->getLabel()]));

      $form_state->setRedirect('linkit.attributes', [
        'linkit_profile' => $this->linkitProfile->id(),
      ]);
    }
  }

  /**
   * Builds the table rows.
   *
   * Only attributes that is not already applied to the profile are shown.
   *
   * @return array
   *   An array of table rows.
   */
  private function buildRows() {
    $rows = [];

    $applied_plugins = $this->linkitProfile->getAttributes()->getConfiguration();
    $all_plugins = $this->manager->getDefinitions();
    uasort($all_plugins, function ($a, $b) {
      return strnatcasecmp($a['label'], $b['label']);
    });
    foreach (array_diff_key($all_plugins, $applied_plugins) as $definition) {
      /** @var \Drupal\linkit\AttributeInterface $plugin */
      $plugin = $this->manager->createInstance($definition['id']);

      $row = [
        'label' => (string) $plugin->getLabel(),
        'description' => (string) $plugin->getDescription(),
      ];

      $rows[$plugin->getPluginId()] = $row;
    }

    return $rows;
  }

}
