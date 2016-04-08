<?php

/**
 * @file
 * Contains \Drupal\rest\Tests\UpdateTest.
 */

namespace Drupal\rest\Tests;

use Drupal\Component\Serialization\Json;

/**
 * Tests the update of resources.
 *
 * @group rest
 */
class UpdateTest extends RESTTestBase {

  /**
   * Modules to install.
   *
   * @var array
   */
  public static $modules = array('hal', 'rest', 'entity_test');

  /**
   * Tests several valid and invalid partial update requests on test entities.
   */
  public function testPatchUpdate() {
    $serializer = $this->container->get('serializer');
    // @todo Test all other entity types here as well.
    $entity_type = 'entity_test';

    $this->enableService('entity:' . $entity_type, 'PATCH');
    // Create a user account that has the required permissions to create
    // resources via the REST API.
    $permissions = $this->entityPermissions($entity_type, 'update');
    $permissions[] = 'restful patch entity:' . $entity_type;
    $account = $this->drupalCreateUser($permissions);
    $this->drupalLogin($account);

    $context = ['account' => $account];

    // Create an entity and save it to the database.
    $entity = $this->entityCreate($entity_type);
    $entity->save();

    // Create a second stub entity for overwriting a field.
    $patch_values['field_test_text'] = array(0 => array(
      'value' => $this->randomString(),
      'format' => 'plain_text',
    ));
    $patch_entity = $this->container->get('entity_type.manager')
      ->getStorage($entity_type)
      ->create($patch_values);
    // We don't want to overwrite the UUID.
    $patch_entity->set('uuid', NULL);
    $serialized = $serializer->serialize($patch_entity, $this->defaultFormat, $context);

    // Update the entity over the REST API.
    $this->httpRequest($entity->urlInfo(), 'PATCH', $serialized, $this->defaultMimeType);
    $this->assertResponse(204);

    // Re-load updated entity from the database.
    $entity = entity_load($entity_type, $entity->id(), TRUE);
    $this->assertEqual($entity->field_test_text->value, $patch_entity->field_test_text->value, 'Field was successfully updated.');

    // Make sure that the field does not get deleted if it is not present in the
    // PATCH request.
    $normalized = $serializer->normalize($patch_entity, $this->defaultFormat, $context);
    unset($normalized['field_test_text']);
    $serialized = $serializer->encode($normalized, $this->defaultFormat);
    $this->httpRequest($entity->urlInfo(), 'PATCH', $serialized, $this->defaultMimeType);
    $this->assertResponse(204);

    $entity = entity_load($entity_type, $entity->id(), TRUE);
    $this->assertNotNull($entity->field_test_text->value. 'Test field has not been deleted.');

    // Try to empty a field.
    $normalized['field_test_text'] = array();
    $serialized = $serializer->encode($normalized, $this->defaultFormat);

    // Update the entity over the REST API.
    $this->httpRequest($entity->urlInfo(), 'PATCH', $serialized, $this->defaultMimeType);
    $this->assertResponse(204);

    // Re-load updated entity from the database.
    $entity = entity_load($entity_type, $entity->id(), TRUE);
    $this->assertNull($entity->field_test_text->value, 'Test field has been cleared.');

    // Enable access protection for the text field.
    // @see entity_test_entity_field_access()
    $entity->field_test_text->value = 'no edit access value';
    $entity->field_test_text->format = 'plain_text';
    $entity->save();

    // Try to empty a field that is access protected.
    $this->httpRequest($entity->urlInfo(), 'PATCH', $serialized, $this->defaultMimeType);
    $this->assertResponse(403);

    // Re-load the entity from the database.
    $entity = entity_load($entity_type, $entity->id(), TRUE);
    $this->assertEqual($entity->field_test_text->value, 'no edit access value', 'Text field was not deleted.');

    // Try to update an access protected field.
    $normalized = $serializer->normalize($patch_entity, $this->defaultFormat, $context);
    $normalized['field_test_text'][0]['value'] = 'no access value';
    $serialized = $serializer->serialize($normalized, $this->defaultFormat, $context);
    $this->httpRequest($entity->urlInfo(), 'PATCH', $serialized, $this->defaultMimeType);
    $this->assertResponse(403);

    // Re-load the entity from the database.
    $entity = entity_load($entity_type, $entity->id(), TRUE);
    $this->assertEqual($entity->field_test_text->value, 'no edit access value', 'Text field was not updated.');

    // Try to update the field with a text format this user has no access to.
    // First change the original field value so we're allowed to edit it again.
    $entity->field_test_text->value = 'test';
    $entity->save();
    $patch_entity->set('field_test_text', array(
      'value' => 'test',
      'format' => 'full_html',
    ));
    $serialized = $serializer->serialize($patch_entity, $this->defaultFormat, $context);
    $this->httpRequest($entity->urlInfo(), 'PATCH', $serialized, $this->defaultMimeType);
    $this->assertResponse(422);

    // Re-load the entity from the database.
    $entity = entity_load($entity_type, $entity->id(), TRUE);
    $this->assertEqual($entity->field_test_text->format, 'plain_text', 'Text format was not updated.');

    // Restore the valid test value.
    $entity->field_test_text->value = $this->randomString();
    $entity->save();

    // Try to send no data at all, which does not make sense on PATCH requests.
    $this->httpRequest($entity->urlInfo(), 'PATCH', NULL, $this->defaultMimeType);
    $this->assertResponse(400);

    // Try to update a non-existing entity with ID 9999.
    $this->httpRequest($entity_type . '/9999', 'PATCH', $serialized, $this->defaultMimeType);
    $this->assertResponse(404);
    $loaded_entity = entity_load($entity_type, 9999, TRUE);
    $this->assertFalse($loaded_entity, 'Entity 9999 was not created.');

    // Try to send invalid data to trigger the entity validation constraints.
    // Send a UUID that is too long.
    $entity->set('uuid', $this->randomMachineName(129));
    $invalid_serialized = $serializer->serialize($entity, $this->defaultFormat, $context);
    $response = $this->httpRequest($entity->urlInfo(), 'PATCH', $invalid_serialized, $this->defaultMimeType);
    $this->assertResponse(422);
    $error = Json::decode($response);
    $this->assertEqual($error['error'], "Unprocessable Entity: validation failed.\nuuid.0.value: <em class=\"placeholder\">UUID</em>: may not be longer than 128 characters.\n");

    // Try to update an entity without proper permissions.
    $this->drupalLogout();
    $this->httpRequest($entity->urlInfo(), 'PATCH', $serialized, $this->defaultMimeType);
    $this->assertResponse(403);

    // Try to update a resource which is not REST API enabled.
    $this->enableService(FALSE);
    $this->drupalLogin($account);
    $this->httpRequest($entity->urlInfo(), 'PATCH', $serialized, $this->defaultMimeType);
    $this->assertResponse(405);
  }

  /**
   * Tests several valid and invalid update requests for the 'user' entity type.
   */
  public function testUpdateUser() {
    $serializer = $this->container->get('serializer');
    $entity_type = 'user';
    // Enables the REST service for 'user' entity type.
    $this->enableService('entity:' . $entity_type, 'PATCH');
    $permissions = $this->entityPermissions($entity_type, 'update');
    $permissions[] = 'restful patch entity:' . $entity_type;
    $account = $this->drupalCreateUser($permissions);
    $account->set('mail', 'old-email@example.com');
    $this->drupalLogin($account);

    // Create an entity and save it to the database.
    $account->save();
    $account->set('changed', NULL);

    // Try and set a new email without providing the password.
    $account->set('mail', 'new-email@example.com');
    $context = ['account' => $account];
    $normalized = $serializer->normalize($account, $this->defaultFormat, $context);
    $serialized = $serializer->serialize($normalized, $this->defaultFormat, $context);
    $response = $this->httpRequest($account->urlInfo(), 'PATCH', $serialized, $this->defaultMimeType);
    $this->assertResponse(422);
    $error = Json::decode($response);
    $this->assertEqual($error['error'], "Unprocessable Entity: validation failed.\nmail: Your current password is missing or incorrect; it's required to change the <em class=\"placeholder\">Email</em>.\n");

    // Try and send the new email with a password.
    $normalized['pass'][0]['existing'] = 'wrong';
    $serialized = $serializer->serialize($normalized, $this->defaultFormat, $context);
    $response = $this->httpRequest($account->urlInfo(), 'PATCH', $serialized, $this->defaultMimeType);
    $this->assertResponse(422);
    $error = Json::decode($response);
    $this->assertEqual($error['error'], "Unprocessable Entity: validation failed.\nmail: Your current password is missing or incorrect; it's required to change the <em class=\"placeholder\">Email</em>.\n");

    // Try again with the password.
    $normalized['pass'][0]['existing'] = $account->pass_raw;
    $serialized = $serializer->serialize($normalized, $this->defaultFormat, $context);
    $this->httpRequest($account->urlInfo(), 'PATCH', $serialized, $this->defaultMimeType);
    $this->assertResponse(204);

    // Try to change the password without providing the current password.
    $new_password = $this->randomString();
    $normalized = $serializer->normalize($account, $this->defaultFormat, $context);
    $normalized['pass'][0]['value'] = $new_password;
    $serialized = $serializer->serialize($normalized, $this->defaultFormat, $context);
    $response = $this->httpRequest($account->urlInfo(), 'PATCH', $serialized, $this->defaultMimeType);
    $this->assertResponse(422);
    $error = Json::decode($response);
    $this->assertEqual($error['error'], "Unprocessable Entity: validation failed.\npass: Your current password is missing or incorrect; it's required to change the <em class=\"placeholder\">Password</em>.\n");

    // Try again with the password.
    $normalized['pass'][0]['existing'] = $account->pass_raw;
    $serialized = $serializer->serialize($normalized, $this->defaultFormat, $context);
    $this->httpRequest($account->urlInfo(), 'PATCH', $serialized, $this->defaultMimeType);
    $this->assertResponse(204);

    // Verify that we can log in with the new password.
    $account->pass_raw = $new_password;
    $this->drupalLogin($account);

  }

}
