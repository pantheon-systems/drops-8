<?php

/**
 * @file
 * Contains \Drupal\system\Tests\Entity\Element\EntityAutocompleteElementFormTest.
 */

namespace Drupal\system\Tests\Entity\Element;

use Drupal\Core\Entity\Element\EntityAutocomplete;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\FormInterface;
use Drupal\Core\Form\FormState;
use Drupal\Core\Form\FormStateInterface;
use Drupal\entity_test\Entity\EntityTest;
use Drupal\system\Tests\Entity\EntityUnitTestBase;
use Drupal\user\Entity\User;

/**
 * Tests the EntityAutocomplete Form API element.
 *
 * @group Form
 */
class EntityAutocompleteElementFormTest extends EntityUnitTestBase implements FormInterface {

  /**
   * User for testing.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $testUser;

  /**
   * User for autocreate testing.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $testAutocreateUser;

  /**
   * An array of entities to be referenced in this test.
   *
   * @var \Drupal\Core\Entity\EntityInterface[]
   */
  protected $referencedEntities;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installSchema('system', ['router', 'key_value_expire']);
    \Drupal::service('router.builder')->rebuild();

    $this->testUser = User::create(array(
      'name' => 'foobar1',
      'mail' => 'foobar1@example.com',
    ));
    $this->testUser->save();
    \Drupal::service('current_user')->setAccount($this->testUser);

    $this->testAutocreateUser = User::create(array(
      'name' => 'foobar2',
      'mail' => 'foobar2@example.com',
    ));
    $this->testAutocreateUser->save();

    for ($i = 1; $i < 3; $i++) {
      $entity = EntityTest::create(array(
        'name' => $this->randomMachineName()
      ));
      $entity->save();
      $this->referencedEntities[] = $entity;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'test_entity_autocomplete';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['single'] = array(
      '#type' => 'entity_autocomplete',
      '#target_type' => 'entity_test',
    );
    $form['single_autocreate'] = array(
      '#type' => 'entity_autocomplete',
      '#target_type' => 'entity_test',
      '#autocreate' => array(
        'bundle' => 'entity_test',
      ),
    );
    $form['single_autocreate_specific_uid'] = array(
      '#type' => 'entity_autocomplete',
      '#target_type' => 'entity_test',
      '#autocreate' => array(
        'bundle' => 'entity_test',
        'uid' => $this->testAutocreateUser->id(),
      ),
    );

    $form['tags'] = array(
      '#type' => 'entity_autocomplete',
      '#target_type' => 'entity_test',
      '#tags' => TRUE,
    );
    $form['tags_autocreate'] = array(
      '#type' => 'entity_autocomplete',
      '#target_type' => 'entity_test',
      '#tags' => TRUE,
      '#autocreate' => array(
        'bundle' => 'entity_test',
      ),
    );
    $form['tags_autocreate_specific_uid'] = array(
      '#type' => 'entity_autocomplete',
      '#target_type' => 'entity_test',
      '#tags' => TRUE,
      '#autocreate' => array(
        'bundle' => 'entity_test',
        'uid' => $this->testAutocreateUser->id(),
      ),
    );

    $form['single_no_validate'] = array(
      '#type' => 'entity_autocomplete',
      '#target_type' => 'entity_test',
      '#validate_reference' => FALSE,
    );
    $form['single_autocreate_no_validate'] = array(
      '#type' => 'entity_autocomplete',
      '#target_type' => 'entity_test',
      '#validate_reference' => FALSE,
      '#autocreate' => array(
        'bundle' => 'entity_test',
      ),
    );

    $form['single_access'] = array(
      '#type' => 'entity_autocomplete',
      '#target_type' => 'entity_test',
      '#default_value' => $this->referencedEntities[0],
    );
    $form['tags_access'] = array(
      '#type' => 'entity_autocomplete',
      '#target_type' => 'entity_test',
      '#tags' => TRUE,
      '#default_value' => array($this->referencedEntities[0], $this->referencedEntities[1]),
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) { }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) { }

  /**
   * Tests valid entries in the EntityAutocomplete Form API element.
   */
  public function testValidEntityAutocompleteElement() {
    $form_state = (new FormState())
      ->setValues([
        'single' => $this->getAutocompleteInput($this->referencedEntities[0]),
        'single_autocreate' => 'single - autocreated entity label',
        'single_autocreate_specific_uid' => 'single - autocreated entity label with specific uid',
        'tags' => $this->getAutocompleteInput($this->referencedEntities[0]) . ', ' . $this->getAutocompleteInput($this->referencedEntities[1]),
        'tags_autocreate' =>
          $this->getAutocompleteInput($this->referencedEntities[0])
          . ', tags - autocreated entity label, '
          . $this->getAutocompleteInput($this->referencedEntities[1]),
        'tags_autocreate_specific_uid' =>
          $this->getAutocompleteInput($this->referencedEntities[0])
          . ', tags - autocreated entity label with specific uid, '
          . $this->getAutocompleteInput($this->referencedEntities[1]),
      ]);
    $form_builder = $this->container->get('form_builder');
    $form_builder->submitForm($this, $form_state);

    // Valid form state.
    $this->assertEqual(count($form_state->getErrors()), 0);

    // Test the 'single' element.
    $this->assertEqual($form_state->getValue('single'), $this->referencedEntities[0]->id());

    // Test the 'single_autocreate' element.
    $value = $form_state->getValue('single_autocreate');
    $this->assertEqual($value['entity']->label(), 'single - autocreated entity label');
    $this->assertEqual($value['entity']->bundle(), 'entity_test');
    $this->assertEqual($value['entity']->getOwnerId(), $this->testUser->id());

    // Test the 'single_autocreate_specific_uid' element.
    $value = $form_state->getValue('single_autocreate_specific_uid');
    $this->assertEqual($value['entity']->label(), 'single - autocreated entity label with specific uid');
    $this->assertEqual($value['entity']->bundle(), 'entity_test');
    $this->assertEqual($value['entity']->getOwnerId(), $this->testAutocreateUser->id());

    // Test the 'tags' element.
    $expected = array(
      array('target_id' => $this->referencedEntities[0]->id()),
      array('target_id' => $this->referencedEntities[1]->id()),
    );
    $this->assertEqual($form_state->getValue('tags'), $expected);

    // Test the 'single_autocreate' element.
    $value = $form_state->getValue('tags_autocreate');
    // First value is an existing entity.
    $this->assertEqual($value[0]['target_id'], $this->referencedEntities[0]->id());
    // Second value is an autocreated entity.
    $this->assertTrue(!isset($value[1]['target_id']));
    $this->assertEqual($value[1]['entity']->label(), 'tags - autocreated entity label');
    $this->assertEqual($value[1]['entity']->getOwnerId(), $this->testUser->id());
    // Third value is an existing entity.
    $this->assertEqual($value[2]['target_id'], $this->referencedEntities[1]->id());

    // Test the 'tags_autocreate_specific_uid' element.
    $value = $form_state->getValue('tags_autocreate_specific_uid');
    // First value is an existing entity.
    $this->assertEqual($value[0]['target_id'], $this->referencedEntities[0]->id());
    // Second value is an autocreated entity.
    $this->assertTrue(!isset($value[1]['target_id']));
    $this->assertEqual($value[1]['entity']->label(), 'tags - autocreated entity label with specific uid');
    $this->assertEqual($value[1]['entity']->getOwnerId(), $this->testAutocreateUser->id());
    // Third value is an existing entity.
    $this->assertEqual($value[2]['target_id'], $this->referencedEntities[1]->id());
  }

  /**
   * Tests invalid entries in the EntityAutocomplete Form API element.
   */
  public function testInvalidEntityAutocompleteElement() {
    $form_builder = $this->container->get('form_builder');

    // Test 'single' with a entity label that doesn't exist
    $form_state = (new FormState())
      ->setValues([
        'single' => 'single - non-existent label',
      ]);
    $form_builder->submitForm($this, $form_state);
    $this->assertEqual(count($form_state->getErrors()), 1);
    $this->assertEqual($form_state->getErrors()['single'], t('There are no entities matching "%value".', array('%value' => 'single - non-existent label')));

    // Test 'single' with a entity ID that doesn't exist.
    $form_state = (new FormState())
      ->setValues([
        'single' => 'single - non-existent label (42)',
      ]);
    $form_builder->submitForm($this, $form_state);
    $this->assertEqual(count($form_state->getErrors()), 1);
    $this->assertEqual($form_state->getErrors()['single'], t('The referenced entity (%type: %id) does not exist.', array('%type' => 'entity_test', '%id' => 42)));

    // Do the same tests as above but on an element with '#validate_reference'
    // set to FALSE.
    $form_state = (new FormState())
      ->setValues([
        'single_no_validate' => 'single - non-existent label',
        'single_autocreate_no_validate' => 'single - autocreate non-existent label'
      ]);
    $form_builder->submitForm($this, $form_state);

    // The element without 'autocreate' support still has to emit a warning when
    // the input doesn't end with an entity ID enclosed in parentheses.
    $this->assertEqual(count($form_state->getErrors()), 1);
    $this->assertEqual($form_state->getErrors()['single_no_validate'], t('There are no entities matching "%value".', array('%value' => 'single - non-existent label')));

    $form_state = (new FormState())
      ->setValues([
        'single_no_validate' => 'single - non-existent label (42)',
        'single_autocreate_no_validate' => 'single - autocreate non-existent label (43)'
      ]);
    $form_builder->submitForm($this, $form_state);

    // The input is complete (i.e. contains an entity ID at the end), no errors
    // are triggered.
    $this->assertEqual(count($form_state->getErrors()), 0);
  }

  /**
   * Tests that access is properly checked by the EntityAutocomplete element.
   */
  public function testEntityAutocompleteAccess() {
    $form_builder = $this->container->get('form_builder');
    $form = $form_builder->getForm($this);

    // Check that the current user has proper access to view entity labels.
    $expected = $this->referencedEntities[0]->label() . ' (' . $this->referencedEntities[0]->id() . ')';
    $this->assertEqual($form['single_access']['#value'], $expected);

    $expected .= ', ' . $this->referencedEntities[1]->label() . ' (' . $this->referencedEntities[1]->id() . ')';
    $this->assertEqual($form['tags_access']['#value'], $expected);

    // Set up a non-admin user that is *not* allowed to view test entities.
    \Drupal::currentUser()->setAccount($this->createUser(array(), array()));

    // Rebuild the form.
    $form = $form_builder->getForm($this);

    $expected = t('- Restricted access -') . ' (' . $this->referencedEntities[0]->id() . ')';
    $this->assertEqual($form['single_access']['#value'], $expected);

    $expected .= ', ' . t('- Restricted access -') . ' (' . $this->referencedEntities[1]->id() . ')';
    $this->assertEqual($form['tags_access']['#value'], $expected);
  }

  /**
   * Returns an entity label in the format needed by the EntityAutocomplete
   * element.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   A Drupal entity.
   *
   * @return string
   *   A string that can be used as a value for EntityAutocomplete elements.
   */
  protected function getAutocompleteInput(EntityInterface $entity) {
    return EntityAutocomplete::getEntityLabels(array($entity));
  }

}
