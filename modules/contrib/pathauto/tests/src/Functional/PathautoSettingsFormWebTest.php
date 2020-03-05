<?php

namespace Drupal\Tests\pathauto\Functional;

use Drupal\pathauto\PathautoGeneratorInterface;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests pathauto settings form.
 *
 * @group pathauto
 */
class PathautoSettingsFormWebTest extends BrowserTestBase {

  use PathautoTestHelperTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['node', 'pathauto'];

  /**
   * Admin user.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  /**
   * Form values that are set by default.
   *
   * @var array
   */
  protected $defaultFormValues = [
    'verbose' => FALSE,
    'separator' => '-',
    'case' => '1',
    'max_length' => '100',
    'max_component_length' => '100',
    'update_action' => '2',
    'transliterate' => '1',
    'reduce_ascii' => FALSE,
    'ignore_words' => 'a, an, as, at, before, but, by, for, from, is, in, into, like, of, off, on, onto, per, since, than, the, this, that, to, up, via, with',
  ];

  /**
   * Punctuation form items with default values.
   *
   * @var array
   */
  protected $defaultPunctuations = [
    'punctuation[double_quotes]' => '0',
    'punctuation[quotes]' => '0',
    'punctuation[backtick]' => '0',
    'punctuation[comma]' => '0',
    'punctuation[period]' => '0',
    'punctuation[hyphen]' => '1',
    'punctuation[underscore]' => '0',
    'punctuation[colon]' => '0',
    'punctuation[semicolon]' => '0',
    'punctuation[pipe]' => '0',
    'punctuation[left_curly]' => '0',
    'punctuation[left_square]' => '0',
    'punctuation[right_curly]' => '0',
    'punctuation[right_square]' => '0',
    'punctuation[plus]' => '0',
    'punctuation[equal]' => '0',
    'punctuation[asterisk]' => '0',
    'punctuation[ampersand]' => '0',
    'punctuation[percent]' => '0',
    'punctuation[caret]' => '0',
    'punctuation[dollar]' => '0',
    'punctuation[hash]' => '0',
    'punctuation[exclamation]' => '0',
    'punctuation[tilde]' => '0',
    'punctuation[left_parenthesis]' => '0',
    'punctuation[right_parenthesis]' => '0',
    'punctuation[question_mark]' => '0',
    'punctuation[less_than]' => '0',
    'punctuation[greater_than]' => '0',
    'punctuation[slash]' => '0',
    'punctuation[back_slash]' => '0',
  ];

  /**
   * {@inheritdoc}
   */
  function setUp() {
    parent::setUp();

    $this->drupalCreateContentType(['type' => 'article']);

    $permissions = [
      'administer pathauto',
      'notify of path changes',
      'administer url aliases',
      'create url aliases',
      'bypass node access',
    ];
    $this->adminUser = $this->drupalCreateUser($permissions);
    $this->drupalLogin($this->adminUser);
    $this->createPattern('node', '/content/[node:title]');
  }

  /**
   * Test if the default values are shown correctly in the form.
   */
  function testDefaultFormValues() {
    $this->drupalGet('/admin/config/search/path/settings');
    $this->assertNoFieldChecked('edit-verbose');
    $this->assertField('edit-separator', $this->defaultFormValues['separator']);
    $this->assertFieldChecked('edit-case');
    $this->assertField('edit-max-length', $this->defaultFormValues['max_length']);
    $this->assertField('edit-max-component-length', $this->defaultFormValues['max_component_length']);
    $this->assertFieldChecked('edit-update-action-2');
    $this->assertFieldChecked('edit-transliterate');
    $this->assertNoFieldChecked('edit-reduce-ascii');
    $this->assertField('edit-ignore-words', $this->defaultFormValues['ignore_words']);
  }

  /**
   * Test the verbose option.
   */
  function testVerboseOption() {
    $edit = ['verbose' => '1'];
    $this->drupalPostForm('/admin/config/search/path/settings', $edit, t('Save configuration'));
    $this->assertText(t('The configuration options have been saved.'));
    $this->assertFieldChecked('edit-verbose');

    $title = 'Verbose settings test';
    $this->drupalGet('/node/add/article');
    $this->assertFieldChecked('edit-path-0-pathauto');
    $this->drupalPostForm(NULL, ['title[0][value]' => $title], t('Save'));
    $this->assertText('Created new alias /content/verbose-settings-test for');

    $node = $this->drupalGetNodeByTitle($title);
    $this->drupalPostForm('/node/' . $node->id() . '/edit', ['title[0][value]' => 'Updated title'], t('Save'));
    $this->assertText('Created new alias /content/updated-title for');
    $this->assertText('replacing /content/verbose-settings-test.');
  }

  /**
   * Tests generating aliases with different settings.
   */
  function testSettingsForm() {
    // Ensure the separator settings apply correctly.
    $this->checkAlias('My awesome content', '/content/my.awesome.content', ['separator' => '.']);

    // Ensure the character case setting works correctly.
    // Leave case the same as source token values.
    $this->checkAlias('My awesome Content', '/content/My-awesome-Content', ['case' => FALSE]);
    $this->checkAlias('Change Lower', '/content/change-lower', ['case' => '1']);

    // Ensure the maximum alias length is working.
    $this->checkAlias('My awesome Content', '/content/my-awesome', ['max_length' => '23']);

    // Ensure the maximum component length is working.
    $this->checkAlias('My awesome Content', '/content/my', ['max_component_length' => '2']);

    // Ensure transliteration option is working.
    $this->checkAlias('è é àl ö äl ü', '/content/e-e-al-o-al-u', ['transliterate' => '1']);
    $this->checkAlias('è é àl äl ö ü', '/content/è-é-àl-äl-ö-ü', ['transliterate' => FALSE]);

    $ignore_words = 'a, new, very, should';
    $this->checkAlias('a very new alias to test', '/content/alias-to-test', ['ignore_words' => $ignore_words]);
  }

  /**
   * Test the punctuation setting form items.
   */
  function testPunctuationSettings() {
    // Test the replacement of punctuations.
    $settings = [];
    foreach ($this->defaultPunctuations as $key => $punctuation) {
      $settings[$key] = PathautoGeneratorInterface::PUNCTUATION_REPLACE;
    }

    $title = 'aa"b`c,d.e-f_g:h;i|j{k[l}m]n+o=p*q%r^s$t#u!v~w(x)y?z>1/2\3';
    $alias = '/content/aa-b-c-d-e-f-g-h-i-j-k-l-m-n-o-p-q-r-s-t-u-v-w-x-y-z-1-2-3';
    $this->checkAlias($title, $alias, $settings);

    // Test the removal of punctuations.
    $settings = [];
    foreach ($this->defaultPunctuations as $key => $punctuation) {
      $settings[$key] = PathautoGeneratorInterface::PUNCTUATION_REMOVE;
    }

    $title = 'a"b`c,d.e-f_g:h;i|j{k[l}m]n+o=p*q%r^s$t#u!v~w(x)y?z>1/2\3';
    $alias = '/content/abcdefghijklmnopqrstuvwxyz123';
    $this->checkAlias($title, $alias, $settings);

    // Keep all punctuations in alias.
    $settings = [];
    foreach ($this->defaultPunctuations as $key => $punctuation) {
      $settings[$key] = PathautoGeneratorInterface::PUNCTUATION_DO_NOTHING;
    }

    $title = 'al"b`c,d.e-f_g:h;i|j{k[l}m]n+o=p*q%r^s$t#u!v~w(x)y?z>1/2\3';
    $alias = '/content/al"b`c,d.e-f_g:h;i|j{k[l}m]n+o=p*q%r^s$t#u!v~w(x)y?z>1/2\3';
    $this->checkAlias($title, $alias, $settings);
  }

  /**
   * Helper method to check the an aliases.
   *
   * @param string $title
   *   The node title to build the aliases from.
   * @param string $alias
   *   The expected alias.
   * @param array $settings
   *   The form values the alias should be generated with.
   */
  protected function checkAlias($title, $alias, $settings = []) {
    // Submit the settings form.
    $edit = array_merge($this->defaultFormValues + $this->defaultPunctuations, $settings);
    $this->drupalPostForm('/admin/config/search/path/settings', $edit, t('Save configuration'));
    $this->assertText(t('The configuration options have been saved.'));

    // If we do not clear the caches here, AliasCleaner will use its
    // cleanStringCache instance variable. Due to that the creation of aliases
    // with $this->createNode() will only work correctly on the first call.
    \Drupal::service('pathauto.generator')->resetCaches();

    // Create a node and check if the settings applied.
    $node = $this->createNode(
      [
        'title' => $title,
        'type' => 'article',
      ]
    );

    $this->drupalGet($alias);
    $this->assertResponse(200);
    $this->assertEntityAlias($node, $alias);
  }

}
