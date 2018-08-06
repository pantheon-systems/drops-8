<?php

namespace Drupal\webform_devel\Controller;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\webform\WebformInterface;
use Drupal\webform_devel\WebformDevelSchemaInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Provides route responses for webform devel schema.
 */
class WebformDevelSchemaController extends ControllerBase implements ContainerInjectionInterface {

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The webform devel schema generator.
   *
   * @var \Drupal\webform_devel\WebformDevelSchemaInterface
   */
  protected $schema;

  /**
   * Constructs a WebformDevelSchemaController object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\webform_devel\WebformDevelSchemaInterface $schema
   *   The webform devel schema generator.
   */
  public function __construct(ConfigFactoryInterface $config_factory, WebformDevelSchemaInterface $schema) {
    $this->configFactory = $config_factory;
    $this->schema = $schema;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('webform_devel.schema')
    );
  }

  /**
   * Returns a webform's schema as a CSV.
   *
   * @param \Drupal\webform\WebformInterface $webform
   *   The webform to be exported.
   *
   * @return \Symfony\Component\HttpFoundation\StreamedResponse
   *   A streamed response containing webform's schema as a CSV.
   */
  public function index(WebformInterface $webform) {
    $multiple_delimiter = $this->configFactory->get('webform.settings')->get('export.multiple_delimiter') ?: ';';

    // From: http://obtao.com/blog/2013/12/export-data-to-a-csv-file-with-symfony/
    $response = new StreamedResponse(function () use ($webform, $multiple_delimiter) {
      $handle = fopen('php://output', 'r+');

      // Header.
      fputcsv($handle, $this->schema->getColumns());

      // Rows.
      $elements = $this->schema->getElements($webform);
      foreach ($elements as $element) {
        $element['options'] = implode($multiple_delimiter, $element['options']);
        fputcsv($handle, $element);
      }

      fclose($handle);
    });
    $response->headers->set('Content-Type', 'application/force-download');
    $response->headers->set('Content-Disposition', 'attachment; filename="' . $webform->id() . '.schema.csv"');
    return $response;
  }

}
