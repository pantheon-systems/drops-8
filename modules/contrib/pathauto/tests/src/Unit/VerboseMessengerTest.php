<?php

namespace Drupal\Tests\pathauto\Unit;

use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Session\AccountInterface;
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
    $config_factory = $this->getConfigFactoryStub(['pathauto.settings' => ['verbose' => TRUE]]);
    $account = $this->createMock(AccountInterface::class);
    $account->expects($this->once())
      ->method('hasPermission')
      ->withAnyParameters()
      ->willReturn(TRUE);
    $messenger = $this->createMock(MessengerInterface::class);

    $this->messenger = new VerboseMessenger($config_factory, $account, $messenger);
  }

  /**
   * Tests add messages.
   *
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
