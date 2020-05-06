<?php

/**
 * @file
 * Hooks related to Webform module.
 */

/**
 * @addtogroup hooks
 * @{
 */

/**
 * Alter the information provided in \Drupal\webform\Annotation\WebformElement.
 *
 * @param array $definitions
 *   The array of webform elements, keyed on the machine-readable element name.
 */
function hook_webform_element_info_alter(array &$definitions) {

}

/**
 * Alter the information provided in \Drupal\webform\Annotation\WebformHandler.
 *
 * @param array $handlers
 *   The array of webform handlers, keyed on the machine-readable handler name.
 */
function hook_webform_handler_info_alter(array &$handlers) {

}

/**
 * Alter the information provided in \Drupal\webform\Annotation\WebformVariant.
 *
 * @param array $variants
 *   The array of webform variants, keyed on the machine-readable handler name.
 */
function hook_webform_variant_info_alter(array &$variants) {

}

/**
 * Alter definition of WebformSourceEntity plugins.
 *
 * @param array $definitions
 *   The array of plugin definitions.
 */
function hook_webform_source_entity_info_alter(array &$definitions) {
  if (isset($definitions['some_plugin_whose_weight_i_wanna_change'])) {
    $definitions['some_plugin_whose_weight_i_wanna_change']['weight'] = -1000;
  }
}

/**
 * Alter a webform element's default properties.
 *
 * @param array &$properties
 *   An associative array containing an element's default properties.
 * @param array $definition
 *   The webform element's definition.
 *
 * @see webform_example_element_properties.module
 */
function hook_webform_element_default_properties_alter(array &$properties, array &$definition) {
  // Add custom data property to all webform elements.
  // Setting the custom property to an empty string makes the corresponding
  // element defined via hook_webform_element_configuration_form_alter()
  // automatically visible.
  $properties['custom_data'] = '';
}

/**
 * Alter a webform element's translatable properties.
 *
 * @param array &$properties
 *   An indexed array containing an element's translatable properties.
 * @param array $definition
 *   The webform element's definition.
 *
 * @see webform_example_element_properties.module
 */
function hook_webform_element_translatable_properties_alter(array &$properties, array &$definition) {
  // Make the custom data property translatable.
  $properties[] = 'custom_data';
}

/**
 * Perform alterations before a webform element configuration form is populated.
 *
 * @param array $form
 *   Nested array of form elements that comprise the webform element properties.
 * @param \Drupal\Core\Form\FormStateInterface $form_state
 *   The current state of the form.
 *
 * @see webform_example_element_properties.module
 * @ingroup form_api
 */
function hook_webform_element_configuration_form_alter(array &$form, \Drupal\Core\Form\FormStateInterface $form_state) {
  /** @var Drupal\webform_ui\Form\WebformUiElementEditForm $form_object */
  $form_object = $form_state->getFormObject();
  $element_plugin = $form_object->getWebformElementPlugin();

  // Make sure the element has the 'custom_data' property.
  if (!$element_plugin->hasProperty('custom_data')) {
    return;
  }

  $form['custom_properties'] = [
    '#type' => 'details',
    '#title' => t('Custom properties'),
    '#description' => t('The below custom properties are provided and managed by the webform_test_custom_properties.module.'),
    '#open' => TRUE,
    // Add custom properties after all fieldset elements, which have a
    // weight of -20.
    // @see \Drupal\webform\Plugin\WebformElementBase::buildConfigurationForm
    '#weight' => -10,
  ];
  $form['custom_properties']['custom_data'] = [
    '#type' => 'textfield',
    '#title' => t('Custom data'),
    '#description' => t("The custom data value will be added to the \$element's render array attributes."),
  ];
}

/**
 * Alter webform elements.
 *
 * @param array $element
 *   Webform specific element properties include:
 *   - #webform: The element's parent webform ID.
 *   - #webform_submission: The element's related webform submission ID.
 *   - #webform_id: The element's unique webform key.
 *   - #webform_key: The element's webform key/name.
 *   - #webform_parent_key: The element's parent key/name.
 *   - #webform_parent_flexbox: TRUE if the element's parent is a
 *     flexbox container.
 *   - #webform_depth: The depth level of the element in the form's
 *     tree hierarchy.
 *   - #webform_children: An array of child element keys/names.
 *   - #webform_multiple: TRUE if element stores multiple values.
 *   - #webform_composite: TRUE if element stores composite values.
 *   - #webform_parents: An array containing the element's parent keys/names.
 *
 *   Webform specific composite sub-element properties include:
 *   - #webform_composite_id: The composite sub-element's ID.
 *   - #webform_composite_key: The composite sub-element's parent key and
 *     element key.
 *   - #webform_composite_parent_key: The composite sub-element's parent key.
 *
 *   Sub-element's can have properties defined using #SUB_ELEMENT__PROPERTY.
 *   For example, an other element's placeholder can be defined using
 *   the #other__placeholder property.
 * @param \Drupal\Core\Form\FormStateInterface $form_state
 *   The current state of the form.
 * @param array $context
 *   An associative array containing the following key-value pairs:
 *   - form: The form structure to which elements is being attached.
 *
 * @see \Drupal\webform\WebformSubmissionForm::prepareElements()
 * @see hook_webform_element_ELEMENT_TYPE_alter()
 */
function hook_webform_element_alter(array &$element, \Drupal\Core\Form\FormStateInterface $form_state, array $context) {
  // Code here acts on all elements included in a webform.
  /** @var \Drupal\webform\WebformSubmissionForm $form_object */
  $form_object = $form_state->getFormObject();
  /** @var \Drupal\webform\WebformSubmissionInterface $webform_submission */
  $webform_submission = $form_object->getEntity();
  /** @var \Drupal\webform\WebformInterface $webform */
  $webform = $webform_submission->getWebform();

  // Add custom data attributes to all elements.
  $element['#attributes']['data-custom'] = '{custom data goes here}';
}

/**
 * Alter webform elements for a specific type.
 *
 * Modules can implement hook_webform_element_ELEMENT_TYPE_alter() to
 * modify a specific webform element, rather than using
 * hook_webform_element_alter() and checking the element type.
 *
 * @param array $element
 *   Webform specific element properties include:
 *   - #webform: The element's parent webform ID.
 *   - #webform_submission: The element's related webform submission ID.
 *   - #webform_id: The element's unique webform key.
 *   - #webform_key: The element's webform key/name.
 *   - #webform_parent_key: The element's parent key/name.
 *   - #webform_parent_flexbox: TRUE if the element's parent is a
 *     flexbox container.
 *   - #webform_depth: The depth level of the element in the form's
 *     tree hierarchy.
 *   - #webform_children: An array of child element keys/names.
 *   - #webform_multiple: TRUE if element stores multiple values.
 *   - #webform_composite: TRUE if element stores composite values.
 *   - #webform_parents: An array containing the element's parent keys/names.
 *
 *   Webform specific composite sub-element properties include:
 *   - #webform_composite_id: The composite sub-element's ID.
 *   - #webform_composite_key: The composite sub-element's parent key and
 *     element key.
 *   - #webform_composite_parent_key: The composite sub-element's parent key.
 *
 *   Sub-element's can have properties defined using #SUB_ELEMENT__PROPERTY.
 *   For example, an other element's placeholder can be defined using
 *   the #other__placeholder property.
 * @param \Drupal\Core\Form\FormStateInterface $form_state
 *   The current state of the form.
 * @param array $context
 *   An associative array. See hook_field_widget_alter() for the structure
 *   and content of the array.
 *
 * @see \Drupal\webform\WebformSubmissionForm::prepareElements()
 * @see hook_webform_element_alter(()
 */
function hook_webform_element_ELEMENT_TYPE_alter(array &$element, \Drupal\Core\Form\FormStateInterface $form_state, array $context) {
  // Add custom data attributes to a specific element type.
  $element['#attributes']['data-custom'] = '{custom data goes here}';

  // Attach a custom library to the element type.
  $element['#attached']['library'][] = 'MODULE/MODULE.element.ELEMENT_TYPE';
}

/**
 * Check and set an element's #access property and/or return access.
 *
 * @param string $operation
 *   An element create, view, or update operation.
 * @param array $element
 *   Webform specific element properties include:
 *   - #webform: The element's parent webform ID.
 *   - #webform_submission: The element's related webform submission ID.
 *   - #webform_id: The element's unique webform key.
 *   - #webform_key: The element's webform key/name.
 *   - #webform_parent_key: The element's parent key/name.
 *   - #webform_parent_flexbox: TRUE if the element's parent is a
 *     flexbox container.
 *   - #webform_depth: The depth level of the element in the form's
 *     tree hierarchy.
 *   - #webform_children: An array of child element keys/names.
 *   - #webform_multiple: TRUE if element stores multiple values.
 *   - #webform_composite: TRUE if element stores composite values.
 *   - #webform_parents: An array containing the element's parent keys/names.
 *
 *   Webform specific composite sub-element properties include:
 *   - #webform_composite_id: The composite sub-element's ID.
 *   - #webform_composite_key: The composite sub-element's parent key and
 *     element key.
 *   - #webform_composite_parent_key: The composite sub-element's parent key.
 *
 *   Sub-element's can have properties defined using #SUB_ELEMENT__PROPERTY.
 *   For example, an other element's placeholder can be defined using
 *   the #other__placeholder property.
 * @param \Drupal\Core\Session\AccountInterface|null $account
 *   (optional) If provided, only those formats that are allowed for this user
 *   account will be returned. All enabled formats will be returned otherwise.
 *   Defaults to NULL.
 * @param array $context
 *   The element's current context which include the webform and
 *   webform submission entity.
 *
 * @return \Drupal\Core\Access\AccessResult
 *   The access provided. Return neutral if no change.
 */
function hook_webform_element_access($operation, array &$element, \Drupal\Core\Session\AccountInterface $account = NULL, array $context = []) {
  // Load the current webform and submission for element's context.
  $webform = $context['webform'];
  $webform_submission = $context['webform_submission'];

  return !empty($element['#confidential']) ? \Drupal\Core\Access\AccessResult::forbidden() : \Drupal\Core\Access\AccessResult::neutral();
}

/**
 * Return information about input masks for text based webform elements.
 *
 * @return array
 *   Return an array of input masks for text based webform elements.
 *
 * @see \Drupal\webform\Plugin\WebformElement\TextBase::getInputMasks
 */
function hook_webform_element_input_masks() {
  $input_masks = [];
  $input_masks["'alias': 'date'"] = [
    'title' => t('Date'),
    'example' => '01/01/2000',
    'mask' => 'dd/mm/yyyy',
  ];
  return $input_masks;
}

/**
 * Return information about input masks for text based webform elements.
 *
 * @param array $input_masks
 *   An array of input masks for text based webform elements.
 *
 * @see \Drupal\webform\Plugin\WebformElement\TextBase::getInputMasks
 */
function hook_webform_element_input_masks_alter(array &$input_masks) {
  $input_masks["'alias': 'date'"] = [
    'title' => t('My Custom Date'),
    'example' => '01/01/2000',
    'mask' => 'dd/mm/yyyy',
  ];
}

/**
 * Alter webform options.
 *
 * @param array $options
 *   An associative array of options.
 * @param array $element
 *   The webform element that the options is for.
 * @param string $options_id
 *   The webform options id. Set to NULL if the options are custom.
 */
function hook_webform_options_alter(array &$options, array &$element, $options_id = NULL) {

}

/**
 * Alter webform options by id.
 *
 * @param array $options
 *   An associative array of options.
 * @param array $element
 *   The webform element that the options is for.
 */
function hook_webform_options_WEBFORM_OPTIONS_ID_alter(array &$options, array &$element) {

}

/**
 * Perform alterations before a webform submission form is rendered.
 *
 * This hook is identical to hook_form_alter() but allows the
 * hook_webform_submission_form_alter() function to be stored in a dedicated
 * include file and it also allows the Webform module to implement webform alter
 * logic on another module's behalf.
 *
 * @param array $form
 *   Nested array of form elements that comprise the webform.
 * @param \Drupal\Core\Form\FormStateInterface $form_state
 *   The current state of the form. The arguments that
 *   \Drupal::formBuilder()->getForm() was originally called with are available
 *   in the array $form_state->getBuildInfo()['args'].
 * @param string $form_id
 *   String representing the webform's id.
 *
 * @see webform.honeypot.inc
 * @see hook_form_BASE_FORM_ID_alter()
 * @see hook_form_FORM_ID_alter()
 *
 * @ingroup form_api
 */
function hook_webform_submission_form_alter(array &$form, \Drupal\Core\Form\FormStateInterface $form_state, $form_id) {

}

/**
 * Perform alterations on webform admin third party settings form.
 *
 * This hook is identical to hook_form_alter() but allows contrib and custom
 * modules to define third party settings.
 *
 * @param array $form
 *   Nested array of form elements that comprise the webform.
 * @param \Drupal\Core\Form\FormStateInterface $form_state
 *   The current state of the form.
 *
 * @see \Drupal\webform\Form\WebformAdminSettingsForm
 * @see webform.honeypot.inc
 *
 * @ingroup form_api
 */
function hook_webform_admin_third_party_settings_form_alter(array &$form, \Drupal\Core\Form\FormStateInterface $form_state) {

}

/**
 * Perform alterations on webform third party settings form.
 *
 * This hook is identical to hook_form_alter() but allows contrib and custom
 * modules to define third party settings.
 *
 * @param array $form
 *   Nested array of form elements that comprise the webform.
 * @param \Drupal\Core\Form\FormStateInterface $form_state
 *   The current state of the form.
 *
 * @see \Drupal\webform\WebformEntitySettingsForm
 * @see webform.honeypot.inc
 *
 * @ingroup form_api
 */
function hook_webform_third_party_settings_form_alter(array &$form, \Drupal\Core\Form\FormStateInterface $form_state) {

}

/**
 * Act on a webform handler when a method is invoked.
 *
 * Allows module developers to implement custom logic that can executed before
 * any webform handler method is invoked.
 *
 * This hook can be used to…
 * - Conditionally enable or disable a handler.
 * - Alter a handler's configuration.
 * - Preprocess submission data being passed to a webform handler.
 *
 * @param \Drupal\webform\Plugin\WebformHandlerInterface $handler
 *   A webform handler attached to a webform.
 * @param string $method_name
 *   The invoked method name converted to snake case.
 * @param array $args
 *   Argument being passed to the handler's method.
 *
 * @see \Drupal\webform\Plugin\WebformHandlerInterface
 */
function hook_webform_handler_invoke_alter(\Drupal\webform\Plugin\WebformHandlerInterface $handler, $method_name, array &$args) {
  $webform = $handler->getWebform();
  $webform_submission = $handler->getWebformSubmission();

  $webform_id = $handler->getWebform()->id();
  $handler_id = $handler->getHandlerId();
  $state = $webform_submission->getState();
}

/**
 * Act on a webform handler when a specific method is invoked.
 *
 * Allows module developers to implement custom logic that can executed before
 * a specified webform handler method is invoked.
 *
 * This hook can be used to…
 * - Conditionally enable or disable a handler.
 * - Alter a handler's configuration.
 * - Preprocess submission data being passed to a webform handler.
 *
 * @param \Drupal\webform\Plugin\WebformHandlerInterface $handler
 *   A webform handler attached to a webform.
 * @param array $args
 *   Argument being passed to the handler's method.
 *
 * @see \Drupal\webform\Plugin\WebformHandlerInterface
 */
function hook_webform_handler_invoke_METHOD_NAME_alter(\Drupal\webform\Plugin\WebformHandlerInterface $handler, array &$args) {
  $webform = $handler->getWebform();
  $webform_submission = $handler->getWebformSubmission();

  $webform_id = $handler->getWebform()->id();
  $handler_id = $handler->getHandlerId();
  $state = $webform_submission->getState();
}

/**
 * Return information about external webform libraries.
 *
 * @internal
 * This hook will most likely be removed once there is a
 * stable release of Libraries API for Drupal 8.
 *
 * @see https://www.drupal.org/project/libraries
 * @see \Drupal\webform\WebformLibrariesManager::initLibraries
 */
function hook_webform_libraries_info() {

}

/**
 * Alter the webform module's libraries information.
 *
 * @internal
 * This hook will most likely be removed once there is a
 * stable release of Libraries API for Drupal 8.
 *
 * @see https://www.drupal.org/project/libraries
 * @see \Drupal\webform\WebformLibrariesManager::initLibraries
 */
function hook_webform_libraries_info_alter(&$libraries) {

}

/**
 * Collect extra webform help from modules.
 *
 * To help on-boarding new users, there is a robust help system developed. If
 * you would like to add extra webform help, you are free to implement this
 * hook.
 *
 * @return array
 *   Extra webform help your module is providing to the users. The return array
 *   should be keyed by help ID (a unique machine-name) and each sub array
 *   should have the following structure:
 *   - access: (bool) Whether the current user is granted access to this help.
 *     Defaults to TRUE.
 *   - routes: (array) Array of route names where your help should be displayed.
 *   - paths: (array) Array of paths where your help should be displayed. You
 *     can use any syntax supported by the "path.matcher" service.
 *   - video_id: (string) Optional video to include in the help message. Allowed
 *     values are the keys of WebformHelpManager::videos array.
 *   - attached: (array) Optional #attached array to attach to your help
 *     renderable array.
 *   - group: (string) Group where your help belongs. Allowed values are the
 *     keys of WebformHelpManager::groups array.
 *   - title: (string) Title of your help
 *   - content: (array) Renderable array of your help
 *   - message_id: (string) Optional message ID that will be supplied into
 *     'webform_message' element. You are free to use 'message_*' keys if you
 *     want to additionally display a message when your help is displayed. These
 *     keyes will be supplied into 'webform_message' element. Refer to the docs
 *     of this element for their meaning.
 *   - message_type: (string) Will be supplied into 'webform_message' element.
 *   - message_close: (bool) Will be supplied into 'webform_message' element.
 *   - message_storage: (string) Will be supplied into 'webform_message'
 *     element.
 */
function hook_webform_help_info() {
  $help = [];

  $help['my_custom_help'] = [
    'access' => \Drupal::currentUser()->hasPermission('my cool permission'),
    'routes' => [
      'my_module.route_where_i_show_this_help',
    ],
    'paths' => [
      '/path/where/*/i-wanna/show-help',
    ],
    'video_id' => 'blocks',
    'attached' => [],
    'group' => 'messages',
    'title' => t('Message: Webform UI Disabled'),
    'content' => t('Please enable the <strong>Webform UI</strong> module if you would like to add easily add and manage elements using a drag-n-drop user interface.'),
    'message_id' => '',
    'message_type' => 'warning',
    'message_close' => TRUE,
    'message_storage' => \Drupal\webform\Element\WebformMessage::STORAGE_STATE,
  ];

  return $help;
}

/**
 * Alter the webform help.
 *
 * @param array $help
 *   Webform help data as collected from hook_webform_help_info().
 */
function hook_webform_help_info_alter(array &$help) {
  if (isset($help['some_help_i_wanna_change'])) {
    $help['title'] = t('This is a really cool help message. Do read it thorough!');
  }
}

/**
 * Supply additional access rules that should be managed on per-webform level.
 *
 * If your module defines any additional access logic that should be managed on
 * per webform level, this hook is likely to be of use. Provide additional
 * access rules into the webform access system through this hook. Then website
 * administrators can assign appropriate grants to your rules for each webform
 * via admin UI. Whenever you need to check if a user has access to execute a
 * certain operation you should do the following:
 *
 *   \Drupal::entityTypeManager()
 *     ->getAccessControlHandler('webform_submission')
 *     ->access($webform_submission, $some_operation, $account);
 *
 * This will return either a positive or a negative result depending on what
 * website administrator has supplied in access settings for the webform in
 * question.
 *
 * @return array
 *   Array of metadata about additional access rules to be managed on per
 *   webform basis. Keys should be machine names whereas values are sub arrays
 *   with the following structure:
 *   - title: (string) Human friendly title of the rule.
 *   - description: (array) Renderable array that explains what this access rule
 *     stands for. Defaults to an empty array.
 *   - weight: (int) Sorting order of this access rule. Defaults to 0.
 *   - roles: (string[]) Array of role IDs that should be granted this access
 *     rule by default. Defaults to an empty array.
 *   - permissions: (string[]) Array of permissions that should be granted this
 *     access rule by default. Defaults to an empty array.
 */
function hook_webform_access_rules() {
  return [
    // A custom operation.
    'some_operation' => [
      'title' => t('Some operation'),
      'weight' => -100,
      'roles' => ['authenticated'],
      'permissions' => ['some permission', 'another permission'],
    ],

    // Custom any and own operations using hook_submission_access().
    //
    // - _any: means to grant access to all webform submissions independently
    //   of authorship
    // - _own: means to grant access only if the user requesting access is
    //   the author of the webform submission on which the operation is
    //   being requested.
    //
    // The below 2 operations can be queried together as following:
    //
    // \Drupal::entityTypeManager()
    //  ->getAccessControlHandler('webform_submission')
    //  ->access($webform_submission, 'some_operation', $account);
    //
    // This will return TRUE as long as the $account is has either
    // 'some_operation_any' or has 'some_operation_own' and is author of
    // the $webform_submission.
    //
    // Note, to implement *_own and *_any you will need to implement
    // hook_webform_submission_access().
    //
    // @see hook_webform_submission_access()
    'some_operation_any' => [
      'title' => t('Some operation on ALL webform submissions'),
      'description' => ['#markup' => t('Allow users to execute such particular operation on all webform submissions independently of whether they are authors of those submissions.')],
    ],
    'some_operation_own' => [
      'title' => t('Some operation on own webform submissions'),
    ],
  ];
}

/**
 * Alter list of access rules that should be managed on per webform level.
 *
 * @param array $access_rules
 *   Array of known access rules. Its structure is identical to the return of
 *   hook_webform_access_rules().
 */
function hook_webform_access_rules_alter(array &$access_rules) {
  if (isset($access_rules['some_specific_rule_i_want_to_alter'])) {
    $access_rules['some_specific_rule_i_want_to_alter']['title'] = t('My very cool altered title!');
  }
}

/**
 * Implements hook_webform_submission_access().
 *
 * Implements *_any and *_own operations for a module.
 */
function hook_webform_submission_access(\Drupal\webform\WebformSubmissionInterface $webform_submission, $operation, \Drupal\Core\Session\AccountInterface $account) {
  /** @var \Drupal\webform\WebformAccessRulesManagerInterface $access_rules_manager */
  $access_rules_manager = \Drupal::service('webform.access_rules_manager');

  // Add support for some module *_any and *_own access rules.
  $access_rules = \Drupal::moduleHandler()->invoke('MY_MODULE', 'webform_access_rules');
  $access_any = isset($access_rules[$operation . '_any']) ? $access_rules_manager->checkWebformSubmissionAccess($operation . '_any', $account, $webform_submission) : \Drupal\Core\Access\AccessResult::forbidden();
  $access_own = (isset($access_rules[$operation . '_own']) && $webform_submission->isOwner($account)) ? $access_rules_manager->checkWebformSubmissionAccess($operation . '_own', $account, $webform_submission) : \Drupal\Core\Access\AccessResult::forbidden();
  $access = $access_any->orIf($access_own);
  if ($access->isAllowed()) {
    return $access;
  }
  else {
    return \Drupal\Core\Access\AccessResult::neutral();
  }
}

/**
 * Act on a custom message being displayed, closed or reset.
 *
 * @param string $operation
 *   closed: Returns TRUE if the message is closed.
 *   close: Sets the message's state to closed.
 *   reset: Resets the message's closed state.
 * @param string $id
 *   The message id.
 *
 * @return mixed|bool
 *   TRUE if message is closed, else NULL
 *
 * @internal
 *   This is an experimental hook whose definition may change.
 *
 * @see \Drupal\webform\Element\WebformMessage::isClosed
 * @see \Drupal\webform\Element\WebformMessage::setClosed
 * @see \Drupal\webform\Element\WebformMessage::resetClosed
 */
function hook_webform_message_custom($operation, $id) {
  // Handle 'webform_test_message_custom' defined in
  // webform.webform.test_element_message.yml.
  if ($id === 'webform_test_message_custom') {
    switch ($operation) {
      case 'closed':
        return \Drupal::state()->get($id, FALSE);

      case 'close':
        \Drupal::state()->set($id, TRUE);
        return NULL;

      case 'reset':
        \Drupal::state()->delete($id);
        return NULL;
    }
  }
}

/**
 * Alter webform submission query access.
 *
 * @param \Drupal\Core\Database\Query\AlterableInterface $query
 *   An Query object describing the composite parts of a SQL query.
 * @param array $webform_submission_tables
 *   An array webform submission tables that contains the table's alias and
 *   OR conditions which are used to build the alter query.
 *
 * @see webform_query_webform_submission_access_alter()
 */
function hook_webform_submission_query_access_alter(\Drupal\Core\Database\Query\AlterableInterface $query, array $webform_submission_tables) {
  // Always allow the current user access to their submissions.
  foreach ($webform_submission_tables as $table) {
    $table['condition']->condition($table['alias'] . '.uid', \Drupal::currentUser()->id());
  }
}

/**
 * @} End of "addtogroup hooks".
 */
