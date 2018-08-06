<?php

/**
 * @file
 * Contains \Drupal\linkit\Form\Matcher\OverviewForm.
 */

namespace Drupal\linkit\Form\Matcher;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\linkit\ConfigurableMatcherInterface;
use Drupal\linkit\MatcherManager;
use Drupal\linkit\ProfileInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides an overview form for matchers on a profile.
 */
class OverviewForm extends FormBase {

  /**
   * The profiles to which the matchers are applied to.
   *
   * @var \Drupal\linkit\ProfileInterface
   */
  private $linkitProfile;

  /**
   * The matcher manager.
   *
   * @var \Drupal\linkit\MatcherManager
   */
  protected $manager;

  /**
   * Constructs a new OverviewForm.
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
    return "linkit_matcher_overview_form";
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, ProfileInterface $linkit_profile = NULL) {
    $this->linkitProfile = $linkit_profile;
    $form['#attached']['library'][] = 'linkit/linkit.admin';
    $form['plugins'] = [
      '#type' => 'table',
      '#header' => [
        [
          'data' => $this->t('Matcher'),
          'colspan' => 2
        ],
        $this->t('Weight'),
        $this->t('Operations'),
      ],
      '#empty' => $this->t('No matchers added.'),
      '#tabledrag' => [
        [
          'action' => 'order',
          'relationship' => 'sibling',
          'group' => 'plugin-order-weight',
        ],
      ],
    ];

    foreach ($this->linkitProfile->getMatchers() as $plugin) {
      $key = $plugin->getUuid();

      $form['plugins'][$key]['#attributes']['class'][] = 'draggable';
      $form['plugins'][$key]['#weight'] = $plugin->getWeight();

      $form['plugins'][$key]['label'] = [
        '#plain_text' => (string) $plugin->getLabel(),
      ];

      $form['plugins'][$key]['summary'] = [];

      $summary = $plugin->getSummary();
      if (!empty($summary)) {
        $form['plugins'][$key]['summary'] = [
          '#type' => 'inline_template',
          '#template' => '<div class="linkit-plugin-summary">{{ summary|safe_join("<br />") }}</div>',
          '#context' => ['summary' => $summary],
        ];
      }

      $form['plugins'][$key]['weight'] = [
        '#type' => 'weight',
        '#title' => t('Weight for @title', ['@title' => (string) $plugin->getLabel()]),
        '#title_display' => 'invisible',
        '#default_value' => $plugin->getWeight(),
        '#attributes' => ['class' => ['plugin-order-weight']],
      ];

      $form['plugins'][$key]['operations'] = [
        '#type' => 'operations',
        '#links' => [],
      ];

      $is_configurable = $plugin instanceof ConfigurableMatcherInterface;
      if ($is_configurable) {
        $form['plugins'][$key]['operations']['#links']['edit'] = [
          'title' => t('Edit'),
          'url' => Url::fromRoute('linkit.matcher.edit', [
            'linkit_profile' =>  $this->linkitProfile->id(),
            'plugin_instance_id' => $key,
          ]),
        ];
      }

      $form['plugins'][$key]['operations']['#links']['delete'] = [
        'title' => t('Delete'),
        'url' => Url::fromRoute('linkit.matcher.delete', [
          'linkit_profile' =>  $this->linkitProfile->id(),
          'plugin_instance_id' => $key,
        ]),
      ];
    }

    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save'),
      '#button_type' => 'primary',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    foreach ($form_state->getValue('plugins') as $id => $plugin_data) {
      if ($this->linkitProfile->getMatchers()->has($id)) {
        $this->linkitProfile->getMatcher($id)->setWeight($plugin_data['weight']);
      }
    }
    $this->linkitProfile->save();
  }

}
