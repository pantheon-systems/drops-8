<?php

namespace Drupal\metatag\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Drupal\Console\Core\Command\Shared\CommandTrait;
use Drupal\Console\Command\Shared\ModuleTrait;
use Drupal\Console\Command\Shared\FormTrait;
use Drupal\Console\Command\Shared\ConfirmationTrait;
use Drupal\Console\Core\Style\DrupalStyle;
use Drupal\metatag\Generator\MetatagGroupGenerator;
use Drupal\Console\Extension\Manager;
use Drupal\Console\Core\Utils\ChainQueue;
use Drupal\Console\Core\Command\Command;
use Drupal\Console\Utils\Validator;

/**
 * Class GenerateGroupCommand.
 *
 * Generate a Metatag group plugin.
 *
 * @package Drupal\metatag
 */
class GenerateGroupCommand extends Command {

  use ModuleTrait;
  use ConfirmationTrait;

  /**
   * The metatag group generator.
   *
   * @var \Drupal\metatag\Generator\MetatagGroupGenerator
   */
  protected $generator;

  /**
   * The console extension manager.
   *
   * @var \Drupal\Console\Extension\Manager
   */
  protected $extensionManager;

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
   * @param \Drupal\metatag\Generator\MetatagGroupGenerator $generator
   *   The generator object.
   * @param \Drupal\Console\Extension\Manager $extensionManager
   *   The extension manager object.
   * @param \Drupal\Console\Core\Utils\ChainQueue $chainQueue
   *   The chain queue object.
   * @param Validator $validator
   */
  public function __construct(
      MetatagGroupGenerator $generator,
      Manager $extensionManager,
      ChainQueue $chainQueue,
      Validator $validator
    ) {
    $this->generator = $generator;
    $this->extensionManager = $extensionManager;
    $this->chainQueue = $chainQueue;
    $this->validator = $validator;

    parent::__construct();
  }

  /**
   * {@inheritdoc}
   */
  protected function configure() {
    $this
      ->setName('generate:plugin:metatag:group')
      ->setDescription($this->trans('commands.generate.metatag.group.description'))
      ->setHelp($this->trans('commands.generate.metatag.group.help'))
      ->addOption(
        'base_class',
        null,
        InputOption::VALUE_REQUIRED,
        $this->trans('commands.common.options.base_class')
      )
      ->addOption(
        'module',
        null,
        InputOption::VALUE_REQUIRED,
        $this->trans('commands.common.options.module')
      )
      ->addOption(
        'label',
        null,
        InputOption::VALUE_REQUIRED,
        $this->trans('commands.generate.metatag.group.options.label')
      )
      ->addOption(
        'description',
        null,
        InputOption::VALUE_OPTIONAL,
        $this->trans('commands.generate.metatag.group.options.description')
      )
      ->addOption(
        'plugin-id',
        null,
        InputOption::VALUE_REQUIRED,
        $this->trans('commands.generate.metatag.group.options.plugin_id')
      )
      ->addOption(
        'class-name',
        null,
        InputOption::VALUE_REQUIRED,
        $this->trans('commands.generate.metatag.group.options.class_name')
      )
      ->addOption(
        'weight',
        null,
        InputOption::VALUE_REQUIRED,
        $this->trans('commands.generate.metatag.group.options.weight')
      );
  }

  /**
   * {@inheritdoc}
   */
  protected function execute(InputInterface $input, OutputInterface $output) {

    $module = $this->validateModule($input->getOption('module'));
    $base_class = $input->getOption('base_class');
    $label = $input->getOption('label');
    $description = $input->getOption('description');
    $plugin_id = $input->getOption('plugin-id');
    $class_name = $this->validator->validateClassName($input->getOption('class-name'));
    $weight = $input->getOption('weight');

    $this->generator
      ->generate($base_class, $module, $label, $description, $plugin_id, $class_name, $weight);

    $this->chainQueue->addCommand('cache:rebuild', ['cache' => 'discovery']);
  }

  /**
   * {@inheritdoc}
   */
  protected function interact(InputInterface $input, OutputInterface $output) {
    // --base_class option.
    // @todo Turn this into a choice() option.
    $base_class = $input->getOption('base_class');
    if (empty($base_class)) {
      $base_class = $this->getIo()->ask(
        $this->trans('commands.generate.metatag.group.questions.base_class'),
        'GroupBase'
      );
    }
    $input->setOption('base_class', $base_class);

    // --module option
    $this->getModuleOption();

    // --label option.
    $label = $input->getOption('label');
    if (empty($label)) {
      $label = $this->getIo()->ask(
        $this->trans('commands.generate.metatag.group.questions.label')
      );
    }
    $input->setOption('label', $label);

    // --description option.
    $description = $input->getOption('description');
    if (empty($description)) {
      $description = $this->getIo()->ask(
        $this->trans('commands.generate.metatag.group.questions.description')
      );
    }
    $input->setOption('description', $description);

    // --plugin-id option.
    $plugin_id = $input->getOption('plugin-id');
    if (empty($plugin_id)) {
      $plugin_id = $this->getIo()->ask(
        $this->trans('commands.generate.metatag.group.questions.plugin_id')
      );
    }
    $input->setOption('plugin-id', $plugin_id);

    // --class-name option.
    $class_name = $input->getOption('class-name');
    if (!$class_name) {
      $class_name = $this->getIo()->ask(
        $this->trans('commands.generate.metatag.group.questions.class_name'),
        '',
        function ($class_name) {
          return $this->validator->validateClassName($class_name);
        }
      );
      $input->setOption('class-name', $class_name);
    }

    // --weight option.
    // @todo Automatically get the next int value based upon the current group.
    $weight = $input->getOption('weight');
    if (is_null($weight)) {
      $weight = $this->getIo()->ask(
        $this->trans('commands.generate.metatag.group.questions.weight'),
        0
      );
    }
    $input->setOption('weight', $weight);
  }

}
