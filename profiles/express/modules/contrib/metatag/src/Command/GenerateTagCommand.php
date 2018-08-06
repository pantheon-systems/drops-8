<?php
/**
 * @file
 * Contains Drupal\metatag\Command\GenerateTagCommand.
 */

namespace Drupal\metatag\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Command\Command;
use Drupal\Console\Core\Command\Shared\CommandTrait;
use Drupal\Console\Core\Style\DrupalStyle;
use Drupal\metatag\MetatagManager;
use Drupal\metatag\Generator\MetatagTagGenerator;
use Drupal\Console\Command\Shared\ModuleTrait;
use Drupal\Console\Command\Shared\FormTrait;
use Drupal\Console\Command\Shared\ConfirmationTrait;
use Symfony\Component\Console\Input\InputOption;
use Drupal\Console\Extension\Manager;
use Drupal\Console\Core\Utils\StringConverter;
use Drupal\Console\Core\Utils\ChainQueue;

/**
 * Class GenerateTagCommand.
 *
 * Generate a Metatag tag plugin.
 *
 * @package Drupal\metatag
 */
class GenerateTagCommand extends Command {

  use CommandTrait;
  use ModuleTrait;
  use FormTrait;
  use ConfirmationTrait;

  /**
   * @var MetatagManager
   */
  protected $metatagManager;

  /**
   * @var MetatagTagGenerator
   */
  protected $generator;

  /** @var Manager  */
  protected $extensionManager;

  /**
   * @var StringConverter
   */
  protected $stringConverter;

  /**
   * @var ChainQueue
   */
  protected $chainQueue;

  /**
   * GenerateTagCommand constructor.
   *
   * @param MetatagManager $metatagManager
   * @param MetatagTagGenerator $generator
   * @param Manager $extensionManager
   * @param StringConverter $stringConverter
   * @param ChainQueue $chainQueue
   */
  public function __construct(
      MetatagManager $metatagManager,
      MetatagTagGenerator $generator,
      Manager $extensionManager,
      StringConverter $stringConverter,
      ChainQueue $chainQueue
    ) {
    $this->metatagManager = $metatagManager;
    $this->generator = $generator;
    $this->extensionManager = $extensionManager;
    $this->stringConverter = $stringConverter;
    $this->chainQueue = $chainQueue;

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
      ->addOption('base_class', '', InputOption::VALUE_REQUIRED,
        $this->trans('commands.common.options.base_class'))
      ->addOption('module', '', InputOption::VALUE_REQUIRED,
        $this->trans('commands.common.options.module'))
      ->addOption('name', '', InputOption::VALUE_REQUIRED,
        $this->trans('commands.generate.metatag.tag.options.name'))
      ->addOption('label', '', InputOption::VALUE_REQUIRED,
        $this->trans('commands.generate.metatag.tag.options.label'))
      ->addOption('description', '', InputOption::VALUE_OPTIONAL,
        $this->trans('commands.generate.metatag.tag.options.description'))
      ->addOption('plugin-id', '', InputOption::VALUE_REQUIRED,
        $this->trans('commands.generate.metatag.tag.options.plugin_id'))
      ->addOption('class-name', '', InputOption::VALUE_REQUIRED,
        $this->trans('commands.generate.metatag.tag.options.class_name'))
      ->addOption('group', '', InputOption::VALUE_REQUIRED,
        $this->trans('commands.generate.metatag.tag.options.group'))
      ->addOption('weight', '', InputOption::VALUE_REQUIRED,
        $this->trans('commands.generate.metatag.tag.options.weight'))
      ->addOption('type', '', InputOption::VALUE_REQUIRED,
        $this->trans('commands.generate.metatag.tag.options.type'))
      ->addOption('secure', '', InputOption::VALUE_REQUIRED,
        $this->trans('commands.generate.metatag.tag.options.secure'))
      ->addOption('multiple', '', InputOption::VALUE_REQUIRED,
        $this->trans('commands.generate.metatag.tag.options.multiple'))
      ;
  }

  /**
   * {@inheritdoc}
   */
  protected function execute(InputInterface $input, OutputInterface $output) {
    $io = new DrupalStyle($input, $output);

    // @see use Drupal\Console\Command\ConfirmationTrait::confirmGeneration
    if (!$this->confirmGeneration($io)) {
      return 1;
    }

    $base_class = $input->getOption('base_class');
    $module = $input->getOption('module');
    $name = $input->getOption('name');
    $label = $input->getOption('label');
    $description = $input->getOption('description');
    $plugin_id = $input->getOption('plugin-id');
    $class_name = $input->getOption('class-name');
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

    $io = new DrupalStyle($input, $output);

    $boolean_options = [
      'FALSE',
      'TRUE',
    ];

    // ToDo: Take this from typed data, so it can be extended?
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
      $base_class = $io->ask(
        $this->trans('commands.generate.metatag.tag.questions.base_class'),
        'MetaNameBase'
      );
    }
    $input->setOption('base_class', $base_class);

    // --module option.
    $module = $input->getOption('module');
    if (empty($module)) {
      // @see Drupal\AppConsole\Command\Helper\ModuleTrait::moduleQuestion
      $module = $this->moduleQuestion($io);
    }
    $input->setOption('module', $module);

    // --name option.
    // @todo Add validation.
    $name = $input->getOption('name');
    if (empty($name)) {
      $name = $io->ask(
        $this->trans('commands.generate.metatag.tag.questions.name')
      );
    }
    $input->setOption('name', $name);

    // --label option.
    $label = $input->getOption('label');
    if (empty($label)) {
      $label = $io->ask(
        $this->trans('commands.generate.metatag.tag.questions.label'),
        $name
      );
    }
    $input->setOption('label', $label);

    // --description option.
    $description = $input->getOption('description');
    if (empty($description)) {
      $description = $io->ask(
        $this->trans('commands.generate.metatag.tag.questions.description')
      );
    }
    $input->setOption('description', $description);

    // --plugin-id option.
    $plugin_id = $input->getOption('plugin-id');
    if (empty($plugin_id)) {
      $plugin_id = $this->nameToPluginId($name);
      $plugin_id = $io->ask(
        $this->trans('commands.generate.metatag.tag.questions.plugin_id'),
        $plugin_id
      );
    }
    $input->setOption('plugin-id', $plugin_id);

    // --class-name option.
    $class_name = $input->getOption('class-name');
    if (empty($class_name)) {
      $class_name = $this->nameToClassName($name);
      $class_name = $io->ask(
        $this->trans('commands.generate.metatag.tag.questions.class_name'),
        $class_name
      );
    }
    $input->setOption('class-name', $class_name);

    // --group option.
    $group = $input->getOption('group');
    if (empty($group)) {
      $groups = $this->getGroups();
      $group = $io->choice(
        $this->trans('commands.generate.metatag.tag.questions.group'),
        $groups
      );
    }
    $input->setOption('group', $group);

    // --weight option.
    // @todo Automatically get the next integer value based upon the current
    //   group.
    $weight = $input->getOption('weight');
    if (is_null($weight)) {
      $weight = $io->ask(
        $this->trans('commands.generate.metatag.tag.questions.weight'),
        0
      );
    }
    $input->setOption('weight', $weight);

    // --type option.
    // @todo Turn this into an option.
    $type = $input->getOption('type');
    if (is_null($type)) {
      $type = $io->choice(
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
      $secure = $io->choice(
        $this->trans('commands.generate.metatag.tag.questions.secure'),
        $boolean_options,
        0
      );
    }
    $input->setOption('secure', $secure);

    // --multiple option.
    $multiple = $input->getOption('multiple');
    if (is_null($multiple)) {
      $multiple = $io->choice(
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

  /**
   * Confirm that a requested group exists.
   *
   * @param string $group
   *   A group's machine name.
   *
   * @return string
   *   The group's name, if available, otherwise an empty string.
   */
  private function validateGroupExist($group) {
    $groups = $this->getGroups();
    if (isset($groups[$group])) {
      return $group;
    }
    return '';
  }

}
