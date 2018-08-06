<?php

namespace Drupal\Tests\webform\Kernel;

use Drupal\KernelTests\Core\Entity\EntityKernelTestBase;
use Drupal\webform\Entity\Webform;
use Drupal\webform\Entity\WebformSubmission;

/**
 * Tests that conditions, provided by the webform module, are working properly.
 *
 * @group webform
 */
class WebformConditionTest extends EntityKernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['webform'];

  /**
   * Tests conditions.
   */
  public function testConditions() {
    $this->installSchema('webform', ['webform']);

    $manager = $this->container->get('plugin.manager.condition', $this->container->get('container.namespaces'));
    $this->createUser();

    // Get some nodes of various types to check against.
    $webform = Webform::create(['id' => 'test']);
    $webform->save();

    // Grab the webform condition and configure it to check against webforms
    // of 'not_test' and set the context to the test webform.
    $condition = $manager->createInstance('webform')
      ->setConfig('webforms', ['not_test' => 'not_test'])
      ->setContextValue('webform', $webform);
    $this->assertFalse($condition->execute(), 'Webform check fails.');
    // Check for the proper summary.
    $this->assertEquals('The webform is not_test', $condition->summary());

    // Set the webform check to test.
    $condition->setConfig('webforms', ['test' => 'test']);
    $this->assertTrue($condition->execute(), 'Webform test pass webform condition check for test');
    // Check for the proper summary.
    $this->assertEquals('The webform is test', $condition->summary());

    // Set the webform check to not_test or test.
    $condition->setConfig('webforms', ['not_test' => 'not_test', 'test' => 'test']);
    $this->assertTrue($condition->execute(), 'Webform test pass webform condition check for not_test or test');
    // Check for the proper summary.
    $this->assertEquals('The webform is not_test or test', $condition->summary());

    // Check a greater than 2 webform summary scenario.
    $condition->setConfig('webforms', ['not_test' => 'not_test', 'test' => 'test', 'other_test' => 'other_test']);
    $this->assertEquals('The webform is not_test, test or other_test', $condition->summary());

    // Test Constructor injection.
    $condition = $manager->createInstance('webform', ['webforms' => ['test' => 'test'], 'context' => ['webform' => $webform]]);
    $this->assertTrue($condition->execute(), 'Constructor injection of context and configuration working as anticipated.');

    // Check webform_submission context.
    /** @var \Drupal\webform\WebformSubmissionInterface $webform_submission */
    $webform_submission = WebformSubmission::create(['webform_id' => $webform->id()]);
    $condition = $manager->createInstance('webform')
      ->setConfig('webforms', ['test' => 'test'])
      ->setContextValue('webform_submission', $webform_submission);
    $this->assertEquals('The webform is test', $condition->summary());
  }

}
