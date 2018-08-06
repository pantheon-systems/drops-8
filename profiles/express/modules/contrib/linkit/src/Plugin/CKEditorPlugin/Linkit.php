<?php

/**
 * @file
 * Contains \Drupal\linkit\Plugin\CKEditorPlugin\Linkit.
 */

namespace Drupal\linkit\Plugin\CKEditorPlugin;

use Drupal\ckeditor\CKEditorPluginBase;
use Drupal\ckeditor\CKEditorPluginConfigurableInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\editor\Entity\Editor;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines the "linkit" plugin.
 *
 * @CKEditorPlugin(
 *   id = "linkit",
 *   label = @Translation("Linkit"),
 *   module = "linkit"
 * )
 */
class Linkit extends CKEditorPluginBase implements CKEditorPluginConfigurableInterface, ContainerFactoryPluginInterface {

  /**
   * The Linkit profile storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $linkitProfileStorage;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityStorageInterface $linkit_profile_storage) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->linkitProfileStorage = $linkit_profile_storage;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity.manager')->getStorage('linkit_profile')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFile() {
    return drupal_get_path('module', 'linkit') . '/js/plugins/linkit/plugin.js';
  }

  /**
   * {@inheritdoc}
   */
  public function getConfig(Editor $editor) {
    return array(
      'linkit_dialogTitleAdd' => t('Add link'),
      'linkit_dialogTitleEdit' => t('Edit link'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getButtons() {
    return array(
      'Linkit' => array(
        'label' => t('Linkit'),
        'image' => drupal_get_path('module', 'linkit') . '/js/plugins/linkit/linkit.png',
      ),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state, Editor $editor) {
    $settings = $editor->getSettings();

    $all_profiles = $this->linkitProfileStorage->loadMultiple();

    $options = array();
    foreach ($all_profiles as $profile) {
      $options[$profile->id()] = $profile->label();
    }

    $form['linkit_profile'] = array(
      '#type' => 'select',
      '#title' => t('Select a linkit profile'),
      '#options' => $options,
      '#default_value' => isset($settings['plugins']['linkit']) ? $settings['plugins']['linkit'] : '',
      '#empty_option' => $this->t('- Select profile -'),
      '#description' => $this->t('Select the linkit profile you wish to use with this text format.'),
      '#element_validate' => array(
        array($this, 'validateLinkitProfileSelection'),
      ),
    );

    return $form;
  }

  /**
   * #element_validate handler for the "linkit_profile" element in settingsForm().
   */
  public function validateLinkitProfileSelection(array $element, FormStateInterface $form_state) {
    $toolbar_buttons = $form_state->getValue(array('editor', 'settings', 'toolbar', 'button_groups'));
    if (strpos($toolbar_buttons, '"Linkit"') !== FALSE && empty($element['#value'])) {
      $form_state->setError($element, t('Please select the linkit profile you wish to use.'));
    }
  }

}
