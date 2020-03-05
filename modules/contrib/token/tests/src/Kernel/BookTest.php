<?php

namespace Drupal\Tests\token\Kernel;

use Drupal\node\Entity\Node;
use Drupal\Core\Url;

/**
 * Test the book tokens.
 *
 * @group token
 */
class BookTest extends KernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['user', 'field', 'filter', 'text', 'node', 'book'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installEntitySchema('user');
    $this->installEntitySchema('node');
    $this->installSchema('book', ['book']);
    $this->installSchema('node', ['node_access']);
    $this->installConfig(['node', 'book', 'field']);
  }

  function testBookTokens() {
    $book = Node::create([
      'type' => 'book',
      'title' => 'Book Main Page',
      'book' => ['bid' => 'new'],
    ]);
    $book->save();

    $page1 = Node::create([
      'type' => 'book',
      'title' => '1st Page',
      'book' => ['bid' => $book->id(), 'pid' => $book->id()],
    ]);
    $page1->save();

    $page2 = Node::create([
      'type' => 'book',
      'title' => '2nd Page',
      'book' => ['bid' => $book->id(), 'pid' => $page1->id()],
    ]);
    $page2->save();

    $book_title = $book->getTitle();

    $tokens = [
      'nid' => $book->id(),
      'title' => $book_title,
      'book:title' => $book_title,
      'book:root' => $book_title,
      'book:root:nid' => $book->id(),
      'book:root:title' => $book_title,
      'book:root:url' => Url::fromRoute('entity.node.canonical', ['node' => $book->id()], ['absolute' => TRUE])->toString(),
      'book:root:content-type' => 'Book page',
      'book:parent' => null,
      'book:parents' => null,
    ];
    $this->assertTokens('node', ['node' => $book], $tokens);

    $tokens = [
      'nid' => $page1->id(),
      'title' => $page1->getTitle(),
      'book:title' => $book_title,
      'book:root' => $book_title,
      'book:root:nid' => $book->id(),
      'book:root:title' => $book_title,
      'book:root:url' => Url::fromRoute('entity.node.canonical', ['node' => $book->id()], ['absolute' => TRUE])->toString(),
      'book:root:content-type' => 'Book page',
      'book:parent:nid' => $book->id(),
      'book:parent:title' => $book_title,
      'book:parent:url' => Url::fromRoute('entity.node.canonical', ['node' => $book->id()], ['absolute' => TRUE])->toString(),
      'book:parents:count' => 1,
      'book:parents:join:/' => $book_title,
    ];
    $this->assertTokens('node', ['node' => $page1], $tokens);

    $tokens = [
      'nid' => $page2->id(),
      'title' => $page2->getTitle(),
      'book:title' => $book_title,
      'book:root' => $book_title,
      'book:root:nid' => $book->id(),
      'book:root:title' => $book_title,
      'book:root:url' => Url::fromRoute('entity.node.canonical', ['node' => $book->id()], ['absolute' => TRUE])->toString(),
      'book:root:content-type' => 'Book page',
      'book:parent:nid' => $page1->id(),
      'book:parent:title' => $page1->getTitle(),
      'book:parent:url' => Url::fromRoute('entity.node.canonical', ['node' => $page1->id()], ['absolute' => TRUE])->toString(),
      'book:parents:count' => 2,
      'book:parents:join:/' => $book_title . '/' . $page1->getTitle(),
    ];
    $this->assertTokens('node', ['node' => $page2], $tokens);
  }

}
