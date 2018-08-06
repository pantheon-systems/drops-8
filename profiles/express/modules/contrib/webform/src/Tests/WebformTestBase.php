<?php

namespace Drupal\webform\Tests;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Component\Utility\Unicode;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Serialization\Yaml;
use Drupal\filter\Entity\FilterFormat;
use Drupal\node\NodeInterface;
use Drupal\simpletest\WebTestBase;
use Drupal\taxonomy\Entity\Term;
use Drupal\taxonomy\Entity\Vocabulary;
use Drupal\user\Entity\Role;
use Drupal\webform\WebformInterface;
use Drupal\webform\Entity\Webform;
use Drupal\webform\Entity\WebformSubmission;

/**
 * Defines an abstract test base for webform tests.
 */
abstract class WebformTestBase extends WebTestBase {

  use WebformTestTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['webform'];

  /**
   * Webforms to load.
   *
   * @var array
   */
  protected static $testWebforms = [];

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    $this->loadWebforms(static::$testWebforms);
  }

  /**
   * {@inheritdoc}
   */
  public function tearDown() {
    $this->purgeSubmissions();
    parent::tearDown();
  }

  /****************************************************************************/
  // User.
  /****************************************************************************/

  /**
   * A normal user to submit webforms.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $normalUser;

  /**
   * A webform administrator.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminWebformUser;

  /**
   * A webform submission administrator.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminSubmissionUser;

  /**
   * A webform own access.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $ownWebformUser;

  /**
   * A webform any access.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $anyWebformUser;

  /**
   * A webform submission own access.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $ownWebformSubmissionUser;

  /**
   * A webform submission any access.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $anyWebformSubmissionUser;

  /**
   * Create webform test users.
   */
  protected function createUsers() {
    // Default user permissions.
    $default_user_permissions = [];
    $default_user_permissions[] = 'access user profiles';
    if (in_array('webform_node', static::$modules)) {
      $default_user_permissions[] = 'access content';
    }

    // Normal user.
    $normal_user_permissions = $default_user_permissions;
    $this->normalUser = $this->drupalCreateUser($normal_user_permissions);

    // Admin webform user.
    $admin_form_user_permissions = array_merge($default_user_permissions, [
      'access site reports',
      'administer site configuration',
      'administer webform',
      'access webform submission log',
      'create webform',
      'administer users',
    ]);
    if (in_array('block', static::$modules)) {
      $admin_form_user_permissions[] = 'administer blocks';
    }
    if (in_array('webform_node', static::$modules)) {
      $admin_form_user_permissions[] = 'administer nodes';
    }
    if (in_array('webform_test_translation', static::$modules)) {
      $admin_form_user_permissions[] = 'translate configuration';
    }
    $this->adminWebformUser = $this->drupalCreateUser($admin_form_user_permissions);

    // Own webform user.
    $this->ownWebformUser = $this->drupalCreateUser(array_merge($default_user_permissions, [
      'access webform overview',
      'create webform',
      'edit own webform',
      'delete own webform',
      'view own webform submission',
      'edit own webform submission',
      'delete own webform submission',
    ]));

    // Any webform user.
    $this->anyWebformUser = $this->drupalCreateUser(array_merge($default_user_permissions, [
      'access webform overview',
      'create webform',
      'edit any webform',
      'delete any webform',
    ]));

    // Own webform submission user.
    $this->ownWebformSubmissionUser = $this->drupalCreateUser(array_merge($default_user_permissions, [
      'view own webform submission',
      'edit own webform submission',
      'delete own webform submission',
    ]));

    // Any webform submission user.
    $this->anyWebformSubmissionUser = $this->drupalCreateUser(array_merge($default_user_permissions, [
      'view any webform submission',
      'edit any webform submission',
      'delete any webform submission',
    ]));

    // Admin submission user.
    $this->adminSubmissionUser = $this->drupalCreateUser(array_merge($default_user_permissions, [
      'access webform submission log',
      'administer webform submission',
    ]));
  }

  /**
   * Add webform submission own permissions to anonymous role.
   */
  protected function addWebformSubmissionOwnPermissionsToAnonymous() {
    /** @var \Drupal\user\RoleInterface $anonymous_role */
    $anonymous_role = Role::load('anonymous');
    $anonymous_role->grantPermission('view own webform submission')
      ->grantPermission('edit own webform submission')
      ->grantPermission('delete own webform submission')
      ->save();
  }

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
    $this->drupalPostForm('webform/' . $webform->id(), $edit, $submit, $options);
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
   *
   * @return int
   *   The created test submission's sid.
   */
  protected function postSubmissionTest(WebformInterface $webform, array $edit = [], $submit = NULL) {
    $submit = $this->getWebformSubmitButtonLabel($webform, $submit);
    $this->drupalPostForm('webform/' . $webform->id() . '/test', $edit, $submit);
    return $this->getLastSubmissionId($webform);
  }

  /****************************************************************************/
  // Log.
  /****************************************************************************/

  /**
   * Get the last submission id.
   *
   * @return int
   *   The last submission id.
   */
  protected function getLastSubmissionLog() {
    $query = \Drupal::database()->select('webform_submission_log', 'l');
    $query->leftJoin('webform_submission', 'ws', 'l.sid = ws.sid');
    $query->fields('l', [
      'lid',
      'uid',
      'sid',
      'handler_id',
      'operation',
      'message',
      'timestamp',
    ]);
    $query->fields('ws', [
      'webform_id',
      'entity_type',
      'entity_id',
    ]);
    $query->orderBy('l.lid', 'DESC');
    $query->range(0, 1);
    return $query->execute()->fetch();
  }

  /**
   * Get the entire submission log.
   *
   * @return int
   *   The last submission id.
   */
  protected function getSubmissionLog() {
    $query = \Drupal::database()->select('webform_submission_log', 'l');
    $query->leftJoin('webform_submission', 'ws', 'l.sid = ws.sid');
    $query->fields('l', [
      'lid',
      'uid',
      'sid',
      'handler_id',
      'operation',
      'message',
      'timestamp',
    ]);
    $query->fields('ws', [
      'webform_id',
      'entity_type',
      'entity_id',
    ]);
    $query->orderBy('l.lid', 'DESC');
    return $query->execute()->fetchAll();
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
    $this->drupalGet('admin/structure/webform/manage/' . $webform->id() . '/results/download', ['query' => $options]);
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
    $sent_emails = $this->drupalGetMails();
    $sent_email = end($sent_emails);
    $this->debug($sent_email);
    return $sent_email;
  }

  /****************************************************************************/
  // Assert.
  /****************************************************************************/

  /**
   * Passes if the substring is contained within text, fails otherwise.
   */
  protected function assertContains($haystack, $needle, $message = '', $group = 'Other') {
    if (!$message) {
      $t_args = [
        '@haystack' => Unicode::truncate($haystack, 150, TRUE, TRUE),
        '@needle' => $needle,
      ];
      $message = new FormattableMarkup('"@needle" found', $t_args);
    }
    $result = (strpos($haystack, $needle) !== FALSE);
    if (!$result) {
      $this->verbose($haystack);
    }
    return $this->assert($result, $message, $group);
  }

  /**
   * Passes if the substring is not contained within text, fails otherwise.
   */
  protected function assertNotContains($haystack, $needle, $message = '', $group = 'Other') {
    if (!$message) {
      $t_args = [
        '@haystack' => Unicode::truncate($haystack, 150, TRUE, TRUE),
        '@needle' => $needle,
      ];

      $message = new FormattableMarkup('"@needle" not found', $t_args);
    }
    $result = (strpos($haystack, $needle) === FALSE);
    if (!$result) {
      $this->verbose($haystack);
    }
    return $this->assert($result, $message, $group);
  }

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
    $this->verbose('<pre>' . htmlentities($string) . '</pre>');
  }

}
