<?php

/**
 * @file
 * Contains \Drupal\linkit\Form\LinkitEditorDialog.
 */

namespace Drupal\linkit\Form;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\filter\Entity\FilterFormat;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\editor\Ajax\EditorDialogSave;
use Drupal\Core\Ajax\CloseModalDialogCommand;
use Drupal\linkit\AttributeCollection;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a linkit dialog for text editors.
 */
class LinkitEditorDialog extends FormBase {

  /**
   * The editor storage service.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $editorStorage;

  /**
   * The linkit profile storage service.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $linkitProfileStorage;

  /**
   * The linkit profile.
   *
   * @var \Drupal\linkit\ProfileInterface
   */
  protected $linkitProfile;

  /**
   * Constructs a form object for linkit dialog.
   *
   * @param \Drupal\Core\Entity\EntityStorageInterface $editor_storage
   *   The editor storage service.
   * @param \Drupal\Core\Entity\EntityStorageInterface $linkit_profile_storage
   *   The linkit profile storage service.
   */
  public function __construct(EntityStorageInterface $editor_storage, EntityStorageInterface $linkit_profile_storage) {
    $this->editorStorage = $editor_storage;
    $this->linkitProfileStorage = $linkit_profile_storage;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.manager')->getStorage('editor'),
      $container->get('entity.manager')->getStorage('linkit_profile')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'linkit_editor_dialog_form';
  }

  /**
   * {@inheritdoc}
   *
   * @param \Drupal\filter\Entity\FilterFormat $filter_format
   *   The filter format for which this dialog corresponds.
   */
  public function buildForm(array $form, FormStateInterface $form_state, FilterFormat $filter_format = NULL) {
    // The default values are set directly from \Drupal::request()->request,
    // provided by the editor plugin opening the dialog.
    $user_input = $form_state->getUserInput();
    $input = isset($user_input['editor_object']) ? $user_input['editor_object'] : [];

    /** @var \Drupal\editor\EditorInterface $editor */
    $editor = $this->editorStorage->load($filter_format->id());
    $linkit_profile_id = $editor->getSettings()['plugins']['linkit']['linkit_profile'];
    $this->linkitProfile = $this->linkitProfileStorage->load($linkit_profile_id);

    $form['#tree'] = TRUE;
    $form['#attached']['library'][] = 'editor/drupal.editor.dialog';
    $form['#prefix'] = '<div id="linkit-editor-dialog-form">';
    $form['#suffix'] = '</div>';

    // Everything under the "attributes" key is merged directly into the
    // generated link tag's attributes.
    $form['attributes']['href'] = [
      '#title' => $this->t('Link'),
      '#type' => 'linkit',
      '#default_value' => isset($input['href']) ? $input['href'] : '',
      '#description' => $this->t('Start typing to find content or paste a URL.'),
      '#autocomplete_route_name' => 'linkit.autocomplete',
      '#autocomplete_route_parameters' => [
        'linkit_profile_id' => $linkit_profile_id
      ],
      '#weight' => 0,
    ];

    $this->addAttributes($form, $form_state, $this->linkitProfile->getAttributes(), $input);

    $form['actions'] = [
      '#type' => 'actions',
    ];

    $form['actions']['save_modal'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save'),
      '#submit' => [],
      '#ajax' => [
        'callback' => '::submitForm',
        'event' => 'click',
      ],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $attributes = array_filter($form_state->getValue('attributes'));
    $form_state->setValue('attributes', $attributes);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $response = new AjaxResponse();

    if ($form_state->getErrors()) {
      unset($form['#prefix'], $form['#suffix']);
      $form['status_messages'] = [
        '#type' => 'status_messages',
        '#weight' => -10,
      ];
      $response->addCommand(new HtmlCommand('#linkit-editor-dialog-form', $form));
    }
    else {
      $response->addCommand(new EditorDialogSave($form_state->getValues()));
      $response->addCommand(new CloseModalDialogCommand());
    }

    return $response;
  }

  /**
   * Adds the attributes enabled on the current profile.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param AttributeCollection $attributes
   *   A collection of attributes for the current profile.
   * @param array $input
   *   An array with the attribute values from the editor.
   */
  private function addAttributes(array &$form, FormStateInterface &$form_state, AttributeCollection $attributes, array $input) {
    if ($attributes->count()) {
      $form['linkit_attributes'] = [
        '#type' => 'container',
        '#title' => $this->t('Attributes'),
        '#weight' => '10',
      ];

      /** @var \Drupal\linkit\AttributeInterface $plugin */
      foreach ($attributes as $plugin) {
        $plugin_name = $plugin->getHtmlName();

        $default_value = isset($input[$plugin_name]) ? $input[$plugin_name] : '';
        $form['linkit_attributes'][$plugin_name] = $plugin->buildFormElement($default_value);
        $form['linkit_attributes'][$plugin_name] += [
          '#parents' => [
            'attributes', $plugin_name,
          ],
        ];
      }
    }
  }

  /**
   * Gets the linkit profile entity.
   *
   * @return \Drupal\linkit\ProfileInterface
   *   The current linkit profile used by this form.
   */
  public function getLinkitProfile() {
    return $this->linkitProfile;
  }

}
