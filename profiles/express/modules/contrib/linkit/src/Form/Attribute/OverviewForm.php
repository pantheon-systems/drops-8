<?php

/**
 * @file
 * Contains \Drupal\linkit\Form\Attribute\OverviewForm.
 */

namespace Drupal\linkit\Form\Attribute;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\linkit\AttributeManager;
use Drupal\linkit\ConfigurableAttributeInterface;
use Drupal\linkit\ProfileInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides an overview form for attribute on a profile.
 */
class OverviewForm extends FormBase {

  /**
   * The profiles to which the attributes are applied to.
   *
   * @var \Drupal\linkit\ProfileInterface
   */
  private $linkitProfile;

  /**
   * The attribute manager.
   *
   * @var \Drupal\linkit\AttributeManager
   */
  protected $manager;

  /**
   * Constructs a new OverviewForm.
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
    return "linkit_attribute_overview_form";
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, ProfileInterface $linkit_profile = NULL) {
    $this->linkitProfile = $linkit_profile;

    $form['plugins'] = [
      '#type' => 'table',
      '#header' => [
        $this->t('Attribute'),
        $this->t('Description'),
        $this->t('Weight'),
        $this->t('Operations'),
      ],
      '#empty' => $this->t('No attributes added.'),
      '#tabledrag' => [
        [
          'action' => 'order',
          'relationship' => 'sibling',
          'group' => 'plugin-order-weight',
        ],
      ],
    ];

    foreach ($this->linkitProfile->getAttributes() as $plugin) {
      $key = $plugin->getPluginId();

      $form['plugins'][$key]['#attributes']['class'][] = 'draggable';
      $form['plugins'][$key]['#weight'] = $plugin->getWeight();

      $form['plugins'][$key]['label'] = [
        '#plain_text' => (string) $plugin->getLabel(),
      ];

      $form['plugins'][$key]['description'] = [
        '#plain_text' => (string) $plugin->getDescription(),
      ];

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

      $is_configurable = $plugin instanceof ConfigurableAttributeInterface;
      if ($is_configurable) {
        $form['plugins'][$key]['operations']['#links']['edit'] = [
          'title' => t('Edit'),
          'url' => Url::fromRoute('linkit.attribute.edit', [
            'linkit_profile' =>  $this->linkitProfile->id(),
            'plugin_instance_id' => $key,
          ]),
        ];
      }

      $form['plugins'][$key]['operations']['#links']['delete'] = [
        'title' => t('Delete'),
        'url' => Url::fromRoute('linkit.attribute.delete', [
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
      if ($this->linkitProfile->getAttributes()->has($id)) {
        $this->linkitProfile->getAttribute($id)->setWeight($plugin_data['weight']);
      }
    }
    $this->linkitProfile->save();
  }

}
