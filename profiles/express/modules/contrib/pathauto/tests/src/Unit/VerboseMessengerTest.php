<?php

namespace Drupal\Tests\pathauto\Unit {

  use Drupal\pathauto\VerboseMessenger;
  use Drupal\Tests\UnitTestCase;

  /**
   * @coversDefaultClass \Drupal\pathauto\VerboseMessenger
   * @group pathauto
   */
  class VerboseMessengerTest extends UnitTestCase {

    /**
     * The messenger under test.
     *
     * @var \Drupal\pathauto\VerboseMessenger
     */
    protected $messenger;

    /**
     * {@inheritdoc}
     */
    protected function setUp() {
      $config_factory = $this->getConfigFactoryStub(array('pathauto.settings' => array('verbose' => TRUE)));
      $account = $this->getMock('\Drupal\Core\Session\AccountInterface');
      $account->expects($this->once())
        ->method('hasPermission')
        ->withAnyParameters()
        ->willReturn(TRUE);

      $this->messenger = new VerboseMessenger($config_factory, $account);
    }

    /**
     * Tests add messages.
     * @covers ::addMessage
     */
    public function testAddMessage() {
      $this->assertTrue($this->messenger->addMessage("Test message"), "The message was added");
    }

    /**
     * @covers ::addMessage
     */
    public function testDoNotAddMessageWhileBulkupdate() {
      $this->assertFalse($this->messenger->addMessage("Test message", "bulkupdate"), "The message was NOT added");
    }

}

}
namespace {
  // @todo Delete after https://drupal.org/node/1858196 is in.
  if (!function_exists('drupal_set_message')) {
    function drupal_set_message() {
    }
  }
}
