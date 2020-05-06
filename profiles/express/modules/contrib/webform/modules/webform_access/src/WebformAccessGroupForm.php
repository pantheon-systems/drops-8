<?php

namespace Drupal\webform_access;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\webform\Entity\Webform;
use Drupal\webform\Plugin\WebformElementManagerInterface;
use Drupal\webform\Utility\WebformDialogHelper;
use Drupal\webform\WebformAccessRulesManagerInterface;
use Drupal\webform\WebformEntityReferenceManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Provides a form to define a webform access group.
 */
class WebformAccessGroupForm extends EntityForm {

  /**
   * The database object.
   *
   * @var object
   */
  protected $database;

  /**
   * Entity manager.
   *
   * @var Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManagerInterface;

  /**
   * The webform element manager.
   *
   * @var \Drupal\webform\Plugin\WebformElementManagerInterface
   */
  protected $elementManager;

  /**
   * The webform entity reference manager.
   *
   * @var \Drupal\webform\WebformEntityReferenceManagerInterface
   */
  protected $webformEntityReferenceManager;

  /**
   * The webform access rules manager.
   *
   * @var \Drupal\webform\WebformAccessRulesManagerInterface
   */
  protected $webformAccessRulesManager;

  /**
   * Constructs a WebformAccessGroupForm.
   *
   * @param \Drupal\Core\Database\Connection $database
   *   The database.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity manager.
   * @param \Drupal\webform\Plugin\WebformElementManagerInterface $element_manager
   *   The webform element manager.
   * @param \Drupal\webform\WebformEntityReferenceManagerInterface $webform_entity_reference_manager
   *   The webform entity reference manager.
   * @param \Drupal\webform\WebformAccessRulesManagerInterface $webform_access_rules_manager
   *   The webform access rules manager.
   */
  public function __construct(Connection $database, EntityTypeManagerInterface $entity_type_manager, WebformElementManagerInterface $element_manager, WebformEntityReferenceManagerInterface $webform_entity_reference_manager, WebformAccessRulesManagerInterface $webform_access_rules_manager) {
    $this->database = $database;
    $this->entityTypeManager = $entity_type_manager;
    $this->elementManager = $element_manager;
    $this->webformEntityReferenceManager = $webform_entity_reference_manager;
    $this->webformAccessRulesManager = $webform_access_rules_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('database'),
      $container->get('entity_type.manager'),
      $container->get('plugin.manager.webform.element'),
      $container->get('webform.entity_reference_manager'),
      $container->get('webform.access_rules_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function prepareEntity() {
    if ($this->operation == 'duplicate') {
      $this->setEntity($this->getEntity()->createDuplicate());
    }
    parent::prepareEntity();
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\webform_access\WebformAccessGroupInterface $webform_access_group */
    $webform_access_group = $this->getEntity();

    // Customize title for duplicate and edit operation.
    switch ($this->operation) {
      case 'duplicate':
        $form['#title'] = $this->t("Duplicate '@label' access group", ['@label' => $webform_access_group->label()]);
        break;

      case 'edit':
        $form['#title'] = $webform_access_group->label();
        break;
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $is_webform_admin = $this->currentUser()->hasPermission('administer webform');

    /** @var \Drupal\webform_access\WebformAccessGroupInterface $webform_access_group */
    $webform_access_group = $this->entity;

    // Access group information which is displayed to group administrators.
    $form['information'] = [
      '#type' => 'details',
      '#title' => $this->t('Group information'),
      '#access' => !$is_webform_admin,
    ];
    $form['information']['label'] = [
      '#type' => 'item',
      '#title' => $this->t('Label'),
      '#markup' => $webform_access_group->label(),
      '#input' => FALSE,
    ];
    if ($description = $webform_access_group->get('description')) {
      $form['information']['description'] = [
        '#type' => 'item',
        '#title' => $this->t('Description'),
        '#markup' => $description,
        '#input' => FALSE,
      ];
    }
    $form['information']['type'] = [
      '#type' => 'item',
      '#title' => $this->t('Type'),
      '#markup' => $webform_access_group->getTypeLabel(),
      '#input' => FALSE,
    ];
    $entities = WebformAccessGroupListBuilder::buildEntities($webform_access_group->getEntityIds());
    if ($entities) {
      $form['information']['entities'] = [
        '#type' => 'item',
        '#title' => $this->t('Nodes'),
        '#input' => FALSE,
        'nodes' => $entities,
      ];
    }
    $permissions = WebformAccessGroupListBuilder::buildPermissions($webform_access_group->get('permissions'));
    if ($permissions) {
      $form['information']['permissions'] = [
        '#type' => 'item',
        '#title' => $this->t('Permissions'),
        '#input' => FALSE,
        'nodes' => $permissions,
      ];
    }
    $admins = WebformAccessGroupListBuilder::buildUserAccounts($webform_access_group->getAdminIds());
    if ($admins) {
      $form['information']['administrators'] = [
        '#type' => 'item',
        '#title' => $this->t('Administrators'),
        '#input' => FALSE,
        'administrators' => $admins,
      ];
    }

    $form['general'] = [
      '#type' => 'details',
      '#title' => $this->t('General information'),
      '#open' => TRUE,
      '#access' => $is_webform_admin,
    ];
    $form['general']['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#required' => TRUE,
      '#attributes' => ($webform_access_group->isNew()) ? ['autofocus' => 'autofocus'] : [],
      '#default_value' => $webform_access_group->label(),
      '#access' => $is_webform_admin,
    ];
    $form['general']['id'] = [
      '#type' => 'machine_name',
      '#machine_name' => [
        'source' => ['general', 'label'],
        'exists' => '\Drupal\webform_access\Entity\WebformAccessGroup::load',
        'label' => '<br/>' . $this->t('Machine name'),
      ],
      '#maxlength' => 32,
      '#field_suffix' => ($webform_access_group->isNew()) ? ' (' . $this->t('Maximum @max characters', ['@max' => 32]) . ')' : '',
      '#required' => TRUE,
      '#disabled' => !$webform_access_group->isNew(),
      '#default_value' => $webform_access_group->id(),
      '#access' => $is_webform_admin,
    ];
    $form['general']['description'] = [
      '#type' => 'webform_html_editor',
      '#title' => $this->t('Description'),
      '#default_value' => $webform_access_group->get('description'),
      '#access' => $is_webform_admin,
    ];
    $form['general']['type'] = [
      '#type' => 'webform_entity_select',
      '#title' => $this->t('Type'),
      '#description' => $this->t("The access group type is used to exposed an access group's users and email addresses to <code>[webform_access]</code> related tokens."),
      '#target_type' => 'webform_access_type',
      '#empty_option' => $this->t('- None -'),
      '#default_value' => $webform_access_group->get('type'),
      '#access' => $is_webform_admin,
    ];

    // Access.
    $form['access'] = [
      '#type' => 'details',
      '#title' => $this->t('Access controls'),
      '#open' => TRUE,
    ];
    // Access: Users.
    $form['access']['users'] = [
      '#type' => 'webform_entity_select',
      '#title' => $this->t('Users'),
      '#description' => $this->t("Select which users can access this group's assigned nodes."),
      '#target_type' => 'user',
      '#multiple' => TRUE,
      '#selection_handler' => 'default:user',
      '#selection_settings' => [
        'include_anonymous' => FALSE,
      ],
      '#select2' => TRUE,
      '#default_value' => $webform_access_group->getUserIds(),

    ];
    $this->elementManager->processElement($form['access']['users']);
    // Access: Entities (Nodes).
    $form['access']['entities'] = [
      '#type' => 'select',
      '#title' => $this->t('Nodes'),
      '#description' => $this->t("Select which nodes that this group's users can access."),
      '#multiple' => TRUE,
      '#select2' => TRUE,
      '#options' => $this->getEntitiesAsOptions(),
      '#default_value' => $webform_access_group->getEntityIds(),
      '#access' => $is_webform_admin,
    ];
    $this->elementManager->processElement($form['access']['entities']);

    // Permissions.
    $permissions_options = [];
    $access_rules = $this->webformAccessRulesManager->getAccessRulesInfo();
    foreach ($access_rules as $permission => $access_rule) {
      $permissions_options[$permission] = [
        'title' => $access_rule['title'],
      ];
    }
    $form['permissions'] = [
      '#type' => 'details',
      '#title' => $this->t('Permissions'),
      '#open' => TRUE,
      '#access' => $is_webform_admin,
    ];
    $form['permissions']['permissions'] = [
      '#type' => 'tableselect',
      '#header' => ['title' => $this->t('Permission')],
      '#js_select' => FALSE,
      '#options' => $permissions_options,
      '#default_value' => $webform_access_group->get('permissions'),
      '#access' => $is_webform_admin,
    ];
    $this->elementManager->processElement($form['permissions']['permissions']);

    // Notifications.
    $form['notifications'] = [
      '#type' => 'details',
      '#title' => $this->t('Custom notifications'),
      '#open' => TRUE,
    ];
    $form['notifications']['emails'] = [
      '#type' => 'webform_multiple',
      '#title' => $this->t('Emails'),
      '#description' => $this->t('Custom email addresses are solely for email notifications and are included in <code>[webform_access]</code> related tokens.'),
      '#add_more_input_label' => $this->t('more emails'),
      '#sorting' => FALSE,
      '#operations' => FALSE,
      '#element' => [
        '#type' => 'email',
        '#title' => $this->t('Emails'),
        '#title_display' => 'invisible',
      ],
      '#default_value' => $webform_access_group->getEmails(),
    ];

    // Administration.
    $form['administration'] = [
      '#type' => 'details',
      '#title' => $this->t('Administration'),
      '#open' => TRUE,
      '#access' => $is_webform_admin,
    ];
    // Administration: Admins.
    $form['administration']['admins'] = [
      '#type' => 'webform_entity_select',
      '#title' => $this->t('Administrators'),
      '#description' => $this->t('Administrators will be able to add and remove users and custom email addresses from this group.') .
        '<br/><br/>' .
        "<em>Please note: Administrators are not automatically assigned access to this group's webforms and will not receive any emails. If administrators should also be able access this access group's webforms or receive emails, you must explicitly add the administrator as a user or email address to this access group.</em>",
      '#target_type' => 'user',
      '#multiple' => TRUE,
      '#selection_handler' => 'default:user',
      '#selection_settings' => [
        'include_anonymous' => FALSE,
      ],
      '#select2' => TRUE,
      '#default_value' => $webform_access_group->getAdminIds(),
      '#access' => $is_webform_admin,
    ];
    $this->elementManager->processElement($form['administration']['admins']);

    $form['#attached']['library'][] = 'webform_access/webform_access.admin';

    return parent::form($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  protected function actions(array $form, FormStateInterface $form_state) {
    $actions = parent::actions($form, $form_state);

    // Open delete button in a modal dialog.
    if (isset($actions['delete'])) {
      $actions['delete']['#attributes'] = WebformDialogHelper::getModalDialogAttributes(WebformDialogHelper::DIALOG_NARROW, $actions['delete']['#attributes']['class']);
      WebformDialogHelper::attachLibraries($actions['delete']);
    }

    return $actions;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $form_state->setValue('permissions', array_filter($form_state->getValue('permissions')));
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\webform_access\WebformAccessGroupInterface $webform_access_group */
    $webform_access_group = $this->getEntity();
    $webform_access_group->setAdminIds($form_state->getValue('admins'));
    $webform_access_group->setUserIds($form_state->getValue('users'));
    $webform_access_group->setEntityIds($form_state->getValue('entities'));
    $webform_access_group->setEmails($form_state->getValue('emails'));
    $webform_access_group->save();

    // Log and display message.
    $context = [
      '@label' => $webform_access_group->label(),
      'link' => $webform_access_group->toLink($this->t('Edit'), 'edit-form')->toString(),
    ];
    $this->logger('webform')->notice('Access group @label saved.', $context);
    $this->messenger()->addStatus($this->t('Access group %label saved.', ['%label' => $webform_access_group->label()]));

    // Redirect to list.
    $form_state->setRedirect('entity.webform_access_group.collection');
  }

  /**
   * Get webform entities as options.
   *
   * @return array
   *   An associative array container webform node options.
   */
  protected function getEntitiesAsOptions() {
    // Collects webform nodes.
    $webform_nodes = [];
    $nids = [];
    $webform_ids = [];

    $table_names = $this->webformEntityReferenceManager->getTableNames();
    foreach ($table_names as $table_name => $field_name) {
      if (strpos($table_name, 'node_revision__') !== 0) {
        continue;
      }
      $query = $this->database->select($table_name, 'n');
      $query->distinct();
      $query->fields('n', ['entity_id', $field_name . '_target_id']);
      $query->condition($field_name . '_target_id', '', '<>');
      $query->isNotNull($field_name . '_target_id');
      $result = $query->execute()->fetchAllKeyed();
      foreach ($result as $nid => $webform_id) {
        $webform_nodes[$nid][$field_name][$webform_id] = $webform_id;
        $webform_ids[$webform_id] = $webform_id;
        $nids[$nid] = $nid;
      }
    }

    /** @var \Drupal\webform\WebformInterface[] $webforms */
    $webforms = Webform::loadMultiple($webform_ids);

    /** @var \Drupal\node\NodeInterface[] $nodes */
    $nodes = $this->entityTypeManager->getStorage('node')->loadMultiple($nids);

    $options = [];
    foreach ($webform_nodes as $nid => $field_names) {
      if (!isset($nodes[$nid])) {
        continue;
      }
      $node = $nodes[$nid];
      foreach ($field_names as $field_name => $webform_ids) {
        foreach ($webform_ids as $webform_id) {
          if (!isset($webforms[$webform_id])) {
            continue;
          }
          $webform = $webforms[$webform_id];
          $options['node:' . $node->id() . ':' . $field_name . ':' . $webform->id()] = $node->label() . ': ' . $webform->label();
        }
      }
    }
    asort($options);
    return $options;
  }

}
