<?php

namespace Drupal\Tests\token\Functional;

use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Session\AnonymousUserSession;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\Tests\TestFileCreationTrait;

/**
 * Tests user tokens.
 *
 * @group token
 */
class TokenUserTest extends TokenTestBase {

  use TestFileCreationTrait;

  /**
   * The user account.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $account = NULL;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['token_user_picture'];

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    $this->account = $this->drupalCreateUser([
      'administer users',
      'administer account settings',
      'access content',
    ]);
    $this->drupalLogin($this->account);
  }

  /**
   * Tests the user releated tokens.
   */
  public function testUserTokens() {
    // Enable user pictures.
    \Drupal::state()->set('user_pictures', 1);
    \Drupal::state()->set('user_picture_file_size', '');

    // Set up the pictures directory.
    $picture_path = 'public://' . \Drupal::state()->get('user_picture_path', 'pictures');
    if (!\Drupal::service('file_system')->prepareDirectory($picture_path, FileSystemInterface::CREATE_DIRECTORY)) {
      $this->fail('Could not create directory ' . $picture_path . '.');
    }

    // Add a user picture to the account.
    $image = current($this->getTestFiles('image'));
    $edit = ['files[user_picture_0]' => \Drupal::service('file_system')->realpath($image->uri)];
    $this->drupalPostForm('user/' . $this->account->id() . '/edit', $edit, t('Save'));

    $storage = \Drupal::entityTypeManager()->getStorage('user');

    // Load actual user data from database.
    $storage->resetCache();
    $this->account = $storage->load($this->account->id());
    $this->assertTrue(!empty($this->account->user_picture->target_id), 'User picture uploaded.');

    $picture = [
      '#theme' => 'user_picture',
      '#account' => $this->account,
    ];
    /** @var \Drupal\Core\Render\RendererInterface $renderer */
    $renderer = \Drupal::service('renderer');
    $user_tokens = [
      'picture' => $renderer->renderPlain($picture),
      'picture:fid' => $this->account->user_picture->target_id,
      'picture:size-raw' => 125,
      'ip-address' => NULL,
      'roles' => implode(', ', $this->account->getRoles()),
    ];
    $this->assertTokens('user', ['user' => $this->account], $user_tokens);

    // Remove the simpletest-created user role.
    $roles = $this->account->getRoles();
    $this->account->removeRole(end($roles));
    $this->account->save();

    // Remove the user picture field and reload the user.
    FieldStorageConfig::loadByName('user', 'user_picture')->delete();
    $storage->resetCache();
    $this->account = $storage->load($this->account->id());

    $user_tokens = [
      'picture' => NULL,
      'picture:fid' => NULL,
      'ip-address' => NULL,
      'roles' => 'authenticated',
      'roles:keys' => AccountInterface::AUTHENTICATED_ROLE,
    ];
    $this->assertTokens('user', ['user' => $this->account], $user_tokens);

    // The ip address token should work for the current user token type.
    $tokens = [
      'ip-address' => \Drupal::request()->getClientIp(),
    ];
    $this->assertTokens('current-user', [], $tokens);

    $anonymous = new AnonymousUserSession();
    $tokens = [
      'roles' => 'anonymous',
      'roles:keys' => AccountInterface::ANONYMOUS_ROLE,
    ];
    $this->assertTokens('user', ['user' => $anonymous], $tokens);
  }

  /**
   * Test user account settings.
   */
  public function testUserAccountSettings() {
    $this->drupalGet('admin/config/people/accounts');
    $this->assertText('The list of available tokens that can be used in e-mails is provided below.');
    $this->assertLink('Browse available tokens.');
    $this->assertLinkByHref('token/tree');
  }
}
