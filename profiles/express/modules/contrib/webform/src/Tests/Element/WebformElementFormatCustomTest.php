<?php

namespace Drupal\webform\Tests\Element;

use Drupal\file\Entity\File;
use Drupal\webform\Entity\Webform;
use Drupal\webform\Tests\WebformTestBase;

/**
 * Tests for webform submission webform element custom #format support.
 *
 * @group Webform
 */
class WebformElementFormatCustomTest extends WebformTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['file', 'webform'];

  /**
   * Webforms to load.
   *
   * @var array
   */
  protected static $testWebforms = ['test_element_format_custom'];

  /**
   * Tests element custom format.
   */
  public function testFormatCustom() {
    $this->drupalLogin($this->rootUser);

    /** @var \Drupal\webform\WebformInterface $webform */
    $webform = Webform::load('test_element_format_custom');

    $files = $this->drupalGetTestFiles('image');
    $this->debug($files[0]);
    $edit = [
      'files[image_custom]' => \Drupal::service('file_system')->realpath($files[0]->uri),
    ];
    $sid = $this->postSubmission($webform, $edit);

    // Retrieves the fid of the last inserted file.
    $fid = (int) \Drupal::database()->query('SELECT MAX(fid) FROM {file_managed}')->fetchField();
    $file = File::load($fid);
    $file_name = $file->getFilename();
    $file_size = $file->getSize();
    $file_url = file_create_url($file->getFileUri());

    /**************************************************************************/
    // Custom HTML.
    /**************************************************************************/

    $this->drupalGet("admin/structure/webform/manage/test_element_format_custom/submission/$sid");

    // Check basic custom HTML format.
    $this->assertRaw('<label>textfield_custom</label>');
    $this->assertRaw('<em>{textfield_custom}</em>');

    // Check multiple custom HTML format.
    $this->assertRaw('<label>textfield_custom</label>');
    $this->assertRaw('<table>');
    $this->assertRaw('<tr ><td>One</td></tr>');
    $this->assertRaw('<tr style="background-color: #ffc"><td>Two</td></tr>');
    $this->assertRaw('<tr ><td>Three</td></tr>');
    $this->assertRaw('<tr style="background-color: #ffc"><td>Four</td></tr>');
    $this->assertRaw('<tr ><td>Five</td></tr>');
    $this->assertRaw('</table>');

    // Check image custom HTML format.
    $this->assertRaw('<label>image_custom</label>');
    $this->assertRaw('value: 1<br/>');
    $this->assertRaw("item['value']: $file_url<br/>");
    $this->assertRaw("item['raw']: $file_url<br/>");
    $this->assertRaw("item['link']:");
    $this->assertRaw('<span class="file file--mime-image-png file--image"> <a href="' . $file_url . '" type="image/png; length='. $file_size . '">' . $file_name .'</a></span>');
    $this->assertRaw('item[\'id\']: 1<br/>');
    $this->assertRaw("item['url']: $file_url<br/>");
    $this->assertRaw('<img class="webform-image-file" alt="' . $file_name . '" src="' . $file_url . '" />');

    // Check composite custom HTML format.
    $this->assertRaw('<label>address_custom</label>');
    $this->assertRaw('element.address: {address}<br/>');
    $this->assertRaw('element.address_2: {address_2}<br/>');
    $this->assertRaw('element.city: {city}<br/>');
    $this->assertRaw('element.state_province: {state_province}<br/>');
    $this->assertRaw('element.postal_code: {postal_code}<br/>');
    $this->assertRaw('element.country: {country}<br/>');

    // Check fieldset displayed as details.
    $this->assertRaw('<details data-webform-element-id="test_element_format_custom--fieldset_custom" class="webform-container webform-container-type-details js-form-wrapper form-wrapper" id="test_element_format_custom--fieldset_custom" open="open">');
    $this->assertRaw('<summary role="button" aria-controls="test_element_format_custom--fieldset_custom" aria-expanded="true" aria-pressed="true">fieldset_custom</summary>');

    // Check container custom HTML format.
    $this->assertRaw('<h3>fieldset_custom_children</h3>' . PHP_EOL .'<hr />');

    /**************************************************************************/
    // Custom Text.
    /**************************************************************************/

    $this->drupalGet("admin/structure/webform/manage/test_element_format_custom/submission/$sid/text");
    $this->assertRaw("textfield_custom: /{textfield_custom}/
textfield_custom:
⦿ One
⦿ Two
⦿ Three
⦿ Four
⦿ Five


image_custom:
value: 1
item['value']: $file_url
item['raw']: $file_url
item['link']: $file_url
item['id']: 1
item['url']: $file_url

address_custom:
element.address: {address}
element.address_2: {address_2}
element.city: {city}
element.state_province: {state_province}
element.postal_code: {postal_code}
element.country: {country}

fieldset_custom
---------------
fieldset_custom_textfield: {fieldset_custom_textfield}

fieldset_custom_children
------------------------
fieldset_custom_children_textfield: {fieldset_custom_children_textfield}");
  }

}
