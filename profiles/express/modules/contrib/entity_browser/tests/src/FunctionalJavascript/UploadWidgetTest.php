<?php

namespace Drupal\Tests\entity_browser\FunctionalJavascript;

use Drupal\user\Entity\Role;

/**
 * Tests the Upload Widget.
 *
 * @group entity_browser
 */
class UploadWidgetTest extends EntityBrowserJavascriptTestBase {

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    // Grant permission to this user to use also the EB page we are testing.
    /** @var \Drupal\user\RoleInterface $role */
    $role = Role::load('authenticated');
    $this->grantPermissions($role, ['access test_entity_browser_standalone_upload entity browser pages']);

  }

  /**
   * Tests Entity Browser upload widget.
   */
  public function testUploadWidget() {

    /** @var \Drupal\entity_browser\EntityBrowserInterface $browser */
    $browser = $this->container->get('entity_type.manager')
      ->getStorage('entity_browser')
      ->load('test_entity_browser_standalone_upload');

    $page = $this->getSession()->getPage();

    // Make sure the test file is not present beforehand.
    $this->assertFileNotExists('public://druplicon.png');

    // Go to the widget standalone page and test the upload.
    $this->drupalGet($browser->getDisplay()->path());
    $page->attachFileToField('edit-upload-upload', \Drupal::root() . '/core/misc/druplicon.png');
    $this->waitForAjaxToFinish();
    $this->assertSession()->fieldExists('druplicon.png');
    $page->pressButton('Select files');
    $this->assertSession()->statusCodeEquals(200);

    // Check if the file was correctly uploaded to the EB destination.
    $this->assertFileExists('public://druplicon.png');

    // Now change upload location and submit label and check again.
    $widget = $browser->getWidget('2dc1ab07-2f8f-42c9-aab7-7eef7f8b7d87');
    $config = $widget->getConfiguration();
    $config['settings']['upload_location'] = 'public://some_location';
    $config['settings']['submit_text'] = 'Fancy submit';
    $widget->setConfiguration($config);
    $browser->save();

    $this->drupalGet($browser->getDisplay()->path());
    $page->attachFileToField('edit-upload-upload', \Drupal::root() . '/core/misc/druplicon.png');
    $this->waitForAjaxToFinish();
    $this->assertSession()->fieldExists('druplicon.png');
    $page->pressButton('Fancy submit');
    $this->assertSession()->statusCodeEquals(200);

    // Check if the file was correctly uploaded to the EB destination.
    $this->assertFileExists('public://some_location/druplicon.png');

  }

}
