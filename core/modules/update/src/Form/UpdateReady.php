<?php

/**
 * @file
 * Contains \Drupal\update\Form\UpdateReady.
 */

namespace Drupal\update\Form;

use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\FileTransfer\Local;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\State\StateInterface;
use Drupal\Core\Updater\Updater;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configure update settings for this site.
 */
class UpdateReady extends FormBase {

  /**
   * The app root.
   *
   * @var string
   */
  protected $root;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The state key value store.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * Constructs a new UpdateReady object.
   *
   * @param string $root
   *   The app root.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The object that manages enabled modules in a Drupal installation.
   * @param \Drupal\Core\State\StateInterface $state
   *   The state key value store.
   */
  public function __construct($root, ModuleHandlerInterface $module_handler, StateInterface $state) {
    $this->root = $root;
    $this->moduleHandler = $module_handler;
    $this->state = $state;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'update_manager_update_ready_form';
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('app.root'),
      $container->get('module_handler'),
      $container->get('state')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $this->moduleHandler->loadInclude('update', 'inc', 'update.manager');
    if (!_update_manager_check_backends($form, 'update')) {
      return $form;
    }

    $form['backup'] = array(
      '#prefix' => '<strong>',
      '#markup' => $this->t('Back up your database and site before you continue. <a href="@backup_url">Learn how</a>.', array('@backup_url' => 'https://www.drupal.org/node/22281')),
      '#suffix' => '</strong>',
    );

    $form['maintenance_mode'] = array(
      '#title' => $this->t('Perform updates with site in maintenance mode (strongly recommended)'),
      '#type' => 'checkbox',
      '#default_value' => TRUE,
    );

    $form['actions'] = array('#type' => 'actions');
    $form['actions']['submit'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Continue'),
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Store maintenance_mode setting so we can restore it when done.
    $_SESSION['maintenance_mode'] = $this->state->get('system.maintenance_mode');
    if ($form_state->getValue('maintenance_mode') == TRUE) {
      $this->state->set('system.maintenance_mode', TRUE);
    }

    if (!empty($_SESSION['update_manager_update_projects'])) {
      // Make sure the Updater registry is loaded.
      drupal_get_updaters();

      $updates = array();
      $directory = _update_manager_extract_directory();

      $projects = $_SESSION['update_manager_update_projects'];
      unset($_SESSION['update_manager_update_projects']);

      $project_real_location = NULL;
      foreach ($projects as $project => $url) {
        $project_location = $directory . '/' . $project;
        $updater = Updater::factory($project_location);
        $project_real_location = drupal_realpath($project_location);
        $updates[] = array(
          'project' => $project,
          'updater_name' => get_class($updater),
          'local_url' => $project_real_location,
        );
      }

      // If the owner of the last directory we extracted is the same as the
      // owner of our configuration directory (e.g. sites/default) where we're
      // trying to install the code, there's no need to prompt for FTP/SSH
      // credentials. Instead, we instantiate a Drupal\Core\FileTransfer\Local
      // and invoke update_authorize_run_update() directly.
      if (fileowner($project_real_location) == fileowner(conf_path())) {
        $this->moduleHandler->loadInclude('update', 'inc', 'update.authorize');
        $filetransfer = new Local($this->root);
        update_authorize_run_update($filetransfer, $updates);
      }
      // Otherwise, go through the regular workflow to prompt for FTP/SSH
      // credentials and invoke update_authorize_run_update() indirectly with
      // whatever FileTransfer object authorize.php creates for us.
      else {
        system_authorized_init('update_authorize_run_update', drupal_get_path('module', 'update') . '/update.authorize.inc', array($updates), $this->t('Update manager'));
        $form_state->setRedirectUrl(system_authorized_get_url());
      }
    }
  }

}
