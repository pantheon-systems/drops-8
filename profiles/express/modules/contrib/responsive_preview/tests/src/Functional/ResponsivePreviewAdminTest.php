<?php

namespace Drupal\Tests\responsive_preview\Functional;

use Drupal\Core\Url;

/**
 * Tests the Crud operation on responsive preview device.
 *
 * @group responsive_preview
 */
class ResponsivePreviewAdminTest extends ResponsivePreviewTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['block'];

  /**
   * Tests configuring devices.
   */
  public function testDeviceConfiguration() {
    $this->placeBlock('local_actions_block');

    // Ensures taht the responsive admin preview is accessible only for user
    // with the adequate permissions.
    $this->drupalGet('admin/config/user-interface/responsive-preview');
    $this->assertSession()->statusCodeEquals(403);

    // Login as administrative user.
    $admin_user = $this->drupalCreateUser(['administer responsive preview']);
    $this->drupalLogin($admin_user);

    // Ensures taht the responsive admin preview is accessible only for user
    // with the adequate permissions.
    $this->drupalGet('admin/config/user-interface/responsive-preview');
    $this->assertSession()->statusCodeEquals(200);

    // Test for the page title.
    $this->assertSession()->titleEquals('Responsive preview device | Drupal');

    // Test for the table.
    $element = $this->xpath('//div[@class="layout-content"]//table');
    $this->assertTrue($element, 'Device entity list table found.');

    // Test the table header.
    $elements = $this->xpath('//div[@class="layout-content"]//table/thead/tr/th');
    $this->assertEquals(count($elements), 5, 'Correct number of table header cells found.');

    // Test the contents of each th cell.
    $expected_items = [
      'Name',
      'Show in list',
      'Dimensions',
      'Weight',
      'Operations',
    ];
    foreach ($elements as $key => $element) {
      $this->assertSame($element->getText(), $expected_items[$key]);
    }

    // Ensures that all default devices are listed in the table.
    $default_devices = $this->getDefaultDevices();
    foreach ($default_devices as $label) {
      $xpath = $this->assertSession()
        ->buildXPathQuery('//table//tr//td[text()=:text]', [':text' => $label]);
      $this->assertSession()->elementExists('xpath', $xpath);
    }

    // Test for the add action button.
    $this->assertSession()->linkExists('Add Device');
    $this->assertSession()
      ->linkByHrefExists('admin/config/user-interface/responsive-preview/add');

    // Test the insert of a new device.
    $edit = [
      'label' => 'Smartwatch',
      'id' => 'smartwatch',
      'status' => '1',
      'dimensions[width]' => '200',
      'dimensions[height]' => '350',
      'dimensions[dppx]' => '3',
      'orientation' => 'portrait',
    ];
    $this->drupalPostForm('admin/config/user-interface/responsive-preview/add', $edit, t('Save'));
    $this->assertSession()
      ->responseContains(t('Device %name has been added.', ['%name' => 'Smartwatch']));
    $this->assertSession()
      ->elementExists('xpath', '//table//tr//td[text()="Smartwatch"]');

    // Ensures that is not possible to insert a non-unique device id.
    $this->drupalPostForm('admin/config/user-interface/responsive-preview/add', $edit, t('Save'));
    $this->assertSession()
      ->responseContains(t('The machine-readable name is already in use. It must be unique.'));

    // Tests the update of an existing device.
    $edit = [
      'label' => 'Smart phone updated',
      'status' => '1',
      'dimensions[width]' => '1600',
      'dimensions[height]' => '2850',
      'dimensions[dppx]' => '2.5',
      'orientation' => 'landscape',
    ];
    $this->drupalPostForm('admin/config/user-interface/responsive-preview/small/edit', $edit, t('Save'));
    $this->assertSession()
      ->responseContains(t('Device %name has been updated.', ['%name' => 'Smart phone updated']));
    $this->assertSession()
      ->elementExists('xpath', '//table//tr//td[text()="Smart phone updated"]');
    $this->assertSession()->checkboxChecked('entities[small][status]');

    // Tests the delete of a predefined devices.
    $this->drupalPostForm('admin/config/user-interface/responsive-preview/large/delete', [], t('Delete'));
    $this->assertSession()
      ->responseContains(t('Device %name has been deleted.', ['%name' => 'Typical desktop']));
    $this->assertSession()
      ->elementNotExists('xpath', '//table//tr//td[text()="Typical desktop"]');

    // Tests the update of the status from the listing page.
    $edit = [
      'entities[medium][status]' => 1,
      'entities[small][status]' => 0,
      'entities[smartwatch][status]' => 0,
    ];
    $this->drupalPostForm('admin/config/user-interface/responsive-preview', $edit, t('Save'));
    $this->assertSession()
      ->responseContains(t('The device settings have been updated.'));
    $this->assertSession()->checkboxChecked('entities[medium][status]');
    $this->assertSession()->checkboxNotChecked('entities[small][status]');
    $this->assertSession()->checkboxNotChecked('entities[smartwatch][status]');

    // Tests the listing page when no devices are present.
    $device_storage = \Drupal::entityTypeManager()
      ->getStorage('responsive_preview_device');
    $device_storage->delete($device_storage->loadMultiple());

    $this->drupalGet('admin/config/user-interface/responsive-preview');
    $this->assertSession()
      ->elementNotExists('xpath', '//input[type="submit" and text="Save"]');
    $this->assertSession()
      ->responseContains(t('No devices available. <a href=":link">Add devices</a>.', [
        ':link' => Url::fromRoute('entity.responsive_preview_device.add_form')
          ->toString(),
      ]));

  }

}
