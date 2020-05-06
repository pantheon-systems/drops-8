<?php

namespace Drupal\webform\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\webform\WebformHelpManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Help video form.
 */
class WebformHelpVideoForm extends FormBase {

  use WebformDialogFormTrait;

  /**
   * The webform help manager.
   *
   * @var \Drupal\Component\Plugin\PluginManagerInterface
   */
  protected $helpManager;

  /**
   * The id of the current video.
   *
   * @var string
   */
  protected $videoId;

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'webform_help_video_form';
  }

  /**
   * Constructs a WebformHelpVideoForm object.
   *
   * @param \Drupal\webform\WebformHelpManagerInterface $help_manager
   *   The webform help manager.
   */
  public function __construct(WebformHelpManagerInterface $help_manager) {
    $this->helpManager = $help_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('webform.help_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $id = NULL) {
    $this->videoId = str_replace('-', '_', $id);

    $video = $this->helpManager->getVideo($this->videoId);
    if (!$video) {
      throw new NotFoundHttpException();
    }

    $form['#title'] = $video['title'];

    // Content.
    if (is_array($video['content'])) {
      $form['content'] = $video['content'];
    }
    else {
      $form['content'] = [
        '#markup' => $video['content'],
      ];
    }

    // Video.
    if ($video['youtube_id']) {
      $form['video'] = [
        '#theme' => 'webform_help_video_youtube',
        '#youtube_id' => $video['youtube_id'],
      ];
    }

    // Related resources.
    if ($video_links = $this->helpManager->getVideoLinks($this->videoId)) {
      $form['resources'] = [
        '#type' => 'details',
        '#title' => $this->t('Additional resources'),
        'links' => [
          '#theme' => 'links',
          '#links' => $video_links,
        ],
      ];
    }

    // Actions.
    if ($this->isDialog()) {
      $form['modal_actions'] = ['#type' => 'actions'];
      $form['modal_actions']['close'] = [
        '#type' => 'submit',
        '#value' => $this->t('Close'),
        '#ajax' => [
          'callback' => '::closeDialog',
          'event' => 'click',
        ],
        '#attributes' => ['class' => ['button', 'button--primary']],
      ];
      if ($this->getRequest()->query->get('more')) {
        $form['modal_actions']['more'] = [
          '#type' => 'link',
          '#title' => $this->t('▶ Watch more videos'),
          '#url' => Url::fromRoute('webform.help'),
          '#attributes' => ['class' => ['button']],
        ];
      }
    }

    $form['#attached']['library'][] = 'webform/webform.help';

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    if ($this->isDialog()) {
      $form_state->clearErrors();
    }
    else {
      parent::validateForm($form, $form_state);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Do nothing.
  }

}
