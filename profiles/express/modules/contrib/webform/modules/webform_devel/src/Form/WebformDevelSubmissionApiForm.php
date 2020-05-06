<?php

namespace Drupal\webform_devel\Form;

use Drupal\Component\Utility\Variable;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\webform\Entity\Webform;
use Drupal\webform\Entity\WebformSubmission;
use Drupal\webform\WebformRequestInterface;
use Drupal\webform\WebformSubmissionForm;
use Drupal\webform\WebformSubmissionGenerateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form used to test programmatic submissions of webforms.
 */
class WebformDevelSubmissionApiForm extends FormBase {

  /**
   * Webform submission storage.
   *
   * @var \Drupal\webform\WebformSubmissionStorageInterface
   */
  protected $submissionStorage;

  /**
   * Webform request handler.
   *
   * @var \Drupal\webform\WebformRequestInterface
   */
  protected $requestHandler;

  /**
   * The webform submission generation service.
   *
   * @var \Drupal\webform\WebformSubmissionGenerateInterface
   */
  protected $generate;

  /**
   * Constructs a WebformDevelSubmissionApiForm object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\webform\WebformRequestInterface $request_handler
   *   The webform request handler.
   * @param \Drupal\webform\WebformSubmissionGenerateInterface $submission_generate
   *   The webform submission generation service.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, WebformRequestInterface $request_handler, WebformSubmissionGenerateInterface $submission_generate) {
    $this->submissionStorage = $entity_type_manager->getStorage('webform_submission');
    $this->requestHandler = $request_handler;
    $this->generate = $submission_generate;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('webform.request'),
      $container->get('webform_submission.generate')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'webform_devel_submission_api_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\webform\WebformInterface $webform */
    /** @var \Drupal\Core\Entity\EntityInterface $source_entity */
    list($webform, $source_entity) = $this->requestHandler->getWebformEntities();

    $values = [];

    // Set webform id.
    $values['webform_id'] = $webform->id();

    // Set source entity type and id.
    if ($source_entity) {
      $values['entity_type'] = $source_entity->getEntityTypeId();
      $values['entity_id'] = $source_entity->id();

    }
    WebformSubmission::preCreate($this->submissionStorage, $values);

    // Generate data as last value.
    unset($values['data']);
    $values['data'] = $this->generate->getData($webform);

    $form['submission'] = [
      '#type' => 'details',
      '#title' => $this->t('Submission values'),
      '#open' => TRUE,
    ];
    $form['submission']['message'] = [
      '#type' => 'webform_message',
      '#message_message' => $this->t("Submitting the below values will trigger the %title webform's ::validateFormValues() and ::submitFormValues() callbacks.", ['%title' => $webform->label()]),
      '#message_type' => 'warning',
    ];
    $form['submission']['values'] = [
      '#type' => 'webform_codemirror',
      '#mode' => 'yaml',
      '#title' => $this->t('Values'),
      '#title_display' => 'hidden',
      '#default_value' => $values,
    ];

    $form['php'] = [
      '#type' => 'details',
      '#title' => $this->t('PHP usage'),
      '#description' => $this->t('Below is an example of how to programatically validate and submit a webform submission using PHP.'),
    ];
    $form['php']['code'] = [
      '#type' => 'webform_codemirror',
      '#mode' => 'php',
      '#title' => $this->t('PHP'),
      '#title_display' => 'hidden',
      '#attributes' => ['readonly' => 'readonly', 'disabled' => 'disabled'],
      '#default_value' => '
// Get submission values and data.
$values = ' . Variable::export($values) . ';

// Check that the webform is open.
$webform = \Drupal\webform\entity\Webform::load(\'' . $webform->id() . '\'); 
$is_open = \Drupal\webform\WebformSubmissionForm::isOpen($webform);
if ($is_open === TRUE) {
  // Validate webform submission values.
  $errors = \Drupal\webform\WebformSubmissionForm::validateFormValues($values);
  
  // Submit webform submission values.
  if (empty($errors)) {
    $webform_submission = \Drupal\webform\WebformSubmissionForm::submitFormValues($values);
  }
}',
    ];

    $form['actions'] = [];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValue('values');

    // Check if the webform is open to new submissions.
    $webform = Webform::load($values['webform_id']);
    if (!$webform) {
      $form_state->setErrorByName('values', $this->t('Webform %webform_id not found.', ['%webform_id' => $values['webform_id']]));
      return;
    }

    $is_open = WebformSubmissionForm::isOpen($webform);
    if ($is_open !== TRUE) {
      $form_state->setErrorByName('values', $is_open);
    }

    // Validate values.
    if ($errors = WebformSubmissionForm::validateFormValues($values)) {
      foreach ($errors as $error) {
        $form_state->setErrorByName('values', $error);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValue('values');
    $webform_submission = WebformSubmissionForm::submitFormValues($values);
    $this->messenger()->addStatus($this->t('New submission %title added.', [
      '%title' => $webform_submission->label(),
    ]));
  }

}
