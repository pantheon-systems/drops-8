<?php

namespace Drupal\Tests\webform_group\Functional;

use Drupal\webform\Entity\Webform;
use Drupal\webform\Utility\WebformElementHelper;
use Drupal\webform_group\Element\WebformGroupRoles;

/**
 * Tests webform group roles element.
 *
 * @group webform_group
 */
class WebformGroupRolesElementTest extends WebformGroupBrowserTestBase {

  /**
   * Tests webform group roles element.
   */
  public function testGroupRolesElement() {
    $webform = Webform::load('test_element_group_roles');

    /**************************************************************************/

    // Check default element properties.
    $element = [];
    $options = WebformGroupRoles::getGroupRolesOptions($element);
    WebformElementHelper::convertRenderMarkupToStrings($options);
    $this->assertEqual([
      'Group role types' => [
        'outsider' => 'Outsider',
        'member' => 'Member',
        'custom' => 'Custom',
      ],
      'Default label' => [
        'default-outsider' => 'Default label: Outsider',
        'default-member' => 'Default label: Member',
        'default-custom' => 'Default label: Custom',
      ],
      'Other label' => [
        'other-outsider' => 'Other label: Outsider',
        'other-member' => 'Other label: Member',
      ],
    ], $options);

    // Check custom element properties.
    $element = [
      '#include_internal' => FALSE,
      '#include_user_roles' => TRUE,
      '#include_anonymous' => TRUE,
    ];
    $options = WebformGroupRoles::getGroupRolesOptions($element);
    WebformElementHelper::convertRenderMarkupToStrings($options);
    $this->assertEqual([
      'Group role types' => [
        'custom' => 'Custom',
      ],
      'Default label' => [
        'default-custom' => 'Default label: Custom',
      ],
    ], $options);

    // Check posting group role.
    $edit = [
      'webform_group_roles' => ['custom', 'member'],
      'webform_group_roles_advanced' => 'custom',
    ];
    $this->postSubmission($webform, $edit);
    $this->assertRaw('webform_group_roles:
  - custom
  - member
webform_group_roles_advanced: custom');
  }

}
