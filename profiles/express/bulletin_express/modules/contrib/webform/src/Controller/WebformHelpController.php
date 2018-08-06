<?php

namespace Drupal\webform\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\webform\WebformHelpManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides route responses for webform help.
 */
class WebformHelpController extends ControllerBase implements ContainerInjectionInterface {

  /**
   * The help manager.
   *
   * @var \Drupal\Component\Plugin\PluginManagerInterface
   */
  protected $helpManager;

  /**
   * Constructs a WebformHelpController object.
   *
   * @param \Drupal\webform\WebformHelpManagerInterface $help_manager
   *   The help manager.
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
   * Returns dedicated help video page.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request.
   * @param string $id
   *   The video id.
   *
   * @return array
   *   A renderable array containing a help video player page.
   */
  public function index(Request $request, $id) {
    $id = str_replace('-', '_', $id);
    $video = $this->helpManager->getVideo($id);
    if (!$video) {
      throw new NotFoundHttpException();
    }

    $build = [];
    if (is_array($video['content'])) {
      $build['content'] = $video['content'];
    }
    else {
      $build['content'] = [
        '#markup' => $video['content'],
      ];
    }
    if ($video['youtube_id']) {
      $build['video'] = [
        '#theme' => 'webform_help_video_youtube',
        '#youtube_id' => $video['youtube_id'],
      ];
    }
    return $build;
  }

  /**
   * Route title callback.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request.
   * @param string $id
   *   The id of the dedicated help section.
   *
   * @return string
   *   The dedicated help section's title.
   */
  public function title(Request $request, $id) {
    $id = str_replace('-', '_', $id);
    $video = $this->helpManager->getVideo($id);
    return (isset($video)) ? $video['title'] : $this->t('Watch video');
  }

}
