<?php

namespace Drupal\Tests\webform_node\Unit;

use Drupal\simpletest\AssertHelperTrait;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\webform_node\WebformNodeUninstallValidator
 * @group webform
 */
class WebformNodeUninstallValidatorTest extends UnitTestCase {

  use AssertHelperTrait;

  /**
   * A mock webform node uninstall validator.
   *
   * @var \Drupal\webform_node\WebformNodeUninstallValidator|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $webformNodeUninstallValidator;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->webformNodeUninstallValidator = $this->getMockBuilder('Drupal\webform_node\WebformNodeUninstallValidator')
      ->disableOriginalConstructor()
      ->setMethods(['hasWebformNodes'])
      ->getMock();
    $this->webformNodeUninstallValidator->setStringTranslation($this->getStringTranslationStub());
  }

  /**
   * @covers ::validate
   */
  public function testValidateNotWebformNode() {
    $this->webformNodeUninstallValidator->expects($this->never())
      ->method('hasWebformNodes');

    $module = 'not_webform_node';
    $expected = [];
    $reasons = $this->webformNodeUninstallValidator->validate($module);
    $this->assertSame($expected, $this->castSafeStrings($reasons));
  }

  /**
   * @covers ::validate
   */
  public function testValidateEntityQueryWithoutResults() {
    $this->webformNodeUninstallValidator->expects($this->once())
      ->method('hasWebformNodes')
      ->willReturn(FALSE);

    $module = 'webform_node';
    $expected = [];
    $reasons = $this->webformNodeUninstallValidator->validate($module);
    $this->assertSame($expected, $this->castSafeStrings($reasons));
  }

  /**
   * @covers ::validate
   */
  public function testValidateEntityQueryWithResults() {
    $this->webformNodeUninstallValidator->expects($this->once())
      ->method('hasWebformNodes')
      ->willReturn(TRUE);

    $module = 'webform_node';
    $expected = ['To uninstall Webform node, delete all content that has the Webform content type.'];
    $reasons = $this->webformNodeUninstallValidator->validate($module);
    $this->assertSame($expected, $this->castSafeStrings($reasons));
  }

}
