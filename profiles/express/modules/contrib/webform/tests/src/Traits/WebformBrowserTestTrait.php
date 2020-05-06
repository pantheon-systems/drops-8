<?php

namespace Drupal\Tests\webform\Traits;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Config\FileStorage;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Serialization\Yaml;
use Drupal\filter\Entity\FilterFormat;
use Drupal\taxonomy\Entity\Term;
use Drupal\taxonomy\Entity\Vocabulary;
use Drupal\webform\WebformInterface;
use Drupal\webform\Entity\Webform;

/**
 * Provides convenience methods for webform assertions in browser tests.
 */
trait WebformBrowserTestTrait {

  /****************************************************************************/
  // Block.
  /****************************************************************************/

  /**
   * Place breadcrumb page, tasks, and actions.
   */
  protected function placeBlocks() {
    $this->drupalPlaceBlock('system_breadcrumb_block');
    $this->drupalPlaceBlock('page_title_block');
    $this->drupalPlaceBlock('local_tasks_block');
    $this->drupalPlaceBlock('local_actions_block');
  }

  /**
   * Place webform test module blocks.
   *
   * @param string $module_name
   *   Test module name.
   */
  protected function placeWebformBlocks($module_name) {
    $config_directory = drupal_get_path('module', 'webform') . '/tests/modules/' . $module_name . '/config';
    $config_files = file_scan_directory($config_directory, '/block\..*/');
    foreach ($config_files as $config_file) {
      $data = Yaml::decode(file_get_contents($config_file->uri));
      $plugin_id = $data['plugin'];
      $settings = $data['settings'];
      unset($settings['id']);
      $this->drupalPlaceBlock($plugin_id, $settings);
    }
  }

  /****************************************************************************/
  // Filter.
  /****************************************************************************/

  /**
   * Basic HTML filter format.
   *
   * @var \Drupal\filter\FilterFormatInterface
   */
  protected $basicHtmlFilter;

  /**
   * Full HTML filter format.
   *
   * @var \Drupal\filter\FilterFormatInterface
   */
  protected $fullHtmlFilter;

  /**
   * Create basic HTML filter format.
   */
  protected function createFilters() {
    $this->basicHtmlFilter = FilterFormat::create([
      'format' => 'basic_html',
      'name' => 'Basic HTML',
      'filters' => [
        'filter_html' => [
          'status' => 1,
          'settings' => [
            'allowed_html' => '<p> <br> <strong> <a> <em>',
          ],
        ],
      ],
    ]);
    $this->basicHtmlFilter->save();

    $this->fullHtmlFilter = FilterFormat::create([
      'format' => 'full_html',
      'name' => 'Full HTML',
    ]);
    $this->fullHtmlFilter->save();
  }

  /****************************************************************************/
  // Taxonomy.
  /****************************************************************************/

  /**
   * Create the 'tags' taxonomy vocabulary.
   */
  protected function createTags() {
    $vocabulary = Vocabulary::create([
      'name' => 'Tags',
      'vid' => 'tags',
      'langcode' => LanguageInterface::LANGCODE_NOT_SPECIFIED,
    ]);
    $vocabulary->save();
    for ($i = 1; $i <= 3; $i++) {
      $parent_term = Term::create([
        'name' => "Parent $i",
        'vid' => 'tags',
        'langcode' => LanguageInterface::LANGCODE_NOT_SPECIFIED,
      ]);
      $parent_term->save();
      for ($x = 1; $x <= 3; $x++) {
        $child_term = Term::create([
          'name' => "Parent $i: Child $x",
          'parent' => $parent_term->id(),
          'vid' => 'tags',
          'langcode' => LanguageInterface::LANGCODE_NOT_SPECIFIED,
        ]);
        $child_term->save();
      }
    }
  }

  /****************************************************************************/
  // Webform.
  /****************************************************************************/

  /**
   * Lazy load a test webforms.
   *
   * @param array $ids
   *   Webform ids.
   */
  protected function loadWebforms(array $ids) {
    foreach ($ids as $id) {
      $this->loadWebform($id);
    }
    $this->pass(new FormattableMarkup('Loaded webforms: %webforms.', [
      '%webforms' => implode(', ', $ids),
    ]));
  }

  /**
   * Lazy load a test webform.
   *
   * @param string $id
   *   Webform id.
   *
   * @return \Drupal\webform\WebformInterface|null
   *   A webform.
   *
   * @see \Drupal\views\Tests\ViewTestData::createTestViews
   */
  protected function loadWebform($id) {
    $storage = \Drupal::entityTypeManager()->getStorage('webform');
    if ($webform = $storage->load($id)) {
      return $webform;
    }
    else {
      $config_name = 'webform.webform.' . $id;
      if (strpos($id, 'test_') === 0) {
        $config_directory = drupal_get_path('module', 'webform') . '/tests/modules/webform_test/config/install';
      }
      elseif (strpos($id, 'example_') === 0) {
        $config_directory = drupal_get_path('module', 'webform') . '/modules/webform_examples/config/install';
      }
      elseif (strpos($id, 'template_') === 0) {
        $config_directory = drupal_get_path('module', 'webform') . '/modules/webform_templates/config/install';
      }
      else {
        throw new \Exception("Webform $id not valid");
      }

      if (!file_exists("$config_directory/$config_name.yml")) {
        throw new \Exception("Webform $id does not exist in $config_directory");
      }

      $file_storage = new FileStorage($config_directory);
      $values = $file_storage->read($config_name);
      $webform = $storage->create($values);
      $webform->save();
      return $webform;
    }
  }

  /**
   * Create a webform.
   *
   * @param array|null $values
   *   (optional) Array of values.
   * @param array|null $elements
   *   (optional) Array of elements.
   * @param array $settings
   *   (optional) Webform settings.
   *
   * @return \Drupal\webform\WebformInterface
   *   A webform.
   */
  protected function createWebform($values = [], array $elements = [], array $settings = []) {
    // Create new webform.
    $id = $this->randomMachineName(8);
    $webform = Webform::create($values + [
      'langcode' => 'en',
      'status' => WebformInterface::STATUS_OPEN,
      'id' => $id,
      'title' => $id,
      'elements' => Yaml::encode($elements),
      'settings' => $settings + Webform::getDefaultSettings(),
    ]);
    $webform->save();
    return $webform;
  }

  /**
   * Reload a test webform.
   *
   * @param string $id
   *   Webform id.
   *
   * @return \Drupal\webform\WebformInterface|null
   *   A webform.
   */
  protected function reloadWebform($id) {
    $storage = \Drupal::entityTypeManager()->getStorage('webform');
    $storage->resetCache([$id]);
    return $storage->load($id);
  }

  /****************************************************************************/
  // Submission.
  /****************************************************************************/

  /**
   * Post a new submission to a webform.
   *
   * @param \Drupal\webform\WebformInterface $webform
   *   A webform.
   * @param array $edit
   *   Submission values.
   * @param string $submit
   *   Value of the submit button whose click is to be emulated.
   * @param array $options
   *   Options to be forwarded to the url generator.
   *
   * @return int
   *   The created submission's sid.
   */
  protected function postSubmission(WebformInterface $webform, array $edit = [], $submit = NULL, array $options = []) {
    $submit = $this->getWebformSubmitButtonLabel($webform, $submit);
    $this->drupalPostForm('/webform/' . $webform->id(), $edit, $submit, $options);
    return $this->getLastSubmissionId($webform);
  }

  /**
   * Post a new test submission to a webform.
   *
   * @param \Drupal\webform\WebformInterface $webform
   *   A webform.
   * @param array $edit
   *   Submission values.
   * @param string $submit
   *   Value of the submit button whose click is to be emulated.
   * @param array $options
   *   Options to be forwarded to the url generator.
   *
   * @return int
   *   The created test submission's sid.
   */
  protected function postSubmissionTest(WebformInterface $webform, array $edit = [], $submit = NULL, array $options = []) {
    $submit = $this->getWebformSubmitButtonLabel($webform, $submit);
    $this->drupalPostForm('/webform/' . $webform->id() . '/test', $edit, $submit, $options);
    return $this->getLastSubmissionId($webform);
  }

  /****************************************************************************/
  // Submission.
  /****************************************************************************/

  /**
   * Load the specified webform submission from the storage.
   *
   * @param int $sid
   *   The submission identifier.
   *
   * @return \Drupal\webform\WebformSubmissionInterface
   *   The loaded webform submission.
   */
  protected function loadSubmission($sid) {
    /** @var \Drupal\webform\WebformSubmissionStorage $storage */
    $storage = $this->container->get('entity_type.manager')->getStorage('webform_submission');
    $storage->resetCache([$sid]);
    return $storage->load($sid);
  }

  /**
   * Purge all submission before the webform.module is uninstalled.
   */
  protected function purgeSubmissions() {
    \Drupal::database()->query('DELETE FROM {webform_submission}');
  }

  /**
   * Get a webform's submit button label.
   *
   * @param \Drupal\webform\WebformInterface $webform
   *   A webform.
   * @param string $submit
   *   Value of the submit button whose click is to be emulated.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup|string
   *   The webform's submit button label.
   */
  protected function getWebformSubmitButtonLabel(WebformInterface $webform, $submit = NULL) {
    if ($submit) {
      return $submit;
    }

    $actions_element = $webform->getElement('actions');
    if ($actions_element && isset($actions_element['#submit__label'])) {
      return $actions_element['#submit__label'];
    }

    return t('Submit');
  }

  /**
   * Get the last submission id.
   *
   * @param \Drupal\webform\WebformInterface $webform
   *   A webform.
   *
   * @return int|null
   *   The last submission id. NULL if saving of results is disabled.
   */
  protected function getLastSubmissionId(WebformInterface $webform) {
    if ($webform->getSetting('results_disabled')) {
      return NULL;
    }

    // Get submission sid.
    $url = UrlHelper::parse($this->getUrl());
    if (isset($url['query']['sid'])) {
      return $url['query']['sid'];
    }
    else {
      $entity_ids = $this->container->get('entity_type.manager')->getStorage('webform_submission')->getQuery()
        ->sort('sid', 'DESC')
        ->condition('webform_id', $webform->id())
        ->accessCheck(FALSE)
        ->execute();
      return reset($entity_ids);
    }
  }

  /****************************************************************************/
  // Export.
  /****************************************************************************/

  /**
   * Request a webform results export CSV.
   *
   * @param \Drupal\webform\WebformInterface $webform
   *   A webform.
   * @param array $options
   *   An associative array of export options.
   */
  protected function getExport(WebformInterface $webform, array $options = []) {
    /** @var \Drupal\webform\WebformSubmissionExporterInterface $exporter */
    $exporter = \Drupal::service('webform_submission.exporter');
    $options += $exporter->getDefaultExportOptions();
    $this->drupalGet('/admin/structure/webform/manage/' . $webform->id() . '/results/download', ['query' => $options]);
  }

  /**
   * Get webform export columns.
   *
   * @param \Drupal\webform\WebformInterface $webform
   *   A webform.
   *
   * @return array
   *   An array of exportable columns.
   */
  protected function getExportColumns(WebformInterface $webform) {
    /** @var \Drupal\webform\WebformSubmissionStorageInterface $submission_storage */
    $submission_storage = \Drupal::entityTypeManager()->getStorage('webform_submission');
    $field_definitions = $submission_storage->getFieldDefinitions();
    $field_definitions = $submission_storage->checkFieldDefinitionAccess($webform, $field_definitions);
    $elements = $webform->getElementsInitializedAndFlattened();
    $columns = array_merge(array_keys($field_definitions), array_keys($elements));
    return array_combine($columns, $columns);
  }

  /****************************************************************************/
  // Email.
  /****************************************************************************/

  /**
   * Gets that last email sent during the currently running test case.
   *
   * @return array
   *   An array containing the last email message captured during the
   *   current test.
   */
  protected function getLastEmail() {
    $sent_emails = $this->getMails();
    $sent_email = end($sent_emails);
    $this->debug($sent_email);
    return $sent_email;
  }

  /****************************************************************************/
  // Assert.
  /****************************************************************************/

  /**
   * Passes if the CSS selector IS found on the loaded page, fail otherwise.
   */
  protected function assertCssSelect($selector, $message = '') {
    $element = $this->cssSelect($selector);
    if (!$message) {
      $message = new FormattableMarkup('Found @selector', ['@selector' => $selector]);
    }
    $this->assertTrue(!empty($element), $message);
  }

  /**
   * Passes if the CSS selector IS NOT found on the loaded page, fail otherwise.
   */
  protected function assertNoCssSelect($selector, $message = '') {
    $element = $this->cssSelect($selector);
    $this->assertTrue(empty($element), $message);
  }

  /****************************************************************************/
  // Debug.
  /****************************************************************************/

  /**
   * Logs verbose (debug) message in a text file.
   *
   * @param mixed $data
   *   Data to be output.
   */
  protected function debug($data) {
    $string = var_export($data, TRUE);
    $string = preg_replace('/=>\s*array\s*\(/', '=> array(', $string);
    $this->htmlOutput('<pre>' . htmlentities($string) . '</pre>');
  }

}
