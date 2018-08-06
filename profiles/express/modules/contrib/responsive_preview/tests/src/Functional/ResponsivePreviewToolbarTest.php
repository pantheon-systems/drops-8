<?php

namespace Drupal\Tests\responsive_preview\Functional;

/**
 * Tests the toolbar integration.
 *
 * @group responsive_preview
 */
class ResponsivePreviewToolbarTest extends ResponsivePreviewTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['toolbar'];

  /**
   * The user for tests.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $previewUser;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    $this->previewUser = $this->drupalCreateUser([
      'access responsive preview',
      'access toolbar',
    ]);
  }

  /**
   * Tests that the toolbar integration works properly.
   */
  public function testToolbarIntegration() {
    $toolbar_xpath = '//div[@id="toolbar-administration"]/nav[@id="toolbar-bar"]';
    $tab_xpath = '//nav[@id="toolbar-bar"]//div[contains(@class, "toolbar-tab-responsive-preview")]';
    $devices = array_keys($this->getDefaultDevices(TRUE));

    // Anonymous user by default cannot use the preview so the module library
    // and the cache tags and contexts should not be present.
    $this->drupalGet('');
    $this->assertNoResponsivePreviewLibrary();
    $this->assertNoResponsivePreviewCachesTagAndContexts();

    // Users with 'access toolbar' permission can use the toolbar but cannot use
    // the preview so the module library should not be included but the cache
    // tags and contexts should be present.
    $toolbar_user = $this->drupalCreateUser(['access toolbar']);
    $this->drupalLogin($toolbar_user);

    $this->assertSession()->elementExists('xpath', $toolbar_xpath);
    $this->assertSession()->elementNotExists('xpath', $tab_xpath);
    $this->assertNoResponsivePreviewLibrary();
    $this->assertResponsivePreviewCachesTagAndContexts();

    // Users with 'access responsive preview' permission can use the toolbar
    // and the preview so the module library and the cache tags and contexts
    // should be included.
    $this->drupalLogin($this->previewUser);

    $this->assertSession()->elementExists('xpath', $toolbar_xpath);
    $this->assertSession()->elementExists('xpath', $tab_xpath);
    $this->assertResponsivePreviewLibrary();
    $this->assertResponsivePreviewCachesTagAndContexts();
    $this->assertDeviceListEquals($devices);

    // Login as user with 'administer responsive preview' permission so you
    // can check the preview behaviour on administrative page. The preview on
    // the admin pages sould not be enabled so the module library should not be
    // included but the cache tags and contexts should be present.
    $admin_user = $this->drupalCreateUser([
      'access responsive preview',
      'access toolbar',
      'administer responsive preview',
    ]);
    $this->drupalLogin($admin_user);

    $this->drupalGet('admin/config/user-interface/responsive-preview');
    $this->assertSession()->elementExists('xpath', $toolbar_xpath);
    $this->assertSession()->elementNotExists('xpath', $tab_xpath);
    $this->assertNoResponsivePreviewLibrary();
    $this->assertResponsivePreviewCachesTagAndContexts();

    $this->drupalGet('');
    $this->assertSession()->elementExists('xpath', $toolbar_xpath);
    $this->assertSession()->elementExists('xpath', $tab_xpath);
    $this->assertResponsivePreviewLibrary();
    $this->assertResponsivePreviewCachesTagAndContexts();
    $this->assertDeviceListEquals($devices);
  }

  /**
   * Tests cache invalidation.
   */
  public function testCacheInvalidation() {
    $device_storage = \Drupal::entityTypeManager()
      ->getStorage('responsive_preview_device');

    $device_ids = array_keys($this->getDefaultDevices(TRUE));
    $devices = array_combine($device_ids, $device_ids);

    $this->drupalLogin($this->previewUser);

    // Initially only the default enabled devices should appear in the list.
    $this->drupalGet('');
    $this->assertDeviceListEquals($devices);

    // Update a device should invalidate the cache, so you should get
    // the updated device list.
    $devices['large'] = 'large';
    $device_storage->load('large')->setStatus(1)->save();

    $this->drupalGet('');
    $this->assertDeviceListEquals($devices);

    // Add a device should invalidate the cache, so you should get
    // the updated device list.
    $devices['new_device'] = 'new_device';
    $device_storage->create([
      'id' => 'new_device',
      'label' => 'Hello, I am new!',
      'status' => '1',
      'orientation' => 'landscape',
      'dimensions' => [
        'width' => '1600',
        'height' => '2850',
        'dppx' => '2.5',
      ],
    ])->save();

    $this->drupalGet('');
    $this->assertDeviceListEquals($devices);

    // Delete a device should invalidate the cache, so you should get
    // the updated device list.
    unset($devices['large'], $devices['new_device']);

    $entities = $device_storage->loadMultiple(['new_device', 'large']);
    $device_storage->delete($entities);

    $this->drupalGet('');
    $this->assertDeviceListEquals($devices);
  }

}
