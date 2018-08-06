<?php

namespace Drupal\webform\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Routing\TrustedRedirectResponse;
use Drupal\webform\WebformHelpManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Help video form.
 */
class WebformHelpVideoForm extends FormBase {

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

    // Actions.
    if (isset($video['submit_label'])) {
      $form['actions'] = ['#type' => 'actions'];
      $form['actions']['submit'] = [
        '#type' => 'submit',
        '#value' => $video['submit_label'],
        '#button_type' => 'primary',
      ];
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $video = $this->helpManager->getVideo($this->videoId);
    /** @var \Drupal\Core\Url $url */
    $url = $video['submit_url'];
    $form_state->setResponse(new TrustedRedirectResponse($url->setAbsolute()->toString()));
  }

}
