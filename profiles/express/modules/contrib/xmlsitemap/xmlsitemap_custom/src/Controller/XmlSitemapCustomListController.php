<?php

namespace Drupal\xmlsitemap_custom\Controller;

use Drupal\Component\Utility\Unicode;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;

/**
 * Builds the list table for all custom links.
 */
class XmlSitemapCustomListController extends ControllerBase {

  /**
   * Renders a list with all custom links.
   *
   * @return array
   *   The list to be rendered.
   */
  public function render() {
    $build['xmlsitemap_add_custom'] = array(
      '#type' => 'link',
      '#title' => t('Add custom link'),
      '#href' => 'admin/config/search/xmlsitemap/custom/add',
    );
    $header = array(
      'loc' => array('data' => t('Location'), 'field' => 'loc', 'sort' => 'asc'),
      'priority' => array('data' => t('Priority'), 'field' => 'priority'),
      'changefreq' => array('data' => t('Change frequency'), 'field' => 'changefreq'),
      'language' => array('data' => t('Language'), 'field' => 'language'),
      'operations' => array('data' => t('Operations')),
    );

    $rows = array();

    $query = db_select('xmlsitemap');
    $query->fields('xmlsitemap');
    $query->condition('type', 'custom');
    $query->extend('Drupal\Core\Database\Query\PagerSelectExtender')->limit(50);
    $query->extend('Drupal\Core\Database\Query\TableSortExtender')->orderByHeader($header);
    $result = $query->execute();

    foreach ($result as $link) {
      $language = $this->languageManager()->getLanguage($link->language);
      $row = array();
      $row['loc'] = $this->l($link->loc, Url::fromUri('base://' . $link->loc));
      $row['priority'] = number_format($link->priority, 1);
      $row['changefreq'] = $link->changefreq ? Unicode::ucfirst(xmlsitemap_get_changefreq($link->changefreq)) : t('None');
      if (isset($header['language'])) {
        $row['language'] = $language->getName();
      }
      $operations['edit'] = array(
        'title' => t('Edit'),
        'url' => Url::fromRoute('xmlsitemap_custom.edit', ['link' => $link->id]),
      );
      $operations['delete'] = array(
        'title' => t('Delete'),
        'url' => Url::fromRoute('xmlsitemap_custom.delete', ['link' => $link->id]),
      );
      $row['operations'] = array(
        'data' => array(
          '#type' => 'operations',
          '#links' => $operations,
        ),
      );
      $rows[] = $row;
    }

    // @todo Convert to tableselect
    $build['xmlsitemap_custom_table'] = array(
      '#type' => 'table',
      '#header' => $header,
      '#rows' => $rows,
      '#empty' => $this->t('No custom links available. <a href="@custom_link">Add custom link</a>', array('@custom_link' => Url::fromRoute('xmlsitemap_custom.add', [], array('query' => $this->getDestinationArray()))->toString())),
    );
    $build['xmlsitemap_custom_pager'] = array('#type' => 'pager');

    return $build;
  }

}
