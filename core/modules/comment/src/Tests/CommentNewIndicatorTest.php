<?php

/**
 * @file
 * Contains \Drupal\comment\Tests\CommentNewIndicatorTest.
 */

namespace Drupal\comment\Tests;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Language\LanguageInterface;
use Drupal\comment\CommentInterface;
use Drupal\Core\Url;
use Drupal\comment\Entity\Comment;

/**
 * Tests the 'new' indicator posted on comments.
 *
 * @group comment
 */
class CommentNewIndicatorTest extends CommentTestBase {

  /**
   * Use the main node listing to test rendering on teasers.
   *
   * @var array
   *
   * @todo Remove this dependency.
   */
  public static $modules = array('views');

  /**
   * Get node "x new comments" metadata from the server for the current user.
   *
   * @param array $node_ids
   *   An array of node IDs.
   *
   * @return string
   *   The response body.
   */
  protected function renderNewCommentsNodeLinks(array $node_ids) {
    // Build POST values.
    $post = array();
    for ($i = 0; $i < count($node_ids); $i++) {
      $post['node_ids[' . $i . ']'] = $node_ids[$i];
    }
    $post['field_name'] = 'comment';

    // Serialize POST values.
    foreach ($post as $key => $value) {
      // Encode according to application/x-www-form-urlencoded
      // Both names and values needs to be urlencoded, according to
      // http://www.w3.org/TR/html4/interact/forms.html#h-17.13.4.1
      $post[$key] = urlencode($key) . '=' . urlencode($value);
    }
    $post = implode('&', $post);

    // Perform HTTP request.
    return $this->curlExec(array(
      CURLOPT_URL => \Drupal::url('comment.new_comments_node_links', array(), array('absolute' => TRUE)),
      CURLOPT_POST => TRUE,
      CURLOPT_POSTFIELDS => $post,
      CURLOPT_HTTPHEADER => array(
        'Accept: application/json',
        'Content-Type: application/x-www-form-urlencoded',
      ),
    ));
  }

  /**
   * Tests new comment marker.
   */
  public function testCommentNewCommentsIndicator() {
    // Test if the right links are displayed when no comment is present for the
    // node.
    $this->drupalLogin($this->adminUser);
    $this->drupalGet('node');
    $this->assertNoLink(t('@count comments', array('@count' => 0)));
    $this->assertLink(t('Read more'));
    // Verify the data-history-node-last-comment-timestamp attribute, which is
    // used by the drupal.node-new-comments-link library to determine whether
    // a "x new comments" link might be necessary or not. We do this in
    // JavaScript to prevent breaking the render cache.
    $this->assertIdentical(0, count($this->xpath('//*[@data-history-node-last-comment-timestamp]')), 'data-history-node-last-comment-timestamp attribute is not set.');

    // Create a new comment. This helper function may be run with different
    // comment settings so use $comment->save() to avoid complex setup.
    /** @var \Drupal\comment\CommentInterface $comment */
    $comment = Comment::create(array(
      'cid' => NULL,
      'entity_id' => $this->node->id(),
      'entity_type' => 'node',
      'field_name' => 'comment',
      'pid' => 0,
      'uid' => $this->loggedInUser->id(),
      'status' => CommentInterface::PUBLISHED,
      'subject' => $this->randomMachineName(),
      'hostname' => '127.0.0.1',
      'langcode' => LanguageInterface::LANGCODE_NOT_SPECIFIED,
      'comment_body' => array(LanguageInterface::LANGCODE_NOT_SPECIFIED => array($this->randomMachineName())),
    ));
    $comment->save();
    $this->drupalLogout();

    // Log in with 'web user' and check comment links.
    $this->drupalLogin($this->webUser);
    $this->drupalGet('node');
    // Verify the data-history-node-last-comment-timestamp attribute. Given its
    // value, the drupal.node-new-comments-link library would determine that the
    // node received a comment after the user last viewed it, and hence it would
    // perform an HTTP request to render the "new comments" node link.
    $this->assertIdentical(1, count($this->xpath('//*[@data-history-node-last-comment-timestamp="' . $comment->getChangedTime() .  '"]')), 'data-history-node-last-comment-timestamp attribute is set to the correct value.');
    $this->assertIdentical(1, count($this->xpath('//*[@data-history-node-field-name="comment"]')), 'data-history-node-field-name attribute is set to the correct value.');
    // The data will be pre-seeded on this particular page in drupalSettings, to
    // avoid the need for the client to make a separate request to the server.
    $settings = $this->getDrupalSettings();
    $this->assertEqual($settings['history'], ['lastReadTimestamps' => [1 => 0]]);
    $this->assertEqual($settings['comment'], [
      'newCommentsLinks' => [
        'node' => [
          'comment' => [
            1 => [
              'new_comment_count' => 1,
              'first_new_comment_link' => Url::fromRoute('entity.node.canonical', ['node' => 1])->setOptions([
                'fragment' => 'new',
              ])->toString(),
            ],
          ],
        ],
      ],
    ]);
    // Pretend the data was not present in drupalSettings, i.e. test the
    // separate request to the server.
    $response = $this->renderNewCommentsNodeLinks(array($this->node->id()));
    $this->assertResponse(200);
    $json = Json::decode($response);
    $expected = array($this->node->id() => array(
      'new_comment_count' => 1,
      'first_new_comment_link' => $this->node->url('canonical', array('fragment' => 'new')),
    ));
    $this->assertIdentical($expected, $json);

    // Failing to specify node IDs for the endpoint should return a 404.
    $this->renderNewCommentsNodeLinks(array());
    $this->assertResponse(404);

    // Accessing the endpoint as the anonymous user should return a 403.
    $this->drupalLogout();
    $this->renderNewCommentsNodeLinks(array($this->node->id()));
    $this->assertResponse(403);
    $this->renderNewCommentsNodeLinks(array());
    $this->assertResponse(403);
  }

}
