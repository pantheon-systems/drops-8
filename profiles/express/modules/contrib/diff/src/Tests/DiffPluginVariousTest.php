<?php

namespace Drupal\diff\Tests;

use Drupal\comment\Plugin\Field\FieldType\CommentItemInterface;
use Drupal\comment\Tests\CommentTestTrait;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\link\LinkItemInterface;

/**
 * Tests the Diff module plugins.
 *
 * @group diff
 */
class DiffPluginVariousTest extends DiffPluginTestBase {

  use CommentTestTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'comment',
  ];

  /**
   * Tests the module plugins.
   */
  public function testPlugins() {
    $this->doTestCommentPlugin();
    $this->doTestCorePlugin();
    $this->doTestCorePluginTimestampField();
    $this->doTestLinkPlugin();
    $this->doTestListPlugin();
    $this->doTestTextPlugin();
    $this->doTestTextWithSummaryPlugin();
  }

  /**
   * Adds a text field.
   *
   * @param string $field_name
   *   The machine field name.
   * @param string $label
   *   The field label.
   * @param string $field_type
   *   The field type.
   * @param string $widget_type
   *   The widget type.
   */
  protected function addArticleTextField($field_name, $label, $field_type, $widget_type) {
    // Create a field.
    $field_storage = FieldStorageConfig::create([
      'field_name' => $field_name,
      'entity_type' => 'node',
      'type' => $field_type,
    ]);
    $field_storage->save();
    FieldConfig::create([
      'field_storage' => $field_storage,
      'bundle' => 'article',
      'label' => $label,
    ])->save();
    $this->formDisplay->load('node.article.default')
      ->setComponent($field_name, ['type' => $widget_type])
      ->save();
    $this->viewDisplay->load('node.article.default')
      ->setComponent($field_name)
      ->save();
  }

  /**
   * Tests the comment plugin.
   *
   * @covers \Drupal\diff\Plugin\diff\Field\CommentFieldBuilder
   */
  public function doTestCommentPlugin() {
    // Add the comment field to articles.
    $this->addDefaultCommentField('node', 'article');

    // Create an article with comments enabled..
    $title = 'Sample article';
    $edit = array(
      'title[0][value]' => $title,
      'body[0][value]' => '<p>Revision 1</p>',
      'comment[0][status]' => CommentItemInterface::OPEN,
    );
    $this->drupalPostForm('node/add/article', $edit, t('Save and publish'));
    $node = $this->drupalGetNodeByTitle($title);

    // Edit the article and close its comments.
    $edit = array(
      'comment[0][status]' => CommentItemInterface::CLOSED,
      'revision' => TRUE,
    );
    $this->drupalPostForm('node/' . $node->id() . '/edit', $edit, t('Save and keep published'));

    // Check the difference between the last two revisions.
    $this->clickLink(t('Revisions'));
    $this->drupalPostForm(NULL, NULL, t('Compare selected revisions'));
    $this->assertText('Comments');
    $this->assertText('Comments for this entity are open.');
    $this->assertText('Comments for this entity are closed.');
  }

  /**
   * Tests the Core plugin.
   *
   * @covers \Drupal\diff\Plugin\diff\Field\CoreFieldBuilder
   */
  public function doTestCorePlugin() {
    // Add an email field (supported by the Diff core plugin) to the Article
    // content type.
    $field_name = 'field_email';
    $field_storage = FieldStorageConfig::create([
      'field_name' => $field_name,
      'entity_type' => 'node',
      'type' => 'email',
    ]);
    $field_storage->save();
    FieldConfig::create([
      'field_storage' => $field_storage,
      'bundle' => 'article',
      'label' => 'Email',
    ])->save();

    // Add the email field to the article form.
    $this->formDisplay->load('node.article.default')
      ->setComponent($field_name, ['type' => 'email_default'])
      ->save();

    // Add the email field to the default display.
    $this->viewDisplay->load('node.article.default')
      ->setComponent($field_name, ['type' => 'basic_string'])
      ->save();

    // Create an article with an email.
    $node = $this->drupalCreateNode([
      'type' => 'article',
      'field_email' => 'foo@example.com',
    ]);

    // Edit the article and change the email.
    $edit = array(
      'field_email[0][value]' => 'bar@example.com',
      'revision' => TRUE,
    );
    $this->drupalPostForm('node/' . $node->id() . '/edit', $edit, t('Save and keep published'));

    // Check the difference between the last two revisions.
    $this->clickLink(t('Revisions'));
    $this->drupalPostForm(NULL, NULL, t('Compare selected revisions'));
    $this->assertText('Email');
    $this->assertText('foo@example.com');
    $this->assertText('bar@example.com');
  }

  /**
   * Tests the Core plugin with a timestamp field.
   *
   * @covers \Drupal\diff\Plugin\diff\Field\CoreFieldBuilder
   */
  public function doTestCorePluginTimestampField() {
    // Add a timestamp field (supported by the Diff core plugin) to the Article
    // content type.
    $field_name = 'field_timestamp';
    $field_storage = FieldStorageConfig::create([
      'field_name' => $field_name,
      'entity_type' => 'node',
      'type' => 'timestamp',
    ]);
    $field_storage->save();
    FieldConfig::create([
      'field_storage' => $field_storage,
      'bundle' => 'article',
      'label' => 'Timestamp test',
    ])->save();

    // Add the timestamp field to the article form.
    $this->formDisplay->load('node.article.default')
      ->setComponent($field_name, ['type' => 'datetime_timestamp'])
      ->save();

    // Add the timestamp field to the default display.
    $this->viewDisplay->load('node.article.default')
      ->setComponent($field_name, ['type' => 'timestamp'])
      ->save();

    $old_timestamp = '321321321';
    $new_timestamp = '123123123';

    // Create an article with an timestamp.
    $this->drupalCreateNode([
      'title' => 'timestamp_test',
      'type' => 'article',
      'field_timestamp' => $old_timestamp,
    ]);

    // Create a new revision with an updated timestamp.
    $node = $this->drupalGetNodeByTitle('timestamp_test');
    $node->field_timestamp = $new_timestamp;
    $node->setNewRevision(TRUE);
    $node->save();

    // Compare the revisions.
    $this->drupalGet('node/' . $node->id() . '/revisions');
    $this->drupalPostForm(NULL, NULL, t('Compare selected revisions'));

    // Assert that the timestamp field does not show a unix time format.
    $this->assertText('Timestamp test');
    $date_formatter = \Drupal::service('date.formatter');
    $this->assertText($date_formatter->format($old_timestamp));
    $this->assertText($date_formatter->format($new_timestamp));
  }

  /**
   * Tests the Link plugin.
   *
   * @covers \Drupal\diff\Plugin\diff\Field\LinkFieldBuilder
   */
  public function doTestLinkPlugin() {
    // Add a link field to the article content type.
    $field_name = 'field_link';
    $field_storage = FieldStorageConfig::create([
      'field_name' => $field_name,
      'entity_type' => 'node',
      'type' => 'link',
    ]);
    $field_storage->save();
    FieldConfig::create([
      'field_storage' => $field_storage,
      'bundle' => 'article',
      'label' => 'Link',
      'settings' => array(
        'title' => DRUPAL_OPTIONAL,
        'link_type' => LinkItemInterface::LINK_GENERIC,
      ),
    ])->save();
    $this->formDisplay->load('node.article.default')
      ->setComponent($field_name, [
        'type' => 'link_default',
        'settings' => [
          'placeholder_url' => 'http://example.com',
        ],
      ])
      ->save();
    $this->viewDisplay->load('node.article.default')
      ->setComponent($field_name, ['type' => 'link'])
      ->save();

    // Enable the comparison of the link's title field.
    $config = \Drupal::configFactory()->getEditable('diff.plugins');
    $settings['compare_title'] = TRUE;
    $config->set('fields.node.field_link.type', 'link_field_diff_builder');
    $config->set('fields.node.field_link.settings', $settings);
    $config->save();

    // Create an article, setting values on the link field.
    $node = $this->drupalCreateNode([
      'type' => 'article',
      'title' => 'Test article',
      'field_link' => [
        'title' => 'Google',
        'uri' => 'http://www.google.com',
      ],
    ]);

    // Update the link field.
    $edit = [
      'field_link[0][title]' => 'Guguel',
      'field_link[0][uri]' => 'http://www.google.es',
      'revision' => TRUE,
    ];
    $this->drupalPostForm('node/' . $node->id() . '/edit', $edit, t('Save and keep published'));

    // Check differences between revisions.
    $this->clickLink(t('Revisions'));
    $this->drupalPostForm(NULL, [], t('Compare selected revisions'));
    $this->assertText('Link');
    $this->assertText('Google');
    $this->assertText('http://www.google.com');
    $this->assertText('Guguel');
    $this->assertText('http://www.google.es');
  }

  /**
   * Tests the List plugin.
   *
   * @covers \Drupal\diff\Plugin\diff\Field\ListFieldBuilder
   */
  public function doTestListPlugin() {
    // Add a list field to the article content type.
    $field_name = 'field_list';
    $field_storage = FieldStorageConfig::create([
      'field_name' => $field_name,
      'entity_type' => 'node',
      'type' => 'list_string',
      'cardinality' => 1,
      'settings' => [
        'allowed_values' => [
          'value_a' => 'Value A',
          'value_b' => 'Value B',
        ],
      ],
    ]);
    $field_storage->save();

    FieldConfig::create([
      'field_name' => $field_name,
      'entity_type' => 'node',
      'bundle' => 'article',
      'required' => FALSE,
      'label' => 'List',
    ])->save();

    $this->formDisplay->load('node.article.default')
      ->setComponent($field_name, ['type' => 'options_select'])
      ->save();
    $this->viewDisplay->load('node.article.default')
      ->setComponent($field_name, ['type' => 'list_default'])
      ->save();

    // Create an article, setting values on the lit field.
    $node = $this->drupalCreateNode([
      'type' => 'article',
      'title' => 'Test article',
      'field_list' => 'value_a',
    ]);

    // Update the list field.
    $edit = [
      'field_list' => 'value_b',
      'revision' => TRUE,
    ];
    $this->drupalPostForm('node/' . $node->id() . '/edit', $edit, t('Save and keep published'));

    // Check differences between revisions.
    $this->clickLink(t('Revisions'));
    $this->drupalPostForm(NULL, [], t('Compare selected revisions'));
    $this->assertText('List');
    $this->assertText('value_a');
    $this->assertText('value_b');
  }

  /**
   * Tests the Text plugin.
   *
   * @covers \Drupal\diff\Plugin\diff\Field\TextFieldBuilder
   */
  public function doTestTextPlugin() {
    // Add a text and a text long field to the Article content type.
    $this->addArticleTextField('field_text', 'Text Field', 'string', 'string_textfield');
    $this->addArticleTextField('field_text_long', 'Text Long Field', 'string_long', 'string_textarea');

    // Create an article, setting values on both fields.
    $node = $this->drupalCreateNode([
      'type' => 'article',
      'title' => 'Test article',
      'field_text' => 'Foo',
      'field_text_long' => 'Fighters',
    ]);

    // Edit the article and update these fields, creating a new revision.
    $edit = [
      'field_text[0][value]' => 'Bar',
      'field_text_long[0][value]' => 'Fly',
      'revision' => TRUE,
    ];
    $this->drupalPostForm('node/' . $node->id() . '/edit', $edit, t('Save and keep published'));

    // Check differences between revisions.
    $this->clickLink(t('Revisions'));
    $this->drupalPostForm(NULL, [], t('Compare selected revisions'));
    $this->assertText('Text Field');
    $this->assertText('Text Long Field');
    $this->assertText('Foo');
    $this->assertText('Fighters');
    $this->assertText('Bar');
    $this->assertText('Fly');
  }

  /**
   * Tests the TextWithSummary plugin.
   *
   * @covers \Drupal\diff\Plugin\diff\Field\TextWithSummaryFieldBuilder
   */
  public function doTestTextWithSummaryPlugin() {
    // Enable the comparison of the summary.
    $config = \Drupal::configFactory()->getEditable('diff.plugins');
    $settings['compare_summary'] = TRUE;
    $config->set('fields.node.body.type', 'text_summary_field_diff_builder');
    $config->set('fields.node.body.settings', $settings);
    $config->save();

    // Create an article, setting the body field.
    $node = $this->drupalCreateNode([
      'type' => 'article',
      'title' => 'Test article',
      'body' => [
        'value' => 'Foo value',
        'summary' => 'Foo summary',
      ],
    ]);

    // Edit the article and update these fields, creating a new revision.
    $edit = [
      'body[0][value]' => 'Bar value',
      'body[0][summary]' => 'Bar summary',
      'revision' => TRUE,
    ];
    $this->drupalPostForm('node/' . $node->id() . '/edit', $edit, t('Save and keep published'));

    // Check differences between revisions.
    $this->clickLink(t('Revisions'));
    $this->drupalPostForm(NULL, [], t('Compare selected revisions'));
    $this->assertText('Body');
    $this->assertText('Foo value');
    $this->assertText('Foo summary');
    $this->assertText('Bar value');
    $this->assertText('Bar summary');
  }

}
