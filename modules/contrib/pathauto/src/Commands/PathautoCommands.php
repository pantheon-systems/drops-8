<?php

namespace Drupal\pathauto\Commands;

use Consolidation\AnnotatedCommand\CommandData;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\pathauto\AliasStorageHelperInterface;
use Drupal\pathauto\AliasTypeBatchUpdateInterface;
use Drupal\pathauto\AliasTypeManager;
use Drupal\pathauto\Form\PathautoBulkUpdateForm;
use Drupal\pathauto\PathautoGeneratorInterface;
use Drush\Commands\DrushCommands;
use Symfony\Component\Console\Input\Input;
use Symfony\Component\Console\Output\Output;
use Symfony\Component\Console\Question\ChoiceQuestion;

/**
 * Drush commands allowing to perform Pathauto tasks from the command line.
 */
class PathautoCommands extends DrushCommands {

  /**
   * The argument option for generating URL aliases of all possible types.
   */
  const TYPE_ALL = 'all';

  /**
   * The configuration object factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The alias type manager.
   *
   * @var \Drupal\pathauto\AliasTypeManager
   */
  protected $aliasTypeManager;

  /**
   * The alias storage helper.
   *
   * @var \Drupal\pathauto\AliasStorageHelperInterface
   */
  protected $aliasStorageHelper;

  /**
   * Constructs a new PathautoCommands object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The configuration object factory.
   * @param \Drupal\pathauto\AliasTypeManager $aliasTypeManager
   *   The alias type manager.
   * @param \Drupal\pathauto\AliasStorageHelperInterface $aliasStorageHelper
   *   The alias storage helper.
   */
  public function __construct(ConfigFactoryInterface $configFactory, AliasTypeManager $aliasTypeManager, AliasStorageHelperInterface $aliasStorageHelper) {
    $this->configFactory = $configFactory;
    $this->aliasTypeManager = $aliasTypeManager;
    $this->aliasStorageHelper = $aliasStorageHelper;
  }

  /**
   * (Re)generate URL aliases.
   *
   * @command pathauto:aliases-generate
   *
   * @param string $action
   *   The action to take. Possible actions are "create" (generate aliases for
   *   un-aliased paths only), "update" (update aliases for paths that have an
   *   existing alias) or "all" (generate aliases for all paths).
   * @param array $types
   *   Comma-separated list of aliase typess to generate. Pass "all" to generate
   *   aliases for all types.
   *
   * @throws \Exception
   *
   * @usage drush pathauto:aliases-generate all all
   *   Generate all URL aliases.
   * @usage drush pathauto:aliases-generate create canonical_entities:node
   *   Generate URL aliases for un-aliased node paths only.
   * @usage drush pathauto:aliases-generate
   *   When the arguments are omitted they can be chosen from an interactive
   *   menu.
   *
   * @aliases pag
   */
  public function generateAliases($action = NULL, array $types = NULL) {
    $batch = [
      'title' => dt('Bulk updating URL aliases'),
      'operations' => [
        ['Drupal\pathauto\Form\PathautoBulkUpdateForm::batchStart', []],
      ],
      'finished' => 'Drupal\pathauto\Form\PathautoBulkUpdateForm::batchFinished',
      'progressive' => FALSE,
    ];

    foreach ($types as $type) {
      $batch['operations'][] = ['Drupal\pathauto\Form\PathautoBulkUpdateForm::batchProcess', [$type, $action]];
    }

    batch_set($batch);
    drush_backend_batch_process();
  }

  /**
   * Delete URL aliases
   *
   * @command pathauto:aliases-delete
   *
   * @param array $types
   *   Comma-separated list of alias types to delete. Pass "all" to delete
   *   aliases for all types.
   *
   * @option purge
   *   Deletes all URL aliases, including manually created ones.
   *
   * @throws \Exception
   *
   * @usage drush pathauto:aliases-delete canonical_entities:node
   *   Delete all automatically generated URL aliases for node entities,
   *   preserving manually created aliases.
   * @usage drush pathauto:aliases-delete all
   *   Delete all automatically generated URL aliases, preserving manually
   *   created ones.
   * @usage drush pathauto:aliases-delete all --purge
   *   Delete all URL aliases, including manually created ones.
   * @usage drush pathauto:aliases-delete
   *   When the alias types are omitted they can be chosen from an interactive
   *   menu.
   *
   * @aliases pad
   */
  public function deleteAliases(array $types = NULL, $options = ['purge' => FALSE]) {
    $delete_all = count($types) === count($this->getAliasTypes());

    // Keeping custom aliases forces us to go the slow way to correctly check
    // the automatic/manual flag.
    if (!$options['purge']) {
      $batch = [
        'title' => dt('Bulk deleting URL aliases'),
        'operations' => [['Drupal\pathauto\Form\PathautoAdminDelete::batchStart', [$delete_all]]],
        'finished' => 'Drupal\pathauto\Form\PathautoAdminDelete::batchFinished',
      ];

      foreach ($types as $type) {
        $batch['operations'][] = ['Drupal\pathauto\Form\PathautoAdminDelete::batchProcess', [$type]];
      }

      batch_set($batch);
      drush_backend_batch_process();
    }
    elseif ($delete_all) {
      $this->aliasStorageHelper->deleteAll();
      $this->logger()->success(dt('All of your path aliases have been deleted.'));
    }
    else {
      foreach ($types as $type) {
        /** @var \Drupal\pathauto\AliasTypeInterface $alias_type */
        $alias_type = $this->aliasTypeManager->createInstance($type);
        $this->aliasStorageHelper->deleteBySourcePrefix($alias_type->getSourcePrefix());
        $this->logger()->success(dt('All of your %label path aliases have been deleted.', [
          '%label' => $alias_type->getLabel(),
        ]));
      }
    }
  }

  /**
   * @hook interact pathauto:aliases-generate
   *
   * @throws \Drush\Exceptions\UserAbortException
   *   Thrown when the user cancels the operation during CLI interaction.
   */
  public function interactGenerateAliases(Input $input, Output $output) {
    if (!$input->getArgument('action')) {
      $action = $this->io()->choice(dt('Choose the action to perform.'), $this->getAllowedGenerateActions());
      $input->setArgument('action', $action);
    }
  }

  /**
   * @hook interact
   */
  public function interactAliasTypes(Input $input, Output $output) {
    if (!$input->getArgument('types')) {
      $available_types = $this->getAliasTypes();
      $question = new ChoiceQuestion(dt('Choose the alias type(s). Separate multiple choices with commas, e.g. "1,2,4", or choose "0" for all types.'), array_merge([static::TYPE_ALL], $available_types), NULL);
      $question->setMultiselect(TRUE);
      $types = $this->io()->askQuestion($question);

      $input->setArgument('types', implode(',', $types));
    }
  }

  /**
   * @hook validate pathauto:aliases-generate
   *
   * @throws \InvalidArgumentException
   *   Thrown when one of the passed arguments is invalid
   */
  public function validateGenerateAliases(CommandData $commandData) {
    $input = $commandData->input();
    $action = $input->getArgument('action');
    $valid_actions = array_keys($this->getAllowedGenerateActions());
    if (!in_array($action, $valid_actions)) {
      $message = dt('Invalid action argument "@invalid_action". Please use one of: @valid_actions', [
        '@invalid_action' => $action,
        '@valid_actions' => '"' . implode('", "', $valid_actions) . '"',
      ]);
      throw new \InvalidArgumentException($message);
    }
  }

  /**
   * @hook validate
   *
   * @throws \InvalidArgumentException
   *   Thrown when one of the passed arguments is invalid
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   *   Thrown when an alias type can not be instantiated.
   */
  public function validateAliaseTypes(CommandData $commandData) {
    $input = $commandData->input();

    // Convert the comma-separated list of types to an array with no duplicates.
    $types = explode(',', $input->getArgument('types'));
    $types = array_map('trim', $types);
    sort($types);
    $types = array_unique($types);

    // Set all available types if the user chooses this option.
    if (in_array(static::TYPE_ALL, $types)) {
      $types = $this->getAliasTypes();
    }

    // Check for invalid types.
    $available_types = $this->getAliasTypes();
    $unsupported_types = array_diff($types, $available_types);
    if (!empty($unsupported_types)) {
      $message = dt('Invalid type argument "@invalid_types". Please choose from the following: @valid_types', [
        '@invalid_types' => '"' . implode('", "', $unsupported_types) . '"',
        '@valid_types' => '"' . implode('", "', [static::TYPE_ALL] + $available_types) . '"',
      ]);
      throw new \InvalidArgumentException($message);
    }

    // Pass the array of types to the command, rather than the comma-separated
    // string.
    $input->setArgument('types', $types);
  }

  /**
   * Returns the allowed actions according to the site configuration.
   *
   * @return array
   *   An associative array of allowed option descriptions, keyed by option
   *   name.
   */
  protected function getAllowedGenerateActions() {
    $actions = [
      PathautoBulkUpdateForm::ACTION_CREATE => dt('Generate a URL alias for un-aliased paths only.'),
    ];

    // The options that affect existing URL aliases are allowed unless the
    // site is configured to preserve existing aliases.
    $config = $this->configFactory->get('pathauto.settings');
    if ($config->get('update_action') !== PathautoGeneratorInterface::UPDATE_ACTION_NO_NEW) {
      $actions[PathautoBulkUpdateForm::ACTION_UPDATE] = dt('Update the URL alias for paths having an old URL alias.');
      $actions[PathautoBulkUpdateForm::ACTION_ALL] = dt('Regenerate URL aliases for all paths.');
    }

    return $actions;
  }

  /**
   * Returns the available alias types for which aliases can be generated.
   *
   * @return array
   *   An indexed array of alias types.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   *   Thrown when an alias type can not be instantiated.
   */
  public function getAliasTypes() {
    $types = [];

    foreach ($this->aliasTypeManager->getVisibleDefinitions() as $id => $definition) {
      /** @var \Drupal\pathauto\AliasTypeInterface $aliasType */
      $aliasType = $this->aliasTypeManager->createInstance($id);
      if ($aliasType instanceof AliasTypeBatchUpdateInterface) {
        $types[] = $aliasType->getPluginId();
      }
    }

    return $types;
  }

}
