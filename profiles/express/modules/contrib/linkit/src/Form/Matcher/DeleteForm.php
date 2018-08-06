<?php

/**
 * @file
 * Contains \Drupal\linkit\Form\Matcher\DeleteForm.
 */

namespace Drupal\linkit\Form\Matcher;

use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\linkit\ProfileInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Provides a form to remove a matcher from a profile.
 */
class DeleteForm extends ConfirmFormBase {

  /**
   * The profiles that the matcher is applied to.
   *
   * @var \Drupal\linkit\ProfileInterface
   */
  protected $linkitProfile;

  /**
   * The matcher to be removed from the profile.
   *
   * @var \Drupal\linkit\MatcherInterface
   */
  protected $linkitMatcher;

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to delete the @plugin matcher from the %profile profile?', ['%profile' => $this->linkitProfile->label(), '@plugin' => $this->linkitMatcher->getLabel()]);
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return Url::fromRoute('linkit.matchers', [
      'linkit_profile' => $this->linkitProfile->id(),
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'linkit_matcher_delete_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, ProfileInterface $linkit_profile = NULL, $plugin_instance_id = NULL) {
    $this->linkitProfile = $linkit_profile;

    if (!$this->linkitProfile->getMatchers()->has($plugin_instance_id)) {
      throw new NotFoundHttpException();
    }

    $this->linkitMatcher = $this->linkitProfile->getMatcher($plugin_instance_id);
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->linkitProfile->removeMatcher($this->linkitMatcher);

    drupal_set_message($this->t('The matcher %label has been deleted.', ['%label' => $this->linkitMatcher->getLabel()]));
    $this->logger('linkit')->notice('The matcher %label has been deleted in the @profile profile.', [
      '%label' => $this->linkitMatcher->getLabel(),
      '@profile' => $this->linkitProfile->label(),
    ]);

    $form_state->setRedirect('linkit.matchers', [
      'linkit_profile' => $this->linkitProfile->id(),
    ]);

  }

}
