<?php

namespace Drupal\webform_devel\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Markup;
use Drupal\Core\Serialization\Yaml;
use Drupal\Core\Url;
use Drupal\webform\Utility\WebformDialogHelper;
use Drupal\webform\Utility\WebformFormHelper;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

/**
 * Export a webform's element to Form API (FAPI).
 */
class WebformDevelEntityFormApiExportForm extends WebformDevelEntityFormApiBaseForm {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\webform\WebformInterface $webform */
    $webform = $this->getEntity();
    $elements = $webform->getElementsDecoded();
    $this->cleanupElements($elements);
    $this->setDefaultValues($elements);

    $elements['actions'] = [
      '#type' => 'actions',
      '#tree' => TRUE,
      'submit' => [
        '#type' => 'submit',
        '#value' => (string) $this->t('Save configuration'),
        '#button_type' => 'primary',
      ],
    ];

    $webform_id = $webform->id();
    $webform_label = $webform->label();
    $webform_form_name = str_replace('_', '', ucwords($webform_id, '_')) . 'SettingsForm';

    // Filenames.
    $file_names = [
      'info' => "/$webform_id/$webform_id.info.yml",
      'routing' => "/$webform_id/$webform_id.routing.yml",
      'form' => "/$webform_id/src/Form/$webform_form_name.php",
      'config' => "/$webform_id/config/install/$webform_id.settings.yml",
    ];

    // Form.
    $webform_elements = str_replace("\n", "\n    ", trim($this->renderExport($elements)));
    $webform_elements = preg_replace("/'##([^#]+)##'/ims", '$config->get(\'$1\')', $webform_elements);
    $build = [
      '#type' => 'inline_template',
      '#template' => $this->getPhpTemplate(),
      '#context' => [
        'name' => $webform->id(),
        'label' => $webform->label(),
        'class_name' => str_replace('_', '', ucwords($webform->id(), '_')),
        'form' => Markup::create($webform_elements),
      ],
    ];
    $form_php = $this->renderer->render($build);

    // Config.
    $config = $this->generate->getData($webform);

    // Form.
    $form['code'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Form API (FAPI) Code'),
      '#file_names' => $file_names,
      '#tree' => TRUE,
    ];
    $form['code']['description'] = [
      '#markup' => $this->t('Learn more about <a href="https://www.drupal.org/docs/8/api/form-api">Form API in Drupal 8</a>.'),
      '#prefix' => '<p>',
      '#suffix' => '</p>',
    ];
    // Info.
    $form['code']['info'] = [
      '#type' => 'webform_codemirror',
      '#mode' => 'yaml',
      '#title' => $this->t('Module info'),
      '#description' => $this->t('Filename: %file', ['%file' => $file_names['info']]),
      '#help' => FALSE,
      '#attributes' => ['readonly' => TRUE],
      '#default_value' => Yaml::encode([
        'name' => $webform_label,
        'type' => 'module',
        'description' => $webform->getDescription(),
        'package' => 'Webform Custom',
        'core' => '8.x',
        'configure' => "$webform_id.settings",
        'dependencies' => ['webform:webform'],
      ]),
    ];
    // Routing.
    $form['code']['routing'] = [
      '#type' => 'webform_codemirror',
      '#mode' => 'yaml',
      '#title' => $this->t('Routing'),
      '#description' => $this->t('Filename: %file', ['%file' => $file_names['routing']]),
      '#help' => FALSE,
      '#attributes' => ['readonly' => TRUE],
      '#default_value' => Yaml::encode([
        $webform_id . '.settings' => [
          'path' => '/admin/config/' . $webform_id,
          'defaults' => [
            '_form' => "\Drupal\\$webform_id\Form\\$webform_form_name",
            '_title' => $webform_label,
          ],
          'requirements' => [
            '_permission' => 'administer configuration',
          ],
        ],
      ]),
    ];
    // Form (API).
    $form['code']['form'] = [
      '#type' => 'webform_codemirror',
      '#mode' => 'php',
      '#title' => $this->t('Configuration settings form'),
      '#description' => $this->t('Filename: %file', ['%file' => $file_names['form']]),
      '#help' => FALSE,
      '#attributes' => ['readonly' => TRUE],
      '#default_value' => $form_php,
    ];
    // Config.
    $form['code']['config'] = [
      '#type' => 'webform_codemirror',
      '#mode' => 'yaml',
      '#title' => $this->t('Default configuration'),
      '#description' => $this->t('Filename: %file', ['%file' => $file_names['config']]),
      '#help' => FALSE,
      '#attributes' => ['readonly' => TRUE],
      '#default_value' => Yaml::encode($config),
    ];

    $form['actions'] = [
      '#type' => 'actions',
      'download' => [
        '#type' => 'submit',
        '#value' => $this->t('Download'),
        '#button_type' => 'primary',
      ],
      'test' => [
        '#type' => 'link',
        '#title' => $this->t('Test'),
        '#url' => Url::fromRoute('entity.webform.fapi_test_form', ['webform' => $webform_id]),
        '#attributes' => WebformDialogHelper::getModalDialogAttributes(WebformDialogHelper::DIALOG_WIDE, ['button']),
      ],
    ];
    WebformDialogHelper::attachLibraries($form);
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    /** @var \Drupal\webform\WebformInterface $webform */
    $webform = $this->getEntity();

    // Get the Tar archive.
    $archive_file_path = file_directory_temp() . '/' . $webform->id() . '.tar.gz';
    $archive = new \Archive_Tar($archive_file_path, 'gz');

    // Add code to archive.
    $file_names = $form['code']['#file_names'];
    $code = $form_state->getValue('code');
    foreach ($file_names as $key => $file_name) {
      $archive->addString($file_name, $code[$key]);
    }

    // Set archive as the response and delete the temp file.
    $response = new BinaryFileResponse($archive_file_path, 200, [], FALSE);
    $response->setContentDisposition(
      ResponseHeaderBag::DISPOSITION_ATTACHMENT,
      $webform->id() . '.tar.gz'
    );
    $response->deleteFileAfterSend(TRUE);
    $form_state->setResponse($response);
  }

  /****************************************************************************/
  // Helper functions.
  /****************************************************************************/

  /**
   * Set webform elements default values using test data..
   *
   * @param array $elements
   *   An render array representing elements.
   */
  protected function setDefaultValues(array &$elements) {
    /** @var \Drupal\webform\WebformInterface $webform */
    $webform = $this->getEntity();
    $flattened_elements =& WebformFormHelper::flattenElements($elements);
    foreach ($flattened_elements as $element_key => &$element) {
      $element_plugin = $this->elementManager->getElementInstance($element);
      if ($element_plugin->isInput($element)) {
        $element['#default_value'] = "##$element_key##";
      }
    }
  }

  /**
   * Get the form's PHP template.
   *
   * @return string
   *   The form's PHP template.
   */
  protected function getPhpTemplate() {
    return <<<'EOT'
<?php

namespace Drupal\{{ name }}\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * {{ label }} configuration settings form.
 */
class {{ class_name }}SettingsForm extends ConfigFormBase {

  /**
   * The webform token manager.
   *
   * @var \Drupal\webform\WebformTokenManagerInterface
   */
  protected $tokenManager;

  /**
   * The webform element (plugin) manager.
   *
   * @var \Drupal\webform\Plugin\WebformElementManagerInterface
   */
  protected $elementManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    $instance->tokenManager = $container->get('webform.token_manager');
    $instance->elementManager = $container->get('plugin.manager.webform.element');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return '{{ name }}_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['{{ name }}.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('{{ name }}.settings');

    {{ form }}

    // Process elements.
    $this->elementManager->processElements($form);

    // Replace tokens.
    $form = $this->tokenManager->replace($form);

    // Attach the webform library.
    $form['#attached']['library'][] = 'webform/webform.form';

    // Autofocus: Save details open/close state.
    $form['#attributes']['class'][] = 'js-webform-autofocus';
    $form['#attached']['library'][] = 'webform/webform.form.auto_focus';

    // Unsaved: Warn users about unsaved changes.
    $form['#attributes']['class'][] = 'js-webform-unsaved';
    $form['#attached']['library'][] = 'webform/webform.form.unsaved';

    // Details save: Attach details element save open/close library.
    $form['#attached']['library'][] = 'webform/webform.element.details.save';

    // Details toggle: Display collapse/expand all details link.
    $form['#attributes']['class'][] = 'js-webform-details-toggle';
    $form['#attributes']['class'][] = 'webform-details-toggle';
    $form['#attached']['library'][] = 'webform/webform.element.details.toggle';

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Get all values.
    $values = $form_state->getValues();

    // Remove Form API values.
    unset(
      $values['form_build_id'],
      $values['form_token'],
      $values['form_id'],
      $values['op'],
      $values['actions']
    );

    // Save config.
    $this->config('{{ name }}.settings')
      ->setData($values)
      ->save();

    // Display message.
    parent::submitForm($form, $form_state);
  }

}
EOT;
  }

}
