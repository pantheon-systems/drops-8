<?php

namespace Drupal\pathauto;

use Drupal\path\Plugin\Field\FieldType\PathFieldItemList;

class PathautoFieldItemList extends PathFieldItemList {

  /**
   * @{inheritdoc}
   */
  protected function delegateMethod($method) {
    // @todo Workaround until this is fixed, see
    //   https://www.drupal.org/project/drupal/issues/2946289.
    $this->ensureComputedValue();

    // Duplicate the logic instead of calling the parent due to the dynamic
    // arguments.
    $result = [];
    $args = array_slice(func_get_args(), 1);
    foreach ($this->list as $delta => $item) {
      // call_user_func_array() is way slower than a direct call so we avoid
      // using it if have no parameters.
      $result[$delta] = $args ? call_user_func_array([$item, $method], $args) : $item->{$method}();
    }
    return $result;
  }

  /**
   * @{inheritdoc}
   */
  protected function computeValue() {
    parent::computeValue();

    // For a new entity, default to creating a new alias.
    if ($this->getEntity()->isNew()) {
      $this->list[0]->set('pathauto', PathautoState::CREATE);
    }
  }

}
