<?php

namespace Drupal\Tests\webform\Unit\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\webform\Access\WebformSourceEntityAccess;

/**
 * @coversDefaultClass \Drupal\webform\Access\WebformSourceEntityAccess
 *
 * @group webform
 */
class WebformSourceEntityAccessTest extends WebformAccessTestBase {

  /**
   * Tests the check webform source entity access.
   *
   * @covers ::checkEntityResultsAccess
   */
  public function testWebformSourceEntityAccess() {
    // Mock anonymous account.
    $anonymous_account = $this->mockAccount();

    // Mock submission account.
    $submission_account = $this->mockAccount([
      'access webform overview' => TRUE,
      'view any webform submission' => TRUE,
    ]);

    // Mock node.
    $node = $this->getMockBuilder('Drupal\node\NodeInterface')
      ->disableOriginalConstructor()
      ->getMock();
    $node->expects($this->any())
      ->method('access')
      ->willReturn(AccessResult::neutral());

    // Mock webform.
    $webform = $this->createMock('Drupal\webform\WebformInterface');

    // Mock webform node.
    $webform_node = $this->getMockBuilder('Drupal\node\NodeInterface')
      ->disableOriginalConstructor()
      ->getMock();
    $webform_node->expects($this->any())
      ->method('access')
      ->willReturn(AccessResult::allowed());

    // Mock entity reference manager.
    $entity_reference_manager = $this->getMockBuilder('Drupal\webform\WebformEntityReferenceManagerInterface')
      ->disableOriginalConstructor()
      ->getMock();
    $entity_reference_manager->expects($this->any())
      ->method('getWebform')
      ->will($this->returnValueMap([
        [$node, NULL],
        [$webform_node, $webform],
      ]));
    $this->container->set('webform.entity_reference_manager', $entity_reference_manager);

    /**************************************************************************/

    // Check entity results access.
    $this->assertEquals(AccessResult::neutral(), WebformSourceEntityAccess::checkEntityResultsAccess($node, $anonymous_account));
    $this->assertEquals(AccessResult::allowed(), WebformSourceEntityAccess::checkEntityResultsAccess($webform_node, $submission_account));
  }

}
