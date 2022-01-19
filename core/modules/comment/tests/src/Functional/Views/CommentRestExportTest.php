<?php

namespace Drupal\Tests\comment\Functional\Views;

use Drupal\Component\Serialization\Json;
use Drupal\comment\Entity\Comment;

/**
 * Tests a comment rest export view.
 *
 * @group comment
 */
class CommentRestExportTest extends CommentTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = ['test_comment_rest'];

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'node',
    'comment',
    'comment_test_views',
    'rest',
    'hal',
  ];

  protected function setUp($import_test_views = TRUE): void {
    parent::setUp($import_test_views);
    // Add another anonymous comment.
    $comment = [
      'uid' => 0,
      'entity_id' => $this->nodeUserCommented->id(),
      'entity_type' => 'node',
      'field_name' => 'comment',
      'subject' => 'A lot, apparently',
      'cid' => '',
      'pid' => $this->comment->id(),
      'mail' => 'someone@example.com',
      'name' => 'bobby tables',
      'hostname' => 'public.example.com',
    ];
    $this->comment = Comment::create($comment);
    $this->comment->save();

    $user = $this->drupalCreateUser(['access comments']);
    $this->drupalLogin($user);
  }

  /**
   * Tests comment row.
   */
  public function testCommentRestExport() {
    $this->drupalGet(sprintf('node/%d/comments', $this->nodeUserCommented->id()), ['query' => ['_format' => 'hal_json']]);
    $this->assertSession()->statusCodeEquals(200);
    $contents = Json::decode($this->getSession()->getPage()->getContent());
    $this->assertEquals('How much wood would a woodchuck chuck', $contents[0]['subject']);
    $this->assertEquals('A lot, apparently', $contents[1]['subject']);
    $this->assertCount(2, $contents);

    // Ensure field-level access is respected - user shouldn't be able to see
    // mail or hostname fields.
    $this->assertSession()->responseNotContains('someone@example.com');
    $this->assertSession()->responseNotContains('public.example.com');
  }

}
