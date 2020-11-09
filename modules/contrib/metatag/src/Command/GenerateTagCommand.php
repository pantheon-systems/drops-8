<?php

namespace Drupal\metatag\Command;

use Drupal\Console\Command\Shared\ConfirmationTrait;
use Drupal\Console\Command\Shared\FormTrait;
use Drupal\Console\Command\Shared\ModuleTrait;
use Drupal\Console\Core\Command\Shared\CommandTrait;
use Drupal\Console\Core\Style\DrupalStyle;
use Drupal\Console\Core\Utils\ChainQueue;
use Drupal\Console\Core\Utils\StringConverter;
use Drupal\Console\Extension\Manager;
use Drupal\metatag\Generator\MetatagTagGenerator;
use Drupal\metatag\MetatagManagerInterface;
use Drupal\Console\Core\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Drupal\Console\Utils\Validator;

/**
 * Class GenerateTagCommand.
 *
 * Generate a Metatag tag plugin.
 *
 * @package Drupal\metatag
 */
class GenerateTagCommand extends Command {

  use ModuleTrait;
  use FormTrait;
  use ConfirmationTrait;

  /**
   * The Metatag manager.
   *
   * @var \Drupal\metatag\MetatagManagerInterface
   */
  protected $metatagManager;

  /**
   * The Metatag tag generator.
   *
   * @var \Drupal\metatag\Generator\MetatagTagGenerator
   */
  protected $generator;

  /**
   * An extension manager.
   *
   * @var \Drupal\Console\Extension\Manager
   */
  protected $extensionManager;

  /**
   * The string converter.
   *
   * @var \Drupal\Console\Core\Utils\StringConverter
   */
  protected $stringConverter;

  /**
   * The console chain queue.
   *
   * @var \Drupal\Console\Core\Utils\ChainQueue
   */
  protected $chainQueue;

  /**
   * @var Validator
   */
  protected $validator;

  /**
   * The GenerateTagCommand constructor.
   *
   * @param \Drupal\metatag\MetatagManagerInterface $metatagManager
   *   The metatag manager object.
   * @param \Drupal\metatag\Generator\MetatagTagGenerator $generator
   *   The tag generator object.
   * @param \Drupal\Console\Extension\Manager $extensionManager
   *   The extension manager object.
   * @param \Drupal\Console\Core\Utils\StringConverter $stringConverter
   *   The string converter object.
   * @param \Drupal\Console\Core\Utils\ChainQueue $chainQueue
   *   The chain queue object.
   * @param Validator $validator
   */
  public function __construct(
      MetatagManagerInterface $metatagManager,
      MetatagTagGenerator $generator,
      Manager $extensionManager,
      StringConverter $stringConverter,
      ChainQueue $chainQueue,
      Validator $validator
  ) {
    $this->metatagManager = $metatagManager;
    $this->generator = $generator;
    $this->extensionManager = $extensionManager;
    $this->stringConverter = $stringConverter;
    $this->chainQueue = $chainQueue;
    $this->validator = $validator;

    parent::__construct();
  }

  /**
   * {@inheritdoc}
   */
  protected function configure() {
    $this
      ->setName('generate:plugin:metatag:tag')
      ->setDescription($this->trans('commands.generate.metatag.tag.description'))
      ->setHelp($this->trans('commands.generate.metatag.tag.help'))
      ->addOption('base_class', null, InputOption::VALUE_REQUIRED,
        $this->trans('commands.common.options.base_class'))
      ->addOption('module', null, InputOption::VALUE_REQUIRED,
        $this->trans('commands.common.options.module'))
      ->addOption('name', null, InputOption::VALUE_REQUIRED,
        $this->trans('commands.generate.metatag.tag.options.name'))
      ->addOption('label', null, InputOption::VALUE_REQUIRED,
        $this->trans('commands.generate.metatag.tag.options.label'))
      ->addOption('description', null, InputOption::VALUE_OPTIONAL,
        $this->trans('commands.generate.metatag.tag.options.description'))
      ->addOption('plugin-id', null, InputOption::VALUE_REQUIRED,
        $this->trans('commands.generate.metatag.tag.options.plugin_id'))
      ->addOption('class-name', null, InputOption::VALUE_REQUIRED,
        $this->trans('commands.generate.metatag.tag.options.class_name'))
      ->addOption('group', null, InputOption::VALUE_REQUIRED,
        $this->trans('commands.generate.metatag.tag.options.group'))
      ->addOption('weight', null, InputOption::VALUE_REQUIRED,
        $this->trans('commands.generate.metatag.tag.options.weight'))
      ->addOption('type', null, InputOption::VALUE_REQUIRED,
        $this->trans('commands.generate.metatag.tag.options.type'))
      ->addOption('secure', null, InputOption::VALUE_REQUIRED,
        $this->trans('commands.generate.metatag.tag.options.secure'))
      ->addOption('multiple', null, InputOption::VALUE_REQUIRED,
        $this->trans('commands.generate.metatag.tag.options.multiple'));
  }

  /**
   * {@inheritdoc}
   */
  protected function execute(InputInterface $input, OutputInterface $output) {
    $base_class = $input->getOption('base_class');
    $module = $this->validateModule($input->getOption('module'));
    $name = $input->getOption('name');
    $label = $input->getOption('label');
    $description = $input->getOption('description');
    $plugin_id = $input->getOption('plugin-id');
    $class_name = $this->validator->validateClassName($input->getOption('class-name'));
    $group = $input->getOption('group');
    $weight = $input->getOption('weight');
    $type = $input->getOption('type');
    $secure = $input->getOption('secure');
    $multiple = $input->getOption('multiple');

    $this->generator
      ->generate($base_class, $module, $name, $label, $description, $plugin_id, $class_name, $group, $weight, $type, $secure, $multiple);

    $this->chainQueue->addCommand('cache:rebuild', ['cache' => 'discovery']);
  }

  /**
   * {@inheritdoc}
   */
  protected function interact(InputInterface $input, OutputInterface $output) {

    $boolean_options = [
      'FALSE',
      'TRUE',
    ];

    // @todo Take this from typed data, so it can be extended?
    $type_options = [
      'integer',
      'string',
      'label',
      'uri',
      'image',
    ];

    // --base_class option.
    // @todo Turn this into a choice() option.
    $base_class = $input->getOption('base_class');
    if (empty($base_class)) {
      $base_class = $this->getIo()->ask(
        $this->trans('commands.generate.metatag.tag.questions.base_class'),
        'MetaNameBase'
      );
    }
    $input->setOption('base_class', $base_class);

    // --module option.
    $this->getModuleOption();

    // --name option.
    // @todo Add validation.
    $name = $input->getOption('name');
    if (empty($name)) {
      $name = $this->getIo()->ask(
        $this->trans('commands.generate.metatag.tag.questions.name')
      );
    }
    $input->setOption('name', $name);

    // --label option.
    $label = $input->getOption('label');
    if (empty($label)) {
      $label = $this->getIo()->ask(
        $this->trans('commands.generate.metatag.tag.questions.label'),
        $name
      );
    }
    $input->setOption('label', $label);

    // --description option.
    $description = $input->getOption('description');
    if (empty($description)) {
      $description = $this->getIo()->ask(
        $this->trans('commands.generate.metatag.tag.questions.description')
      );
    }
    $input->setOption('description', $description);

    // --plugin-id option.
    $plugin_id = $input->getOption('plugin-id');
    if (empty($plugin_id)) {
      $plugin_id = $this->nameToPluginId($name);
      $plugin_id = $this->getIo()->ask(
        $this->trans('commands.generate.metatag.tag.questions.plugin_id'),
        $plugin_id
      );
    }
    $input->setOption('plugin-id', $plugin_id);

    // --class-name option.
    $class_name = $input->getOption('class-name');
    if (!$class_name) {
      $class_name = $this->getIo()->ask(
        $this->trans('commands.generate.metatag.tag.questions.class_name'),
        $this->nameToClassName($name),
        function ($class_name) {
          return $this->validator->validateClassName($class_name);
        }
      );
      $input->setOption('class-name', $class_name);
    }


    // --group option.
    $group = $input->getOption('group');
    if (empty($group)) {
      $groups = $this->getGroups();
      $group = $this->getIo()->choice(
        $this->trans('commands.generate.metatag.tag.questions.group'),
        $groups
      );
    }
    $input->setOption('group', $group);

    // --weight option.
    // @todo Automatically get the next int value based upon the current group.
    $weight = $input->getOption('weight');
    if (is_null($weight)) {
      $weight = $this->getIo()->ask(
        $this->trans('commands.generate.metatag.tag.questions.weight'),
        0
      );
    }
    $input->setOption('weight', $weight);

    // --type option.
    // @todo Turn this into an option.
    $type = $input->getOption('type');
    if (is_null($type)) {
      $type = $this->getIo()->choice(
        $this->trans('commands.generate.metatag.tag.questions.type'),
        $type_options,
        0
      );
    }
    $input->setOption('type', $type);

    // --secure option.
    // @todo Turn this into an option.
    $secure = $input->getOption('secure');
    if (is_null($secure)) {
      $secure = $this->getIo()->choice(
        $this->trans('commands.generate.metatag.tag.questions.secure'),
        $boolean_options,
        0
      );
    }
    $input->setOption('secure', $secure);

    // --multiple option.
    $multiple = $input->getOption('multiple');
    if (is_null($multiple)) {
      $multiple = $this->getIo()->choice(
        $this->trans('commands.generate.metatag.tag.questions.multiple'),
        $boolean_options,
        0
      );
    }
    $input->setOption('multiple', $multiple);
  }

  /**
   * Convert the meta tag's name to a plugin ID.
   *
   * @param string $name
   *   The meta tag name to convert.
   *
   * @return string
   *   The original string with all non-alphanumeric characters converted to
   *   underline chars.
   */
  private function nameToPluginId($name) {
    return $this->stringConverter->createMachineName($name);
  }

  /**
   * Convert the meta tag's name to a class name.
   *
   * @param string $name
   *   The meta tag name to convert.
   *
   * @return string
   *   The original string with all non-alphanumeric characters removed and
   *   converted to CamelCase.
   */
  private function nameToClassName($name) {
    return $this->stringConverter->humanToCamelCase($name);
  }

  /**
   * All of the meta tag groups.
   *
   * @return array
   *   A list of the available groups.
   */
  private function getGroups() {
    return array_keys($this->metatagManager->sortedGroups());
  }

}
