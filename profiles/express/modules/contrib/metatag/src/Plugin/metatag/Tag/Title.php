<?php

namespace Drupal\metatag\Plugin\metatag\Tag;

/**
 * The standard page title.
 *
 * @MetatagTag(
 *   id = "title",
 *   label = @Translation("Page title"),
 *   description = @Translation("The text to display in the title bar of a visitor's web browser when they view this page. This meta tag may also be used as the title of the page when a visitor bookmarks or favorites this page, or as the page title in a search engine result. It is common to append '[site:name]' to the end of this, so the site's name is automatically added. It is recommended that the title is no greater than 55 - 65 characters long, including spaces."),
 *   name = "title",
 *   group = "basic",
 *   weight = -1,
 *   type = "label",
 *   secure = FALSE,
 *   multiple = FALSE
 * )
 */
class Title extends MetaNameBase {

  /**
   * Override the output of this tag so it's an actual TITLE tag.
   *
   * @todo Override the existing title tag X-)
   */
  // public function output() {
  //   if (empty($this->value)) {
  //     // If there is no value, we don't want a tag output.
  //     $element = '';
  //   }
  //   else {
  //     $element = [
  //       '#theme' => 'hidden',
  //       // '#tag' => 'title',
  //       '#value' => $this->value(),
  //     ];
  //   }
  //
  //   return $element;
  // }
}
