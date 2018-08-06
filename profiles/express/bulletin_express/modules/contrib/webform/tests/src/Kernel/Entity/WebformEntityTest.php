<?php

namespace Drupal\Tests\user\Kernel\Entity;

use Drupal\Core\Serialization\Yaml;
use Drupal\KernelTests\KernelTestBase;
use Drupal\webform\Entity\Webform;
use Drupal\webform\WebformInterface;

/**
 * Tests the webform entity class.
 *
 * @group webform
 * @see \Drupal\webform\Entity\Webform
 */
class WebformEntityTest extends KernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['system', 'path', 'webform', 'user', 'field'];

  /**
   * Tests some of the methods.
   */
  public function testWebformMethods() {
    $this->installConfig('webform');

    /**************************************************************************/
    // Create.
    /**************************************************************************/

    // Create webform.
    /** @var \Drupal\webform\WebformInterface $webform */
    $webform = Webform::create(['id' => 'webform_test']);
    $webform->save();
    $this->assertEquals('webform_test', $webform->id());
    $this->assertFalse($webform->isTemplate());
    $this->assertTrue($webform->isOpen());

    /**************************************************************************/
    // Status.
    /**************************************************************************/

    // Check set status to FALSE.
    $webform->setStatus(FALSE);
    $this->assertFalse($webform->isOpen());
    $this->assertEquals($webform->get('status'), WebformInterface::STATUS_CLOSED);
    $this->assertFalse($webform->isScheduled());

    // Check set status to TRUE.
    $webform->setStatus(TRUE);
    $this->assertTrue($webform->isOpen());
    $this->assertEquals($webform->get('status'), WebformInterface::STATUS_OPEN);

    // Check set status to NULL.
    $webform->setStatus(NULL);
    $this->assertTrue($webform->isOpen());
    $this->assertEquals($webform->get('status'), WebformInterface::STATUS_SCHEDULED);

    // Check set status to WebformInterface::STATUS_CLOSED.
    $webform->setStatus(WebformInterface::STATUS_CLOSED);
    $this->assertFalse($webform->isOpen());

    // Check set status to WebformInterface::STATUS_OPEN.
    $webform->setStatus(WebformInterface::STATUS_OPEN);
    $this->assertTrue($webform->isOpen());

    // Check set status to WebformInterface::STATUS_SCHEDULED.
    $webform->setStatus(WebformInterface::STATUS_SCHEDULED);
    $this->assertTrue($webform->isOpen());
    $this->assertTrue($webform->isScheduled());

    /**************************************************************************/
    // Scheduled.
    /**************************************************************************/

    $webform->setStatus(WebformInterface::STATUS_SCHEDULED);

    // Check set open date to yesterday.
    $webform->set('open', date('Y-m-d\TH:i:s', strtotime('today -1 days')));
    $webform->set('close', NULL);
    $this->assertTrue($webform->isOpen());

    // Check set open date to tomorrow.
    $webform->set('open', date('Y-m-d\TH:i:s', strtotime('today +1 day')));
    $webform->set('close', NULL);
    $this->assertFalse($webform->isOpen());

    // Check set close date to yesterday.
    $webform->set('open', NULL);
    $webform->set('close', date('Y-m-d\TH:i:s', strtotime('today -1 day')));
    $this->assertFalse($webform->isOpen());

    // Check set close date to tomorrow.
    $webform->set('open', NULL);
    $webform->set('close', date('Y-m-d\TH:i:s', strtotime('today +1 day')));
    $this->assertTrue($webform->isOpen());

    // Check set open date to tomorrow with close date in 10 days.
    $webform->set('open', date('Y-m-d\TH:i:s', strtotime('today +1 day')));
    $webform->set('close', date('Y-m-d\TH:i:s', strtotime('today +10 days')));
    $this->assertFalse($webform->isOpen());
    $this->assertTrue($webform->isOpening());

    // Check set open date to yesterday with close date in +10 days.
    $webform->set('open', date('Y-m-d\TH:i:s', strtotime('today -1 day')));
    $webform->set('close', date('Y-m-d\TH:i:s', strtotime('today +10 days')));
    $this->assertTrue($webform->isOpen());

    // Check set open date to yesterday with close date -10 days.
    $webform->set('open', date('Y-m-d\TH:i:s', strtotime('today -1 day')));
    $webform->set('close', date('Y-m-d\TH:i:s', strtotime('today -10 days')));
    $this->assertFalse($webform->isOpen());
    $this->assertFalse($webform->isOpening());

    // Check that open overrides scheduled.
    $webform->setStatus(TRUE);
    $webform->set('open', date('Y-m-d\TH:i:s', strtotime('today -1 day')));
    $webform->set('close', date('Y-m-d\TH:i:s', strtotime('today -10 days')));
    $this->assertTrue($webform->isOpen());

    // Check that closed overrides scheduled.
    $webform->setStatus(FALSE);
    $webform->set('open', date('Y-m-d\TH:i:s', strtotime('today +1 day')));
    $webform->set('close', date('Y-m-d\TH:i:s', strtotime('today -10 days')));
    $this->assertFalse($webform->isOpen());

    // Check that open and close date is set to NULL when status is set to open
    // or closed.
    $webform->set('open', date('Y-m-d\TH:i:s', strtotime('today +1 day')));
    $webform->set('close', date('Y-m-d\TH:i:s', strtotime('today -10 days')));
    $this->assertNotNull($webform->get('open'));
    $this->assertNotNull($webform->get('close'));
    $webform->setStatus(TRUE);
    $this->assertNull($webform->get('open'));
    $this->assertNull($webform->get('close'));

    /**************************************************************************/
    // Templates.
    /**************************************************************************/

    // Check that templates are always closed.
    $webform->set('template', TRUE)->save();
    $this->assertTrue($webform->isTemplate());
    $this->assertFalse($webform->isOpen());

    /**************************************************************************/
    // Elements.
    /**************************************************************************/

    // Set elements.
    $elements = [
      'root' => [
        '#type' => 'textfield',
        '#title' => 'root',
      ],
      'container' => [
        '#type' => 'container',
        '#title' => 'container',
        'child' => [
          '#type' => 'textfield',
          '#title' => 'child',
        ],
      ],
    ];
    $webform->setElements($elements);

    // Check that elements are serialized to YAML.
    $this->assertTrue($webform->getElementsRaw(), Yaml::encode($elements));

    // Check elements decoded and flattened.
    $flattened_elements = [
      'root' => [
        '#type' => 'textfield',
        '#title' => 'root',
      ],
      'container' => [
        '#type' => 'container',
        '#title' => 'container',
      ],
      'child' => [
        '#type' => 'textfield',
        '#title' => 'child',
      ],
    ];
    $this->assertEquals($webform->getElementsDecodedAndFlattened(), $flattened_elements);

    // Check elements initialized  and flattened.
    $elements_initialized_and_flattened = [
      'root' => [
        '#type' => 'textfield',
        '#title' => 'root',
        '#webform_id' => 'webform_test--root',
        '#webform_key' => 'root',
        '#webform_parent_key' => '',
        '#webform_parent_flexbox' => FALSE,
        '#webform_depth' => 0,
        '#webform_children' => [],
        '#webform_multiple' => FALSE,
        '#webform_composite' => FALSE,
        '#admin_title' => NULL,
      ],
      'container' => [
        '#type' => 'container',
        '#title' => 'container',
        '#webform_id' => 'webform_test--container',
        '#webform_key' => 'container',
        '#webform_parent_key' => '',
        '#webform_parent_flexbox' => FALSE,
        '#webform_depth' => 0,
        '#webform_children' => [],
        '#webform_multiple' => FALSE,
        '#webform_composite' => FALSE,
        '#admin_title' => NULL,
      ],
      'child' => [
        '#type' => 'textfield',
        '#title' => 'child',
        '#webform_id' => 'webform_test--child',
        '#webform_key' => 'child',
        '#webform_parent_key' => 'container',
        '#webform_parent_flexbox' => FALSE,
        '#webform_depth' => 1,
        '#webform_children' => [],
        '#webform_multiple' => FALSE,
        '#webform_composite' => FALSE,
        '#admin_title' => NULL,
      ],
    ];
    $this->assertEquals($webform->getElementsInitializedAndFlattened(), $elements_initialized_and_flattened);

    // Check elements flattened has value.
    $elements_initialized_flattened_and_has_value = $elements_initialized_and_flattened;
    unset($elements_initialized_flattened_and_has_value['container']);
    $this->assertEquals($webform->getElementsInitializedFlattenedAndHasValue(), $elements_initialized_flattened_and_has_value);

    // Check invalid elements.
    $webform->set('elements', 'invalid')->save();
    $this->assertFalse($webform->getElementsInitialized());

    /**************************************************************************/
    // Wizard pages.
    /**************************************************************************/

    // Check get no wizard pages.
    $this->assertEquals($webform->getPages(), []);

    // Set wizard pages.
    $wizard_elements = [
      'page_1' => ['#type' => 'webform_wizard_page', '#title' => 'Page 1'],
      'page_2' => ['#type' => 'webform_wizard_page', '#title' => 'Page 2'],
      'page_3' => ['#type' => 'webform_wizard_page', '#title' => 'Page 3'],
    ];
    $webform->set('elements', $wizard_elements)->save();

    // Check get wizard pages.
    $wizard_pages = [
      'page_1' => ['#title' => 'Page 1'],
      'page_2' => ['#title' => 'Page 2'],
      'page_3' => ['#title' => 'Page 3'],
      'complete' => ['#title' => 'Complete'],
    ];
    $this->assertEquals($webform->getPages(), $wizard_pages);

    // Check get wizard pages with preview.
    $webform->setSetting('preview', TRUE)->save();
    $wizard_pages = [
      'page_1' => ['#title' => 'Page 1'],
      'page_2' => ['#title' => 'Page 2'],
      'page_3' => ['#title' => 'Page 3'],
      'preview' => ['#title' => 'Preview'],
      'complete' => ['#title' => 'Complete'],
    ];
    $this->assertEquals($webform->getPages(), $wizard_pages);

    // Check get wizard pages with preview with disable pages.
    $webform->setSetting('preview', TRUE)->save();
    $wizard_pages = [
      'start' => ['#title' => 'Start'],
      'preview' => ['#title' => 'Preview'],
      'complete' => ['#title' => 'Complete'],
    ];
    $this->assertEquals($webform->getPages(TRUE), $wizard_pages);

    // @todo Add the below assertions.
    // Check access rules.
    // Check get submission form.
    // Check handlers CRUD operations.
  }

  /**
   * Test paths.
   */
  public function testPaths() {
    $this->installConfig('webform');

    /** @var \Drupal\webform\WebformInterface $webform */
    $webform = Webform::create(['id' => 'webform_test']);
    $webform->save();
    $aliases = \Drupal::database()->query('SELECT source, alias FROM {url_alias}')->fetchAllKeyed();
    $this->assertEquals($aliases['/webform/webform_test'], '/form/webform-test');
    $this->assertEquals($aliases['/webform/webform_test/confirmation'], '/form/webform-test/confirmation');
    $this->assertEquals($aliases['/webform/webform_test/submissions'], '/form/webform-test/submissions');
  }

  /**
   * Test elements CRUD operations.
   */
  public function testElementsCrud() {
    $this->installEntitySchema('webform_submission');

    /** @var \Drupal\webform\WebformInterface $webform */
    $webform = Webform::create(['id' => 'webform_test']);
    $webform->save();

    // Check set new root element.
    $elements = [
      'root' => [
        '#type' => 'container',
        '#title' => 'root',
      ],
    ];
    $webform->setElementProperties('root', $elements['root']);
    $this->assertEquals($webform->getElementsRaw(), Yaml::encode($elements));

    // Check add new container to root.
    $elements['root']['container'] = [
      '#type' => 'container',
      '#title' => 'container',
    ];
    $webform->setElementProperties('container', $elements['root']['container'], 'root');
    $this->assertEquals($webform->getElementsRaw(), Yaml::encode($elements));

    // Check add new element to container.
    $elements['root']['container']['element'] = [
      '#type' => 'textfield',
      '#title' => 'element',
    ];
    $webform->setElementProperties('element', $elements['root']['container']['element'], 'container');
    $this->assertEquals($webform->getElementsRaw(), Yaml::encode($elements));

    // Check delete container with al recursively delete all children.
    $elements = [
      'root' => [
        '#type' => 'container',
        '#title' => 'root',
      ],
    ];
    $webform->deleteElement('container');
    $this->assertEquals($webform->getElementsRaw(), Yaml::encode($elements));
  }

}
