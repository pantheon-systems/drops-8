<?php

namespace Drupal\metatag_views\Form;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\metatag\MetatagManagerInterface;
use Drupal\metatag_views\MetatagViewsValuesCleanerTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class MetatagViewsEditForm.
 *
 * @package Drupal\metatag_views\Form
 */
class MetatagViewsEditForm extends FormBase {

  use MetatagViewsValuesCleanerTrait;

  /**
   * Drupal\metatag\MetatagManager definition.
   *
   * @var \Drupal\metatag\MetatagManager
   */
  protected $metatagManager;

  /**
   * The Views manager.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $viewsManager;

  /**
   * Array of display settings from ViewEntityInterface::getDisplay().
   *
   * @var array
   */
  protected $display;

  /**
   * View entity object.
   *
   * @var \Drupal\views\ViewEntityInterface
   */
  protected $view;

  /**
   * {@inheritdoc}
   */
  public function __construct(MetatagManagerInterface $metatag_manager, EntityTypeManagerInterface $entity_manager) {
    $this->metatagManager = $metatag_manager;
    $this->viewsManager = $entity_manager->getStorage('view');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('metatag.manager'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'metatag_views_edit_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Get the parameters from request.
    $view_id = \Drupal::request()->get('view_id');
    $display_id = \Drupal::request()->get('display_id');

    // Get meta tags from the view entity.
    $metatags = [];
    if ($view_id && $display_id) {
      $metatags = metatag_get_view_tags($view_id, $display_id);
    }

    $form['metatags'] = $this->metatagManager->form($metatags, $form, ['view']);
    $form['metatags']['#title'] = $this->t('Metatags');
    $form['metatags']['#type'] = 'fieldset';

    // Need to create that AFTER the $form['metatags'] as the whole form is
    // passed to the $metatagManager->form() which causes duplicated field.
    $form['view'] = [
      '#type' => 'value',
      '#title' => $this->t('View'),
      '#weight' => -100,
      '#default_value' => $view_id . ':' . $display_id,
      '#required' => TRUE,
    ];

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $values, array $element, array $token_types = [], array $included_groups = NULL, array $included_tags = NULL) {
    // Add the outer fieldset.
    $element += [
      '#type' => 'details',
    ];

    $element += $this->tokenService->tokenBrowser($token_types);

    $groups_and_tags = $this->sortedGroupsWithTags();

    $first = TRUE;
    foreach ($groups_and_tags as $group_id => $group) {
      // Only act on groups that have tags and are in the list of included
      // groups (unless that list is null).
      if (isset($group['tags']) && (is_null($included_groups) || in_array($group_id, $included_groups))) {
        // Create the fieldset.
        $element[$group_id]['#type'] = 'details';
        $element[$group_id]['#title'] = $group['label'];
        $element[$group_id]['#description'] = $group['description'];
        $element[$group_id]['#open'] = $first;
        $first = FALSE;

        foreach ($group['tags'] as $tag_id => $tag) {
          // Only act on tags in the included tags list, unless that is null.
          if (is_null($included_tags) || in_array($tag_id, $included_tags)) {
            // Make an instance of the tag.
            $tag = $this->tagPluginManager->createInstance($tag_id);

            // Set the value to the stored value, if any.
            $tag_value = isset($values[$tag_id]) ? $values[$tag_id] : NULL;
            $tag->setValue($tag_value);

            // Create the bit of form for this tag.
            $element[$group_id][$tag_id] = $tag->form($element);
          }
        }
      }
    }

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Get the submitted form values.
    $view_name = $form_state->getValue('view');
    list($view_id, $display_id) = explode(':', $view_name);

    $metatags = $form_state->getValues();
    unset($metatags['view']);
    $metatags = $this->clearMetatagViewsDisallowedValues($metatags);

    /** @var \Drupal\views\ViewEntityInterface $view */
    $view = $this->viewsManager->load($view_id);

    // Store the meta tags on the view.
    $config_name = $view->getConfigDependencyName();
    $config_path = 'display.' . $display_id . '.display_options.display_extenders.metatag_display_extender.metatags';

    // Set configuration values based on form submission. This always edits the
    // original language.
    $configuration = $this->configFactory()->getEditable($config_name);
    if (empty($this->removeEmptyTags($metatags))) {
      $configuration->clear($config_path);
    }
    else {
      $configuration->set($config_path, $metatags);
    }
    $configuration->save();

    // Redirect back to the views list.
    $form_state->setRedirect('metatag_views.metatags.list');

    $this->messenger()->addMessage($this->t('Metatags for @view : @display have been saved.', [
      '@view' => $view->label(),
      '@display' => $view->getDisplay($display_id)['display_title'],
    ]));
  }

}
