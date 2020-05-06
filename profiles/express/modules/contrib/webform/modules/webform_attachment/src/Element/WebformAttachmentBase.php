<?php

namespace Drupal\webform_attachment\Element;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element\RenderElement;
use Drupal\Core\Url;
use Drupal\webform\WebformSubmissionForm;
use Drupal\webform\WebformSubmissionInterface;

/**
 * Provides a base class for 'webform_attachment' elements.
 */
abstract class WebformAttachmentBase extends RenderElement implements WebformAttachmentInterface {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $class = get_class($this);
    return [
      '#filename' => '',
      '#sanitize' => FALSE,
      '#link_title' => '',
      '#download' => FALSE,
      '#trim' => FALSE,
      '#process' => [
        [$class, 'processWebformAttachment'],
        [$class, 'processAjaxForm'],
      ],
      '#theme_wrappers' => ['form_element'],
    ];
  }

  /**
   * Processes a 'webform_attachment' element.
   */
  public static function processWebformAttachment(&$element, FormStateInterface $form_state, &$complete_form) {
    $form_object = $form_state->getFormObject();

    // Attachments only work for webform submissions.
    if (!$form_object instanceof WebformSubmissionForm) {
      $element['#access'] = FALSE;
      return $element;
    }

    /** @var \Drupal\webform\WebformSubmissionInterface $webform_submission */
    $webform_submission = $form_object->getEntity();

    // Attachments only work for completed and saved webform submissions.
    if (!$webform_submission->id() || !$webform_submission->isCompleted()) {
      $element['#access'] = FALSE;
      return $element;
    }

    // Link to file download.
    $element['link'] = static::getFileLink($element, $webform_submission);

    return $element;
  }

  /****************************************************************************/
  // File methods.
  /****************************************************************************/

  /**
   * {@inheritdoc}
   */
  public static function getFileName(array $element, WebformSubmissionInterface $webform_submission) {
    if (!empty($element['#filename'])) {
      /** @var \Drupal\webform\WebformTokenManagerInterface $token_manager */
      $token_manager = \Drupal::service('webform.token_manager');

      $filename = $token_manager->replace($element['#filename'], $webform_submission);

      // Remove forward slashes from filename to prevent the below error.
      //
      //   Parameter "filename" for route
      //   "entity.webform.user.submission.attachment" must match "[^/]++".
      $filename = str_replace('/', '', $filename);

      // Sanitize filename.
      // @see http://stackoverflow.com/questions/2021624/string-sanitizer-for-filename
      // @see \Drupal\webform\Plugin\WebformElement\WebformManagedFileBase::getFileDestinationUri
      if (!empty($element['#sanitize'])) {
        /** @var \Drupal\Component\Transliteration\TransliterationInterface $transliteration */
        $transliteration = \Drupal::service('transliteration');
        /** @var \Drupal\Core\Language\LanguageManagerInterface $language_manager */
        $language_manager = \Drupal::service('language_manager');
        $langcode = $language_manager->getCurrentLanguage()->getId();

        $extension = pathinfo($filename, PATHINFO_EXTENSION);

        $basename = substr(pathinfo($filename, PATHINFO_BASENAME), 0, -strlen(".$extension"));
        $basename = mb_strtolower($basename);
        $basename = $transliteration->transliterate($basename, $langcode, '-');
        $basename = preg_replace('([^\w\s\d\-_~,;:\[\]\(\].]|[\.]{2,})', '', $basename);
        $basename = preg_replace('/\s+/', '-', $basename);
        $basename = trim($basename, '-');
        $filename = $basename . '.' . $extension;
      }

      return $filename;
    }
    else {
      return $element['#webform_key'] . '.txt';
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getFileMimeType(array $element, WebformSubmissionInterface $webform_submission) {
    /** @var \Symfony\Component\HttpFoundation\File\MimeType\MimeTypeGuesserInterface $file_mime_type_guesser */
    $file_mime_type_guesser = \Drupal::service('file.mime_type.guesser');
    $file_name = static::getFileName($element, $webform_submission);
    return $file_mime_type_guesser->guess($file_name);
  }

  /**
   * {@inheritdoc}
   */
  public static function getFileUrl(array $element, WebformSubmissionInterface $webform_submission) {
    if (!$webform_submission->id()) {
      return NULL;
    }

    $route_name = 'entity.webform.user.submission.attachment';
    $route_parameters = [
      'webform' => $webform_submission->getWebform()->id(),
      'webform_submission' => $webform_submission->id(),
      'element' => $element['#webform_key'],
      'filename' => static::getFileName($element, $webform_submission),
    ];
    $route_options = ['absolute' => TRUE];
    $url = Url::fromRoute($route_name, $route_parameters, $route_options);
    return $url;
  }

  /**
   * {@inheritdoc}
   */
  public static function getFileLink(array $element, WebformSubmissionInterface $webform_submission) {
    $title = (!empty($element['#link_title'])) ? $element['#link_title'] : static::getFileName($element, $webform_submission);
    $url = static::getFileUrl($element, $webform_submission);
    return [
      '#type' => 'link',
      '#title' => $title,
      '#url' => $url,
    ];
  }

}
