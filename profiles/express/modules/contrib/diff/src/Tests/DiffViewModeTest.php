<?php

namespace Drupal\diff\Tests;

/**
 * Tests field visibility when using a custom view mode.
 *
 * @group diff
 */
class DiffViewModeTest extends DiffTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['field_ui'];

  /**
   * Tests field visibility using a cutom view mode.
   */
  public function testViewMode() {
    $this->drupalLogin($this->rootUser);

    // Create a node.
    $node = $this->drupalCreateNode([
      'type' => 'article',
      'title' => 'Sample node',
      'body' => [
        'value' => 'Foo',
      ],
    ]);

    // Edit the article and change the email.
    $edit = array(
      'body[0][value]' => 'Fighters',
      'revision' => TRUE,
    );
    $this->drupalPostForm('node/' . $node->id() . '/edit', $edit, t('Save and keep published'));

    // Set the Body field to hidden in the diff view mode.
    $edit = [
      'fields[body][region]' => 'hidden',
    ];
    $this->drupalPostForm('admin/structure/types/manage/article/display', $edit, t('Save'));
    $edit = [
      'fields[body][region]' => 'hidden',
    ];
    $this->drupalPostForm('admin/structure/types/manage/article/display/teaser', $edit, t('Save'));

    // Check the difference between the last two revisions.
    $this->drupalGet('node/' . $node->id() . '/revisions');
    $this->drupalPostForm(NULL, [], t('Compare selected revisions'));
    $this->assertNoText('Body');
    $this->assertNoText('Foo');
    $this->assertNoText('Fighters');
  }

}
