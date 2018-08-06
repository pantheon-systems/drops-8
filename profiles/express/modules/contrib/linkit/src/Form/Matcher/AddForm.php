<?php

/**
 * @file
 * Contains \Drupal\linkit\Form\Matcher\AddForm.
 */

namespace Drupal\linkit\Form\Matcher;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\linkit\ConfigurableMatcherInterface;
use Drupal\linkit\MatcherManager;
use Drupal\linkit\ProfileInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a form to apply matchers to a profile.
 */
class AddForm extends FormBase {

  /**
   * The profiles to which the matchers will be applied.
   *
   * @var \Drupal\linkit\ProfileInterface
   */
  protected $linkitProfile;


  /**
   * The matcher manager.
   *
   * @var \Drupal\linkit\MatcherManager
   */
  protected $manager;

  /**
   * Constructs a new AddForm.
   *
   * @param \Drupal\linkit\MatcherManager $manager
   *   The matcher manager.
   */
  public function __construct(MatcherManager $manager) {
    $this->manager = $manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.linkit.matcher')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return "linkit_matcher_add_form";
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, ProfileInterface $linkit_profile = NULL) {
    $this->linkitProfile = $linkit_profile;

    $form['#attached']['library'][] = 'linkit/linkit.admin';
    $header = [
      'label' => $this->t('Matchers'),
    ];

    $form['plugin'] = [
      '#type' => 'tableselect',
      '#header' => $header,
      '#options' => $this->buildRows(),
      '#empty' => $this->t('No matchers available.'),
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

    $options = [];
    foreach ($this->manager->getDefinitions() as $id => $plugin) {
      $options[$id] = $plugin['label'];
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    if (empty($form_state->getValue('plugin'))) {
      $form_state->setErrorByName('plugin', $this->t('No matcher selected.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $form_state->cleanValues();

    /** @var \Drupal\linkit\MatcherInterface $plugin */
    $plugin = $this->manager->createInstance($form_state->getValue('plugin'));

    $plugin_uuid = $this->linkitProfile->addMatcher($plugin->getConfiguration());
    $this->linkitProfile->save();

    $this->logger('linkit')->notice('Added %label matcher to the @profile profile.', [
      '%label' => $this->linkitProfile->getMatcher($plugin_uuid)->getLabel(),
      '@profile' => $this->linkitProfile->label(),
    ]);

    $is_configurable = $plugin instanceof ConfigurableMatcherInterface;
    if ($is_configurable) {
      $form_state->setRedirect('linkit.matcher.edit', [
        'linkit_profile' => $this->linkitProfile->id(),
        'plugin_instance_id' => $plugin_uuid,
      ]);
    }
    else {
      drupal_set_message($this->t('Added %label matcher.', ['%label' => $plugin->getLabel()]));

      $form_state->setRedirect('linkit.matchers', [
        'linkit_profile' => $this->linkitProfile->id(),
      ]);
    }
  }

  /**
   * Builds the table rows.
   *
   * @return array
   *   An array of table rows.
   */
  private function buildRows() {
    $rows = [];
    $all_plugins = $this->manager->getDefinitions();
    uasort($all_plugins, function ($a, $b) {
      return strnatcasecmp($a['label'], $b['label']);
    });
    foreach ($all_plugins as $definition) {
      /** @var \Drupal\linkit\MatcherInterface $plugin */
      $plugin = $this->manager->createInstance($definition['id']);
      $row = [
        'label' => $plugin->getLabel(),
      ];
      $rows[$plugin->getPluginId()] = $row;
    }

    return $rows;
  }

}
