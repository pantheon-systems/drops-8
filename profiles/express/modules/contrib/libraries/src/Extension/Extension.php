<?php

namespace Drupal\libraries\Extension;

use Drupal\Core\Extension\Extension as CoreExtension;

/**
 * @todo
 */
class Extension extends CoreExtension implements ExtensionInterface {

  /**
   * {@inheritdoc}
   *
   * @todo Determine whether this needs to be cached.
   */
  public function getLibraryDependencies() {
    // @todo Make this unit-testable.
    $type = $this->getType();
    // system_get_info() lists profiles as type "module"
    $type = $type == 'profile' ? 'module' : $type;
    $info = system_get_info($type, $this->getName());
    assert('!empty($info)');
    return isset($info['library_dependencies']) ? $info['library_dependencies'] : [];
  }

}
