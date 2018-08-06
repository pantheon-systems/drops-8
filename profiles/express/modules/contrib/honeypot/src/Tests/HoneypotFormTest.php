<?php

namespace Drupal\honeypot\Tests;

use Drupal\simpletest\WebTestBase;
use Drupal\comment\Tests\CommentTestTrait;
use Drupal\comment\Plugin\Field\FieldType\CommentItemInterface;
use Drupal\contact\Entity\ContactForm;

/**
 * Test Honeypot spam protection functionality.
 *
 * @group honeypot
 */
class HoneypotFormTest extends WebTestBase {

  use CommentTestTrait;

  protected $adminUser;
  protected $webUser;
  protected $node;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['honeypot', 'node', 'comment', 'contact'];

  /**
   * Setup before test.
   */
  public function setUp() {
    // Enable modules required for this test.
    parent::setUp();

    // Set up required Honeypot configuration.
    $honeypot_config = \Drupal::configFactory()->getEditable('honeypot.settings');
    $honeypot_config->set('element_name', 'url');
    // Disable time_limit protection.
    $honeypot_config->set('time_limit', 0);
    // Test protecting all forms.
    $honeypot_config->set('protect_all_forms', TRUE);
    $honeypot_config->set('log', FALSE);
    $honeypot_config->save();

    // Set up other required configuration.
    $user_config = \Drupal::configFactory()->getEditable('user.settings');
    $user_config->set('verify_mail', TRUE);
    $user_config->set('register', USER_REGISTER_VISITORS);
    $user_config->save();

    // Create an Article node type.
    if ($this->profile != 'standard') {
      $this->drupalCreateContentType(['type' => 'article', 'name' => 'Article']);
      // Create comment field on article.
      $this->addDefaultCommentField('node', 'article');
    }

    // Set up admin user.
    $this->adminUser = $this->drupalCreateUser([
      'administer honeypot',
      'bypass honeypot protection',
      'administer content types',
      'administer users',
      'access comments',
      'post comments',
      'skip comment approval',
      'administer comments',
    ]);

    // Set up web user.
    $this->webUser = $this->drupalCreateUser([
      'access comments',
      'post comments',
      'create article content',
      'access site-wide contact form',
    ]);

    // Set up example node.
    $this->node = $this->drupalCreateNode([
      'type' => 'article',
      'comment' => CommentItemInterface::OPEN,
    ]);
  }

  /**
   * Make sure user login form is not protected.
   */
  public function testUserLoginNotProtected() {
    $this->drupalGet('user');
    $this->assertNoText('id="edit-url" name="url"', 'Honeypot not enabled on user login form.');
  }

  /**
   * Test user registration (anonymous users).
   */
  public function testProtectRegisterUserNormal() {
    // Set up form and submit it.
    $edit['name'] = $this->randomMachineName();
    $edit['mail'] = $edit['name'] . '@example.com';
    $this->drupalPostForm('user/register', $edit, t('Create new account'));

    // Form should have been submitted successfully.
    $this->assertText(t('A welcome message with further instructions has been sent to your email address.'), 'User registered successfully.');
  }

  /**
   * Test for user register honeypot filled.
   */
  public function testProtectUserRegisterHoneypotFilled() {
    // Set up form and submit it.
    $edit['name'] = $this->randomMachineName();
    $edit['mail'] = $edit['name'] . '@example.com';
    $edit['url'] = 'http://www.example.com/';
    $this->drupalPostForm('user/register', $edit, t('Create new account'));

    // Form should have error message.
    $this->assertText(t('There was a problem with your form submission. Please refresh the page and try again.'), 'Registration form protected by honeypot.');
  }

  /**
   * Test for user register too fast.
   */
  public function testProtectRegisterUserTooFast() {
    \Drupal::configFactory()->getEditable('honeypot.settings')->set('time_limit', 1)->save();

    // First attempt a submission that does not trigger honeypot.
    $edit['name'] = $this->randomMachineName();
    $edit['mail'] = $edit['name'] . '@example.com';
    $this->drupalGet('user/register');
    sleep(2);
    $this->drupalPostForm(NULL, $edit, t('Create new account'));
    $this->assertNoText(t('There was a problem with your form submission.'));

    // Set the time limit a bit higher so we can trigger honeypot.
    \Drupal::configFactory()->getEditable('honeypot.settings')->set('time_limit', 5)->save();

    // Set up form and submit it.
    $edit['name'] = $this->randomMachineName();
    $edit['mail'] = $edit['name'] . '@example.com';
    $this->drupalPostForm('user/register', $edit, t('Create new account'));

    // Form should have error message.
    $this->assertText(t('There was a problem with your form submission. Please wait 6 seconds and try again.'), 'Registration form protected by time limit.');
  }

  /**
   * Test comment form protection.
   */
  public function testProtectCommentFormNormal() {
    $comment = 'Test comment.';

    // Disable time limit for honeypot.
    \Drupal::configFactory()->getEditable('honeypot.settings')->set('time_limit', 0)->save();

    // Log in the web user.
    $this->drupalLogin($this->webUser);

    // Set up form and submit it.
    $edit["comment_body[0][value]"] = $comment;
    $this->drupalPostForm('comment/reply/node/' . $this->node->id() . '/comment', $edit, t('Save'));
    $this->assertText(t('Your comment has been queued for review'), 'Comment posted successfully.');
  }

  /**
   * Test for comment form honeypot filled.
   */
  public function testProtectCommentFormHoneypotFilled() {
    $comment = 'Test comment.';

    // Log in the web user.
    $this->drupalLogin($this->webUser);

    // Set up form and submit it.
    $edit["comment_body[0][value]"] = $comment;
    $edit['url'] = 'http://www.example.com/';
    $this->drupalPostForm('comment/reply/node/' . $this->node->id() . '/comment', $edit, t('Save'));
    $this->assertText(t('There was a problem with your form submission. Please refresh the page and try again.'), 'Comment posted successfully.');
  }

  /**
   * Test for comment form honeypot bypass.
   */
  public function testProtectCommentFormHoneypotBypass() {
    // Log in the admin user.
    $this->drupalLogin($this->adminUser);

    // Get the comment reply form and ensure there's no 'url' field.
    $this->drupalGet('comment/reply/node/' . $this->node->id() . '/comment');
    $this->assertNoText('id="edit-url" name="url"', 'Honeypot home page field not shown.');
  }

  /**
   * Test node form protection.
   */
  public function testProtectNodeFormTooFast() {
    // Log in the admin user.
    $this->drupalLogin($this->webUser);

    // Reset the time limit to 5 seconds.
    \Drupal::configFactory()->getEditable('honeypot.settings')->set('time_limit', 5)->save();

    // Set up the form and submit it.
    $edit["title[0][value]"] = 'Test Page';
    $this->drupalPostForm('node/add/article', $edit, t('Save'));
    $this->assertText(t('There was a problem with your form submission.'), 'Honeypot node form timestamp protection works.');
  }

  /**
   * Test node form protection.
   */
  public function testProtectNodeFormPreviewPassthru() {
    // Log in the admin user.
    $this->drupalLogin($this->webUser);

    // Post a node form using the 'Preview' button and make sure it's allowed.
    $edit["title[0][value]"] = 'Test Page';
    $this->drupalPostForm('node/add/article', $edit, t('Preview'));
    $this->assertNoText(t('There was a problem with your form submission.'), 'Honeypot not blocking node form previews.');
  }

  /**
   * Test protection on the Contact form.
   */
  public function testProtectContactForm() {
    $this->drupalLogin($this->adminUser);

    // Disable 'protect_all_forms'.
    \Drupal::configFactory()->getEditable('honeypot.settings')->set('protect_all_forms', FALSE)->save();

    // Create a Website feedback contact form.
    $feedback_form = ContactForm::create([
      'id' => 'feedback',
      'label' => 'Website feedback',
      'recipients' => [],
      'reply' => '',
      'weight' => 0,
    ]);
    $feedback_form->save();
    $contact_settings = \Drupal::configFactory()->getEditable('contact.settings');
    $contact_settings->set('default_form', 'feedback')->save();

    // Submit the admin form so we can verify the right forms are displayed.
    $this->drupalPostForm('admin/config/content/honeypot', [
      'form_settings[contact_message_feedback_form]' => TRUE,
    ], t('Save configuration'));

    $this->drupalLogin($this->webUser);
    $this->drupalGet('contact/feedback');
    $this->assertField('url', 'Honeypot field is added to Contact form.');
  }

}
