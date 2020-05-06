<?php

namespace Drupal\Tests\webform\Functional\Element;

use Drupal\Core\Url;
use Drupal\file\Entity\File;
use Drupal\webform\Entity\Webform;
use Drupal\webform\Entity\WebformSubmission;

/**
 * Test for webform element managed file handling.
 *
 * @group Webform
 */
class WebformElementManagedFilePrivateTest extends WebformElementManagedFileTestBase {

  /**
   * Webforms to load.
   *
   * @var array
   */
  protected static $testWebforms = ['test_element_managed_file'];

  /**
   * Test private files.
   */
  public function testPrivateFiles() {
    $admin_submission_user = $this->drupalCreateUser([
      'administer webform submission',
    ]);

    $webform = Webform::load('test_element_managed_file');

    /**************************************************************************/

    $elements = $webform->getElementsDecoded();
    $elements['managed_file_single']['#uri_scheme'] = 'private';
    $webform->setElements($elements);
    $webform->save();

    $this->drupalLogin($admin_submission_user);

    // Upload private file as authenticated user.
    $edit = [
      'files[managed_file_single]' => \Drupal::service('file_system')->realpath($this->files[0]->uri),
    ];
    $sid = $this->postSubmission($webform, $edit);

    /** @var \Drupal\webform\WebformSubmissionInterface $submission */
    $submission = WebformSubmission::load($sid);

    /** @var \Drupal\file\Entity\File $file */
    $fid = $this->getLastFileId();
    $file = File::load($fid);

    // Check that test file 3 was uploaded to the current submission.
    $this->assertEqual($submission->getElementData('managed_file_single'), $fid, 'Test file 3 was upload to the current submission');

    // Check test file 3 file usage.
    $this->assertIdentical(['webform' => ['webform_submission' => [$sid => '1']]], $this->fileUsage->listUsage($file), 'The file has 3 usage.');

    // Check test file 3 uploaded file path.
    $this->assertEqual($file->getFileUri(), 'private://webform/test_element_managed_file/' . $sid . '/' . $this->files[0]->filename);

    // Check private file access allowed.
    $this->drupalGet(file_create_url($file->getFileUri()));
    $this->assertResponse(200);

    $this->drupalLogout();

    // Check private file access redirects to user login page with destination.
    $this->drupalGet(file_create_url($file->getFileUri()));
    $this->assertResponse(200);

    $destination_url = Url::fromUri('base://system/files', [
      'query' => [
        'file' => 'webform/test_element_managed_file/' . $sid . '/' . $this->files[0]->filename,
      ],
    ]);
    $this->assertUrl('user/login', [
      'query' => [
        'destination' => $destination_url->toString(),
      ],
    ]);

    // Upload private file and preview as anonymous user.
    $edit = [
      'files[managed_file_single]' => \Drupal::service('file_system')->realpath($this->files[1]->uri),
    ];
    $this->drupalPostForm('/webform/' . $webform->id(), $edit, t('Preview'));

    $temp_file_uri = file_create_url('private://webform/test_element_managed_file/_sid_/' . basename($this->files[1]->uri));

    // Check that temp file is not linked.
    $this->assertNoRaw('<span class="file file--mime-text-plain file--text"> <a href="' . $temp_file_uri . '" type="text/plain; length=16384">text-1.txt</a></span>');
    $this->assertRaw('<span class="file file--mime-text-plain file--text"> ' . basename($this->files[1]->uri) . '</span>');

    // Check that anonymous user can't access temp file.
    $this->drupalGet($temp_file_uri);
    $this->assertResponse(403);

    // Check that authenticated user can't access temp file.
    $this->drupalLogin($admin_submission_user);
    $this->drupalGet($temp_file_uri);
    $this->assertResponse(403);

    // Disable redirect anonymous users to login when attempting to access
    // private file uploads.
    \Drupal::configFactory()->getEditable('webform.settings')
      ->set('file.file_private_redirect', FALSE)
      ->save();

    // Check private file access redirects to user login page with destination.
    $this->drupalLogout();
    $this->drupalGet(file_create_url($file->getFileUri()));
    $this->assertResponse(403);
    $this->assertUrl('system/files/webform/test_element_managed_file/' . $sid . '/' . $this->files[0]->filename);
  }

}
