<?php

namespace Drupal\honeypot\Tests;

use Drupal\comment\Tests\CommentTestTrait;
use Drupal\comment\Plugin\Field\FieldType\CommentItemInterface;
use Drupal\contact\Entity\ContactForm;
use Drupal\simpletest\WebTestBase;
use Drupal\user\Entity\Role;
use Drupal\user\RoleInterface;

/**
 * Tests page caching on Honeypot protected forms.
 *
 * @group honeypot
 */
class HoneypotFormCacheTest extends WebTestBase {

  use CommentTestTrait;
  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['honeypot', 'node', 'comment', 'contact'];

  protected $node;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Set up required Honeypot configuration.
    $honeypot_config = \Drupal::configFactory()->getEditable('honeypot.settings');
    $honeypot_config->set('element_name', 'url');
    // Enable time_limit protection.
    $honeypot_config->set('time_limit', 5);
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
  }

  /**
   * Test enabling and disabling of page cache based on time limit settings.
   */
  public function testCacheContactForm() {
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

    // Give anonymous users permission to view contact form.
    Role::load(RoleInterface::ANONYMOUS_ID)
      ->grantPermission('access site-wide contact form')
      ->save();

    // Prime the cache.
    $this->drupalGet('contact/feedback');

    // Test on cache header with time limit enabled, cache should miss.
    $this->drupalGet('contact/feedback');
    $this->assertEqual($this->drupalGetHeader('X-Drupal-Cache'), '', 'Page was not cached.');

    // Disable time limit.
    \Drupal::configFactory()->getEditable('honeypot.settings')->set('time_limit', 0)->save();

    // Prime the cache.
    $this->drupalGet('contact/feedback');
    // Test on cache header with time limit disabled, cache should hit.
    $this->drupalGet('contact/feedback');
    $this->assertEqual($this->drupalGetHeader('X-Drupal-Cache'), 'HIT', 'Page was cached.');

    // Re-enable the time limit, we should not be seeing the cached version.
    \Drupal::configFactory()->getEditable('honeypot.settings')->set('time_limit', 5)->save();
    $this->drupalGet('contact/feedback');
    $this->assertEqual($this->drupalGetHeader('X-Drupal-Cache'), '', 'Page was not cached.');
  }

  /**
   * Test enabling and disabling of page cache based on time limit settings.
   */
  public function testCacheCommentForm() {
    // Set up example node.
    $this->node = $this->drupalCreateNode([
      'type' => 'article',
      'comment' => CommentItemInterface::OPEN,
    ]);

    // Give anonymous users permission to post comments.
    Role::load(RoleInterface::ANONYMOUS_ID)
      ->grantPermission('post comments')
      ->grantPermission('access comments')
      ->save();

    // Prime the cache.
    $this->drupalGet('node/' . $this->node->id());

    // Test on cache header with time limit enabled, cache should miss.
    $this->drupalGet('node/' . $this->node->id());
    $this->assertEqual($this->drupalGetHeader('X-Drupal-Cache'), '', 'Page was not cached.');

    // Disable time limit.
    \Drupal::configFactory()->getEditable('honeypot.settings')->set('time_limit', 0)->save();

    // Prime the cache.
    $this->drupalGet('node/' . $this->node->id());

    // Test on cache header with time limit disabled, cache should hit.
    $this->drupalGet('node/' . $this->node->id());
    $this->assertEqual($this->drupalGetHeader('X-Drupal-Cache'), 'HIT', 'Page was cached.');

  }

}
