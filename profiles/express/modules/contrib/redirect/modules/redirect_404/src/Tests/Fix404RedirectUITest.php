<?php

namespace Drupal\redirect_404\Tests;

use Drupal\Core\Url;

/**
 * UI tests for redirect_404 module.
 *
 * @group redirect_404
 */
class Fix404RedirectUITest extends Redirect404TestBase {

  /**
   * Tests the fix 404 pages workflow.
   */
  public function testFix404Pages() {
    // Visit a non existing page to have the 404 redirect_error entry.
    $this->drupalGet('non-existing0');

    // Go to the "fix 404" page and check the listing.
    $this->drupalGet('admin/config/search/redirect/404');
    $this->assertText('non-existing0');
    $this->clickLink(t('Add redirect'));

    // Check if we generate correct Add redirect url and if the form is
    // pre-filled.
    $destination = Url::fromRoute('redirect_404.fix_404')->getInternalPath();
    $options = [
      'query' => [
        'source' => 'non-existing0',
        'language' => 'en',
        'destination' => $destination,
      ]
    ];
    $this->assertUrl('admin/config/search/redirect/add', $options);
    $this->assertFieldByName('redirect_source[0][path]', 'non-existing0');
    // Save the redirect.
    $edit = ['redirect_redirect[0][uri]' => '/node'];
    $this->drupalPostForm(NULL, $edit, t('Save'));
    $this->assertUrl('admin/config/search/redirect/404');
    $this->assertText('There are no 404 errors to fix.');
    // Check if the redirect works as expected.
    $this->drupalGet('non-existing0');
    $this->assertUrl('node');

    // Test removing a redirect assignment, visit again the non existing page.
    $this->drupalGet('admin/config/search/redirect');
    $this->assertText('non-existing0');
    $this->clickLink('Delete', 0);
    $this->drupalPostForm(NULL, [], 'Delete');
    $this->assertUrl('admin/config/search/redirect');
    $this->assertText('There is no redirect yet.');
    $this->drupalGet('admin/config/search/redirect/404');
    $this->assertText('There are no 404 errors to fix.');
    // Should be listed again in the 404 overview.
    $this->drupalGet('non-existing0');
    $this->drupalGet('admin/config/search/redirect/404');
    $this->assertText('non-existing0');

    // Visit multiple non existing pages to test the Redirect 404 View.
    $this->drupalGet('non-existing0?test=1');
    $this->drupalGet('non-existing0?test=2');
    $this->drupalGet('non-existing1');
    $this->drupalGet('non-existing2');
    $this->drupalGet('admin/config/search/redirect/404');
    $this->assertText('non-existing0?test=1');
    $this->assertText('non-existing0?test=2');
    $this->assertText('non-existing0');
    $this->assertText('non-existing1');
    $this->assertText('non-existing2');

    // Test the Path view filter.
    $this->drupalGet('admin/config/search/redirect/404', ['query' => ['path' => 'test=']]);
    $this->assertText('non-existing0?test=1');
    $this->assertText('non-existing0?test=2');
    $this->assertNoText('non-existing1');
    $this->assertNoText('non-existing2');
    $this->drupalGet('admin/config/search/redirect/404', ['query' => ['path' => 'existing1']]);
    $this->assertNoText('non-existing0?test=1');
    $this->assertNoText('non-existing0?test=2');
    $this->assertNoText('non-existing0');
    $this->assertText('non-existing1');
    $this->assertNoText('non-existing2');
    $this->drupalGet('admin/config/search/redirect/404');
    $this->assertText('non-existing0?test=1');
    $this->assertText('non-existing0?test=2');
    $this->assertText('non-existing0');
    $this->assertText('non-existing1');
    $this->assertText('non-existing2');
    $this->drupalGet('admin/config/search/redirect/404', ['query' => ['path' => 'g2']]);
    $this->assertNoText('non-existing0?test=1');
    $this->assertNoText('non-existing0?test=2');
    $this->assertNoText('non-existing0');
    $this->assertNoText('non-existing1');
    $this->assertText('non-existing2');

    // Assign a redirect to 'non-existing2'.
    $this->clickLink('Add redirect');
    $options = [
      'query' => [
        'source' => 'non-existing2',
        'language' => 'en',
        'destination' => $destination,
      ]
    ];
    $this->assertUrl('admin/config/search/redirect/add', $options);
    $this->assertFieldByName('redirect_source[0][path]', 'non-existing2');
    $this->drupalPostForm(NULL, $edit, t('Save'));
    $this->assertUrl('admin/config/search/redirect/404');
    $this->assertText('non-existing0?test=1');
    $this->assertText('non-existing0?test=2');
    $this->assertText('non-existing0');
    $this->assertText('non-existing1');
    $this->assertNoText('non-existing2');
    // Check if the redirect works as expected.
    $this->drupalGet('admin/config/search/redirect');
    $this->assertText('non-existing2');
  }

  /**
   * Tests the redirect ignore pages.
   */
  public function testIgnorePages() {
    // Create two nodes.
    $node1 = $this->drupalCreateNode(['type' => 'page']);
    $node2 = $this->drupalCreateNode(['type' => 'page']);

    // Set some pages to be ignored just for the test.
    $node_to_ignore = '/node/' . $node1->id() . '/test';
    $terms_to_ignore = '/term/*';
    $pages = $node_to_ignore . "\r\n" . $terms_to_ignore;
    \Drupal::configFactory()
      ->getEditable('redirect_404.settings')
      ->set('pages', $pages)
      ->save();

    // Visit ignored or non existing pages.
    $this->drupalGet('node/' . $node1->id() . '/test');
    $this->drupalGet('term/foo');
    $this->drupalGet('term/1');
    // Go to the "fix 404" page and check there are no 404 entries.
    $this->drupalGet('admin/config/search/redirect/404');
    $this->assertNoText('node/' . $node1->id() . '/test');
    $this->assertNoText('term/foo');
    $this->assertNoText('term/1');

    // Visit non existing but 'unignored' page.
    $this->drupalGet('node/' . $node2->id() . '/test');
    // Go to the "fix 404" page and check there is a 404 entry.
    $this->drupalGet('admin/config/search/redirect/404');
    $this->assertText('node/' . $node2->id() . '/test');

    // Add this 404 entry to the 'ignore path' list, assert it works properly.
    $path_to_ignore = '/node/' . $node2->id() . '/test';
    $destination = '&destination=admin/config/search/redirect/404';
    $this->clickLink('Ignore');
    $this->assertUrl('admin/config/search/redirect/settings?ignore=' . $path_to_ignore . $destination);
    $this->assertText('Resolved the path ' . $path_to_ignore . ' in the database. Please check the ignored list and save the settings.');
    $xpath = $this->xpath('//*[@id="edit-ignore-pages"]')[0]->asXML();
    $this->assertTrue(strpos($xpath, $node_to_ignore), $node_to_ignore . " in 'Path to ignore' found");
    $this->assertTrue(strpos($xpath, $terms_to_ignore), $terms_to_ignore . " in 'Path to ignore' found");
    $this->assertTrue(strpos($xpath, $path_to_ignore), $path_to_ignore . " in 'Path to ignore' found");

    // Save the path with wildcard, but omitting the leading slash.
    $nodes_to_ignore = 'node/*';
    $edit = ['ignore_pages' => $nodes_to_ignore . "\r\n" . $terms_to_ignore];
    $this->drupalPostForm(NULL, $edit, 'Save configuration');
    // Should redirect to 'Fix 404'. Check the 404 entry is not shown anymore.
    $this->assertUrl('admin/config/search/redirect/404');
    $this->assertText('Configuration was saved.');
    $this->assertNoText('node/' . $node2->id() . '/test');
    $this->assertText('There are no 404 errors to fix.');

    // Go back to the settings to check the 'Path to ignore' configurations.
    $this->drupalGet('admin/config/search/redirect/settings');
    $xpath = $this->xpath('//*[@id="edit-ignore-pages"]')[0]->asXML();
    // Check that the new page to ignore has been saved with leading slash.
    $this->assertTrue(strpos($xpath, '/' . $nodes_to_ignore), '/' . $nodes_to_ignore . " in 'Path to ignore' found");
    $this->assertTrue(strpos($xpath, $terms_to_ignore), $terms_to_ignore . " in 'Path to ignore' found");
    $this->assertFalse(strpos($xpath, $node_to_ignore), $node_to_ignore . " in 'Path to ignore' found");
    $this->assertFalse(strpos($xpath, $path_to_ignore), $path_to_ignore . " in 'Path to ignore' found");
  }

}
