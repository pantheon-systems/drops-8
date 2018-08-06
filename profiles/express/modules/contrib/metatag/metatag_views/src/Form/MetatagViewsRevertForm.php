<?php

namespace Drupal\metatag_views\Form;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\views\ViewEntityInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a confirmation form for deleting mymodule data.
 */
class MetatagViewsRevertForm extends ConfirmFormBase {

  /**
   * Entity manager for views entities.
   *
   * @var EntityTypeManagerInterface
   */
  protected $viewsManager;

  /**
   * The view entity to revert metatags on.
   *
   * @var ViewEntityInterface $view
   */
  protected $view;

  /**
   * The view's display id.
   *
   * @var string
   */
  protected $display_id;

  /**
   * Constructor.
   */
  public function __construct(EntityTypeManagerInterface $entity_manager) {
    $this->viewsManager = $entity_manager->getStorage('view');
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
    return $this->t('Do you want to revert metatags for @view_name : @display_name?', [
      '@view_name' => $this->view->label(),
      '@display_name' => $this->view->getDisplay($this->display_id)['display_title'],
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
    return $this->t('You are about to revert the custom metatags for the %display_name display on the %view_name view. This action cannot be undone.', [
      '%view_name' => $this->view->label(),
      '%display_name' => $this->view->getDisplay($this->display_id)['display_title'],
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
   *
   * @param int $id
   *   (optional) The ID of the item to be deleted.
   */
  public function buildForm(array $form, FormStateInterface $form_state, $view_id = NULL, $display_id = NUL) {
    $this->view = $this->viewsManager->load($view_id);
    $this->display_id = $display_id;

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Removed metatags from the view.
    $config_name = $this->view->getConfigDependencyName();
    $config_path = 'display.' . $this->display_id . '.display_options.display_extenders.metatag_display_extender.metatags';

    $configuration = $this->configFactory()->getEditable($config_name)
      ->clear($config_path)
      ->save();

    // Redirect back to the views list.
    $form_state->setRedirect('metatag_views.metatags.list');

    drupal_set_message($this->t('Reverted metatags for @view_name : @display_name', [
      '@view_name' => $this->view->label(),
      '@display_name' => $this->view->getDisplay($this->display_id)['display_title'],
    ]));
  }

}
