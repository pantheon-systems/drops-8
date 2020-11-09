<?php

namespace Drupal\metatag_views\Form;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a form for reverting views metatags.
 */
class MetatagViewsRevertForm extends ConfirmFormBase {

  /**
   * Entity manager for views entities.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $viewsManager;

  /**
   * The view entity to revert meta tags on.
   *
   * @var \Drupal\views\ViewEntityInterface
   */
  protected $view;

  /**
   * The view's display id.
   *
   * @var string
   */
  protected $displayId;

  /**
   * {@inheritdoc}
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->viewsManager = $entity_type_manager->getStorage('view');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'metatag_views_revert_form';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Do you want to revert meta tags for @view_name : @display_name?', [
      '@view_name' => $this->view->label(),
      '@display_name' => $this->view->getDisplay($this->displayId)['display_title'],
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('metatag_views.metatags.list');
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->t('You are about to revert the custom meta tags for the %display_name display on the %view_name view. This action cannot be undone.', [
      '%view_name' => $this->view->label(),
      '%display_name' => $this->view->getDisplay($this->displayId)['display_title'],
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Revert');
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelText() {
    return $this->t('Cancel');
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $view_id = NULL, $display_id = NUL) {
    $this->view = $this->viewsManager->load($view_id);
    $this->displayId = $display_id;

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Removed meta tags from the view.
    $config_name = $this->view->getConfigDependencyName();
    $config_path = 'display.' . $this->displayId . '.display_options.display_extenders.metatag_display_extender.metatags';

    $this->configFactory()->getEditable($config_name)
      ->clear($config_path)
      ->save();

    // Redirect back to the views list.
    $form_state->setRedirect('metatag_views.metatags.list');

    $this->messenger()->addMessage($this->t('Reverted meta tags for @view_name : @display_name', [
      '@view_name' => $this->view->label(),
      '@display_name' => $this->view->getDisplay($this->displayId)['display_title'],
    ]));
  }

}
