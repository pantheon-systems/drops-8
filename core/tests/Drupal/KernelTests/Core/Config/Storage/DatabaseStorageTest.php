<?php

/**
 * @file
 * Contains \Drupal\KernelTests\Core\Config\Storage\DatabaseStorageTest.
 */

namespace Drupal\KernelTests\Core\Config\Storage;

use Drupal\Core\Config\DatabaseStorage;

/**
 * Tests DatabaseStorage operations.
 *
 * @group config
 */
class DatabaseStorageTest extends ConfigStorageTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->storage = new DatabaseStorage($this->container->get('database'), 'config');
    $this->invalidStorage = new DatabaseStorage($this->container->get('database'), 'invalid');

    // ::listAll() verifications require other configuration data to exist.
    $this->storage->write('system.performance', array());
  }

  protected function read($name) {
    $data = db_query('SELECT data FROM {config} WHERE name = :name', array(':name' => $name))->fetchField();
    return unserialize($data);
  }

  protected function insert($name, $data) {
    db_insert('config')->fields(array('name' => $name, 'data' => $data))->execute();
  }

  protected function update($name, $data) {
    db_update('config')->fields(array('data' => $data))->condition('name', $name)->execute();
  }

  protected function delete($name) {
    db_delete('config')->condition('name', $name)->execute();
  }
}
