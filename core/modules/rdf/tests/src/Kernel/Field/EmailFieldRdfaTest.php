<?php
/**
 * @file
 * Contains \Drupal\Tests\rdf\Kernel\Field\EmailFieldRdfaTest.
 */

namespace Drupal\Tests\rdf\Kernel\Field;

use Drupal\entity_test\Entity\EntityTest;

/**
 * Tests RDFa output by email field formatters.
 *
 * @group rdf
 */
class EmailFieldRdfaTest extends FieldRdfaTestBase {

  /**
   * {@inheritdoc}
   */
  protected $fieldType = 'email';

  /**
   * {@inheritdoc}
   */
  public static $modules = array('text');

  protected function setUp() {
    parent::setUp();

    $this->createTestField();

    // Add the mapping.
    $mapping = rdf_get_mapping('entity_test', 'entity_test');
    $mapping->setFieldMapping($this->fieldName, array(
      'properties' => array('schema:email'),
    ))->save();

    // Set up test values.
    $this->testValue = 'test@example.com';
    $this->entity = EntityTest::create(array());
    $this->entity->{$this->fieldName}->value = $this->testValue;
  }

  /**
   * Tests all email formatters.
   */
  public function testAllFormatters() {
    // Test the plain formatter.
    $this->assertFormatterRdfa(array('type'=>'string'), 'http://schema.org/email', array('value' => $this->testValue));
    // Test the mailto formatter.
    $this->assertFormatterRdfa(array('type'=>'email_mailto'), 'http://schema.org/email', array('value' => $this->testValue));
  }
}
