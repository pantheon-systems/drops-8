<?php

namespace Drupal\media_entity\Tests;

use Drupal\Component\Utility\Xss;
use Drupal\media_entity\Entity\Media;
use Drupal\media_entity\MediaInterface;
use Drupal\simpletest\WebTestBase;

/**
 * Ensures that media UI work correctly.
 *
 * @group media_entity
 */
class MediaUITest extends WebTestBase {

  use MediaTestTrait;

  /**
   * The test user.
   *
   * @var \Drupal\User\UserInterface
   */
  protected $adminUser;

  /**
   * A non-admin test user.
   *
   * @var \Drupal\User\UserInterface
   */
  protected $nonAdminUser;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'media_entity',
    'field_ui',
    'views_ui',
    'node',
    'block',
    'entity',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->drupalPlaceBlock('local_actions_block');
    $this->drupalPlaceBlock('local_tasks_block');
    $this->adminUser = $this->drupalCreateUser([
      'administer media',
      'administer media fields',
      'administer media form display',
      'administer media display',
      'administer media bundles',
      // Media entity permissions.
      'view media',
      'create media',
      'update media',
      'update any media',
      'delete media',
      'delete any media',
      'access media overview',
      // Other permissions.
      'administer views',
      'access content overview',
      'view all revisions',
    ]);
    $this->drupalLogin($this->adminUser);

    $this->nonAdminUser = $this->drupalCreateUser([
      // Media entity permissions.
      'view media',
      'create media',
      'update media',
      'update any media',
      'delete media',
      'delete any media',
      'access media overview',
      // Other permissions.
      'administer views',
      'access content overview',
    ]);
  }

  /**
   * Tests a media bundle administration.
   */
  public function testMediaBundles() {
    $this->container->get('module_installer')->install(['media_entity_test_type']);

    // Test and create one media bundle.
    $bundle = $this->createMediaBundle();
    $bundle_id = $bundle['id'];
    unset($bundle['id']);

    // Check if all action links exist.
    $this->assertLinkByHref('admin/structure/media/add');
    $this->assertLinkByHref('admin/structure/media/manage/' . $bundle_id . '/fields');
    $this->assertLinkByHref('admin/structure/media/manage/' . $bundle_id . '/form-display');
    $this->assertLinkByHref('admin/structure/media/manage/' . $bundle_id . '/display');

    // Assert that fields have expected values before editing.
    $this->drupalGet('admin/structure/media/manage/' . $bundle_id);
    $this->assertFieldByName('label', $bundle['label'], 'Label field has correct value.');
    $this->assertFieldByName('description', $bundle['description'], 'Description field has a correct value.');
    $this->assertFieldByName('type', $bundle['type'], 'Generic plugin is selected.');
    $this->assertNoFieldChecked('edit-options-new-revision', 'Revision checkbox is not checked.');
    $this->assertFieldChecked('edit-options-status', 'Status checkbox is checked.');
    $this->assertNoFieldChecked('edit-options-queue-thumbnail-downloads', 'Queue thumbnail checkbox is not checked.');
    $this->assertText('Create new revision', 'Revision checkbox label found.');
    $this->assertText('Automatically create a new revision of media entities. Users with the Administer media permission will be able to override this option.', 'Revision help text found');
    $this->assertText('Download thumbnails via a queue.', 'Queue thumbnails help text found');
    $this->assertText('Entities will be automatically published when they are created.', 'Published help text found');
    $this->assertText("This type provider doesn't need configuration.");
    $this->assertText('No metadata fields available.');
    $this->assertText('Media type plugins can provide metadata fields such as title, caption, size information, credits, ... Media entity can automatically save this metadata information to entity fields, which can be configured below. Information will only be mapped if the entity field is empty.');

    // Try to change media type and check if new configuration sub-form appears.
    $commands = $this->drupalPostAjaxForm(NULL, ['type' => 'test_type'], 'type');
    // WebTestBase::drupalProcessAjaxResponse() won't correctly execute our ajax
    // commands so we have to do it manually. Code below is based on the logic
    // in that function.
    $content = $this->content;
    $dom = new \DOMDocument();
    @$dom->loadHTML($content);
    $xpath = new \DOMXPath($dom);
    foreach ($commands as $command) {
      if ($command['command'] == 'insert' && $command['method'] == 'replaceWith') {
        $wrapperNode = $xpath->query('//*[@id="' . ltrim($command['selector'], '#') . '"]')->item(0);
        $newDom = new \DOMDocument();
        @$newDom->loadHTML('<div>' . $command['data'] . '</div>');
        $newNode = @$dom->importNode($newDom->documentElement->firstChild->firstChild, TRUE);
        $wrapperNode->parentNode->replaceChild($newNode, $wrapperNode);
        $content = $dom->saveHTML();
        $this->setRawContent($content);
      }
    }
    $this->assertFieldByName('type_configuration[test_type][test_config_value]', 'This is default value.');
    $this->assertText('Field 1', 'First metadata field found.');
    $this->assertText('Field 2', 'Second metadata field found.');
    $this->assertFieldByName('field_mapping[field_1]', '_none', 'First metadata field is not mapped by default.');
    $this->assertFieldByName('field_mapping[field_2]', '_none', 'Second metadata field is not mapped by default.');

    // Test if the edit machine name button is disabled.
    $elements = $this->xpath('//*[@id="edit-label-machine-name-suffix"]/span[@class="admin-link"]');
    $this->assertTrue(empty($elements), 'Edit machine name not found.');

    // Edit and save media bundle form fields with new values.
    $bundle['label'] = $this->randomMachineName();
    $bundle['description'] = $this->randomMachineName();
    $bundle['type'] = 'test_type';
    $bundle['type_configuration[test_type][test_config_value]'] = 'This is new config value.';
    $bundle['field_mapping[field_1]'] = 'name';
    $bundle['options[new_revision]'] = TRUE;
    $bundle['options[status]'] = FALSE;
    $bundle['options[queue_thumbnail_downloads]'] = TRUE;

    $this->drupalPostForm(NULL, $bundle, t('Save media bundle'));

    // Test if edit worked and if new field values have been saved as expected.
    $this->drupalGet('admin/structure/media/manage/' . $bundle_id);
    $this->assertFieldByName('label', $bundle['label'], 'Label field has correct value.');
    $this->assertFieldByName('description', $bundle['description'], 'Description field has correct value.');
    $this->assertFieldByName('type', $bundle['type'], 'Test type is selected.');
    $this->assertFieldChecked('edit-options-new-revision', 'Revision checkbox is checked.');
    $this->assertFieldChecked('edit-options-queue-thumbnail-downloads', 'Queue thumbnail checkbox is checked.');
    $this->assertNoFieldChecked('edit-options-status', 'Status checkbox is not checked.');
    $this->assertFieldByName('type_configuration[test_type][test_config_value]', 'This is new config value.');
    $this->assertText('Field 1', 'First metadata field found.');
    $this->assertText('Field 2', 'Second metadata field found.');
    $this->assertFieldByName('field_mapping[field_1]', 'name', 'First metadata field is mapped to the name field.');
    $this->assertFieldByName('field_mapping[field_2]', '_none', 'Second metadata field is not mapped.');

    /** @var \Drupal\media_entity\MediaBundleInterface $loaded_bundle */
    $loaded_bundle = $this->container->get('entity_type.manager')
      ->getStorage('media_bundle')
      ->load($bundle_id);
    $this->assertEqual($loaded_bundle->id(), $bundle_id, 'Media bundle ID saved correctly.');
    $this->assertEqual($loaded_bundle->label(), $bundle['label'], 'Media bundle label saved correctly.');
    $this->assertEqual($loaded_bundle->getDescription(), $bundle['description'], 'Media bundle description saved correctly.');
    $this->assertEqual($loaded_bundle->getType()->getPluginId(), $bundle['type'], 'Media bundle type saved correctly.');
    $this->assertEqual($loaded_bundle->getType()->getConfiguration()['test_config_value'], $bundle['type_configuration[test_type][test_config_value]'], 'Media bundle type configuration saved correctly.');
    $this->assertTrue($loaded_bundle->shouldCreateNewRevision(), 'New revisions are configured to be created.');
    $this->assertTrue($loaded_bundle->getQueueThumbnailDownloads(), 'Thumbnails are created through queues.');
    $this->assertFalse($loaded_bundle->getStatus(), 'Default status is unpublished.');
    $this->assertEqual($loaded_bundle->field_map, ['field_1' => $bundle['field_mapping[field_1]']], 'Field mapping was saved correctly.');

    // Test that a media being created with default status to "FALSE" will be
    // created unpublished.
    /** @var MediaInterface $unpublished_media */
    $unpublished_media = Media::create(['name' => 'unpublished test media', 'bundle' => $loaded_bundle->id()]);
    $this->assertFalse($unpublished_media->isPublished(), 'Unpublished media correctly created.');

    // Tests media bundle delete form.
    $this->clickLink(t('Delete'));
    $this->assertUrl('admin/structure/media/manage/' . $bundle_id . '/delete');
    $this->drupalPostForm(NULL, [], t('Delete'));
    $this->assertUrl('admin/structure/media');
    $this->assertRaw(t('The media bundle %name has been deleted.', ['%name' => $bundle['label']]));
    $this->assertNoRaw(Xss::filterAdmin($bundle['description']));
    // Test bundle delete prevention when there is existing media.
    $bundle2 = $this->createMediaBundle();
    $media = Media::create(['name' => 'lorem ipsum', 'bundle' => $bundle2['id']]);
    $media->save();
    $this->drupalGet('admin/structure/media/manage/' . $bundle2['id']);
    $this->clickLink(t('Delete'));
    $this->assertUrl('admin/structure/media/manage/' . $bundle2['id'] . '/delete');
    $this->assertNoFieldById('edit-submit');
    $this->assertRaw(t('%type is used by 1 piece of content on your site. You can not remove this content type until you have removed all of the %type content.', ['%type' => $bundle2['label']]));
  }

  /**
   * Tests the media actions (add/edit/delete).
   */
  public function testMediaWithOnlyOneBundle() {
    /** @var \Drupal\media_entity\MediaBundleInterface $bundle */
    $bundle = $this->drupalCreateMediaBundle();

    // Assert that media item list is empty.
    $this->drupalGet('admin/content/media');
    $this->assertResponse(200);
    $this->assertText('No content available.');

    $this->drupalGet('media/add');
    $this->assertResponse(200);
    $this->assertUrl('media/add/' . $bundle->id());
    $this->assertFieldChecked('edit-revision', 'New revision should always be created when a new entity is being created.');

    // Tests media item add form.
    $edit = [
      'name[0][value]' => $this->randomMachineName(),
      'revision_log' => $this->randomString(),
    ];
    $this->drupalPostForm('media/add', $edit, t('Save and publish'));
    $this->assertTitle($edit['name[0][value]'] . ' | Drupal');
    $media_id = $this->container->get('entity.query')->get('media')->execute();
    $media_id = reset($media_id);
    /** @var \Drupal\media_entity\MediaInterface $media */
    $media = $this->container->get('entity_type.manager')
      ->getStorage('media')
      ->loadUnchanged($media_id);
    $this->assertEqual($media->getRevisionLogMessage(), $edit['revision_log'], 'Revision log was saved.');

    // Test if the media list contains exactly 1 media bundle.
    $this->drupalGet('admin/content/media');
    $this->assertResponse(200);
    $this->assertText($edit['name[0][value]']);

    // Tests media edit form.
    $this->drupalGet('media/' . $media_id . '/edit');
    $this->assertNoFieldChecked('edit-revision', 'New revisions are disabled by default.');
    $edit['name[0][value]'] = $this->randomMachineName();
    $this->drupalPostForm(NULL, $edit, t('Save and keep published'));
    $this->assertTitle($edit['name[0][value]'] . ' | Drupal');

    // Assert that the media list updates after an edit.
    $this->drupalGet('admin/content/media');
    $this->assertResponse(200);
    $this->assertText($edit['name[0][value]']);

    // Test that there is no empty vertical tabs element, if the container is
    // empty (see #2750697).
    // Make the "Publisher ID" and "Created" fields hidden.
    $edit = [
      'fields[created][parent]' => 'hidden',
      'fields[uid][parent]' => 'hidden',
    ];
    $this->drupalPostForm('/admin/structure/media/manage/' . $bundle->id . '/form-display', $edit, t('Save'));
    // Assure we are testing with a user without permission to manage revisions.
    $this->drupalLogout();
    $this->drupalLogin($this->nonAdminUser);
    // Check the container is not present.
    $this->drupalGet('media/' . $media_id . '/edit');
    // An empty tab container would look like this.
    $raw_html = '<div data-drupal-selector="edit-advanced" data-vertical-tabs-panes><input class="vertical-tabs__active-tab" data-drupal-selector="edit-advanced-active-tab" type="hidden" name="advanced__active_tab" value="" />' . "\n" . '</div>';
    $this->assertNoRaw($raw_html);
    // Continue testing as admin.
    $this->drupalLogout();
    $this->drupalLogin($this->adminUser);

    // Enable revisions by default.
    $bundle->setNewRevision(TRUE);
    $bundle->save();
    $this->drupalGet('media/' . $media_id . '/edit');
    $this->assertFieldChecked('edit-revision', 'New revisions are disabled by default.');
    $edit = [
      'name[0][value]' => $this->randomMachineName(),
      'revision_log' => $this->randomString(),
    ];
    $this->drupalPostForm(NULL, $edit, t('Save and keep published'));
    $this->assertTitle($edit['name[0][value]'] . ' | Drupal');
    /** @var \Drupal\media_entity\MediaInterface $media */
    $media = $this->container->get('entity_type.manager')
      ->getStorage('media')
      ->loadUnchanged($media_id);
    $this->assertEqual($media->getRevisionLogMessage(), $edit['revision_log'], 'Revision log was saved.');

    // Tests media delete form.
    $this->drupalPostForm('media/' . $media_id . '/delete', [], t('Delete'));
    $media_id = \Drupal::entityQuery('media')->execute();
    $this->assertFalse($media_id);

    // Assert that the media list is empty after deleting the media item.
    $this->drupalGet('admin/content/media');
    $this->assertResponse(200);
    $this->assertNoText($edit['name[0][value]']);
    $this->assertText('No content available.');
  }

  /**
   * Tests the views wizards provided by the media module.
   */
  public function testMediaViewsWizard() {
    $bundle = $this->drupalCreateMediaBundle();
    $data = [
      'name' => $this->randomMachineName(),
      'bundle' => $bundle->id(),
      'type' => 'Unknown',
      'uid' => $this->adminUser->id(),
      'langcode' => \Drupal::languageManager()->getDefaultLanguage()->getId(),
      'status' => Media::PUBLISHED,
    ];
    $media = Media::create($data);
    $media->save();

    // Test the Media wizard.
    $this->drupalPostForm('admin/structure/views/add', [
      'label' => 'media view',
      'id' => 'media_test',
      'show[wizard_key]' => 'media',
      'page[create]' => 1,
      'page[title]' => 'media_test',
      'page[path]' => 'media_test',
    ], t('Save and edit'));

    $this->drupalGet('media_test');
    $this->assertText($data['name']);

    user_role_revoke_permissions('anonymous', ['access content']);
    $this->drupalLogout();
    $this->drupalGet('media_test');
    $this->assertResponse(403);

    $this->drupalLogin($this->adminUser);

    // Test the MediaRevision wizard.
    $this->drupalPostForm('admin/structure/views/add', [
      'label' => 'media revision view',
      'id' => 'media_revision',
      'show[wizard_key]' => 'media_revision',
      'page[create]' => 1,
      'page[title]' => 'media_revision',
      'page[path]' => 'media_revision',
    ], t('Save and edit'));

    $this->drupalGet('media_revision');
    // Check only for the label of the changed field as we want to only test
    // if the field is present and not its value.
    $this->assertText($data['name']);

    user_role_revoke_permissions('anonymous', ['view revisions']);
    $this->drupalLogout();
    $this->drupalGet('media_revision');
    $this->assertResponse(403);
  }

  /**
   * Tests the "media/add" and "admin/content/media" pages.
   *
   * Tests if the "media/add" page gives you a selecting option if there are
   * multiple media bundles available.
   */
  public function testMediaWithMultipleBundles() {
    // Test access to media overview page.
    $this->drupalLogout();
    $this->drupalGet('admin/content/media');
    $this->assertResponse(403);

    $this->drupalLogin($this->adminUser);
    $this->drupalGet('admin/content');

    // Test there is a media tab in the menu.
    $this->clickLink('Media');
    $this->assertResponse(200);
    $this->assertText('No content available.');

    // Tests and creates the first media bundle.
    $first_media_bundle = $this->createMediaBundle();

    // Test and create a second media bundle.
    $second_media_bundle = $this->createMediaBundle();

    // Test if media/add displays two media bundle options.
    $this->drupalGet('media/add');

    // Checks for the first media bundle.
    $this->assertRaw($first_media_bundle['label']);
    $this->assertRaw(Xss::filterAdmin($first_media_bundle['description']));

    // Checks for the second media bundle.
    $this->assertRaw($second_media_bundle['label']);
    $this->assertRaw(Xss::filterAdmin($second_media_bundle['description']));

    // Continue testing media bundle filter.
    $this->doTestMediaBundleFilter($first_media_bundle, $second_media_bundle);
  }

  /**
   * Creates and tests a new media bundle.
   *
   * @return array
   *   Returns the media bundle fields.
   */
  public function createMediaBundle() {
    // Generates and holds all media bundle fields.
    $name = $this->randomMachineName();
    $edit = [
      'id' => strtolower($name),
      'label' => $name,
      'type' => 'generic',
      'description' => $this->randomMachineName(),
    ];

    // Create new media bundle.
    $this->drupalPostForm('admin/structure/media/add', $edit, t('Save media bundle'));
    $this->assertText('The media bundle ' . $name . ' has been added.');

    // Check if media bundle is successfully created.
    $this->drupalGet('admin/structure/media');
    $this->assertResponse(200);
    $this->assertRaw($edit['label']);
    $this->assertRaw(Xss::filterAdmin($edit['description']));

    return $edit;
  }

  /**
   * Creates a media item in the media bundle that is passed along.
   *
   * @param array $media_bundle
   *   The media bundle the media item should be assigned to.
   *
   * @return array
   *   Returns the
   */
  public function createMediaItem($media_bundle) {
    // Define the media item name.
    $name = $this->randomMachineName();
    $edit = [
      'name[0][value]' => $name,
    ];
    // Save it and retrieve new media item ID, then return all information.
    $this->drupalPostForm('media/add/' . $media_bundle['id'], $edit, t('Save and publish'));
    $this->assertTitle($edit['name[0][value]'] . ' | Drupal');
    $media_id = \Drupal::entityQuery('media')->execute();
    $media_id = reset($media_id);
    $edit['id'] = $media_id;

    return $edit;
  }

  /**
   * Tests the media list filter functionality.
   */
  public function doTestMediaBundleFilter($first_media_bundle, $second_media_bundle) {
    // Assert that the list is not empty and contains at least 2 media items
    // with each a different media bundle.
    (is_array($first_media_bundle) && is_array($second_media_bundle) ?: $this->assertTrue(FALSE));

    $first_media_item = $this->createMediaItem($first_media_bundle);
    $second_media_item = $this->createMediaItem($second_media_bundle);

    // Go to media item list.
    $this->drupalGet('admin/content/media');
    $this->assertResponse(200);
    $this->assertLink('Add media');

    // Assert that all available media items are in the list.
    $this->assertText($first_media_item['name[0][value]']);
    $this->assertText($first_media_bundle['label']);
    $this->assertText($second_media_item['name[0][value]']);
    $this->assertText($second_media_bundle['label']);

    // Filter for each bundle and assert that the list has been updated.
    $this->drupalGet('admin/content/media', ['query' => ['provider' => $first_media_bundle['id']]]);
    $this->assertResponse(200);
    $this->assertText($first_media_item['name[0][value]']);
    $this->assertText($first_media_bundle['label']);
    $this->assertNoText($second_media_item['name[0][value]']);

    $this->drupalGet('admin/content/media', ['query' => ['provider' => $second_media_bundle['id']]]);
    $this->assertResponse(200);
    $this->assertNoText($first_media_item['name[0][value]']);
    $this->assertText($second_media_item['name[0][value]']);
    $this->assertText($second_media_bundle['label']);

    // Filter all and check for all items again.
    $this->drupalGet('admin/content/media', ['query' => ['provider' => 'All']]);
    $this->assertResponse(200);
    $this->assertText($first_media_item['name[0][value]']);
    $this->assertText($first_media_bundle['label']);
    $this->assertText($second_media_item['name[0][value]']);
    $this->assertText($second_media_bundle['label']);
  }

}
