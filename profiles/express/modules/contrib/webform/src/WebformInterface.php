<?php

namespace Drupal\webform;

use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\Core\Entity\EntityWithPluginCollectionInterface;
use Drupal\user\EntityOwnerInterface;
use Drupal\webform\Plugin\WebformHandlerInterface;
use Drupal\webform\Plugin\WebformVariantInterface;

/**
 * Provides an interface defining a webform entity.
 */
interface WebformInterface extends ConfigEntityInterface, EntityWithPluginCollectionInterface, EntityOwnerInterface {

  /**
   * Webform title.
   */
  const TITLE_WEBFORM = 'webform';

  /**
   * Source entity title.
   */
  const TITLE_SOURCE_ENTITY = 'source_entity';

  /**
   * Both source entity and webform title.
   */
  const TITLE_SOURCE_ENTITY_WEBFORM = 'source_entity_webform';

  /**
   * Both webform and source entity title.
   */
  const TITLE_WEBFORM_SOURCE_ENTITY = 'webform_source_entity';

  /**
   * Denote drafts are not allowed.
   *
   * @var string
   */
  const DRAFT_NONE = 'none';

  /**
   * Denote drafts are allowed for authenticated users only.
   *
   * @var string
   */
  const DRAFT_AUTHENTICATED = 'authenticated';

  /**
   * Denote drafts are allowed for authenticated and anonymous users.
   *
   * @var string
   */
  const DRAFT_ALL = 'all';

  /**
   * Webform status open.
   */
  const STATUS_OPEN = 'open';

  /**
   * Webform status closed.
   */
  const STATUS_CLOSED = 'closed';

  /**
   * Webform status scheduled.
   */
  const STATUS_SCHEDULED = 'scheduled';

  /**
   * Webform status archived.
   */
  const STATUS_ARCHIVED = 'archived';

  /**
   * Webform confirmation page.
   */
  const CONFIRMATION_PAGE = 'page';

  /**
   * Webform confirmation URL.
   */
  const CONFIRMATION_URL = 'url';

  /**
   * Webform confirmation URL with message.
   */
  const CONFIRMATION_URL_MESSAGE = 'url_message';

  /**
   * Webform confirmation inline.
   */
  const CONFIRMATION_INLINE = 'inline';

  /**
   * Webform confirmation message.
   */
  const CONFIRMATION_MESSAGE = 'message';

  /**
   * Webform confirmation modal.
   */
  const CONFIRMATION_MODAL = 'modal';

  /**
   * Webform confirmation default.
   */
  const CONFIRMATION_DEFAULT = 'default';

  /**
   * Webform confirmation none.
   */
  const CONFIRMATION_NONE = 'none';

  /**
   * Display standard 403 access denied page.
   */
  const ACCESS_DENIED_DEFAULT = 'default';

  /**
   * Display customized access denied message.
   */
  const ACCESS_DENIED_MESSAGE = 'message';

  /**
   * Display customized 403 access denied page.
   */
  const ACCESS_DENIED_PAGE = 'page';

  /**
   * Redirect to user login with custom message.
   */
  const ACCESS_DENIED_LOGIN = 'login';

  /**
   * Returns the webform's (original) langcode.
   *
   * @return string
   *   The webform's (original) langcode.
   */
  public function getLangcode();

  /**
   * Returns the webform's weight.
   *
   * Only applies to when multiple webforms are attached to a single node.
   *
   * @return int
   *   The webform's weight.
   */
  public function getWeight();

  /**
   * Determine if the webform has page or is attached to other entities.
   *
   * @return bool
   *   TRUE if the webform is a page with dedicated path.
   */
  public function hasPage();

  /**
   * Determine if the webform's elements include a managed_file upload element.
   *
   * @return bool
   *   TRUE if the webform's elements include a managed_file upload element.
   */
  public function hasManagedFile();

  /**
   * Determine if the webform's elements include attachments.
   *
   * @return bool
   *   TRUE if the webform's elements include attachments.
   */
  public function hasAttachments();

  /**
   * Determine if the webform's elements include computed values.
   *
   * @return bool
   *   TRUE if the webform's elements include computed values.
   */
  public function hasComputed();

  /**
   * Determine if the webform's elements include variants.
   *
   * @return bool
   *   TRUE if the webform's elements include variants.
   */
  public function hasVariants();

  /**
   * Determine if the webform is using a Flexbox layout.
   *
   * @return bool
   *   TRUE if the webform is using a Flexbox layout.
   */
  public function hasFlexboxLayout();

  /**
   * Determine if the webform has any containers.
   *
   * @return bool
   *   TRUE if the webform has any containers.
   */
  public function hasContainer();

  /**
   * Determine if the webform has conditional logic (i.e. #states).
   *
   * @return bool
   *   TRUE if the webform has conditional logic.
   */
  public function hasConditions();

  /**
   * Determine if the webform has required elements.
   *
   * @return bool
   *   TRUE if the webform has required elements.
   */
  public function hasRequired();

  /**
   * Determine if the webform has any custom actions (aka submit buttons).
   *
   * @return bool
   *   TRUE if the webform has any custom actions (aka submit buttons).
   */
  public function hasActions();

  /**
   * Get the number of actions (aka submit buttons).
   *
   * @return int
   *   The number of actions (aka submit buttons).
   */
  public function getNumberOfActions();

  /**
   * Determine if the webform has preview page.
   *
   * @return bool
   *   TRUE if the webform has preview page.
   */
  public function hasPreview();

  /**
   * Determine if the webform has multi-step form wizard pages.
   *
   * @return bool
   *   TRUE if the webform has multi-step form wizard pages.
   */
  public function hasWizardPages();

  /**
   * Get the number of wizard pages.
   *
   * @return int
   *   The number of wizard pages.
   */
  public function getNumberOfWizardPages();

  /**
   * Returns the webform's current operation.
   *
   * @return string
   *   The webform's operation.
   */
  public function getOperation();

  /**
   * Sets the webform's current operation .
   *
   * @param string $operation
   *   The webform's operation.
   *
   * @return $this
   *
   * @see \Drupal\webform\WebformSubmissionForm
   */
  public function setOperation($operation);

  /**
   * Determine if the webform is being tested.
   *
   * @return bool
   *   TRUE if the webform is being tested.
   */
  public function isTest();

  /**
   * Sets the webform settings and properties override state.
   *
   * Setting the override state to TRUE allows modules to alter a webform's
   * settings and properties while blocking a webform from being saved with
   * the overridden settings.
   *
   * @param bool $override
   *   The override state of the Webform.
   *
   * @return $this
   *
   * @see \Drupal\webform\WebformInterface::setSettingsOverride
   * @see \Drupal\webform\Entity\Webform::preSave
   */
  public function setOverride($override = TRUE);

  /**
   * Returns the webform override status.
   *
   * @return bool
   *   TRUE if the webform has any overridden settings or properties.
   */
  public function isOverridden();

  /**
   * Sets the status of the configuration entity.
   *
   * @param string|bool|null $status
   *   The status of the configuration entity.
   *   - TRUE => WebformInterface::STATUS_OPEN.
   *   - FALSE => WebformInterface::STATUS_CLOSED.
   *   - NULL => WebformInterface::STATUS_SCHEDULED.
   *
   * @return $this
   */
  public function setStatus($status);

  /**
   * Returns the webform opened status indicator.
   *
   * @return bool
   *   TRUE if the webform is open to new submissions.
   */
  public function isOpen();

  /**
   * Returns the webform closed status indicator.
   *
   * @return bool
   *   TRUE if the webform is closed to new submissions.
   */
  public function isClosed();

  /**
   * Returns the webform scheduled status indicator.
   *
   * @return bool
   *   TRUE if the webform is scheduled to open/close to new submissions.
   */
  public function isScheduled();

  /**
   * Determines if the webform is currently closed but scheduled to open.
   *
   * @return bool
   *   TRUE if the webform is currently closed but scheduled to open.
   */
  public function isOpening();

  /**
   * Returns the webform template indicator.
   *
   * @return bool
   *   TRUE if the webform is a template and available for duplication.
   */
  public function isTemplate();

  /**
   * Returns the webform archive indicator.
   *
   * @return bool
   *   TRUE if the webform is archived.
   */
  public function isArchived();

  /**
   * Returns the webform confidential indicator.
   *
   * @return bool
   *   TRUE if the webform is confidential.
   */
  public function isConfidential();

  /**
   * Determine if remote IP address is being stored.
   *
   * @return bool
   *   TRUE if remote IP address is being stored.
   */
  public function hasRemoteAddr();

  /**
   * Determine if the saving of submissions is disabled.
   *
   * @return bool
   *   TRUE if the saving of submissions is disabled.
   */
  public function isResultsDisabled();

  /**
   * Checks if a webform has submissions.
   *
   * @return bool
   *   TRUE if the webform has submissions.
   */
  public function hasSubmissions();

  /**
   * Determine if submissions are being logged.
   *
   * @return bool
   *   TRUE if submissions are being logged.
   */
  public function hasSubmissionLog();

  /**
   * Determine if the current webform is translated.
   *
   * @return bool
   *   TRUE if the current webform is translated.
   */
  public function hasTranslations();

  /**
   * Returns the webform's description.
   *
   * @return string
   *   A webform's description.
   */
  public function getDescription();

  /**
   * Sets a webform's description.
   *
   * @param string $description
   *   A description.
   *
   * @return $this
   */
  public function setDescription($description);

  /**
   * Returns the webform's global and custom CSS and JavaScript assets.
   *
   * @return array
   *   An associative array container the webform's CSS and JavaScript.
   */
  public function getAssets();

  /**
   * Returns the webform's CSS.
   *
   * @return string
   *   The webform's CSS.
   */
  public function getCss();

  /**
   * Sets the webform's CSS.
   *
   * @param string $css
   *   The webform's CSS.
   *
   * @return $this
   */
  public function setCss($css);

  /**
   * Returns the webform's JavaScript.
   *
   * @return string
   *   The webform's CSS.
   */
  public function getJavaScript();

  /**
   * Sets the webform's JavaScript.
   *
   * @param string $javascript
   *   The webform's JavaScript.
   *
   * @return $this
   */
  public function setJavaScript($javascript);

  /**
   * Returns the webform settings.
   *
   * @return array
   *   A structured array containing all the webform settings.
   */
  public function getSettings();

  /**
   * Sets the webform settings.
   *
   * @param array $settings
   *   The structured array containing all the webform setting.
   *
   * @return $this
   */
  public function setSettings(array $settings);

  /**
   * Returns the webform settings for a given key.
   *
   * @param string $key
   *   The key of the setting to retrieve.
   * @param bool $default
   *   Flag to lookup the default settings from 'webform.settings' config.
   *   Only used when rendering webform.
   *
   * @return mixed
   *   The settings value, or NULL if no settings exists.
   */
  public function getSetting($key, $default = FALSE);

  /**
   * Sets a webform setting for a given key.
   *
   * @param string $key
   *   The key of the setting to store.
   * @param mixed $value
   *   The data to store.
   *
   * @return $this
   */
  public function setSetting($key, $value);

  /**
   * Reset overridden settings to original settings.
   */
  public function resetSettings();

  /**
   * Sets the webform settings override.
   *
   * Using this methods stops a webform from being saved with the overridden
   * settings.
   *
   * @param array $settings
   *   The structured array containing the webform setting override.
   *
   * @return $this
   */
  public function setSettingsOverride(array $settings);

  /**
   * Sets a webform setting override for a given key.
   *
   * Using this methods stops a webform from being saved with the overridden
   * setting.
   *
   * @param string $key
   *   The key of the setting override to store.
   * @param mixed $value
   *   The data to store.
   *
   * @return $this
   */
  public function setSettingOverride($key, $value);

  /**
   * Sets the value of an overridden property.
   *
   * Using this methods stops a webform from being saved with the overridden
   * property.
   *
   * @param string $property_name
   *   The name of the property that should be set.
   * @param mixed $value
   *   The value the property should be set to.
   *
   * @return $this
   */
  public function setPropertyOverride($property_name, $value);

  /**
   * Returns the webform access rules.
   *
   * @return array
   *   A structured array containing all the webform access rules.
   */
  public function getAccessRules();

  /**
   * Sets the webform access rules.
   *
   * @param array $access
   *   The structured array containing all the webform access rules.
   *
   * @return $this
   */
  public function setAccessRules(array $access);

  /**
   * Returns the webform default settings.
   *
   * @return array
   *   A structured array containing all the webform default settings.
   */
  public static function getDefaultSettings();

  /**
   * Get webform submission webform.
   *
   * @param array $values
   *   (optional) An array of values to set, keyed by property name.
   * @param string $operation
   *   (optional) The operation identifying the webform submission form
   *   variation to be returned.
   *   Defaults to 'add'. This is typically used in routing.
   *
   * @return array
   *   A render array representing a webform submission webform.
   */
  public function getSubmissionForm(array $values = [], $operation = 'add');

  /**
   * Get original elements (YAML) value.
   *
   * @return string|null
   *   The original elements' raw value. Original elements is NULL for new YAML
   *   webforms.
   */
  public function getElementsOriginalRaw();

  /**
   * Get original elements decoded as an associative array.
   *
   * @return array|bool
   *   Elements as an associative array. Returns FALSE if elements YAML is invalid.
   */
  public function getElementsOriginalDecoded();

  /**
   * Get elements (YAML) value.
   *
   * @return string
   *   The elements raw value.
   */
  public function getElementsRaw();

  /**
   * Get webform elements decoded as an associative array.
   *
   * @return array|bool
   *   Elements as an associative array. Returns FALSE if elements YAML is invalid.
   */
  public function getElementsDecoded();

  /**
   * Set element properties.
   *
   * @param string $key
   *   The element's key.
   * @param array $properties
   *   An associative array of properties.
   * @param string $parent_key
   *   (optional) The element's parent key. Only used for new elements.
   *
   * @return $this
   */
  public function setElementProperties($key, array $properties, $parent_key = '');

  /**
   * Remove an element.
   *
   * @param string $key
   *   The element's key.
   */
  public function deleteElement($key);

  /**
   * Get webform elements initialized as an associative array.
   *
   * @return array|bool
   *   Elements as an associative array. Returns FALSE if elements YAML is invalid.
   */
  public function getElementsInitialized();

  /**
   * Get webform raw elements decoded and flattened into an associative array.
   *
   * @param string $operation
   *   (optional) The operation that is to be performed on the element.
   *
   * @return array
   *   Webform raw elements decoded and flattened into an associative array
   *   keyed by element key. Returns FALSE if elements YAML is invalid.
   */
  public function getElementsDecodedAndFlattened($operation = NULL);

  /**
   * Get webform elements initialized and flattened into an associative array.
   *
   * @param string $operation
   *   (optional) The operation that is to be performed on the element.
   *
   * @return array
   *   Webform elements flattened into an associative array keyed by element key.
   *   Returns FALSE if elements YAML is invalid.
   */
  public function getElementsInitializedAndFlattened($operation = NULL);

  /**
   * Get webform flattened list of elements.
   *
   * @param string $operation
   *   (optional) The operation that is to be performed on the element.
   *
   * @return array
   *   Webform elements flattened into an associative array keyed by element key.
   */
  public function getElementsInitializedFlattenedAndHasValue($operation = NULL);

  /**
   * Get webform managed file elements.
   *
   * @return array
   *   Webform managed file elements.
   */
  public function getElementsManagedFiles();

  /**
   * Get webform attachment elements.
   *
   * @return array
   *   Webform attachment elements.
   */
  public function getElementsAttachments();

  /**
   * Get webform computed elements.
   *
   * @return array
   *   Webform computed elements.
   */
  public function getElementsComputed();

  /**
   * Get webform variant elements.
   *
   * @return array
   *   Webform variant elements.
   */
  public function getElementsVariant();

  /**
   * Get webform element's selectors as options.
   *
   * @param array $options
   *   (Optional) Options to be appled to element selectors.
   *
   * @return array
   *   Webform elements selectors as options.
   */
  public function getElementsSelectorOptions(array $options = []);

  /**
   * Get webform element options as autocomplete source values.
   *
   * @return array
   *   Webform element options as autocomplete source values.
   */
  public function getElementsSelectorSourceValues();

  /**
   * Get webform elements that can be prepopulated.
   *
   * @return array
   *   Webform elements that can be prepopulated.
   */
  public function getElementsPrepopulate();

  /**
   * Get webform elements default data.
   *
   * @return array
   *   Webform elements default data.
   */
  public function getElementsDefaultData();

  /**
   * Sets elements (YAML) value.
   *
   * @param array $elements
   *   An renderable array of elements.
   *
   * @return $this
   */
  public function setElements(array $elements);

  /**
   * Get a webform's initialized element.
   *
   * @param string $key
   *   The element's key.
   * @param bool $include_children
   *   Include initialized children.
   *
   * @return array|null
   *   An associative array containing an initialized element.
   */
  public function getElement($key, $include_children = FALSE);

  /**
   * Get a webform's raw (uninitialized) element.
   *
   * @param string $key
   *   The element's key.
   *
   * @return array|null
   *   An associative array containing an raw (uninitialized) element.
   */
  public function getElementDecoded($key);

  /**
   * Get webform wizard pages.
   *
   * @param string $operation
   *   The webform submission operation.
   *   Usually 'default', 'add', 'edit', 'edit_all', 'api', or 'test'.
   * @param \Drupal\webform\WebformSubmissionInterface|null $webform_submission
   *   (Optional) A webform submission. If a webform submission is defined and
   *   the 'wizard_progress_states' is TRUE, wizard page conditional logic
   *   will be evaluated.
   *
   * @return array
   *   An associative array of webform wizard pages.
   *
   * @see \Drupal\webform\Entity\WebformSubmission
   */
  public function getPages($operation = '', WebformSubmissionInterface $webform_submission = NULL);

  /**
   * Get webform wizard page.
   *
   * @param string $operation
   *   Operation being performed.
   * @param string|int $key
   *   The name/key of a webform wizard page.
   *
   * @return array|null
   *   A webform wizard page element.
   */
  public function getPage($operation, $key);

  /**
   * Update submit and confirm paths (i.e. URL aliases) associated with this webform.
   */
  public function updatePaths();

  /**
   * Update submit and confirm paths associated with this webform.
   */
  public function deletePaths();

  /****************************************************************************/
  // Handler plugins.
  /****************************************************************************/

  /**
   * Determine if the webform has any message handlers.
   *
   * @return bool
   *   TRUE if the webform has any message handlers.
   */
  public function hasMessageHandler();

  /**
   * Determine if a webform handler requires anonymous submission tracking.
   *
   * @return bool
   *   TRUE if a webform handler requires anonymous submission tracking.
   *
   * @see \Drupal\webform_options_limit\Plugin\WebformHandler\OptionsLimitWebformHandler
   */
  public function hasAnonymousSubmissionTrackingHandler();

  /**
   * Returns a specific webform handler.
   *
   * @param string $handler_id
   *   The webform handler ID.
   *
   * @return \Drupal\webform\Plugin\WebformHandlerInterface
   *   The webform handler object.
   */
  public function getHandler($handler_id);

  /**
   * Returns the webform handlers for this webform.
   *
   * @param string $plugin_id
   *   (optional) Plugin id used to return specific plugin instances
   *   (i.e. handlers).
   * @param bool $status
   *   (optional) Status used to return enabled or disabled plugin instances
   *   (i.e. handlers).
   * @param int $results
   *   (optional) Value indicating if webform submissions are saved to internal
   *   or external system.
   * @param int $submission
   *   (optional) Value indicating if webform submissions must be saved to the
   *   database.
   *
   * @return \Drupal\webform\Plugin\WebformHandlerPluginCollection|\Drupal\webform\Plugin\WebformHandlerInterface[]
   *   The webform handler plugin collection.
   */
  public function getHandlers($plugin_id = NULL, $status = NULL, $results = NULL, $submission = NULL);

  /**
   * Saves a webform handler for this webform.
   *
   * @param \Drupal\webform\Plugin\WebformHandlerInterface $handler
   *   The webform handler object.
   *
   * @return string
   *   The webform handler ID.
   */
  public function addWebformHandler(WebformHandlerInterface $handler);

  /**
   * Update a webform handler for this webform.
   *
   * @param \Drupal\webform\Plugin\WebformHandlerInterface $handler
   *   The webform handler object.
   *
   * @return $this
   */
  public function updateWebformHandler(WebformHandlerInterface $handler);

  /**
   * Deletes a webform handler from this webform.
   *
   * @param \Drupal\webform\Plugin\WebformHandlerInterface $handler
   *   The webform handler object.
   *
   * @return $this
   */
  public function deleteWebformHandler(WebformHandlerInterface $handler);

  /**
   * Invoke a handlers method.
   *
   * @param string $method
   *   The handler method to be invoked.
   * @param mixed $data
   *   The argument to passed by reference to the handler method.
   * @param mixed $context1
   *   (optional) An additional variable that is passed by reference.
   * @param mixed $context2
   *   (optional) An additional variable that is passed by reference.
   * @param mixed $context3
   *   (optional) An additional variable that is passed by reference.
   *
   * @return \Drupal\Core\Access\AccessResult|null
   *   If 'access' method is invoked an AccessResult is returned.
   */
  public function invokeHandlers($method, &$data, &$context1 = NULL, &$context2 = NULL, &$context3 = NULL);

  /****************************************************************************/
  // Element plugins.
  /****************************************************************************/

  /**
   * Invoke elements method.
   *
   * @param string $method
   *   The handler method to be invoked.
   * @param mixed $data
   *   The argument to passed by reference to the handler method.
   * @param mixed $context1
   *   (optional) An additional variable that is passed by reference.
   * @param mixed $context2
   *   (optional) An additional variable that is passed by reference.
   */
  public function invokeElements($method, &$data, &$context1 = NULL, &$context2 = NULL);

  /****************************************************************************/
  // Variant plugins.
  /****************************************************************************/

  /**
   * Determine if a specific webform variant exists.
   *
   * @param string $variant_id
   *   The webform variant ID.
   *
   * @return boolean
   *   TRUE if a specific webform variant exists.
   */
  public function hasVariant($variant_id);

  /**
   * Returns a specific webform variant.
   *
   * @param string $variant_id
   *   The webform variant ID.
   *
   * @return \Drupal\webform\Plugin\WebformVariantInterface
   *   The webform variant object.
   */
  public function getVariant($variant_id);

  /**
   * Returns the webform variants for this webform.
   *
   * @param string $plugin_id
   *   (optional) Plugin id used to return specific plugin instances
   *   (i.e. variants).
   * @param bool $status
   *   (optional) Status used to return enabled or disabled plugin instances
   *   (i.e. variants).
   * @param bool $element_key
   *   (optional) Element key used to return enabled or disabled plugin instances
   *   (i.e. variants).
   *
   * @return \Drupal\webform\Plugin\WebformVariantPluginCollection|\Drupal\webform\Plugin\WebformVariantInterface[]
   *   The webform variant plugin collection.
   */
  public function getVariants($plugin_id = NULL, $status = NULL, $element_key = NULL);

  /**
   * Saves a webform variant for this webform.
   *
   * @param \Drupal\webform\Plugin\WebformVariantInterface $variant
   *   The webform variant object.
   *
   * @return string
   *   The webform variant ID.
   */
  public function addWebformVariant(WebformVariantInterface $variant);

  /**
   * Update a webform variant for this webform.
   *
   * @param \Drupal\webform\Plugin\WebformVariantInterface $variant
   *   The webform variant object.
   *
   * @return $this
   */
  public function updateWebformVariant(WebformVariantInterface $variant);

  /**
   * Deletes a webform variant from this webform.
   *
   * @param \Drupal\webform\Plugin\WebformVariantInterface $variant
   *   The webform variant object.
   *
   * @return $this
   */
  public function deleteWebformVariant(WebformVariantInterface $variant);

  /**
   * Apply webform variants based on a webform submission or parameter.
   *
   * @param \Drupal\webform\WebformSubmissionInterface $webform_submission
   *   A webform submission.
   * @param array $variants
   *   An associative array of variant element keys and variant ids.
   * @param bool $force
   *   Apply disabled variants. Defaults to FALSE.
   *
   * @throws \Exception
   *   Throws exception if submission was not created using this webform.
   */
  public function applyVariants(WebformSubmissionInterface $webform_submission = NULL, $variants = [], $force = FALSE);

  /**
   * Get variants data from a webform submission.
   *
   * @param \Drupal\webform\WebformSubmissionInterface $webform_submission
   *   A webform submission.
   *
   * @return array
   *   A associative array containing the variant element keys
   *   and variant value.
   */
  public function getVariantsData(WebformSubmissionInterface $webform_submission);

  /****************************************************************************/
  // Revisions.
  /****************************************************************************/

  /**
   * Required to allow webform which are config entities to have an EntityViewBuilder.
   *
   * Prevents:
   *   Fatal error: Call to undefined method
   *   Drupal\webform\Entity\Webform::isDefaultRevision()
   *   in /private/var/www/sites/d8_dev/core/lib/Drupal/Core/Entity/EntityViewBuilder.php
   *   on line 169
   *
   * @see \Drupal\Core\Entity\RevisionableInterface::isDefaultRevision()
   *
   * @return bool
   *   Always return TRUE since config entities are not revisionable.
   */
  public function isDefaultRevision();

  /****************************************************************************/
  // State data.
  /****************************************************************************/

  /**
   * Returns the stored value for a given key in the webform's state.
   *
   * @param string $key
   *   The key of the data to retrieve.
   * @param mixed $default
   *   The default value to use if the key is not found.
   *
   * @return mixed
   *   The stored value, or NULL if no value exists.
   */
  public function getState($key, $default = NULL);

  /**
   * Saves a value for a given key in the webform's state.
   *
   * @param string $key
   *   The key of the data to store.
   * @param mixed $value
   *   The data to store.
   */
  public function setState($key, $value);

  /**
   * Deletes an item from the webform's state.
   *
   * @param string $key
   *   The item name to delete.
   */
  public function deleteState($key);

  /**
   * Determine if the stored value for a given key exists in the webform's state.
   *
   * @param string $key
   *   The key of the data to retrieve.
   *
   * @return bool
   *   TRUE if the stored value for a given key exists.
   */
  public function hasState($key);

  /****************************************************************************/
  // User data.
  /****************************************************************************/

  /**
   * Returns the stored value for a given key in the webform's user data.
   *
   * @param string $key
   *   The key of the data to retrieve.
   * @param mixed $default
   *   The default value to use if the key is not found.
   *
   * @return mixed
   *   The stored value, or NULL if no value exists.
   */
  public function getUserData($key, $default = NULL);

  /**
   * Saves a value for a given key in the webform's user data.
   *
   * @param string $key
   *   The key of the data to store.
   * @param mixed $value
   *   The data to store.
   */
  public function setUserData($key, $value);

  /**
   * Deletes an item from the webform's user data.
   *
   * @param string $key
   *   The item name to delete.
   */
  public function deleteUserData($key);

  /**
   * Determine if the stored value for a given key exists in the webform's user data.
   *
   * @param string $key
   *   The key of the data to retrieve.
   *
   * @return bool
   *   TRUE if the stored value for a given key exists.
   */
  public function hasUserData($key);

}
