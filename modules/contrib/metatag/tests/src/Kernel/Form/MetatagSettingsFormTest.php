<?php

namespace Drupal\Tests\metatag\Kernel\Form;

use Drupal\Core\Form\FormInterface;
use Drupal\KernelTests\KernelTestBase;
use Drupal\metatag\Form\MetatagSettingsForm;

/**
 * Tests the metatag settings form.
 *
 * @coversDefaultClass \Drupal\metatag\Form\MetatagSettingsForm
 *
 * @group metatag
 */
class MetatagSettingsFormTest extends KernelTestBase {

  /**
   * The metatag form object under test.
   *
   * @var \Drupal\metatag\Form\MetatagSettingsForm
   */
  protected $metatagSettingsForm;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    // Core modules.
    'system',

    // Contrib modules.
    'token',

    // This module.
    'metatag',
  ];

  /**
   * {@inheritdoc}
   *
   * @covers ::__construct
   */
  protected function setUp() {
    parent::setUp();

    $this->installConfig(static::$modules);
    $this->metatagSettingsForm = new MetatagSettingsForm(
      $this->container->get('config.factory')
    );
  }

  /**
   * Tests for \Drupal\metatag\Form\MetatagSettingsForm.
   */
  public function testMetatagSettingsForm() {
    $this->assertInstanceOf(FormInterface::class, $this->metatagSettingsForm);

    $this->assertEquals('metatag_admin_settings', $this->metatagSettingsForm->getFormId());

    $method = new \ReflectionMethod(MetatagSettingsForm::class, 'getEditableConfigNames');
    $method->setAccessible(TRUE);

    $name = $method->invoke($this->metatagSettingsForm);
    $this->assertEquals(['metatag.settings'], $name);
  }

}
