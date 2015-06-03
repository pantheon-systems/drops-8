<?php

/**
 * @file
 * Contains \Drupal\config\Form\ConfigSingleImportForm.
 */

namespace Drupal\config\Form;

use Drupal\Component\Serialization\Yaml;
use Drupal\Core\Config\StorageInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a form for importing a single configuration file.
 */
class ConfigSingleImportForm extends ConfirmFormBase {

  /**
   * The entity manager.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityManager;

  /**
   * The config storage.
   *
   * @var \Drupal\Core\Config\StorageInterface
   */
  protected $configStorage;

  /**
   * If the config exists, this is that object. Otherwise, FALSE.
   *
   * @var \Drupal\Core\Config\Config|\Drupal\Core\Config\Entity\ConfigEntityInterface|bool
   */
  protected $configExists = FALSE;

  /**
   * The submitted data needing to be confirmed.
   *
   * @var array
   */
  protected $data = array();

  /**
   * Constructs a new ConfigSingleImportForm.
   *
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager.
   * @param \Drupal\Core\Config\StorageInterface $config_storage
   *   The config storage.
   */
  public function __construct(EntityManagerInterface $entity_manager, StorageInterface $config_storage) {
    $this->entityManager = $entity_manager;
    $this->configStorage = $config_storage;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.manager'),
      $container->get('config.storage')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'config_single_import_form';
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('config.import_single');
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    if ($this->data['config_type'] === 'system.simple') {
      $name = $this->data['config_name'];
      $type = $this->t('simple configuration');
    }
    else {
      $definition = $this->entityManager->getDefinition($this->data['config_type']);
      $name = $this->data['import'][$definition->getKey('id')];
      $type = $definition->getLowercaseLabel();
    }

    $args = array(
      '%name' => $name,
      '@type' => strtolower($type),
    );
    if ($this->configExists) {
      $question = $this->t('Are you sure you want to update the %name @type?', $args);
    }
    else {
      $question = $this->t('Are you sure you want to create a new %name @type?', $args);
    }
    return $question;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // When this is the confirmation step fall through to the confirmation form.
    if ($this->data) {
      return parent::buildForm($form, $form_state);
    }

    $entity_types = array();
    foreach ($this->entityManager->getDefinitions() as $entity_type => $definition) {
      if ($definition->isSubclassOf('Drupal\Core\Config\Entity\ConfigEntityInterface')) {
        $entity_types[$entity_type] = $definition->getLabel();
      }
    }
    // Sort the entity types by label, then add the simple config to the top.
    uasort($entity_types, 'strnatcasecmp');
    $config_types = array(
      'system.simple' => $this->t('Simple configuration'),
    ) + $entity_types;
    $form['config_type'] = array(
      '#title' => $this->t('Configuration type'),
      '#type' => 'select',
      '#options' => $config_types,
      '#required' => TRUE,
    );
    $form['config_name'] = array(
      '#title' => $this->t('Configuration name'),
      '#description' => $this->t('Enter the name of the configuration file without the <em>.yml</em> extension. (e.g. <em>system.site</em>)'),
      '#type' => 'textfield',
      '#states' => array(
        'required' => array(
          ':input[name="config_type"]' => array('value' => 'system.simple'),
        ),
        'visible' => array(
          ':input[name="config_type"]' => array('value' => 'system.simple'),
        ),
      ),
    );
    $form['import'] = array(
      '#title' => $this->t('Paste your configuration here'),
      '#type' => 'textarea',
      '#rows' => 24,
      '#required' => TRUE,
    );
    $form['advanced'] = array(
      '#type' => 'details',
      '#title' => $this->t('Advanced'),
    );
    $form['advanced']['custom_entity_id'] = array(
      '#title' => $this->t('Custom Entity ID'),
      '#type' => 'textfield',
      '#description' => $this->t('Specify a custom entity ID. This will override the entity ID in the configuration above.'),
    );
    $form['actions'] = array('#type' => 'actions');
    $form['actions']['submit'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Import'),
      '#button_type' => 'primary',
    );
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // The confirmation step needs no additional validation.
    if ($this->data) {
      return;
    }

    // Decode the submitted import.
    $data = Yaml::decode($form_state->getValue('import'));

    // Validate for config entities.
    if ($form_state->getValue('config_type') !== 'system.simple') {
      $definition = $this->entityManager->getDefinition($form_state->getValue('config_type'));
      $id_key = $definition->getKey('id');

      // If a custom entity ID is specified, override the value in the
      // configuration data being imported.
      if (!$form_state->isValueEmpty('custom_entity_id')) {
        $data[$id_key] = $form_state->getValue('custom_entity_id');
      }

      $entity_storage = $this->entityManager->getStorage($form_state->getValue('config_type'));
      // If an entity ID was not specified, set an error.
      if (!isset($data[$id_key])) {
        $form_state->setErrorByName('import', $this->t('Missing ID key "@id_key" for this @entity_type import.', array('@id_key' => $id_key, '@entity_type' => $definition->getLabel())));
        return;
      }
      // If there is an existing entity, ensure matching ID and UUID.
      if ($entity = $entity_storage->load($data[$id_key])) {
        $this->configExists = $entity;
        if (!isset($data['uuid'])) {
          $form_state->setErrorByName('import', $this->t('An entity with this machine name already exists but the import did not specify a UUID.'));
          return;
        }
        if ($data['uuid'] !== $entity->uuid()) {
          $form_state->setErrorByName('import', $this->t('An entity with this machine name already exists but the UUID does not match.'));
          return;
        }
      }
      // If there is no entity with a matching ID, check for a UUID match.
      elseif (isset($data['uuid']) && $entity_storage->loadByProperties(array('uuid' => $data['uuid']))) {
        $form_state->setErrorByName('import', $this->t('An entity with this UUID already exists but the machine name does not match.'));
      }
    }
    else {
      $config = $this->config($form_state->getValue('config_name'));
      $this->configExists = !$config->isNew() ? $config : FALSE;
    }

    // Store the decoded version of the submitted import.
    $form_state->setValueForElement($form['import'], $data);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // If this form has not yet been confirmed, store the values and rebuild.
    if (!$this->data) {
      $form_state->setRebuild();
      $this->data = $form_state->getValues();
      return;
    }

    // If a simple configuration file was added, set the data and save.
    if ($this->data['config_type'] === 'system.simple') {
      $this->configFactory()->getEditable($this->data['config_name'])->setData($this->data['import'])->save();
      drupal_set_message($this->t('The %name configuration was imported.', array('%name' => $this->data['config_name'])));
    }
    // For a config entity, create an entity and save it.
    else {
      try {
        $entity_storage = $this->entityManager->getStorage($this->data['config_type']);
        if ($this->configExists) {
          $entity = $entity_storage->updateFromStorageRecord($this->configExists, $this->data['import']);
        }
        else {
          $entity = $entity_storage->createFromStorageRecord($this->data['import']);
        }
        $entity->save();
        drupal_set_message($this->t('The @entity_type %label was imported.', array('@entity_type' => $entity->getEntityTypeId(), '%label' => $entity->label())));
      }
      catch (\Exception $e) {
        drupal_set_message($e->getMessage(), 'error');
      }
    }
  }

}
