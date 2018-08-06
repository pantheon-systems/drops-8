<?php

/**
 * @file
 * Contains \Drupal\linkit\Form\Attribute\EditForm.
 */

namespace Drupal\linkit\Form\Attribute;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormState;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\linkit\ProfileInterface;

/**
 *  Provides an edit form for attributes.
 */
class EditForm extends FormBase {

  /**
   * The profiles to which the attributes will be applied.
   *
   * @var \Drupal\linkit\ProfileInterface
   */
  protected $linkitProfile;

  /**
   * The attribute to edit.
   *
   * @var \Drupal\linkit\ConfigurableAttributeInterface
   */
  protected $linkitAttribute;

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'linkit_attribute_edit_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, ProfileInterface $linkit_profile = NULL, $plugin_instance_id = NULL) {
    $this->linkitProfile = $linkit_profile;
    $this->linkitAttribute = $this->linkitProfile->getAttribute($plugin_instance_id);
    $form['data'] = [
      '#tree' => true,
    ];

    $form['data'] += $this->linkitAttribute->buildConfigurationForm($form, $form_state);

    $form['actions'] = array('#type' => 'actions');
    $form['actions']['submit'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Save changes'),
      '#submit' => array('::submitForm'),
      '#button_type' => 'primary',
    );
    $form['actions']['delete'] = array(
      '#type' => 'link',
      '#title' => $this->t('Delete'),
      '#url' => Url::fromRoute('linkit.attribute.delete', [
        'linkit_profile' => $this->linkitProfile->id(),
        'plugin_instance_id' => $this->linkitAttribute->getPluginId(),
      ]),
      '#attributes' => [
        'class' => ['button', 'button--danger'],
      ],
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $form_state->cleanValues();
    $plugin_data = (new FormState())->setValues($form_state->getValue('data'));
    $this->linkitAttribute->submitConfigurationForm($form, $plugin_data);
    $this->linkitProfile->save();

    drupal_set_message($this->t('Saved %label configuration.', array('%label' => $this->linkitAttribute->getLabel())));
    $this->logger('linkit')->notice('The attribute %label has been updated in the @profile profile.', [
      '%label' => $this->linkitAttribute->getLabel(),
      '@profile' => $this->linkitProfile->label(),
    ]);

    $form_state->setRedirect('linkit.attributes', [
      'linkit_profile' => $this->linkitProfile->id(),
    ]);
  }

}
