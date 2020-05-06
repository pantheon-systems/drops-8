<?php

namespace Drupal\webform\Plugin\WebformElement;

use Drupal\Component\Utility\Bytes;
use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\Unicode;
use Drupal\Component\Transliteration\TransliterationInterface;
use Drupal\Component\Utility\Environment;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\EventSubscriber\MainContentViewSubscriber;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Link;
use Drupal\Core\Render\Element;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Url as UrlGenerator;
use Drupal\Core\Render\ElementInfoManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StreamWrapper\StreamWrapperInterface;
use Drupal\file\Entity\File;
use Drupal\file\FileInterface;
use Drupal\webform\Element\WebformHtmlEditor;
use Drupal\webform\Entity\WebformSubmission;
use Drupal\webform\Plugin\WebformElementAttachmentInterface;
use Drupal\webform\Plugin\WebformElementBase;
use Drupal\webform\Utility\WebformOptionsHelper;
use Drupal\webform\WebformInterface;
use Drupal\webform\WebformSubmissionForm;
use Drupal\webform\WebformSubmissionInterface;
use Drupal\webform\Plugin\WebformElementManagerInterface;
use Drupal\webform\Plugin\WebformElementEntityReferenceInterface;
use Drupal\webform\WebformLibrariesManagerInterface;
use Drupal\webform\WebformTokenManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a base class webform 'managed_file' elements.
 */
abstract class WebformManagedFileBase extends WebformElementBase implements WebformElementAttachmentInterface, WebformElementEntityReferenceInterface {

  /**
   * List of blacklisted mime types that must be downloaded.
   *
   * @var array
   */
  static protected $blacklistedMimeTypes = [
    'application/pdf',
    'application/xml',
    'image/svg+xml',
    'text/html',
  ];

  /**
   * The 'file_system' service.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected $fileSystem;

  /**
   * The 'file.usage' service.
   *
   * @var \Drupal\file\FileUsage\FileUsageInterface
   */
  protected $fileUsage;

  /**
   * The 'transliteration' service.
   *
   * @var \Drupal\Component\Transliteration\TransliterationInterface
   */
  protected $transliteration;

  /**
   * The 'language_manager' service.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * WebformManagedFileBase constructor.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Psr\Log\LoggerInterface $logger
   *   A logger instance.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration factory.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Render\ElementInfoManagerInterface $element_info
   *   The element info manager.
   * @param \Drupal\webform\Plugin\WebformElementManagerInterface $element_manager
   *   The webform element manager.
   * @param \Drupal\webform\WebformTokenManagerInterface $token_manager
   *   The webform token manager.
   * @param \Drupal\webform\WebformLibrariesManagerInterface $libraries_manager
   *   The webform libraries manager.
   * @param \Drupal\Core\File\FileSystemInterface $file_system
   *   The file system service.
   * @param \Drupal\file\FileUsage\FileUsageInterface|null $file_usage
   *   The file usage service.
   * @param \Drupal\Component\Transliteration\TransliterationInterface $transliteration
   *   The transliteration service.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, LoggerInterface $logger, ConfigFactoryInterface $config_factory, AccountInterface $current_user, EntityTypeManagerInterface $entity_type_manager, ElementInfoManagerInterface $element_info, WebformElementManagerInterface $element_manager, WebformTokenManagerInterface $token_manager, WebformLibrariesManagerInterface $libraries_manager, FileSystemInterface $file_system, $file_usage, TransliterationInterface $transliteration, LanguageManagerInterface $language_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $logger, $config_factory, $current_user, $entity_type_manager, $element_info, $element_manager, $token_manager, $libraries_manager);

    $this->fileSystem = $file_system;
    $this->fileUsage = $file_usage;
    $this->transliteration = $transliteration;
    $this->languageManager = $language_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('logger.factory')->get('webform'),
      $container->get('config.factory'),
      $container->get('current_user'),
      $container->get('entity_type.manager'),
      $container->get('plugin.manager.element_info'),
      $container->get('plugin.manager.webform.element'),
      $container->get('webform.token_manager'),
      $container->get('webform.libraries_manager'),
      $container->get('file_system'),
      // We soft depend on "file" module so this service might not be available.
      $container->has('file.usage') ? $container->get('file.usage') : NULL,
      $container->get('transliteration'),
      $container->get('language_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function defineDefaultProperties() {
    $file_extensions = $this->getFileExtensions();
    $properties = parent::defineDefaultProperties() + [
      'multiple' => FALSE,
      'max_filesize' => '',
      'file_extensions' => $file_extensions,
      'file_name' => '',
      'file_help' => '',
      'file_preview' => '',
      'file_placeholder' => '',
      'uri_scheme' => 'private',
      'sanitize' => FALSE,
      'button' => FALSE,
      'button__title' => '',
      'button__attributes' => [],
    ];
    // File uploads can't be prepopulated.
    unset($properties['prepopulate']);
    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  protected function defineTranslatableProperties() {
    return array_merge(parent::defineTranslatableProperties(), ['file_placeholder']);
  }

  /****************************************************************************/

  /**
   * {@inheritdoc}
   */
  public function supportsMultipleValues() {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function hasMultipleWrapper() {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function isMultiline(array $element) {
    if ($this->hasMultipleValues($element)) {
      return TRUE;
    }
    else {
      return parent::isMultiline($element);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function isEnabled() {
    if (!parent::isEnabled()) {
      return FALSE;
    }

    // Disable File element is there are no visible stream wrappers.
    $scheme_options = static::getVisibleStreamWrappers();
    return (empty($scheme_options)) ? FALSE : TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function hasManagedFiles(array $element) {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function displayDisabledWarning(array $element) {
    // Display standard disabled element warning.
    if (!parent::isEnabled()) {
      parent::displayDisabledWarning($element);
    }
    else {
      // Display 'managed_file' stream wrappers warning.
      $scheme_options = static::getVisibleStreamWrappers();
      $uri_scheme = $this->getUriScheme($element);
      if (!isset($scheme_options[$uri_scheme]) && $this->currentUser->hasPermission('administer webform')) {
        $this->messenger()->addWarning($this->t('The \'File\' element is unavailable because a <a href="https://www.ostraining.com/blog/drupal/creating-drupal-8-private-file-system/">private files directory</a> has not been configured and public file uploads have not been enabled. For more information see: <a href="https://www.drupal.org/psa-2016-003">DRUPAL-PSA-2016-003</a>'));
        $context = [
          'link' => Link::fromTextAndUrl($this->t('Edit'), UrlGenerator::fromRoute('<current>'))->toString(),
        ];
        $this->logger->notice("The 'File' element is unavailable because no stream wrappers are available", $context);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function prepare(array &$element, WebformSubmissionInterface $webform_submission = NULL) {
    // Track if this element has been processed because the work-around below
    // for 'Issue #2705471: Webform states File fields' which nests the
    // 'managed_file' element in a basic container, which triggers this element
    // to processed a second time.
    if (!empty($element['#webform_managed_file_processed'])) {
      return;
    }
    $element['#webform_managed_file_processed'] = TRUE;

    // Must come after #element_validate hook is defined.
    parent::prepare($element, $webform_submission);

    // Check if the URI scheme exists and can be used the upload location.
    $scheme_options = static::getVisibleStreamWrappers();
    $uri_scheme = $this->getUriScheme($element);
    if (!isset($scheme_options[$uri_scheme])) {
      $element['#access'] = FALSE;
      $this->displayDisabledWarning($element);
    }
    elseif ($webform_submission) {
      $element['#upload_location'] = $this->getUploadLocation($element, $webform_submission->getWebform());
    }

    // Get file limit.
    if ($webform_submission) {
      $file_limit = $webform_submission->getWebform()->getSetting('form_file_limit')
          ?: \Drupal::config('webform.settings')->get('settings.default_form_file_limit')
          ?: '';
    }
    else {
      $file_limit = '';
    }

    // Validate callbacks.
    $element_validate = [];
    // Convert File entities into file ids (akk fids).
    $element_validate[] = [get_class($this), 'validateManagedFile'];
    // Check file upload limit.
    if ($file_limit) {
      $element_validate[] = [get_class($this), 'validateManagedFileLimit'];
    }
    // NOTE: Using array_splice() to make sure that self::validateManagedFile
    // is executed before all other validation hooks are executed but after
    // \Drupal\file\Element\ManagedFile::validateManagedFile.
    array_splice($element['#element_validate'], 1, 0, $element_validate);

    // Upload validators.
    $element['#upload_validators']['file_validate_size'] = [$this->getMaxFileSize($element)];
    $element['#upload_validators']['file_validate_extensions'] = [$this->getFileExtensions($element)];
    $element['#upload_validators']['webform_file_validate_name_length'] = [];

    // Add file upload help to the element as #description, #help, or #more.
    // Copy upload validator so that we can add webform's file limit to
    // file upload help only.
    $upload_validators = $element['#upload_validators'];
    if ($file_limit) {
      $upload_validators['webform_file_limit'] = [Bytes::toInt($file_limit)];
    }
    $file_upload_help = [
      '#theme' => 'file_upload_help',
      '#upload_validators' => $upload_validators,
      '#cardinality' => (empty($element['#multiple'])) ? 1 : $element['#multiple'],
    ];
    $file_help = (isset($element['#file_help'])) ? $element['#file_help'] : 'description';
    if ($file_help !== 'none') {
      if (isset($element["#$file_help"])) {
        if (is_array($element["#$file_help"])) {
          $file_help_content = $element["#$file_help"];
        }
        else {
          $file_help_content = ['#markup' => $element["#$file_help"]];
        }
        $file_help_content += ['#suffix' => '<br/>'];
        $element["#$file_help"] = ['content' => $file_help_content];
      }
      else {
        $element["#$file_help"] = [];
      }
      $element["#$file_help"]['file_upload_help'] = $file_upload_help;
    }

    // Issue #2705471: Webform states File fields.
    // Workaround: Wrap the 'managed_file' element in a basic container.
    if (!empty($element['#prefix'])) {
      $container = [
        '#prefix' => $element['#prefix'],
        '#suffix' => $element['#suffix'],
      ];
      unset($element['#prefix'], $element['#suffix']);
      $container[$element['#webform_key']] = $element + ['#webform_managed_file_processed' => TRUE];
      $element = $container;
    }

    // Add process callback.
    // Set element's #process callback so that is not replaced by
    // additional #process callbacks.
    $this->setElementDefaultCallback($element, 'process');
    $element['#process'][] = [get_class($this), 'processManagedFile'];

    // Add managed file upload tracking.
    if (\Drupal::moduleHandler()->moduleExists('file')) {
      $element['#attached']['library'][] = 'webform/webform.element.managed_file';
    }
  }

  /**
   * {@inheritdoc}
   */
  public function setDefaultValue(array &$element) {
    if (!empty($element['#default_value'])) {
      $element['#default_value'] = (array) $element['#default_value'];
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function formatHtmlItem(array $element, WebformSubmissionInterface $webform_submission, array $options = []) {
    $value = $this->getValue($element, $webform_submission, $options);
    $file = $this->getFile($element, $value, $options);

    if (empty($file)) {
      return '';
    }

    $format = $this->getItemFormat($element);
    switch ($format) {
      case 'basename':
      case 'extension':
      case 'data':
      case 'id':
      case 'mime':
      case 'name':
      case 'raw':
      case 'size':
      case 'url':
      case 'value':
        return $this->formatTextItem($element, $webform_submission, $options);

      case 'link':
        return [
          '#theme' => 'file_link',
          '#file' => $file,
        ];

      default:
        $theme = str_replace('webform_', 'webform_element_', $this->getPluginId());
        if (strpos($theme, 'webform_') !== 0) {
          $theme = 'webform_element_' . $theme;
        }
        return [
          '#theme' => $theme,
          '#element' => $element,
          '#value' => $value,
          '#options' => $options,
          '#file' => $file,
        ];
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function formatTextItem(array $element, WebformSubmissionInterface $webform_submission, array $options = []) {
    $value = $this->getValue($element, $webform_submission, $options);
    $file = $this->getFile($element, $value, $options);

    if (empty($file)) {
      return '';
    }

    $format = $this->getItemFormat($element);
    switch ($format) {
      case 'data':
        return base64_encode(file_get_contents($file->getFileUri()));

      case 'id':
        return $file->id();

      case 'mime':
        return $file->getMimeType();

      case 'name':
        return $file->getFilename();

      case 'basename':
        $filename = $file->getFilename();
        $extension = pathinfo($filename, PATHINFO_EXTENSION);
        return substr(pathinfo($filename, PATHINFO_BASENAME), 0, -strlen(".$extension"));

      case 'size':
        return $file->getSize();

      case 'extension':
        return pathinfo($file->getFileUri(), PATHINFO_EXTENSION);

      case 'url':
      case 'value':
      case 'raw':
      default:
        return file_create_url($file->getFileUri());
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getItemDefaultFormat() {
    return 'file';
  }

  /**
   * {@inheritdoc}
   */
  public function getItemFormats() {
    return parent::getItemFormats() + [
      'file' => $this->t('File'),
      'link' => $this->t('Link'),
      'url' => $this->t('URL'),
      'name' => $this->t('File name'),
      'basename' => $this->t('File base name (no extension)'),
      'id' => $this->t('File ID'),
      'mime' => $this->t('File mime type'),
      'size' => $this->t('File size (Bytes)'),
      'data' => $this->t('File content (Base64)'),
      'extension' => $this->t('File extension'),
    ];
  }

  /**
   * Get file.
   *
   * @param array $element
   *   An element.
   * @param array|mixed $value
   *   A value.
   * @param array $options
   *   An array of options.
   *
   * @return \Drupal\file\FileInterface
   *   A file.
   */
  protected function getFile(array $element, $value, array $options) {
    if (empty($value)) {
      return NULL;
    }
    if ($value instanceof FileInterface) {
      return $value;
    }

    return $this->entityTypeManager->getStorage('file')->load($value);
  }

  /**
   * Get files.
   *
   * @param array $element
   *   An element.
   * @param array|mixed $value
   *   A value.
   * @param array $options
   *   An array of options.
   *
   * @return array
   *   An associative array containing files.
   */
  protected function getFiles(array $element, $value, array $options = []) {
    if (empty($value)) {
      return [];
    }
    return $this->entityTypeManager->getStorage('file')->loadMultiple((array) $value);
  }

  /**
   * {@inheritdoc}
   */
  public function getElementSelectorOptions(array $element) {
    $title = $this->getAdminLabel($element);
    $name = $element['#webform_key'];
    $input = ($this->hasMultipleValues($element)) ? ":input[name=\"files[{$name}][]\"]" : ":input[name=\"files[{$name}]\"]";
    return [$input => $title . '  [' . $this->getPluginLabel() . ']'];
  }

  /**
   * {@inheritdoc}
   */
  public function postSave(array &$element, WebformSubmissionInterface $webform_submission, $update = TRUE) {
    // Get current value and original value for this element.
    $key = $element['#webform_key'];

    $webform = $webform_submission->getWebform();
    if ($webform->isResultsDisabled()) {
      return;
    }

    $original_data = $webform_submission->getOriginalData();
    $data = $webform_submission->getData();

    $value = isset($data[$key]) ? $data[$key] : [];
    $fids = (is_array($value)) ? $value : [$value];

    $original_value = isset($original_data[$key]) ? $original_data[$key] : [];
    $original_fids = (is_array($original_value)) ? $original_value : [$original_value];

    // Delete the old file uploads.
    $delete_fids = array_diff($original_fids, $fids);
    static::deleteFiles($webform_submission, $delete_fids);

    // Add new files.
    $this->addFiles($element, $webform_submission, $fids);
  }

  /**
   * {@inheritdoc}
   */
  public function postDelete(array &$element, WebformSubmissionInterface $webform_submission) {
    // Uploaded files are deleted via the webform submission.
    // This ensures that all files associated with a submission are deleted.
    // @see \Drupal\webform\WebformSubmissionStorage::delete
  }

  /**
   * {@inheritdoc}
   */
  public function getTestValues(array $element, WebformInterface $webform, array $options = []) {
    if ($this->isDisabled()) {
      return NULL;
    }

    // Get element or composite key.
    if (isset($element['#webform_key'])) {
      $key = $element['#webform_key'];
    }
    elseif (isset($element['#webform_composite_key'])) {
      $key = $element['#webform_composite_key'];
    }
    else {
      return NULL;
    }

    // Append delta to key.
    // @see \Drupal\webform\Plugin\WebformElement\WebformCompositeBase::getTestValues
    if (isset($options['delta'])) {
      $key .= '_' . $options['delta'];
    }

    $file_extensions = explode(' ', $this->getFileExtensions($element));
    $file_extension = $file_extensions[array_rand($file_extensions)];
    $upload_location = $this->getUploadLocation($element, $webform);
    $file_destination = $upload_location . '/' . $key . '.' . $file_extension;

    // Look for an existing temp files that have not been uploaded.
    $fids = $this->entityTypeManager->getStorage('file')->getQuery()
      ->condition('status', 0)
      ->condition('uid', $this->currentUser->id())
      ->condition('uri', $upload_location . '/' . $key . '.%', 'LIKE')
      ->execute();
    if ($fids) {
      return reset($fids);
    }

    // Copy sample file or generate a new temp file that can be uploaded.
    $sample_file = drupal_get_path('module', 'webform') . '/tests/files/sample.' . $file_extension;
    if (file_exists($sample_file)) {
      $file_uri = $this->fileSystem->copy($sample_file, $file_destination);
    }
    else {
      $file_uri = $this->fileSystem->saveData('{empty}', $file_destination);
    }

    $file = $this->entityTypeManager->getStorage('file')->create([
      'uri' => $file_uri,
      'uid' => $this->currentUser->id(),
    ]);
    $file->save();

    $fid = $file->id();
    return [$fid];
  }

  /**
   * Get max file size for an element.
   *
   * @param array $element
   *   An element.
   *
   * @return int
   *   Max file size.
   */
  protected function getMaxFileSize(array $element) {
    $max_filesize = $this->configFactory->get('webform.settings')->get('file.default_max_filesize') ?: Environment::getUploadMaxSize();
    $max_filesize = Bytes::toInt($max_filesize);
    if (!empty($element['#max_filesize'])) {
      $max_filesize = min($max_filesize, Bytes::toInt($element['#max_filesize'] . 'MB'));
    }
    return $max_filesize;
  }

  /**
   * Get the allowed file extensions for an element.
   *
   * @param array $element
   *   An element.
   *
   * @return int
   *   File extensions.
   */
  protected function getFileExtensions(array $element = NULL) {
    return (!empty($element['#file_extensions'])) ? $element['#file_extensions'] : $this->getDefaultFileExtensions();
  }

  /**
   * Get the default allowed file extensions.
   *
   * @return int
   *   File extensions.
   */
  protected function getDefaultFileExtensions() {
    $file_type = str_replace('webform_', '', $this->getPluginId());
    return $this->configFactory->get('webform.settings')->get("file.default_{$file_type}_extensions");
  }

  /**
   * Get file upload location.
   *
   * @param array $element
   *   An element.
   * @param \Drupal\webform\WebformInterface $webform
   *   A webform.
   *
   * @return string
   *   Upload location.
   */
  protected function getUploadLocation(array $element, WebformInterface $webform) {
    if (empty($element['#upload_location'])) {
      $upload_location = $this->getUriScheme($element) . '://webform/' . $webform->id() . '/_sid_';
    }
    else {
      $upload_location = $element['#upload_location'];
    }

    // Make sure the upload location exists and is writable.
    $this->fileSystem->prepareDirectory($upload_location, FileSystemInterface::CREATE_DIRECTORY | FileSystemInterface::MODIFY_PERMISSIONS);

    return $upload_location;
  }

  /**
   * Get file upload URI scheme.
   *
   * Defaults to private file uploads.
   *
   * Drupal file upload by anonymous or untrusted users into public file systems
   * -- PSA-2016-003.
   *
   * @param array $element
   *   An element.
   *
   * @return string
   *   File upload URI scheme.
   *
   * @see https://www.drupal.org/psa-2016-003
   */
  protected function getUriScheme(array $element) {
    if (isset($element['#uri_scheme'])) {
      return $element['#uri_scheme'];
    }
    $scheme_options = static::getVisibleStreamWrappers();
    if (isset($scheme_options['private'])) {
      return 'private';
    }
    elseif (isset($scheme_options['public'])) {
      return 'public';
    }
    else {
      return 'private';
    }
  }

  /**
   * Process callback  for managed file elements.
   */
  public static function processManagedFile(&$element, FormStateInterface $form_state, &$complete_form) {
    // Disable inline form errors for multiple file upload checkboxes.
    if (!empty($element['#multiple'])) {
      foreach (Element::children($element) as $key) {
        if (isset($element[$key]['selected'])) {
          $element[$key]['selected']['#error_no_message'] = TRUE;
        }
      }
    }

    // Truncate multiple files.
    // Checks if user has uploaded more files than allowed.
    // @see \Drupal\file\Plugin\Field\FieldWidget\FileWidget::validateMultipleCount
    // @see \Drupal\file\Element\ManagedFile::processManagedFile.
    if (!empty($element['#multiple'])
      && ($element['#multiple'] > 1)
      && !empty($element['#files'])
      && (count($element['#files']) > $element['#multiple'])) {

      $total_files = count($element['#files']);
      $multiple = $element['#multiple'];

      $fids = [];
      $removed_names = [];
      $count = 0;
      foreach ($element['#files'] as $delta => $file) {
        if ($count >= $multiple) {
          unset($element['file_' . $delta]);
          unset($element['#files'][$delta]);
          $removed_names[] = $file->getFilename();
          $file->delete();
        }
        else {
          $fids[] = $delta;
        }
        $count++;
      }
      $element['fids']['#value'] = $fids;
      $element['#value']['fids'] = $fids;

      $args = [
        '%title' => $element['#title'],
        '@max' => $element['#multiple'],
        '@count' => $total_files,
        '%list' => implode(', ', $removed_names),
      ];
      $message = t('%title can only hold @max values but there were @count uploaded. The following files have been omitted as a result: %list.', $args);
      \Drupal::messenger()->addWarning($message);
    }
    if (!empty($element['#multiple']) && !empty($element['#files'])
      && (count($element['#files']) === $element['#multiple'])) {
      $element['upload']['#access'] = FALSE;
      // We can't complete remove the upload button because it breaks
      // the Ajax callback. Instead, we are going visually hide it from
      // browsers with JavaScript disabled.
      $element['upload_button']['#attributes']['style'] = 'display:none';
    }

    // Preview uploaded file.
    if (!empty($element['#file_preview'])) {
      // Get the element's plugin object.
      /** @var \Drupal\webform\Plugin\WebformElementManagerInterface $element_manager */
      $element_manager = \Drupal::service('plugin.manager.webform.element');
      /** @var \Drupal\webform\Plugin\WebformElement\WebformManagedFileBase $element_plugin */
      $element_plugin = $element_manager->getElementInstance($element);

      // Get the webform submission.
      /** @var \Drupal\webform\WebformSubmissionForm $form_object */
      $form_object = $form_state->getFormObject();
      /** @var \Drupal\webform\webform_submission $webform_submission */
      $webform_submission = $form_object->getEntity();

      // Create a temporary preview element with an overridden #format.
      $preview_element = ['#format' => $element['#file_preview']] + $element;

      // Convert '#theme': file_link to a container with a file preview.
      $fids = (array) $webform_submission->getElementData($element['#webform_key']) ?: [];
      foreach ($fids as $delta => $fid) {
        $child_key = 'file_' . $fid;
        // Make sure the child element exists.
        if (!isset($element[$child_key])) {
          continue;
        }

        // Set multiple options delta.
        $options = ['delta' => $delta];

        $file = File::load((string) $fid);
        // Make sure the file entity exists.
        if (!$file) {
          continue;
        }

        // Don't allow anonymous temporary files to be previewed.
        // @see template_preprocess_file_link().
        // @see webform_preprocess_file_link().
        if ($file->isTemporary() && $file->getOwner()->isAnonymous() && strpos($file->getFileUri(), 'private://') === 0) {
          continue;
        }

        $preview = $element_plugin->previewManagedFile($preview_element, $webform_submission, $options);
        if (isset($element[$child_key]['filename'])) {
          // Single file.
          // Covert file link to a container with preview.
          unset($element[$child_key]['filename']['#theme']);
          $element[$child_key]['filename']['#type'] = 'container';
          $element[$child_key]['filename']['#attributes']['class'][] = 'webform-managed-file-preview';
          $element[$child_key]['filename']['#attributes']['class'][] = Html::getClass($element['#type'] . '-preview');
          $element[$child_key]['filename']['preview'] = $preview;
        }
        elseif (isset($element[$child_key]['selected'])) {
          // Multiple files.
          // Convert file link checkbox #title to preview.
          $element[$child_key]['selected']['#wrapper_attributes']['class'][] = 'webform-managed-file-preview-wrapper';
          $element[$child_key]['selected']['#wrapper_attributes']['class'][] = Html::getClass($element['#type'] . '-preview-wrapper');
          $element[$child_key]['selected']['#label_attributes']['class'][] = 'webform-managed-file-preview';
          $element[$child_key]['selected']['#label_attributes']['class'][] = Html::getClass($element['#type'] . '-preview');

          $element[$child_key]['selected']['#title'] = \Drupal::service('renderer')->render($preview);
        }
      }
    }

    // File placeholder.
    if (!empty($element['#file_placeholder']) && (empty($element['#value']) || empty($element['#value']['fids']))) {
      $element['file_placeholder'] = [
        '#type' => 'container',
        '#attributes' => [
          'class' => [
            'webform-managed-file-placeholder',
            Html::getClass($element['#type'] . '-placeholder'),
          ],
        ],
        // Display placeholder before file upload input.
        '#weight' => ($element['upload']['#weight'] - 1),
        'content' => WebformHtmlEditor::checkMarkup($element['#file_placeholder']),
      ];
    }

    return $element;
  }

  /**
   * Preview a managed file element upload.
   *
   * @param array $element
   *   An element.
   * @param \Drupal\webform\WebformSubmissionInterface $webform_submission
   *   A webform submission.
   * @param array $options
   *   An array of options.
   *
   * @return string|array
   *   A preview.
   */
  public function previewManagedFile(array $element, WebformSubmissionInterface $webform_submission, array $options = []) {
    $build = $this->formatHtmlItem($element, $webform_submission, $options);
    return (is_array($build)) ? $build : ['#markup' => $build];
  }

  /**
   * Form API callback. Consolidate the array of fids for this field into a single fids.
   */
  public static function validateManagedFile(array &$element, FormStateInterface $form_state, &$complete_form) {
    // Issue #3130448: Add custom #required_message support to
    // ManagedFile elements.
    // @see https://www.drupal.org/project/drupal/issues/3130448
    if (!empty($element['#required_error'])) {
      $errors = $form_state->getErrors();
      $key = $element['#webform_key'];
      if (isset($errors[$key])
        && $errors[$key] instanceof TranslatableMarkup
        && $errors[$key]->getUntranslatedString() === '@name field is required.') {
        $errors[$key]->__construct($element['#required_error']);
      }
    }

    if (!empty($element['#files'])) {
      $fids = array_keys($element['#files']);
      if (empty($element['#multiple'])) {
        $form_state->setValueForElement($element, reset($fids));
      }
      else {
        $form_state->setValueForElement($element, $fids);
      }
    }
    else {
      $form_state->setValueForElement($element, NULL);
    }
  }

  /**
   * Form API callback. Validate file upload limit.
   *
   * @see \Drupal\webform\WebformSubmissionForm::validateForm
   */
  public static function validateManagedFileLimit(array &$element, FormStateInterface $form_state, &$complete_form) {
    // Set empty files to NULL and exit.
    if (empty($element['#files'])) {
      return;
    }

    // Only validate file limits for ajax uploads.
    $wrapper_format = \Drupal::request()->get(MainContentViewSubscriber::WRAPPER_FORMAT);
    if (!$wrapper_format || !in_array($wrapper_format, ['drupal_ajax', 'drupal_modal', 'drupal_dialog'])) {
      return;
    }

    $fids = array_keys($element['#files']);

    // Get WebformSubmissionForm object.
    $form_object = $form_state->getFormObject();
    if (!($form_object instanceof WebformSubmissionForm)) {
      return;
    }

    // Skip validation when removing file upload.
    $trigger_element = $form_state->getTriggeringElement();
    $op = (string) $trigger_element['#value'];
    if (in_array($op, [(string) t('Remove'), (string) t('Remove selected')])) {
      return;
    }

    // Get file upload limit.
    /** @var \Drupal\webform\WebformSubmissionInterface $webform_submission */
    $webform_submission = $form_object->getEntity();
    $file_limit = $webform_submission->getWebform()->getSetting('form_file_limit')
      ?: \Drupal::config('webform.settings')->get('settings.default_form_file_limit')
      ?: '';
    $file_limit = Bytes::toInt($file_limit);

    // Track file size across all file upload elements.
    static $total_file_size = 0;
    /** @var \Drupal\file\FileInterface[] $files */
    $files = File::loadMultiple($fids);
    foreach ($files as $file) {
      $total_file_size += (int) $file->getSize();
    }

    // If has access and total file size exceeds file limit then display error.
    $has_access = (!isset($element['#access']) || $element['#access']);
    if ($has_access && $total_file_size > $file_limit) {
      $t_args = ['%quota' => format_size($file_limit)];
      $message = t("This form's file upload quota of %quota has been exceeded. Please remove some files.", $t_args);
      $form_state->setError($element, $message);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    // Remove unsupported inline title display.
    unset($form['form']['display_container']['title_display']['#options']['inline']);

    $form['file'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('File settings'),
    ];

    // Warn people about temporary files when saving of results is disabled.
    /** @var \Drupal\webform\WebformInterface $webform */
    $webform = $form_state->getFormObject()->getWebform();
    if ($webform->isResultsDisabled()) {
      $temporary_maximum_age = $this->configFactory->get('system.file')->get('temporary_maximum_age');
      $temporary_interval = \Drupal::service('date.formatter')->formatInterval($temporary_maximum_age);
      $form['file']['file_message'] = [
        '#type' => 'webform_message',
        '#message_message' => '<strong>' . $this->t('Saving of results is disabled.') . '</strong> ' .
          $this->t('Uploaded files will be temporarily stored on the server and referenced in the database for %interval.', ['%interval' => $temporary_interval]) . ' ' .
          $this->t('Uploaded files should be attached to an email and/or remote posted to an external server.')
        ,
        '#message_type' => 'warning',
        '#access' => TRUE,
      ];
    }

    $scheme_options = static::getVisibleStreamWrappers();
    $form['file']['uri_scheme'] = [
      '#type' => 'radios',
      '#title' => $this->t('File upload destination'),
      '#description' => $this->t('Select where the final files should be stored. Private file storage has more overhead than public files, but allows restricted access to files within this element.'),
      '#required' => TRUE,
      '#options' => $scheme_options,
    ];
    // Public files security warning.
    if (isset($scheme_options['public'])) {
      $form['file']['uri_public_warning'] = [
        '#type' => 'webform_message',
        '#message_type' => 'warning',
        '#message_message' => $this->t('Public files upload destination is dangerous for webforms that are available to anonymous and/or untrusted users.') . ' ' .
          $this->t('For more information see: <a href="https://www.drupal.org/psa-2016-003">DRUPAL-PSA-2016-003</a>'),
        '#access' => TRUE,
        '#states' => [
          'visible' => [
            ':input[name="properties[uri_scheme]"]' => ['value' => 'public'],
          ],
        ],
      ];
    }
    // Private files not set warning.
    if (!isset($scheme_options['private'])) {
      $form['file']['uri_private_warning'] = [
        '#type' => 'webform_message',
        '#message_type' => 'warning',
        '#message_message' => $this->t('Private file system is not set. This must be changed in <a href="https://www.drupal.org/documentation/modules/file">settings.php</a>. For more information see: <a href="https://www.drupal.org/psa-2016-003">DRUPAL-PSA-2016-003</a>'),
        '#access' => TRUE,
      ];
    }

    $max_filesize = \Drupal::config('webform.settings')->get('file.default_max_filesize') ?: Environment::getUploadMaxSize();
    $max_filesize = Bytes::toInt($max_filesize);
    $max_filesize = ($max_filesize / 1024 / 1024);
    $form['file']['file_help'] = [
      '#type' => 'select',
      '#title' => $this->t('File upload help display'),
      '#description' => $this->t('Determines the placement of the file upload help .'),
      '#options' => [
        '' => $this->t('Description'),
        'help' => $this->t('Help'),
        'more' => $this->t('More'),
        'none' => $this->t('None'),
      ],
    ];
    $form['file']['file_placeholder'] = [
      '#type' => 'webform_html_editor',
      '#title' => $this->t('File upload placeholder'),
      '#description' => $this->t('The placeholder will be shown before a file is uploaded.'),
    ];
    $form['file']['file_preview'] = [
      '#type' => 'select',
      '#title' => $this->t('File upload preview (Authenticated users only)'),
      '#description' => $this->t('Select how the uploaded file previewed.') . '<br/><br/>' .
          $this->t('Allowing anonymous users to preview files is dangerous.') . '<br/>' .
          $this->t('For more information see: <a href="https://www.drupal.org/psa-2016-003">DRUPAL-PSA-2016-003</a>'),
      '#options' => WebformOptionsHelper::appendValueToText($this->getItemFormats()),
      '#empty_option' => '<' . $this->t('no preview') . '>',
    ];
    $form['file']['max_filesize'] = [
      '#type' => 'number',
      '#title' => $this->t('Maximum file size'),
      '#field_suffix' => $this->t('MB (Max: @filesize MB)', ['@filesize' => $max_filesize]),
      '#placeholder' => $max_filesize,
      '#description' => $this->t('Enter the max file size a user may upload.'),
      '#min' => 1,
      '#max' => $max_filesize,
      '#step' => 'any',
    ];
    $form['file']['file_extensions'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Allowed file extensions'),
      '#description' => $this->t('Separate extensions with a space and do not include the leading dot.') . '<br/><br/>' .
        $this->t('Defaults to: %value', ['%value' => $this->getDefaultFileExtensions()]),
      '#maxlength' => 255,
    ];
    $form['file']['file_name'] = [
      '#type' => 'webform_checkbox_value',
      '#title' => $this->t('Rename files'),
      '#description' => $this->t('Rename uploaded files to this tokenized pattern. Do not include the extension here. The actual file extension will be automatically appended to this pattern.'),
      '#element' => [
        '#type' => 'textfield',
        '#title' => $this->t('File name pattern'),
        '#description' => $this->t('File names combined with their full URI can not exceed 255 characters. File names that exceed this limit will be truncated.'),
        '#maxlength' => NULL,
      ],
    ];
    $form['file']['sanitize'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Sanitize file name'),
      '#description' => $this->t('If checked, file name will be transliterated, lower-cased and all special characters converted to dashes (-).'),
      '#return_value' => TRUE,
    ];
    $t_args = [
      '%file_rename' => $form['file']['file_name']['#title'],
      '%sanitization' => $form['file']['sanitize']['#title'],
    ];
    $form['file']['file_name_warning'] = [
      '#type' => 'webform_message',
      '#message_type' => 'warning',
      '#message_message' => $this->t('For security reasons we advise to use %file_rename together with the %sanitization option.', $t_args),
      '#access' => TRUE,
      '#states' => [
        'visible' => [
          ':input[name="properties[file_name][checkbox]"]' => ['checked' => TRUE],
          ':input[name="properties[sanitize]"]' => ['checked' => FALSE],
        ],
      ],
    ];
    $form['file']['multiple'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Multiple'),
      '#description' => $this->t('Check this option if the user should be allowed to upload multiple files.'),
      '#return_value' => TRUE,
    ];

    // Button.
    // @see webform_preprocess_file_managed_file()
    $form['file']['button'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Replace file upload input with an upload button'),
      '#description' => $this->t('If checked the file upload input will be replaced with click-able label styled as button.'),
      '#return_value' => TRUE,
    ];
    $form['file']['button__title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('File upload button title'),
      '#description' => $this->t('Defaults to: %value', ['%value' => $this->t('Choose file')]),
      '#states' => [
        'visible' => [
          ':input[name="properties[button]"]' => ['checked' => TRUE],
        ],
      ],
    ];
    $form['file']['button__attributes'] = [
      '#type' => 'webform_element_attributes',
      '#title' => $this->t('File upload button'),
      '#classes' => $this->configFactory->get('webform.settings')->get('settings.button_classes'),
      '#class__description' => $this->t("Apply classes to the button. Button classes default to 'button button-primary'."),
      '#states' => [
        'visible' => [
          ':input[name="properties[button]"]' => ['checked' => TRUE],
        ],
      ],
    ];

    // Hide default value, which is not applicable for file uploads.
    $form['default']['#access'] = FALSE;

    return $form;
  }

  /**
   * Delete a webform submission file's usage and mark it as temporary.
   *
   * Marks unused webform submission files as temporary.
   * In Drupal 8.4.x+ unused webform managed files are no longer
   * marked as temporary.
   *
   * @param \Drupal\webform\WebformSubmissionInterface $webform_submission
   *   A webform submission.
   * @param null|array $fids
   *   An array of file ids. If NULL all files are deleted.
   */
  public static function deleteFiles(WebformSubmissionInterface $webform_submission, array $fids = NULL) {
    // Make sure the file.module is enabled since this method is called from
    // \Drupal\webform\WebformSubmissionStorage::delete.
    if (!\Drupal::moduleHandler()->moduleExists('file')) {
      return;
    }

    if ($fids === NULL) {
      $fids = \Drupal::database()->select('file_usage', 'fu')
        ->fields('fu', ['fid'])
        ->condition('module', 'webform')
        ->condition('type', 'webform_submission')
        ->condition('id', $webform_submission->id())
        ->execute()
        ->fetchCol();
    }

    if (empty($fids)) {
      return;
    }

    /** @var \Drupal\file\FileUsage\FileUsageInterface $file_usage */
    $file_usage = \Drupal::service('file.usage');

    $make_unused_managed_files_temporary = \Drupal::config('webform.settings')->get('file.make_unused_managed_files_temporary');
    $delete_temporary_managed_files = \Drupal::config('webform.settings')->get('file.delete_temporary_managed_files');

    /** @var \Drupal\file\FileInterface[] $files */
    $files = File::loadMultiple($fids);
    foreach ($files as $file) {
      $file_usage->delete($file, 'webform', 'webform_submission', $webform_submission->id());
      // Make unused files temporary.
      if ($make_unused_managed_files_temporary && empty($file_usage->listUsage($file)) && !$file->isTemporary()) {
        $file->setTemporary();
        $file->save();
      }

      // Immediately delete temporary files.
      // This makes sure that the webform submission uploaded directory is
      // empty and can be deleted.
      // @see \Drupal\webform\WebformSubmissionStorage::delete
      if ($delete_temporary_managed_files && $file->isTemporary()) {
        $file->delete();
      }
    }
  }

  /**
   * Add a webform submission file's usage and mark it as permanent.
   *
   * @param array $element
   *   An element.
   * @param \Drupal\webform\WebformSubmissionInterface $webform_submission
   *   A webform submission.
   * @param array $fids
   *   An array of file ids.
   */
  public function addFiles(array $element, WebformSubmissionInterface $webform_submission, array $fids) {
    // Make sure the file.module is enabled since this method is called from
    // \Drupal\webform\Plugin\WebformElement\WebformCompositeBase::postSave.
    if (!\Drupal::moduleHandler()->moduleExists('file')) {
      return;
    }
    // Make sure there are files that need to added.
    if (empty($fids)) {
      return;
    }

    /** @var \Drupal\file\FileInterface[] $files */
    $files = $this->entityTypeManager->getStorage('file')->loadMultiple($fids);
    foreach ($files as $file) {
      $source_uri = $file->getFileUri();
      $destination_uri = $this->getFileDestinationUri($element, $file, $webform_submission);

      // Save file if there is a new destination URI.
      if ($source_uri != $destination_uri) {
        $destination_uri = $this->fileSystem->move($source_uri, $destination_uri);
        $file->setFileUri($destination_uri);
        $file->setFileName($this->fileSystem->basename($destination_uri));
        $file->save();
      }

      // Update file usage table.
      // Setting file usage will also make the file's status permanent.
      $this->fileUsage->delete($file, 'webform', 'webform_submission', $webform_submission->id());
      $this->fileUsage->add($file, 'webform', 'webform_submission', $webform_submission->id());
    }
  }

  /**
   * Determine the destination URI where to save an uploaded file.
   *
   * @param array $element
   *   Element whose destination URI is requested.
   * @param \Drupal\file\FileInterface $file
   *   File whose destination URI is requested.
   * @param \Drupal\webform\WebformSubmissionInterface $webform_submission
   *   Webform submission that contains the file whose destination URI is
   *   requested.
   *
   * @return string
   *   Destination URI under which the file should be saved.
   */
  protected function getFileDestinationUri(array $element, FileInterface $file, WebformSubmissionInterface $webform_submission) {
    $destination_folder = $this->fileSystem->dirname($file->getFileUri());
    $destination_filename = $file->getFilename();
    $destination_extension = pathinfo($destination_filename, PATHINFO_EXTENSION);
    $destination_basename = substr(pathinfo($destination_filename, PATHINFO_BASENAME), 0, -strlen(".$destination_extension"));

    // Replace /_sid_/ token with the submission id.
    if (strpos($destination_folder, '/_sid_')) {
      $destination_folder = str_replace('/_sid_', '/' . $webform_submission->id(), $destination_folder);
      $this->fileSystem->prepareDirectory($destination_folder, FileSystemInterface::CREATE_DIRECTORY | FileSystemInterface::MODIFY_PERMISSIONS);
    }

    // Replace tokens in file name.
    if (isset($element['#file_name']) && $element['#file_name']) {
      $destination_basename = $this->tokenManager->replace($element['#file_name'], $webform_submission);
    }

    // Sanitize filename.
    // @see http://stackoverflow.com/questions/2021624/string-sanitizer-for-filename
    // @see \Drupal\webform_attachment\Element\WebformAttachmentBase::getFileName
    if (!empty($element['#sanitize'])) {
      $destination_extension = mb_strtolower($destination_extension);

      $destination_basename = mb_strtolower($destination_basename);
      $destination_basename = $this->transliteration->transliterate($destination_basename, $this->languageManager->getCurrentLanguage()->getId(), '-');
      $destination_basename = preg_replace('([^\w\s\d\-_~,;:\[\]\(\].]|[\.]{2,})', '', $destination_basename);
      $destination_basename = preg_replace('/\s+/', '-', $destination_basename);
      $destination_basename = trim($destination_basename, '-');

      // If the basename is empty use the element's key, composite key, or type.
      if (empty($destination_basename)) {
        if (isset($element['#webform_key'])) {
          $destination_basename = $element['#webform_key'];
        }
        elseif (isset($element['#webform_composite_key'])) {
          $destination_basename = $element['#webform_composite_key'];
        }
        else {
          $destination_basename = $element['#type'];
        }
      }
    }

    // Make sure $destination_uri does not exceed 250 + _01 character limit for
    // the 'file_managed' table uri column.
    // @see file_validate_name_length()
    // @see https://drupal.stackexchange.com/questions/36760/overcoming-255-character-uri-limit-for-files-managed
    $filename_maxlength = 250;
    // Subtract the destination's folder length.
    $filename_maxlength -= mb_strlen($destination_folder);
    // Subtract the destination's extension length.
    $filename_maxlength -= mb_strlen($destination_extension);
    // Subtract the directory's forward slash and the extension's period.
    $filename_maxlength -= 2;
    // Truncate the base name.
    $destination_basename = mb_strimwidth($destination_basename, 0, $filename_maxlength);

    return $destination_folder . '/' . $destination_basename . '.' . $destination_extension;
  }

  /**
   * Control access to webform submission private file downloads.
   *
   * @param string $uri
   *   The URI of the file.
   *
   * @return mixed
   *   Returns NULL if the file is not attached to a webform submission.
   *   Returns -1 if the user does not have permission to access a webform.
   *   Returns an associative array of headers.
   *
   * @see hook_file_download()
   * @see webform_file_download()
   */
  public static function accessFileDownload($uri) {
    $files = \Drupal::entityTypeManager()
      ->getStorage('file')
      ->loadByProperties(['uri' => $uri]);
    if (empty($files)) {
      return NULL;
    }

    /** @var \Drupal\file\FileInterface $file */
    $file = reset($files);
    if (empty($file)) {
      return NULL;
    }

    /** @var \Drupal\file\FileUsage\FileUsageInterface $file_usage */
    $file_usage = \Drupal::service('file.usage');
    $usage = $file_usage->listUsage($file);
    foreach ($usage as $module => $entity_types) {
      // Check for Webform module.
      if ($module != 'webform') {
        continue;
      }

      foreach ($entity_types as $entity_type => $counts) {
        $entity_ids = array_keys($counts);

        // Check for webform submission entity type.
        if ($entity_type != 'webform_submission' || empty($entity_ids)) {
          continue;
        }

        // Get webform submission.
        $webform_submission = WebformSubmission::load(reset($entity_ids));
        if (!$webform_submission) {
          continue;
        }

        // Check webform submission view access.
        if (!$webform_submission->access('view')) {
          return -1;
        }

        // Return file content headers.
        $headers = file_get_content_headers($file);

        /** @var \Drupal\Core\File\FileSystemInterface $file_system */
        $file_system = \Drupal::service('file_system');
        $filename = $file_system->basename($uri);
        // Force blacklisted files to be downloaded instead of opening in the browser.
        if (in_array($headers['Content-Type'], static::$blacklistedMimeTypes)) {
          $headers['Content-Disposition'] = 'attachment; filename="' . Unicode::mimeHeaderEncode($filename) . '"';
        }
        else {
          $headers['Content-Disposition'] = 'inline; filename="' . Unicode::mimeHeaderEncode($filename) . '"';
        }

        return $headers;
      }
    }
    return NULL;
  }

  /**
   * Get visible stream wrappers.
   *
   * @return array
   *   An associative array of visible stream wrappers keyed by type.
   */
  public static function getVisibleStreamWrappers() {
    $stream_wrappers = \Drupal::service('stream_wrapper_manager')->getNames(StreamWrapperInterface::WRITE_VISIBLE);
    if (!\Drupal::config('webform.settings')->get('file.file_public')) {
      unset($stream_wrappers['public']);
    }
    return $stream_wrappers;
  }

  /**
   * {@inheritdoc}
   */
  public function getTargetType(array $element) {
    return 'file';
  }

  /**
   * {@inheritdoc}
   */
  public function getTargetEntity(array $element, WebformSubmissionInterface $webform_submission, array $options = []) {
    $value = $this->getValue($element, $webform_submission, $options);
    if (empty($value)) {
      return NULL;
    }
    return $this->getFile($element, $value, $options);
  }

  /**
   * {@inheritdoc}
   */
  public function getTargetEntities(array $element, WebformSubmissionInterface $webform_submission, array $options = []) {
    $value = $this->getValue($element, $webform_submission, $options);
    if (empty($value)) {
      return NULL;
    }
    return $this->getFiles($element, $value, $options);
  }

  /**
   * {@inheritdoc}
   */
  public function getAttachments(array $element, WebformSubmissionInterface $webform_submission, array $options = []) {
    $attachments = [];
    $files = $this->getTargetEntities($element, $webform_submission, $options) ?: [];
    foreach ($files as $file) {
      $attachments[] = [
        'filecontent' => file_get_contents($file->getFileUri()),
        'filename' => $file->getFilename(),
        'filemime' => $file->getMimeType(),
        // File URIs that are not supportted return FALSE, when this happens
        // still use the file's URI as the file's path.
        'filepath' => \Drupal::service('file_system')->realpath($file->getFileUri()) ?: $file->getFileUri(),
        // URI is used when debugging or resending messages.
        // @see \Drupal\webform\Plugin\WebformHandler\EmailWebformHandler::buildAttachments
        '_fileurl' => file_create_url($file->getFileUri()),
      ];
    }
    return $attachments;
  }

}
