<?php

namespace Drupal\pathauto\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\pathauto\AliasTypeManager;
use Drupal\pathauto\AliasStorageHelperInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Alias mass delete form.
 */
class PathautoAdminDelete extends FormBase {

  /**
   * Provides helper methods for accessing alias storage.
   *
   * @var \Drupal\pathauto\AliasStorageHelperInterface
   */
  protected $aliasStorageHelper;

  /**
   * The alias type manager.
   *
   * @var \Drupal\pathauto\AliasTypeManager
   */
  protected $aliasTypeManager;

  /**
   * Constructs a PathautoAdminDelete object.
   *
   * @param \Drupal\pathauto\AliasStorageHelperInterface $alias_storage_helper
   *   Provides helper methods for accessing alias storage.
   * @param \Drupal\pathauto\AliasTypeManager $alias_type_manager
   *   The alias type manager.
   */
  public function __construct(AliasStorageHelperInterface $alias_storage_helper, AliasTypeManager $alias_type_manager) {
    $this->aliasStorageHelper = $alias_storage_helper;
    $this->aliasTypeManager = $alias_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('pathauto.alias_storage_helper'),
      $container->get('plugin.manager.alias_type')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'pathauto_admin_delete';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['delete'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Choose aliases to delete'),
      '#tree' => TRUE,
    ];

    // First we do the "all" case.
    $total_count = $this->aliasStorageHelper->countAll();
    $form['delete']['all_aliases'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('All aliases'),
      '#default_value' => FALSE,
      '#description' => $this->t('Delete all aliases. Number of aliases which will be deleted: %count.', ['%count' => $total_count]),
    ];

    // Next, iterate over all visible alias types.
    $definitions = $this->aliasTypeManager->getVisibleDefinitions();

    foreach ($definitions as $id => $definition) {
      /** @var \Drupal\pathauto\AliasTypeInterface $alias_type */
      $alias_type = $this->aliasTypeManager->createInstance($id);
      $count = $this->aliasStorageHelper->countBySourcePrefix($alias_type->getSourcePrefix());
      $form['delete']['plugins'][$id] = [
        '#type' => 'checkbox',
        '#title' => (string) $definition['label'],
        '#default_value' => FALSE,
        '#description' => $this->t('Delete aliases for all @label. Number of aliases which will be deleted: %count.', ['@label' => (string) $definition['label'], '%count' => $count]),
      ];
    }

    $form['options'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Delete options'),
      '#tree' => TRUE,
    ];

    // Provide checkbox for not deleting custom aliases.
    $form['options']['keep_custom_aliases'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Only delete automatically generated aliases'),
      '#default_value' => TRUE,
      '#description' => $this->t('When checked, aliases which have been manually set are not affected by this mass-deletion.'),
    ];

    // Warn them and give a button that shows we mean business.
    $form['warning'] = ['#markup' => '<p>' . $this->t('<strong>Note:</strong> there is no confirmation. Be sure of your action before clicking the "Delete aliases now!" button.<br />You may want to make a backup of the database and/or the path_alias and path_alias_revision tables prior to using this feature.') . '</p>'];
    $form['buttons']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Delete aliases now!'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $delete_all = $form_state->getValue(['delete', 'all_aliases']);
    // Keeping custom aliases forces us to go the slow way to correctly check
    // the automatic/manual flag.
    if ($form_state->getValue(['options', 'keep_custom_aliases'])) {
      $batch = [
        'title' => $this->t('Bulk deleting URL aliases'),
        'operations' => [['Drupal\pathauto\Form\PathautoAdminDelete::batchStart', [$delete_all]]],
        'finished' => 'Drupal\pathauto\Form\PathautoAdminDelete::batchFinished',
      ];

      if ($delete_all) {
        foreach (array_keys($form_state->getValue(['delete', 'plugins'])) as $id) {
          $batch['operations'][] = ['Drupal\pathauto\Form\PathautoAdminDelete::batchProcess', [$id]];
        }
      }
      else {
        foreach (array_keys(array_filter($form_state->getValue(['delete', 'plugins']))) as $id) {
          $batch['operations'][] = ['Drupal\pathauto\Form\PathautoAdminDelete::batchProcess', [$id]];
        }
      }

      batch_set($batch);
    }
    elseif ($delete_all) {
      $this->aliasStorageHelper->deleteAll();
      $this->messenger()->addMessage($this->t('All of your path aliases have been deleted.'));
    }
    else {
      foreach (array_keys(array_filter($form_state->getValue(['delete', 'plugins']))) as $id) {
        $alias_type = $this->aliasTypeManager->createInstance($id);
        $this->aliasStorageHelper->deleteBySourcePrefix((string) $alias_type->getSourcePrefix());
        $this->messenger()->addMessage($this->t('All of your %label path aliases have been deleted.', ['%label' => $alias_type->getLabel()]));
      }
    }
  }

  /**
   * Batch callback; record if aliases of all types must be deleted.
   */
  public static function batchStart($delete_all, &$context) {
    $context['results']['delete_all'] = $delete_all;
    $context['results']['deletions'] = [];
  }

  /**
   * Common batch processing callback for all operations.
   */
  public static function batchProcess($id, &$context) {
    /** @var \Drupal\pathauto\AliasTypeBatchUpdateInterface $alias_type */
    $alias_type = \Drupal::service('plugin.manager.alias_type')->createInstance($id);
    $alias_type->batchDelete($context);
  }

  /**
   * Batch finished callback.
   */
  public static function batchFinished($success, $results, $operations) {
    if ($success) {
      if ($results['delete_all']) {
        \Drupal::service('messenger')
          ->addMessage(t('All of your automatically generated path aliases have been deleted.'));
      }
      elseif (isset($results['deletions'])) {
        foreach (array_values($results['deletions']) as $label) {
          \Drupal::service('messenger')
            ->addMessage(t('All of your automatically generated %label path aliases have been deleted.', [
              '%label' => $label,
            ]));
        }
      }
    }
    else {
      $error_operation = reset($operations);
      \Drupal::service('messenger')
        ->addMessage(t('An error occurred while processing @operation with arguments : @args', [
          '@operation' => $error_operation[0],
          '@args' => print_r($error_operation[0]),
        ]));
    }
  }

}
