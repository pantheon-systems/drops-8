<?php

/**
 * @file
 * Contains \Drupal\Core\Link.
 */

namespace Drupal\Core;

use Drupal\Core\Render\RenderableInterface;
use Drupal\Core\Routing\LinkGeneratorTrait;

/**
 * Defines an object that holds information about a link.
 */
class Link implements RenderableInterface {

  /**
   * @deprecated in Drupal 8.0.x-dev, will be removed before Drupal 9.0.0.
   */
  use LinkGeneratorTrait;

  /**
   * The text of the link.
   *
   * @var string
   */
  protected $text;

  /**
   * The URL of the link.
   *
   * @var \Drupal\Core\Url
   */
  protected $url;

  /**
   * Constructs a new Link object.
   *
   * @param string $text
   *   The text of the link.
   * @param \Drupal\Core\Url $url
   *   The url object.
   */
  public function __construct($text, Url $url) {
    $this->text = $text;
    $this->url = $url;
  }

  /**
   * Creates a Link object from a given route name and parameters.
   *
   * @param string $text
   *   The text of the link.
   * @param string $route_name
   *   The name of the route
   * @param array $route_parameters
   *   (optional) An associative array of parameter names and values.
   * @param array $options
   *   (optional) An associative array of additional options, with the following
   *   elements:
   *   - 'query': An array of query key/value-pairs (without any URL-encoding)
   *     to append to the URL. Merged with the parameters array.
   *   - 'fragment': A fragment identifier (named anchor) to append to the URL.
   *     Do not include the leading '#' character.
   *   - 'absolute': Defaults to FALSE. Whether to force the output to be an
   *     absolute link (beginning with http:). Useful for links that will be
   *     displayed outside the site, such as in an RSS feed.
   *   - 'language': An optional language object used to look up the alias
   *     for the URL. If $options['language'] is omitted, it defaults to the
   *     current language for the language type LanguageInterface::TYPE_URL.
   *   - 'https': Whether this URL should point to a secure location. If not
   *     defined, the current scheme is used, so the user stays on HTTP or HTTPS
   *     respectively. TRUE enforces HTTPS and FALSE enforces HTTP.
   *
   * @return static
   */
  public static function createFromRoute($text, $route_name, $route_parameters = array(), $options = array()) {
    return new static($text, new Url($route_name, $route_parameters, $options));
  }

  /**
   * Creates a Link object from a given Url object.
   *
   * @param string $text
   *   The text of the link.
   * @param \Drupal\Core\Url $url
   *   The Url to create the link for.
   *
   * @return static
   */
  public static function fromTextAndUrl($text, Url $url) {
    return new static($text, $url);
  }

  /**
   * Returns the text of the link.
   *
   * @return string
   */
  public function getText() {
    return $this->text;
  }

  /**
   * Sets the new text of the link.
   *
   * @param string $text
   *   The new text.
   *
   * @return $this
   */
  public function setText($text) {
    $this->text = $text;
    return $this;
  }

  /**
   * Returns the URL of the link.
   *
   * @return \Drupal\Core\Url
   */
  public function getUrl() {
    return $this->url;
  }

  /**
   * Sets the URL of this link.
   *
   * @param Url $url
   *   The URL object to set
   *
   * @return $this
   */
  public function setUrl(Url $url) {
    $this->url = $url;
    return $this;
  }

  /**
   * Generates the HTML for this Link object.
   *
   * @return \Drupal\Core\GeneratedLink
   *   The link HTML markup.
   *
   * @deprecated in Drupal 8.0.x-dev, will be removed before Drupal 9.0.0. Use
   *   self::toRenderable() instead.
   */
  public function toString() {
    return $this->getLinkGenerator()->generateFromLink($this);
  }

  /**
   * {@inheritdoc}
   */
  public function toRenderable() {
    return [
      '#type' => 'link',
      '#url' => $this->url,
      '#title' => $this->text,
    ];
  }

}
