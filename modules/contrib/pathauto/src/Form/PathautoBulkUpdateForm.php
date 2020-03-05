<?php

namespace Drupal\pathauto\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\pathauto\AliasTypeBatchUpdateInterface;
use Drupal\pathauto\AliasTypeManager;
use Drupal\pathauto\PathautoGeneratorInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configure file system settings for this site.
 */
class PathautoBulkUpdateForm extends FormBase {

  /**
   * Generate URL aliases for un-aliased paths only.
   */
  const ACTION_CREATE = 'create';

  /**
   * Update URL aliases for paths that have an existing alias.
   */
  const ACTION_UPDATE = 'update';

  /**
   * Regenerate URL aliases for all paths.
   */
  const ACTION_ALL = 'all';

  /**
   * The alias type manager.
   *
   * @var \Drupal\pathauto\AliasTypeManager
   */
  protected $aliasTypeManager;

  /**
   * Constructs a PathautoBulkUpdateForm object.
   *
   * @param \Drupal\pathauto\AliasTypeManager $alias_type_manager
   *   The alias type manager.
   */
  public function __construct(AliasTypeManager $alias_type_manager) {
    $this->aliasTypeManager = $alias_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.alias_type')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'pathauto_bulk_update_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $form = [];

    $form['#update_callbacks'] = [];

    $form['update'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Select the types of paths for which to generate URL aliases'),
      '#options' => [],
      '#default_value' => [],
    ];

    $definitions = $this->aliasTypeManager->getVisibleDefinitions();

    foreach ($definitions as $id => $definition) {
      $alias_type = $this->aliasTypeManager->createInstance($id);
      if ($alias_type instanceof AliasTypeBatchUpdateInterface) {
        $form['update']['#options'][$id] = $alias_type->getLabel();
      }
    }

    $form['action'] = [
      '#type' => 'radios',
      '#title' => $this->t('Select which URL aliases to generate'),
      '#options' => [static::ACTION_CREATE => $this->t('Generate a URL alias for un-aliased paths only')],
      '#default_value' => static::ACTION_CREATE,
    ];

    $config = $this->config('pathauto.settings');

    if ($config->get('update_action') == PathautoGeneratorInterface::UPDATE_ACTION_NO_NEW) {
      // Existing aliases should not be updated.
      $form['warning'] = [
        '#markup' => $this->t('<a href=":url">Pathauto settings</a> are set to ignore paths which already have a URL alias. You can only create URL aliases for paths having none.', [':url' => Url::fromRoute('pathauto.settings.form')->toString()]),
      ];
    }
    else {
      $form['action']['#options'][static::ACTION_UPDATE] = $this->t('Update the URL alias for paths having an old URL alias');
      $form['action']['#options'][static::ACTION_ALL] = $this->t('Regenerate URL aliases for all paths');
    }

    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Update'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $batch = [
      'title' => $this->t('Bulk updating URL aliases'),
      'operations' => [
        ['Drupal\pathauto\Form\PathautoBulkUpdateForm::batchStart', []],
      ],
      'finished' => 'Drupal\pathauto\Form\PathautoBulkUpdateForm::batchFinished',
    ];

    $action = $form_state->getValue('action');

    foreach ($form_state->getValue('update') as $id) {
      if (!empty($id)) {
        $batch['operations'][] = ['Drupal\pathauto\Form\PathautoBulkUpdateForm::batchProcess', [$id, $action]];
      }
    }

    batch_set($batch);
  }

  /**
   * Batch callback; initialize the number of updated aliases.
   */
  public static function batchStart(&$context) {
    $context['results']['updates'] = 0;
  }

  /**
   * Common batch processing callback for all operations.
   *
   * Required to load our include the proper batch file.
   */
  public static function batchProcess($id, $action, &$context) {
    /** @var \Drupal\pathauto\AliasTypeBatchUpdateInterface $alias_type */
    $alias_type = \Drupal::service('plugin.manager.alias_type')->createInstance($id);
    $alias_type->batchUpdate($action, $context);
  }

  /**
   * Batch finished callback.
   */
  public static function batchFinished($success, $results, $operations) {
    if ($success) {
      if ($results['updates']) {
        \Drupal::service('messenger')->addMessage(\Drupal::translation()
          ->formatPlural($results['updates'], 'Generated 1 URL alias.', 'Generated @count URL aliases.'));
      }
      else {
        \Drupal::service('messenger')
          ->addMessage(t('No new URL aliases to generate.'));
      }
    }
    else {
      $error_operation = reset($operations);
      \Drupal::service('messenger')
        ->addMessage(t('An error occurred while processing @operation with arguments : @args'), [
          '@operation' => $error_operation[0],
          '@args' => print_r($error_operation[0]),
        ]);
    }
  }

}
