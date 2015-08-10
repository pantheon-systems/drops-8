<?php

/**
 * @file
 * Contains \Drupal\file\Tests\FileFieldWidgetTest.
 */

namespace Drupal\file\Tests;

use Drupal\comment\Entity\Comment;
use Drupal\comment\Tests\CommentTestTrait;
use Drupal\field\Entity\FieldConfig;
use Drupal\field_ui\Tests\FieldUiTestTrait;
use Drupal\user\RoleInterface;
use Drupal\file\Entity\File;

/**
 * Tests the file field widget, single and multi-valued, with and without AJAX,
 * with public and private files.
 *
 * @group file
 */
class FileFieldWidgetTest extends FileFieldTestBase {

  use CommentTestTrait;
  use FieldUiTestTrait;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->drupalPlaceBlock('system_breadcrumb_block');
  }

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('comment', 'block');

  /**
   * Tests upload and remove buttons for a single-valued File field.
   */
  function testSingleValuedWidget() {
    $node_storage = $this->container->get('entity.manager')->getStorage('node');
    $type_name = 'article';
    $field_name = strtolower($this->randomMachineName());
    $this->createFileField($field_name, 'node', $type_name);

    $test_file = $this->getTestFile('text');

    foreach (array('nojs', 'js') as $type) {
      // Create a new node with the uploaded file and ensure it got uploaded
      // successfully.
      // @todo This only tests a 'nojs' submission, because drupalPostAjaxForm()
      //   does not yet support file uploads.
      $nid = $this->uploadNodeFile($test_file, $field_name, $type_name);
      $node_storage->resetCache(array($nid));
      $node = $node_storage->load($nid);
      $node_file = File::load($node->{$field_name}->target_id);
      $this->assertFileExists($node_file, 'New file saved to disk on node creation.');

      // Ensure the file can be downloaded.
      $this->drupalGet(file_create_url($node_file->getFileUri()));
      $this->assertResponse(200, 'Confirmed that the generated URL is correct by downloading the shipped file.');

      // Ensure the edit page has a remove button instead of an upload button.
      $this->drupalGet("node/$nid/edit");
      $this->assertNoFieldByXPath('//input[@type="submit"]', t('Upload'), 'Node with file does not display the "Upload" button.');
      $this->assertFieldByXpath('//input[@type="submit"]', t('Remove'), 'Node with file displays the "Remove" button.');

      // "Click" the remove button (emulating either a nojs or js submission).
      switch ($type) {
        case 'nojs':
          $this->drupalPostForm(NULL, array(), t('Remove'));
          break;
        case 'js':
          $button = $this->xpath('//input[@type="submit" and @value="' . t('Remove') . '"]');
          $this->drupalPostAjaxForm(NULL, array(), array((string) $button[0]['name'] => (string) $button[0]['value']));
          break;
      }

      // Ensure the page now has an upload button instead of a remove button.
      $this->assertNoFieldByXPath('//input[@type="submit"]', t('Remove'), 'After clicking the "Remove" button, it is no longer displayed.');
      $this->assertFieldByXpath('//input[@type="submit"]', t('Upload'), 'After clicking the "Remove" button, the "Upload" button is displayed.');
      // Test label has correct 'for' attribute.
      $input = $this->xpath('//input[@name="files[' . $field_name . '_0]"]');
      $label = $this->xpath('//label[@for="' . (string) $input[0]['id'] . '"]');
      $this->assertTrue(isset($label[0]), 'Label for upload found.');

      // Save the node and ensure it does not have the file.
      $this->drupalPostForm(NULL, array(), t('Save and keep published'));
      $node_storage->resetCache(array($nid));
      $node = $node_storage->load($nid);
      $this->assertTrue(empty($node->{$field_name}->target_id), 'File was successfully removed from the node.');
    }
  }

  /**
   * Tests upload and remove buttons for multiple multi-valued File fields.
   */
  function testMultiValuedWidget() {
    $node_storage = $this->container->get('entity.manager')->getStorage('node');
    $type_name = 'article';
    // Use explicit names instead of random names for those fields, because of a
    // bug in drupalPostForm() with multiple file uploads in one form, where the
    // order of uploads depends on the order in which the upload elements are
    // added to the $form (which, in the current implementation of
    // FileStorage::listAll(), comes down to the alphabetical order on field
    // names).
    $field_name = 'test_file_field_1';
    $field_name2 = 'test_file_field_2';
    $cardinality = 3;
    $this->createFileField($field_name, 'node', $type_name, array('cardinality' => $cardinality));
    $this->createFileField($field_name2, 'node', $type_name, array('cardinality' => $cardinality));

    $test_file = $this->getTestFile('text');

    foreach (array('nojs', 'js') as $type) {
      // Visit the node creation form, and upload 3 files for each field. Since
      // the field has cardinality of 3, ensure the "Upload" button is displayed
      // until after the 3rd file, and after that, isn't displayed. Because
      // SimpleTest triggers the last button with a given name, so upload to the
      // second field first.
      // @todo This is only testing a non-Ajax upload, because drupalPostAjaxForm()
      //   does not yet emulate jQuery's file upload.
      //
      $this->drupalGet("node/add/$type_name");
      foreach (array($field_name2, $field_name) as $each_field_name) {
        for ($delta = 0; $delta < 3; $delta++) {
          $edit = array('files[' . $each_field_name . '_' . $delta . '][]' => drupal_realpath($test_file->getFileUri()));
          // If the Upload button doesn't exist, drupalPostForm() will automatically
          // fail with an assertion message.
          $this->drupalPostForm(NULL, $edit, t('Upload'));
        }
      }
      $this->assertNoFieldByXpath('//input[@type="submit"]', t('Upload'), 'After uploading 3 files for each field, the "Upload" button is no longer displayed.');

      $num_expected_remove_buttons = 6;

      foreach (array($field_name, $field_name2) as $current_field_name) {
        // How many uploaded files for the current field are remaining.
        $remaining = 3;
        // Test clicking each "Remove" button. For extra robustness, test them out
        // of sequential order. They are 0-indexed, and get renumbered after each
        // iteration, so array(1, 1, 0) means:
        // - First remove the 2nd file.
        // - Then remove what is then the 2nd file (was originally the 3rd file).
        // - Then remove the first file.
        foreach (array(1,1,0) as $delta) {
          // Ensure we have the expected number of Remove buttons, and that they
          // are numbered sequentially.
          $buttons = $this->xpath('//input[@type="submit" and @value="Remove"]');
          $this->assertTrue(is_array($buttons) && count($buttons) === $num_expected_remove_buttons, format_string('There are %n "Remove" buttons displayed (JSMode=%type).', array('%n' => $num_expected_remove_buttons, '%type' => $type)));
          foreach ($buttons as $i => $button) {
            $key = $i >= $remaining ? $i - $remaining : $i;
            $check_field_name = $field_name2;
            if ($current_field_name == $field_name && $i < $remaining) {
              $check_field_name = $field_name;
            }

            $this->assertIdentical((string) $button['name'], $check_field_name . '_' . $key. '_remove_button');
          }

          // "Click" the remove button (emulating either a nojs or js submission).
          $button_name = $current_field_name . '_' . $delta . '_remove_button';
          switch ($type) {
            case 'nojs':
              // drupalPostForm() takes a $submit parameter that is the value of the
              // button whose click we want to emulate. Since we have multiple
              // buttons with the value "Remove", and want to control which one we
              // use, we change the value of the other ones to something else.
              // Since non-clicked buttons aren't included in the submitted POST
              // data, and since drupalPostForm() will result in $this being updated
              // with a newly rebuilt form, this doesn't cause problems.
              foreach ($buttons as $button) {
                if ($button['name'] != $button_name) {
                  $button['value'] = 'DUMMY';
                }
              }
              $this->drupalPostForm(NULL, array(), t('Remove'));
              break;
            case 'js':
              // drupalPostAjaxForm() lets us target the button precisely, so we don't
              // require the workaround used above for nojs.
              $this->drupalPostAjaxForm(NULL, array(), array($button_name => t('Remove')));
              break;
          }
          $num_expected_remove_buttons--;
          $remaining--;

          // Ensure an "Upload" button for the current field is displayed with the
          // correct name.
          $upload_button_name = $current_field_name . '_' . $remaining . '_upload_button';
          $buttons = $this->xpath('//input[@type="submit" and @value="Upload" and @name=:name]', array(':name' => $upload_button_name));
          $this->assertTrue(is_array($buttons) && count($buttons) == 1, format_string('The upload button is displayed with the correct name (JSMode=%type).', array('%type' => $type)));

          // Ensure only at most one button per field is displayed.
          $buttons = $this->xpath('//input[@type="submit" and @value="Upload"]');
          $expected = $current_field_name == $field_name ? 1 : 2;
          $this->assertTrue(is_array($buttons) && count($buttons) == $expected, format_string('After removing a file, only one "Upload" button for each possible field is displayed (JSMode=%type).', array('%type' => $type)));
        }
      }

      // Ensure the page now has no Remove buttons.
      $this->assertNoFieldByXPath('//input[@type="submit"]', t('Remove'), format_string('After removing all files, there is no "Remove" button displayed (JSMode=%type).', array('%type' => $type)));

      // Save the node and ensure it does not have any files.
      $this->drupalPostForm(NULL, array('title[0][value]' => $this->randomMachineName()), t('Save and publish'));
      $matches = array();
      preg_match('/node\/([0-9]+)/', $this->getUrl(), $matches);
      $nid = $matches[1];
      $node_storage->resetCache(array($nid));
      $node = $node_storage->load($nid);
      $this->assertTrue(empty($node->{$field_name}->target_id), 'Node was successfully saved without any files.');
    }

    $upload_files = array($test_file, $test_file);
    // Try to upload multiple files, but fewer than the maximum.
    $nid = $this->uploadNodeFiles($upload_files, $field_name, $type_name);
    $node_storage->resetCache(array($nid));
    $node = $node_storage->load($nid);
    $this->assertEqual(count($node->{$field_name}), count($upload_files), 'Node was successfully saved with mulitple files.');

    // Try to upload more files than allowed on revision.
    $this->uploadNodeFiles($upload_files, $field_name, $nid, 1);
    $args = array(
      '%field' => $field_name,
      '@count' => $cardinality
    );
    $this->assertRaw(t('%field: this field cannot hold more than @count values.', $args));
    $node_storage->resetCache(array($nid));
    $node = $node_storage->load($nid);
    $this->assertEqual(count($node->{$field_name}), count($upload_files), 'More files than allowed could not be saved to node.');

    // Try to upload exactly the allowed number of files on revision.
    $this->uploadNodeFile($test_file, $field_name, $nid, 1);
    $node_storage->resetCache(array($nid));
    $node = $node_storage->load($nid);
    $this->assertEqual(count($node->{$field_name}), $cardinality, 'Node was successfully revised to maximum number of files.');

    // Try to upload exactly the allowed number of files, new node.
    $upload_files[] = $test_file;
    $nid = $this->uploadNodeFiles($upload_files, $field_name, $type_name);
    $node_storage->resetCache(array($nid));
    $node = $node_storage->load($nid);
    $this->assertEqual(count($node->{$field_name}), $cardinality, 'Node was successfully saved with maximum number of files.');

    // Try to upload more files than allowed, new node.
    $upload_files[] = $test_file;
    $this->uploadNodeFiles($upload_files, $field_name, $type_name);

    $args = [
      '%field' => $field_name,
      '@max' => $cardinality,
      '@count' => count($upload_files),
      '%list' => $test_file->getFileName(),
    ];
    $this->assertRaw(t('Field %field can only hold @max values but there were @count uploaded. The following files have been omitted as a result: %list.', $args));
  }

  /**
   * Tests a file field with a "Private files" upload destination setting.
   */
  function testPrivateFileSetting() {
    $node_storage = $this->container->get('entity.manager')->getStorage('node');
    // Grant the admin user required permissions.
    user_role_grant_permissions($this->adminUser->roles[0]->target_id, array('administer node fields'));

    $type_name = 'article';
    $field_name = strtolower($this->randomMachineName());
    $this->createFileField($field_name, 'node', $type_name);
    $field = FieldConfig::loadByName('node', $type_name, $field_name);
    $field_id = $field->id();

    $test_file = $this->getTestFile('text');

    // Change the field setting to make its files private, and upload a file.
    $edit = array('settings[uri_scheme]' => 'private');
    $this->drupalPostForm("admin/structure/types/manage/$type_name/fields/$field_id/storage", $edit, t('Save field settings'));
    $nid = $this->uploadNodeFile($test_file, $field_name, $type_name);
    $node_storage->resetCache(array($nid));
    $node = $node_storage->load($nid);
    $node_file = File::load($node->{$field_name}->target_id);
    $this->assertFileExists($node_file, 'New file saved to disk on node creation.');

    // Ensure the private file is available to the user who uploaded it.
    $this->drupalGet(file_create_url($node_file->getFileUri()));
    $this->assertResponse(200, 'Confirmed that the generated URL is correct by downloading the shipped file.');

    // Ensure we can't change 'uri_scheme' field settings while there are some
    // entities with uploaded files.
    $this->drupalGet("admin/structure/types/manage/$type_name/fields/$field_id/storage");
    $this->assertFieldByXpath('//input[@id="edit-settings-uri-scheme-public" and @disabled="disabled"]', 'public', 'Upload destination setting disabled.');

    // Delete node and confirm that setting could be changed.
    $node->delete();
    $this->drupalGet("admin/structure/types/manage/$type_name/fields/$field_id/storage");
    $this->assertFieldByXpath('//input[@id="edit-settings-uri-scheme-public" and not(@disabled)]', 'public', 'Upload destination setting enabled.');
  }

  /**
   * Tests that download restrictions on private files work on comments.
   */
  function testPrivateFileComment() {
    $user = $this->drupalCreateUser(array('access comments'));

    // Grant the admin user required comment permissions.
    $roles = $this->adminUser->getRoles();
    user_role_grant_permissions($roles[1], array('administer comment fields', 'administer comments'));

    // Revoke access comments permission from anon user, grant post to
    // authenticated.
    user_role_revoke_permissions(RoleInterface::ANONYMOUS_ID, array('access comments'));
    user_role_grant_permissions(RoleInterface::AUTHENTICATED_ID, array('post comments', 'skip comment approval'));

    // Create a new field.
    $this->addDefaultCommentField('node', 'article');

    $name = strtolower($this->randomMachineName());
    $label = $this->randomMachineName();
    $storage_edit = array('settings[uri_scheme]' => 'private');
    $this->fieldUIAddNewField('admin/structure/comment/manage/comment', $name, $label, 'file', $storage_edit);

    // Manually clear cache on the tester side.
    \Drupal::entityManager()->clearCachedFieldDefinitions();

    // Create node.
    $edit = array(
      'title[0][value]' => $this->randomMachineName(),
    );
    $this->drupalPostForm('node/add/article', $edit, t('Save and publish'));
    $node = $this->drupalGetNodeByTitle($edit['title[0][value]']);

    // Add a comment with a file.
    $text_file = $this->getTestFile('text');
    $edit = array(
      'files[field_' . $name . '_' . 0 . ']' => drupal_realpath($text_file->getFileUri()),
      'comment_body[0][value]' => $comment_body = $this->randomMachineName(),
    );
    $this->drupalPostForm('node/' . $node->id(), $edit, t('Save'));

    // Get the comment ID.
    preg_match('/comment-([0-9]+)/', $this->getUrl(), $matches);
    $cid = $matches[1];

    // Log in as normal user.
    $this->drupalLogin($user);

    $comment = Comment::load($cid);
    $comment_file = $comment->{'field_' . $name}->entity;
    $this->assertFileExists($comment_file, 'New file saved to disk on node creation.');
    // Test authenticated file download.
    $url = file_create_url($comment_file->getFileUri());
    $this->assertNotEqual($url, NULL, 'Confirmed that the URL is valid');
    $this->drupalGet(file_create_url($comment_file->getFileUri()));
    $this->assertResponse(200, 'Confirmed that the generated URL is correct by downloading the shipped file.');

    // Test anonymous file download.
    $this->drupalLogout();
    $this->drupalGet(file_create_url($comment_file->getFileUri()));
    $this->assertResponse(403, 'Confirmed that access is denied for the file without the needed permission.');

    // Unpublishes node.
    $this->drupalLogin($this->adminUser);
    $this->drupalPostForm('node/' . $node->id() . '/edit', array(), t('Save and unpublish'));

    // Ensures normal user can no longer download the file.
    $this->drupalLogin($user);
    $this->drupalGet(file_create_url($comment_file->getFileUri()));
    $this->assertResponse(403, 'Confirmed that access is denied for the file without the needed permission.');
  }

  /**
   * Tests validation with the Upload button.
   */
  function testWidgetValidation() {
    $type_name = 'article';
    $field_name = strtolower($this->randomMachineName());
    $this->createFileField($field_name, 'node', $type_name);
    $this->updateFileField($field_name, $type_name, array('file_extensions' => 'txt'));

    foreach (array('nojs', 'js') as $type) {
      // Create node and prepare files for upload.
      $node = $this->drupalCreateNode(array('type' => 'article'));
      $nid = $node->id();
      $this->drupalGet("node/$nid/edit");
      $test_file_text = $this->getTestFile('text');
      $test_file_image = $this->getTestFile('image');
      $name = 'files[' . $field_name . '_0]';

      // Upload file with incorrect extension, check for validation error.
      $edit[$name] = drupal_realpath($test_file_image->getFileUri());
      switch ($type) {
        case 'nojs':
          $this->drupalPostForm(NULL, $edit, t('Upload'));
          break;
        case 'js':
          $button = $this->xpath('//input[@type="submit" and @value="' . t('Upload') . '"]');
          $this->drupalPostAjaxForm(NULL, $edit, array((string) $button[0]['name'] => (string) $button[0]['value']));
          break;
      }
      $error_message = t('Only files with the following extensions are allowed: %files-allowed.', array('%files-allowed' => 'txt'));
      $this->assertRaw($error_message, t('Validation error when file with wrong extension uploaded (JSMode=%type).', array('%type' => $type)));

      // Upload file with correct extension, check that error message is removed.
      $edit[$name] = drupal_realpath($test_file_text->getFileUri());
      switch ($type) {
        case 'nojs':
          $this->drupalPostForm(NULL, $edit, t('Upload'));
          break;
        case 'js':
          $button = $this->xpath('//input[@type="submit" and @value="' . t('Upload') . '"]');
          $this->drupalPostAjaxForm(NULL, $edit, array((string) $button[0]['name'] => (string) $button[0]['value']));
          break;
      }
      $this->assertNoRaw($error_message, t('Validation error removed when file with correct extension uploaded (JSMode=%type).', array('%type' => $type)));
    }
  }
}
