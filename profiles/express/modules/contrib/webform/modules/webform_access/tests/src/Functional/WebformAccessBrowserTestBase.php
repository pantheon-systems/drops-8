<?php

namespace Drupal\Tests\webform_access\Functional;

use Drupal\webform_access\Entity\WebformAccessGroup;
use Drupal\webform_access\Entity\WebformAccessType;
use Drupal\Tests\webform_node\Functional\WebformNodeBrowserTestBase;

/**
 * Test base for webform access.
 */
abstract class WebformAccessBrowserTestBase extends WebformNodeBrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['webform', 'webform_access'];

  /**
   * Webform node[].
   *
   * @var \Drupal\node\NodeInterface
   */
  protected $nodes = [];

  /**
   * Users.
   *
   * @var \Drupal\user\UserInterface[]
   */
  protected $users = [];

  /**
   * Access types (manager, employee, and customer).
   *
   * @var \Drupal\webform_access\WebformAccessTypeInterface[]
   */
  protected $types = [];

  /**
   * Access groups (manager, employee, and customer).
   *
   * @var \Drupal\webform_access\WebformAccessGroupInterface[]
   */
  protected $groups = [];

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    // Create webform nodes.
    $this->nodes['contact_01'] = $this->createWebformNode('contact', ['title' => 'contact_01']);
    $this->nodes['contact_02'] = $this->createWebformNode('contact', ['title' => 'contact_02']);

    // Create webform access types and groups.
    $types = [
      'manager' => [
        'administer',
      ],
      'employee' => [
        'view_any',
        'update_any',
      ],
      'customer' => [
        'view_own',
        'update_own',
      ],
    ];
    foreach ($types as $type => $permissions) {
      $this->users[$type] = $this->drupalCreateUser([], $type . '_user');

      $values = [
        'id' => $type,
        'label' => $type . '_type',
      ];
      $webform_access_type = WebformAccessType::create($values);
      $webform_access_type->save();
      $this->types[$type] = $webform_access_type;

      $values = [
        'id' => $type,
        'type' => $type,
        'label' => $type . '_group',
        'permissions' => $permissions,
      ];
      $webform_access_group = WebformAccessGroup::create($values);
      $webform_access_group->addEntityId('node', $this->nodes['contact_01']->id(), 'webform', 'contact');
      $webform_access_group->save();
      $this->groups[$type] = $webform_access_group;
    }
  }

}
