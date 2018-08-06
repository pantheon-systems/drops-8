<?php

/**
 * @file
 * Contains \Drupal\linkit\Form\Matcher\EditForm.
 */

namespace Drupal\linkit\Form\Matcher;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormState;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\linkit\ProfileInterface;

/**
 *  Provides an edit form for matchers.
 */
class EditForm extends FormBase {

  /**
   * The profiles to which the matchers will be applied.
   *
   * @var \Drupal\linkit\ProfileInterface
   */
  protected $linkitProfile;

  /**
   * The matcher to edit.
   *
   * @var \Drupal\linkit\ConfigurableMatcherInterface
   */
  protected $linkitMatcher;

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'linkit_matcher_edit_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, ProfileInterface $linkit_profile = NULL, $plugin_instance_id = NULL) {
    $this->linkitProfile = $linkit_profile;
    $this->linkitMatcher = $this->linkitProfile->getMatcher($plugin_instance_id);

    $form += $this->linkitMatcher->buildConfigurationForm($form, $form_state);

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
      '#url' => Url::fromRoute('linkit.matcher.delete', [
        'linkit_profile' => $this->linkitProfile->id(),
        'plugin_instance_id' => $this->linkitMatcher->getUuid(),
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
    $plugin_data = (new FormState())->setValues($form_state->getValues());
    $this->linkitMatcher->submitConfigurationForm($form, $plugin_data);
    $this->linkitProfile->save();

    drupal_set_message($this->t('Saved %label configuration.', array('%label' => $this->linkitMatcher->getLabel())));
    $this->logger('linkit')->notice('The matcher %label has been updated in the @profile profile.', [
      '%label' => $this->linkitMatcher->getLabel(),
      '@profile' => $this->linkitProfile->label(),
    ]);

    $form_state->setRedirect('linkit.matchers', [
      'linkit_profile' => $this->linkitProfile->id(),
    ]);
  }
}
