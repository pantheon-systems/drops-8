<?php

/**
 * @file
 * Contains \Drupal\linkit\Form\Attribute\DeleteForm.
 */

namespace Drupal\linkit\Form\Attribute;

use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\linkit\ProfileInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Provides a form to remove an attribute from a profile.
 */
class DeleteForm extends ConfirmFormBase {

  /**
   * The profiles that the attribute is applied to.
   *
   * @var \Drupal\linkit\ProfileInterface
   */
  protected $linkitProfile;

  /**
   * The attribute to be removed from the profile.
   *
   * @var \Drupal\linkit\AttributeInterface
   */
  protected $linkitAttribute;

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to delete the @plugin attribute from the %profile profile?', ['%profile' => $this->linkitProfile->label(), '@plugin' => $this->linkitAttribute->getLabel()]);
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return Url::fromRoute('linkit.attributes', [
      'linkit_profile' => $this->linkitProfile->id(),
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'linkit_attribute_delete_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, ProfileInterface $linkit_profile = NULL, $plugin_instance_id = NULL) {
    $this->linkitProfile = $linkit_profile;

    if (!$this->linkitProfile->getAttributes()->has($plugin_instance_id)) {
      throw new NotFoundHttpException();
    }

    $this->linkitAttribute = $this->linkitProfile->getAttribute($plugin_instance_id);
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    if ($this->linkitProfile->getAttributes()->has($this->linkitAttribute->getPluginId())) {
      $this->linkitProfile->removeAttribute($this->linkitAttribute->getPluginId());
      $this->linkitProfile->save();

      drupal_set_message($this->t('The attribute %label has been deleted.', ['%label' => $this->linkitAttribute->getLabel()]));
      $this->logger('linkit')->notice('The attribute %label has been deleted in the @profile profile.', [
        '%label' => $this->linkitAttribute->getLabel(),
        '@profile' => $this->linkitProfile->label(),
      ]);
    }

    $form_state->setRedirect('linkit.attributes', [
      'linkit_profile' => $this->linkitProfile->id(),
    ]);
  }

}
