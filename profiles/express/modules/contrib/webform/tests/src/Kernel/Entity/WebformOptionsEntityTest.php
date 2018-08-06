<?php

namespace Drupal\Tests\webform\Kernel\Entity;

use Drupal\Core\Serialization\Yaml;
use Drupal\KernelTests\KernelTestBase;
use Drupal\webform\Entity\WebformOptions;

/**
 * Tests the webform options entity class.
 *
 * @group webform
 * @see \Drupal\webform\Entity\WebformOptions
 */
class WebformOptionsEntityTest extends KernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['system', 'user', 'webform', 'field'];

  /**
   * Tests some of the methods.
   */
  public function testWebformOptionsMethods() {
    // Create webform options.
    $values = ['id' => 'webform_options_test'];
    /** @var \Drupal\webform\WebformOptionsInterface $webform_options */
    $webform_options = WebformOptions::create($values);
    $this->assertEquals('webform_options_test', $webform_options->id());

    // Check get options.
    $options = [
      'one' => 'One',
      'two' => 'Two',
      'Three' => 'Three',
    ];
    $webform_options->set('options', Yaml::encode($options));
    $this->assertEquals($webform_options->getOptions(), $options);

    // @todo Add the below assertions.
    // Check get invalid options.
    // Check options alter hook.
    // Check customizing dynamic options.
    // Check get element's #options.
  }

}
