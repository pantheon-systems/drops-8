<?php

/**
 * @file
 * Contains \Drupal\Core\Plugin\Context\Context.
 */

namespace Drupal\Core\Plugin\Context;

use Drupal\Component\Plugin\Context\Context as ComponentContext;
use Drupal\Component\Plugin\Exception\ContextException;
use Drupal\Component\Utility\SafeMarkup;
use Drupal\Core\TypedData\TypedDataInterface;
use Drupal\Core\TypedData\TypedDataTrait;

/**
 * A Drupal specific context wrapper class.
 */
class Context extends ComponentContext implements ContextInterface {

  use TypedDataTrait;

  /**
   * The data associated with the context.
   *
   * @var \Drupal\Core\TypedData\TypedDataInterface
   */
  protected $contextData;

  /**
   * The definition to which a context must conform.
   *
   * @var \Drupal\Core\Plugin\Context\ContextDefinitionInterface
   */
  protected $contextDefinition;

  /**
   * {@inheritdoc}
   */
  public function getContextValue() {
    if (!isset($this->contextData)) {
      $definition = $this->getContextDefinition();
      $default_value = $definition->getDefaultValue();

      if (isset($default_value)) {
        // Keep the default value here so that subsequent calls don't have to
        // look it up again.
        $this->setContextValue($default_value);
      }
      elseif ($definition->isRequired()) {
        $type = $definition->getDataType();
        throw new ContextException(SafeMarkup::format("The @type context is required and not present.", array('@type' => $type)));
      }
      return $default_value;
    }
    return $this->getTypedDataManager()->getCanonicalRepresentation($this->contextData);
  }

  /**
   * {@inheritdoc}
   */
  public function setContextValue($value) {
    if ($value instanceof TypedDataInterface) {
      return $this->setContextData($value);
    }
    else {
      return $this->setContextData($this->getTypedDataManager()->create($this->contextDefinition->getDataDefinition(), $value));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getConstraints() {
    return $this->contextDefinition->getConstraints();
  }

  /**
   * {@inheritdoc}
   */
  public function getContextData() {
    if (!isset($this->contextData)) {
      $definition = $this->getContextDefinition();
      $default_value = $definition->getDefaultValue();
      if (isset($default_value)) {
        // Store the default value so that subsequent calls don't have to look
        // it up again.
        $this->contextData = $this->getTypedDataManager()->create($definition->getDataDefinition(), $default_value);
      }
    }
    return $this->contextData;
  }

  /**
   * {@inheritdoc}
   */
  public function setContextData(TypedDataInterface $data) {
    $this->contextData = $data;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getContextDefinition() {
    return $this->contextDefinition;
  }

  /**
   * {@inheritdoc}
   */
  public function validate() {
    return $this->getContextData()->validate();
  }

}
