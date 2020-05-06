<?php

namespace Drupal\webform_access\Breadcrumb;

use Drupal\Core\Breadcrumb\BreadcrumbBuilderInterface;
use Drupal\Core\Breadcrumb\Breadcrumb;
use Drupal\Core\Link;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\Core\Url;

/**
 * Provides a webform access breadcrumb builder.
 */
class WebformAccessBreadcrumbBuilder implements BreadcrumbBuilderInterface {

  use StringTranslationTrait;

  /**
   * The current route's entity or plugin type.
   *
   * @var string
   */
  protected $type;

  /**
   * Constructs a WebformAccessBreadcrumbBuilder.
   *
   * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
   *   The string translation service.
   */
  public function __construct(TranslationInterface $string_translation) {
    $this->setStringTranslation($string_translation);
  }

  /**
   * {@inheritdoc}
   */
  public function applies(RouteMatchInterface $route_match) {
    $route_name = $route_match->getRouteName();
    // All routes must begin or contain 'webform_access'.
    if (strpos($route_name, 'webform_access') === FALSE) {
      return FALSE;
    }
    $path = Url::fromRouteMatch($route_match)->toString();

    if (strpos($path, 'admin/structure/webform/access/') === FALSE) {
      return FALSE;
    }

    if (strpos($path, 'admin/structure/webform/access/group/manage/') !== FALSE) {
      $this->type = 'webform_access_group';
    }
    elseif (strpos($path, 'admin/structure/webform/access/type/manage/') !== FALSE) {
      $this->type = 'webform_access_type';
    }
    else {
      $this->type = 'webform_access';
    }

    return ($this->type) ? TRUE : FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function build(RouteMatchInterface $route_match) {
    $breadcrumb = new Breadcrumb();
    $breadcrumb->addLink(Link::createFromRoute($this->t('Home'), '<front>'));
    $breadcrumb->addLink(Link::createFromRoute($this->t('Administration'), 'system.admin'));
    $breadcrumb->addLink(Link::createFromRoute($this->t('Structure'), 'system.admin_structure'));
    $breadcrumb->addLink(Link::createFromRoute($this->t('Webforms'), 'entity.webform.collection'));
    $breadcrumb->addLink(Link::createFromRoute($this->t('Access'), 'entity.webform_access_group.collection'));
    switch ($this->type) {
      case 'webform_access_group':
        $breadcrumb->addLink(Link::createFromRoute($this->t('Groups'), 'entity.webform_access_group.collection'));
        break;

      case 'webform_access_type';
        $breadcrumb->addLink(Link::createFromRoute($this->t('Types'), 'entity.webform_access_type.collection'));
        break;
    }

    // This breadcrumb builder is based on a route parameter, and hence it
    // depends on the 'route' cache context.
    $breadcrumb->addCacheContexts(['route']);

    return $breadcrumb;
  }

}
