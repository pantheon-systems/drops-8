<?php

namespace Drupal\webform\Tests\Handler;

use Drupal\webform\Entity\Webform;
use Drupal\webform\Tests\WebformTestBase;

/**
 * Tests for webform handler plugin conditions.
 *
 * @group Webform
 */
class WebformHandlerConditionsTest extends WebformTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['webform', 'webform_test_handler'];

  /**
   * Webforms to load.
   *
   * @var array
   */
  protected static $testWebforms = ['test_handler_conditions'];

  /**
   * Tests webform handler plugin conditions.
   */
  public function testConditions() {
    $this->drupalLogin($this->rootUser);

    /** @var \Drupal\webform\WebformInterface $webform */
    $webform = Webform::load('test_handler_conditions');

    $this->drupalGet('webform/test_handler_conditions');

    // Check no triggers.
    $this->assertRaw('Invoked test_a: Drupal\webform_test_handler\Plugin\WebformHandler\TestWebformHandler:preCreate');
    $this->assertRaw('Invoked test_b: Drupal\webform_test_handler\Plugin\WebformHandler\TestWebformHandler:preCreate');
    $this->assertRaw('Invoked test_a: Drupal\webform_test_handler\Plugin\WebformHandler\TestWebformHandler:alterElements');
    $this->assertRaw('Invoked test_b: Drupal\webform_test_handler\Plugin\WebformHandler\TestWebformHandler:alterElements');

    // Check post submission no trigger.
    $this->postSubmission($webform);
    $this->assertRaw('Invoked test_a: Drupal\webform_test_handler\Plugin\WebformHandler\TestWebformHandler:preCreate');
    $this->assertRaw('Invoked test_b: Drupal\webform_test_handler\Plugin\WebformHandler\TestWebformHandler:preCreate');
    $this->assertRaw('Invoked test_a: Drupal\webform_test_handler\Plugin\WebformHandler\TestWebformHandler:alterElements');
    $this->assertRaw('Invoked test_b: Drupal\webform_test_handler\Plugin\WebformHandler\TestWebformHandler:alterElements');

    // Trigger only A handler
    $this->postSubmission($webform, ['trigger_a' => TRUE]);

    // Check non submission hooks are executed.
    $this->assertRaw('Invoked test_a: Drupal\webform_test_handler\Plugin\WebformHandler\TestWebformHandler:preCreate');
    $this->assertRaw('Invoked test_b: Drupal\webform_test_handler\Plugin\WebformHandler\TestWebformHandler:preCreate');
    $this->assertRaw('Invoked test_a: Drupal\webform_test_handler\Plugin\WebformHandler\TestWebformHandler:alterElements');
    $this->assertRaw('Invoked test_b: Drupal\webform_test_handler\Plugin\WebformHandler\TestWebformHandler:alterElements');

    // Check trigger A submission hooks are executed.
    $this->assertRaw('Invoked test_a: Drupal\webform_test_handler\Plugin\WebformHandler\TestWebformHandler:validateForm');
    $this->assertRaw('Invoked test_a: Drupal\webform_test_handler\Plugin\WebformHandler\TestWebformHandler:submitForm');
    $this->assertRaw('Invoked test_a: Drupal\webform_test_handler\Plugin\WebformHandler\TestWebformHandler:preSave');
    $this->assertRaw('Invoked test_a: Drupal\webform_test_handler\Plugin\WebformHandler\TestWebformHandler:postSave insert');
    $this->assertRaw('Test A');
    $this->assertRaw('Invoked test_a: Drupal\webform_test_handler\Plugin\WebformHandler\TestWebformHandler:confirmForm');

    // Trigger only B handler
    $this->postSubmission($webform, ['trigger_b' => TRUE]);

    // Check non submission hooks are executed.
    $this->assertRaw('Invoked test_a: Drupal\webform_test_handler\Plugin\WebformHandler\TestWebformHandler:preCreate');
    $this->assertRaw('Invoked test_b: Drupal\webform_test_handler\Plugin\WebformHandler\TestWebformHandler:preCreate');
    $this->assertRaw('Invoked test_a: Drupal\webform_test_handler\Plugin\WebformHandler\TestWebformHandler:alterElements');
    $this->assertRaw('Invoked test_b: Drupal\webform_test_handler\Plugin\WebformHandler\TestWebformHandler:alterElements');

    // Check trigger A submission hooks are no executed.
    $this->assertNoRaw('Invoked test_a: Drupal\webform_test_handler\Plugin\WebformHandler\TestWebformHandler:validateForm');
    $this->assertNoRaw('Invoked test_a: Drupal\webform_test_handler\Plugin\WebformHandler\TestWebformHandler:submitForm');
    $this->assertNoRaw('Invoked test_a: Drupal\webform_test_handler\Plugin\WebformHandler\TestWebformHandler:preSave');
    $this->assertNoRaw('Invoked test_a: Drupal\webform_test_handler\Plugin\WebformHandler\TestWebformHandler:postSave insert');
    $this->assertNoRaw('Test A');
    $this->assertNoRaw('Invoked test_a: Drupal\webform_test_handler\Plugin\WebformHandler\TestWebformHandler:confirmForm');

    // Check trigger B submission hooks are executed.
    $this->assertRaw('Invoked test_b: Drupal\webform_test_handler\Plugin\WebformHandler\TestWebformHandler:validateForm');
    $this->assertRaw('Invoked test_b: Drupal\webform_test_handler\Plugin\WebformHandler\TestWebformHandler:submitForm');
    $this->assertRaw('Invoked test_b: Drupal\webform_test_handler\Plugin\WebformHandler\TestWebformHandler:preSave');
    $this->assertRaw('Invoked test_b: Drupal\webform_test_handler\Plugin\WebformHandler\TestWebformHandler:postSave insert');
    $this->assertRaw('Test B');
    $this->assertRaw('Invoked test_b: Drupal\webform_test_handler\Plugin\WebformHandler\TestWebformHandler:confirmForm');
  }

}
