<?php

/**
 * @file
 * Contains \Drupal\system\Tests\Entity\EntityAutocompleteTest.
 */

namespace Drupal\system\Tests\Entity;

use Drupal\Component\Serialization\Json;
use Drupal\Component\Utility\Crypt;
use Drupal\Component\Utility\SafeMarkup;
use Drupal\Component\Utility\Tags;
use Drupal\Core\Site\Settings;
use Drupal\system\Controller\EntityAutocompleteController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Tests the autocomplete functionality.
 *
 * @group Entity
 */
class EntityAutocompleteTest extends EntityUnitTestBase {

  /**
   * The entity type used in this test.
   *
   * @var string
   */
  protected $entityType = 'entity_test';

  /**
   * The bundle used in this test.
   *
   * @var string
   */
  protected $bundle = 'entity_test';

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->installSchema('system', ['key_value']);
  }

  /**
   * Tests autocompletion edge cases with slashes in the names.
   */
  function testEntityReferenceAutocompletion() {
    // Add an entity with a slash in its name.
    $entity_1 = entity_create($this->entityType, array('name' => '10/16/2011'));
    $entity_1->save();

    // Add another entity that differs after the slash character.
    $entity_2 = entity_create($this->entityType, array('name' => '10/17/2011'));
    $entity_2->save();

    // Add another entity that has both a comma and a slash character.
    $entity_3 = entity_create($this->entityType, array('name' => 'label with, and / test'));
    $entity_3->save();

    // Try to autocomplete a entity label that matches both entities.
    // We should get both entities in a JSON encoded string.
    $input = '10/';
    $data = $this->getAutocompleteResult($input);
    $this->assertIdentical($data[0]['label'], SafeMarkup::checkPlain($entity_1->name->value), 'Autocomplete returned the first matching entity');
    $this->assertIdentical($data[1]['label'], SafeMarkup::checkPlain($entity_2->name->value), 'Autocomplete returned the second matching entity');

    // Try to autocomplete a entity label that matches the first entity.
    // We should only get the first entity in a JSON encoded string.
    $input = '10/16';
    $data = $this->getAutocompleteResult($input);
    $target = array(
      'value' => $entity_1->name->value . ' (1)',
      'label' => SafeMarkup::checkPlain($entity_1->name->value),
    );
    $this->assertIdentical(reset($data), $target, 'Autocomplete returns only the expected matching entity.');

    // Try to autocomplete a entity label that matches the second entity, and
    // the first entity  is already typed in the autocomplete (tags) widget.
    $input = $entity_1->name->value . ' (1), 10/17';
    $data = $this->getAutocompleteResult($input);
    $this->assertIdentical($data[0]['label'], SafeMarkup::checkPlain($entity_2->name->value), 'Autocomplete returned the second matching entity');

    // Try to autocomplete a entity label with both a comma and a slash.
    $input = '"label with, and / t';
    $data = $this->getAutocompleteResult($input);
    $n = $entity_3->name->value . ' (3)';
    // Entity labels containing commas or quotes must be wrapped in quotes.
    $n = Tags::encode($n);
    $target = array(
      'value' => $n,
      'label' => SafeMarkup::checkPlain($entity_3->name->value),
    );
    $this->assertIdentical(reset($data), $target, 'Autocomplete returns an entity label containing a comma and a slash.');
  }

  /**
   * Tests that missing or invalid selection setting key are handled correctly.
   */
  public function testSelectionSettingsHandling() {
    $entity_reference_controller = EntityAutocompleteController::create($this->container);
    $request = Request::create('entity_reference_autocomplete/' . $this->entityType . '/default');
    $request->query->set('q', $this->randomString());

    try {
      // Pass an invalid selection settings key (i.e. one that does not exist
      // in the key/value store).
      $selection_settings_key = $this->randomString();
      $entity_reference_controller->handleAutocomplete($request, $this->entityType, 'default', $selection_settings_key);

      $this->fail('Non-existent selection settings key throws an exception.');
    }
    catch (AccessDeniedHttpException $e) {
      $this->pass('Non-existent selection settings key throws an exception.');
    }

    try {
      // Generate a valid hash key but store a modified settings array.
      $selection_settings = [];
      $selection_settings_key = Crypt::hmacBase64(serialize($selection_settings) . $this->entityType . 'default', Settings::getHashSalt());

      $selection_settings[$this->randomMachineName()] = $this->randomString();
      \Drupal::keyValue('entity_autocomplete')->set($selection_settings_key, $selection_settings);

      $entity_reference_controller->handleAutocomplete($request, $this->entityType, 'default', $selection_settings_key);
    }
    catch (AccessDeniedHttpException $e) {
      if ($e->getMessage() == 'Invalid selection settings key.') {
        $this->pass('Invalid selection settings key throws an exception.');
      }
      else {
        $this->fail('Invalid selection settings key throws an exception.');
      }
    }

  }

  /**
   * Returns the result of an Entity reference autocomplete request.
   *
   * @param string $input
   *   The label of the entity to query by.
   *
   * @return mixed
   *  The JSON value encoded in its appropriate PHP type.
   */
  protected function getAutocompleteResult($input) {
    $request = Request::create('entity_reference_autocomplete/' . $this->entityType . '/default');
    $request->query->set('q', $input);

    $selection_settings = [];
    $selection_settings_key = Crypt::hmacBase64(serialize($selection_settings) . $this->entityType . 'default', Settings::getHashSalt());
    \Drupal::keyValue('entity_autocomplete')->set($selection_settings_key, $selection_settings);

    $entity_reference_controller = EntityAutocompleteController::create($this->container);
    $result = $entity_reference_controller->handleAutocomplete($request, $this->entityType, 'default', $selection_settings_key)->getContent();

    return Json::decode($result);
  }

}
