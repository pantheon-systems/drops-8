<?php

/**
 * @file
 * Contains \Drupal\Core\TypedData\TypedDataManager.
 */

namespace Drupal\Core\TypedData;

use Drupal\Component\Plugin\Exception\PluginException;
use Drupal\Component\Utility\SafeMarkup;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\DependencyInjection\ClassResolverInterface;
use Drupal\Core\DependencyInjection\DependencySerializationTrait;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\TypedData\Validation\ExecutionContextFactory;
use Drupal\Core\TypedData\Validation\RecursiveValidator;
use Drupal\Core\Validation\ConstraintManager;
use Drupal\Core\Validation\ConstraintValidatorFactory;
use Drupal\Core\Validation\DrupalTranslator;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Manages data type plugins.
 */
class TypedDataManager extends DefaultPluginManager {
  use DependencySerializationTrait;

  /**
   * The validator used for validating typed data.
   *
   * @var \Symfony\Component\Validator\Validator\ValidatorInterface
   */
  protected $validator;

  /**
   * The validation constraint manager to use for instantiating constraints.
   *
   * @var \Drupal\Core\Validation\ConstraintManager
   */
  protected $constraintManager;

  /**
   * An array of typed data property prototypes.
   *
   * @var array
   */
  protected $prototypes = array();

  /**
   * The class resolver.
   *
   * @var \Drupal\Core\DependencyInjection\ClassResolverInterface
   */
  protected $classResolver;

  /**
   * Constructs a new TypedDataManager.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\Core\DependencyInjection\ClassResolverInterface $class_resolver
   *   The class resolver.
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler, ClassResolverInterface $class_resolver) {
    $this->alterInfo('data_type_info');
    $this->setCacheBackend($cache_backend, 'typed_data_types_plugins');
    $this->classResolver = $class_resolver;

    parent::__construct('Plugin/DataType', $namespaces, $module_handler, NULL, 'Drupal\Core\TypedData\Annotation\DataType');
  }

  /**
   * Instantiates a typed data object.
   *
   * @param string $data_type
   *   The data type, for which a typed object should be instantiated.
   * @param array $configuration
   *   The plugin configuration array, i.e. an array with the following keys:
   *   - data_definition: The data definition object, i.e. an instance of
   *     \Drupal\Core\TypedData\DataDefinitionInterface.
   *   - name: (optional) If a property or list item is to be created, the name
   *     of the property or the delta of the list item.
   *   - parent: (optional) If a property or list item is to be created, the
   *     parent typed data object implementing either the ListInterface or the
   *     ComplexDataInterface.
   *
   * @return \Drupal\Core\TypedData\TypedDataInterface
   *   The instantiated typed data object.
   */
  public function createInstance($data_type, array $configuration = array()) {
    $data_definition = $configuration['data_definition'];
    $type_definition = $this->getDefinition($data_type);

    if (!isset($type_definition)) {
      throw new \InvalidArgumentException(format_string('Invalid data type %plugin_id has been given.', array('%plugin_id' => $data_type)));
    }

    // Allow per-data definition overrides of the used classes, i.e. take over
    // classes specified in the type definition.
    $class = $data_definition->getClass();

    if (!isset($class)) {
      throw new PluginException(sprintf('The plugin (%s) did not specify an instance class.', $data_type));
    }
    return $class::createInstance($data_definition, $configuration['name'], $configuration['parent']);
  }

  /**
   * Creates a new typed data object instance.
   *
   * @param \Drupal\Core\TypedData\DataDefinitionInterface $definition
   *   The data definition of the typed data object. For backwards-compatibility
   *   an array representation of the data definition may be passed also.
   * @param mixed $value
   *   (optional) The data value. If set, it has to match one of the supported
   *   data type format as documented for the data type classes.
   * @param string $name
   *   (optional) If a property or list item is to be created, the name of the
   *   property or the delta of the list item.
   * @param mixed $parent
   *   (optional) If a property or list item is to be created, the parent typed
   *   data object implementing either the ListInterface or the
   *   ComplexDataInterface.
   *
   * @return \Drupal\Core\TypedData\TypedDataInterface
   *   The instantiated typed data object.
   *
   * @see \Drupal::typedDataManager()
   * @see \Drupal\Core\TypedData\TypedDataManager::getPropertyInstance()
   * @see \Drupal\Core\TypedData\Plugin\DataType\BinaryData
   * @see \Drupal\Core\TypedData\Plugin\DataType\BooleanData
   * @see \Drupal\Core\TypedData\Plugin\DataType\Date
   * @see \Drupal\Core\TypedData\Plugin\DataType\Duration
   * @see \Drupal\Core\TypedData\Plugin\DataType\FloatData
   * @see \Drupal\Core\TypedData\Plugin\DataType\IntegerData
   * @see \Drupal\Core\TypedData\Plugin\DataType\StringData
   * @see \Drupal\Core\TypedData\Plugin\DataType\Uri
   */
  public function create(DataDefinitionInterface $definition, $value = NULL, $name = NULL, $parent = NULL) {
    $typed_data = $this->createInstance($definition->getDataType(), array(
      'data_definition' => $definition,
      'name' => $name,
      'parent' => $parent,
    ));
    if (isset($value)) {
      $typed_data->setValue($value, FALSE);
    }
    return $typed_data;
  }

  /**
   * Creates a new data definition object.
   *
   * While data definitions objects may be created directly if the definition
   * class used by a data type is known, this method allows the creation of data
   * definitions for any given data type.
   *
   * E.g., if a definition for a map is to be created, the following code
   * could be used instead of calling this method with the argument 'map':
   * @code
   *   $map_definition = \Drupal\Core\TypedData\MapDataDefinition::create();
   * @endcode
   *
   * @param string $data_type
   *   The data type, for which a data definition should be created.
   *
   * @return \Drupal\Core\TypedData\DataDefinitionInterface
   *   A data definition for the given data type.
   *
   * @see \Drupal\Core\TypedData\TypedDataManager::createListDataDefinition()
   */
  public function createDataDefinition($data_type) {
    $type_definition = $this->getDefinition($data_type);
    if (!isset($type_definition)) {
      throw new \InvalidArgumentException(format_string('Invalid data type %plugin_id has been given.', array('%plugin_id' => $data_type)));
    }
    $class = $type_definition['definition_class'];
    return $class::createFromDataType($data_type);
  }

  /**
   * Creates a new list data definition for items of the given data type.
   *
   * @param string $item_type
   *   The item type, for which a list data definition should be created.
   *
   * @return \Drupal\Core\TypedData\ListDataDefinitionInterface
   *   A list definition for items of the given data type.
   *
   * @see \Drupal\Core\TypedData\TypedDataManager::createDataDefinition()
   */
  public function createListDataDefinition($item_type) {
    $type_definition = $this->getDefinition($item_type);
    if (!isset($type_definition)) {
      throw new \InvalidArgumentException(format_string('Invalid data type %plugin_id has been given.', array('%plugin_id' => $item_type)));
    }
    $class = $type_definition['list_definition_class'];
    return $class::createFromItemType($item_type);
  }

  /**
   * Implements \Drupal\Component\Plugin\PluginManagerInterface::getInstance().
   *
   * @param array $options
   *   An array of options with the following keys:
   *   - object: The parent typed data object, implementing the
   *     TypedDataInterface and either the ListInterface or the
   *     ComplexDataInterface.
   *   - property: The name of the property to instantiate, or the delta of the
   *     the list item to instantiate.
   *   - value: The value to set. If set, it has to match one of the supported
   *     data type formats as documented by the data type classes.
   *
   * @throws \InvalidArgumentException
   *   If the given property is not known, or the passed object does not
   *   implement the ListInterface or the ComplexDataInterface.
   *
   * @return \Drupal\Core\TypedData\TypedDataInterface
   *   The new property instance.
   *
   * @see \Drupal\Core\TypedData\TypedDataManager::getPropertyInstance()
   */
  public function getInstance(array $options) {
    return $this->getPropertyInstance($options['object'], $options['property'], $options['value']);
  }

  /**
   * Get a typed data instance for a property of a given typed data object.
   *
   * This method will use prototyping for fast and efficient instantiation of
   * many property objects with the same property path; e.g.,
   * when multiple comments are used comment_body.0.value needs to be
   * instantiated very often.
   * Prototyping is done by the root object's data type and the given
   * property path, i.e. all property instances having the same property path
   * and inheriting from the same data type are prototyped.
   *
   * @param \Drupal\Core\TypedData\TypedDataInterface $object
   *   The parent typed data object, implementing the TypedDataInterface and
   *   either the ListInterface or the ComplexDataInterface.
   * @param string $property_name
   *   The name of the property to instantiate, or the delta of an list item.
   * @param mixed $value
   *   (optional) The data value. If set, it has to match one of the supported
   *   data type formats as documented by the data type classes.
   *
   * @throws \InvalidArgumentException
   *   If the given property is not known, or the passed object does not
   *   implement the ListInterface or the ComplexDataInterface.
   *
   * @return \Drupal\Core\TypedData\TypedDataInterface
   *   The new property instance.
   *
   * @see \Drupal\Core\TypedData\TypedDataManager::create()
   */
  public function getPropertyInstance(TypedDataInterface $object, $property_name, $value = NULL) {
    // For performance, try to reuse existing prototypes instead of
    // constructing new objects when possible. A prototype is reused when
    // creating a data object:
    // - for a similar root object (same data type and settings),
    // - at the same property path under that root object.
    $root_definition = $object->getRoot()->getDataDefinition();
    // If the root object is a list, we want to look at the data type and the
    // settings of its item definition.
    if ($root_definition instanceof ListDataDefinition) {
      $root_definition = $root_definition->getItemDefinition();
    }

    // Root data type and settings.
    $parts[] = $root_definition->getDataType();
    if ($settings = $root_definition->getSettings()) {
      // Hash the settings into a string. crc32 is the fastest way to hash
      // something for non-cryptographic purposes.
      $parts[] = crc32(serialize($settings));
    }
    // Property path for the requested data object. When creating a list item,
    // use 0 in the key as all items look the same.
    $parts[] = $object->getPropertyPath() . '.' . (is_numeric($property_name) ? 0 : $property_name);
    $key = implode(':', $parts);

    // Create the prototype if needed.
    if (!isset($this->prototypes[$key])) {
      // Fetch the data definition for the child object from the parent.
      if ($object instanceof ComplexDataInterface) {
        $definition = $object->getDataDefinition()->getPropertyDefinition($property_name);
      }
      elseif ($object instanceof ListInterface) {
        $definition = $object->getItemDefinition();
      }
      else {
        throw new \InvalidArgumentException("The passed object has to either implement the ComplexDataInterface or the ListInterface.");
      }
      if (!$definition) {
        throw new \InvalidArgumentException('Property ' . SafeMarkup::checkPlain($property_name) . ' is unknown.');
      }
      // Create the prototype without any value, but with initial parenting
      // so that constructors can set up the objects correclty.
      $this->prototypes[$key] = $this->create($definition, NULL, $property_name, $object);
    }

    // Clone the prototype, update its parenting information, and assign the
    // value.
    $property = clone $this->prototypes[$key];
    $property->setContext($property_name, $object);
    if (isset($value)) {
      $property->setValue($value, FALSE);
    }
    return $property;
  }

  /**
   * Sets the validator for validating typed data.
   *
   * @param \Symfony\Component\Validator\Validator\ValidatorInterface $validator
   *   The validator object to set.
   */
  public function setValidator(ValidatorInterface $validator) {
    $this->validator = $validator;
  }

  /**
   * Gets the validator for validating typed data.
   *
   * @return \Symfony\Component\Validator\Validator\ValidatorInterface
   *   The validator object.
   */
  public function getValidator() {
    if (!isset($this->validator)) {
      $this->validator = new RecursiveValidator(
        new ExecutionContextFactory(new DrupalTranslator()),
        new ConstraintValidatorFactory($this->classResolver),
        $this
      );
    }
    return $this->validator;
  }

  /**
   * Sets the validation constraint manager.
   *
   * The validation constraint manager is used to instantiate validation
   * constraint plugins.
   *
   * @param \Drupal\Core\Validation\ConstraintManager
   *   The constraint manager to set.
   */
  public function setValidationConstraintManager(ConstraintManager $constraintManager) {
    $this->constraintManager = $constraintManager;
  }

  /**
   * Gets the validation constraint manager.
   *
   * @return \Drupal\Core\Validation\ConstraintManager
   *   The constraint manager.
   */
  public function getValidationConstraintManager() {
    return $this->constraintManager;
  }

  /**
   * Gets default constraints for the given data definition.
   *
   * This generates default constraint definitions based on the data definition;
   * e.g. a NotNull constraint is generated if the data is defined as required.
   * Besides that any constraints defined for the data type, i.e. below the
   * 'constraint' key of the type's plugin definition, are taken into account.
   *
   * @param \Drupal\Core\TypedData\DataDefinitionInterface $definition
   *   A data definition.
   *
   * @return array
   *   An array of validation constraint definitions, keyed by constraint name.
   *   Each constraint definition can be used for instantiating
   *   \Symfony\Component\Validator\Constraint objects.
   */
  public function getDefaultConstraints(DataDefinitionInterface $definition) {
    $constraints = array();
    $type_definition = $this->getDefinition($definition->getDataType());
    // Auto-generate a constraint for data types implementing a primitive
    // interface.
    if (is_subclass_of($type_definition['class'], '\Drupal\Core\TypedData\PrimitiveInterface')) {
      $constraints['PrimitiveType'] = array();
    }
    // Add in constraints specified by the data type.
    if (isset($type_definition['constraints'])) {
      $constraints += $type_definition['constraints'];
    }
    // Add the NotNull constraint for required data.
    if ($definition->isRequired()) {
      $constraints['NotNull'] = array();
    }
    // Check if the class provides allowed values.
    if (is_subclass_of($definition->getClass(),'Drupal\Core\TypedData\OptionsProviderInterface')) {
      $constraints['AllowedValues'] = array();
    }
    return $constraints;
  }

  /**
   * {@inheritdoc}
   */
  public function clearCachedDefinitions() {
    parent::clearCachedDefinitions();
    $this->prototypes = array();
  }

  /**
   * Gets the canonical representation of a TypedData object.
   *
   * The canonical representation is typically used when data is passed on to
   * other code components. In many use cases, the TypedData object is mostly
   * unified adapter wrapping a primary value (e.g. a string, an entity...)
   * which is the canonical representation that consuming code like constraint
   * validators are really interested in. For some APIs, though, the domain
   * object (e.g. Field API's FieldItem and FieldItemList) directly implements
   * TypedDataInterface, and the canonical representation is thus the data
   * object itself.
   *
   * When a TypedData object gets validated, for example, its canonical
   * representation is passed on to constraint validators, which thus receive
   * an Entity unwrapped, but a FieldItem as is.
   *
   * Data types specify whether their data objects need unwrapping by using the
   * 'unwrap_for_canonical_representation' property in the data definition
   * (defaults to TRUE).
   *
   * @param \Drupal\Core\TypedData\TypedDataInterface $data
   *   The data.
   *
   * @return mixed
   *   The canonical representation of the passed data.
   */
  public function getCanonicalRepresentation(TypedDataInterface $data) {
    $data_definition = $data->getDataDefinition();
    // In case a list is passed, respect the 'wrapped' key of its data type.
    if ($data_definition instanceof ListDataDefinitionInterface) {
      $data_definition = $data_definition->getItemDefinition();
    }
    // Get the plugin definition of the used data type.
    $type_definition = $this->getDefinition($data_definition->getDataType());
    if (!empty($type_definition['unwrap_for_canonical_representation'])) {
      return $data->getValue();
    }
    return $data;
  }

}
