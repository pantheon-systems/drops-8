<?php

namespace Drupal\Tests\metatag\Functional;

use Drupal\Tests\BrowserTestBase;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Ensures that meta tags do not allow xss vulnerabilities.
 *
 * @group metatag
 */
class MetatagXssTest extends BrowserTestBase {

  use StringTranslationTrait;

  /**
   * String that causes an alert when page titles aren't filtered for xss.
   *
   * @var string
   */
  private $xssTitleString = '<script>alert("xss");</script>';

  /**
   * String that causes an alert when meta tags aren't filtered for xss.
   *
   * @var string
   */
  private $xssString = '"><script>alert("xss");</script><meta "';

  /**
   * Rendered xss tag that has escaped attribute to avoid xss injection.
   *
   * @var string
   */
  private $escapedXssTag = '<meta name="abstract" content="&quot;&gt;alert(&quot;xss&quot;);" />';

  /**
   * String that causes an alert when meta tags aren't filtered for xss.
   *
   * "Image" meta tags are processed differently to others, so this checks for a
   * different string.
   *
   * @var string
   */
  private $xssImageString = '"><script>alert("image xss");</script><meta "';

  /**
   * Rendered xss tag that has escaped attribute to avoid xss injection.
   *
   * @var string
   */
  private $escapedXssImageTag = '<link rel="image_src" href="&quot;&gt;alert(&quot;image xss&quot;);" />';

  /**
   * Administrator user for tests.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'node',
    'views',
    'system',
    'field',
    'field_ui',
    'token',
    'metatag',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Create a user that can manage content types and create content.
    $admin_permissions = [
      'administer content types',
      'administer nodes',
      'bypass node access',
      'administer meta tags',
      'administer site configuration',
      'access content',
      'administer content types',
      'administer nodes',
      'administer node fields',
    ];

    // Create and login a with the admin-ish permissions user.
    $this->adminUser = $this->drupalCreateUser($admin_permissions);
    $this->drupalLogin($this->adminUser);

    // Set up a content type.
    $this->drupalCreateContentType(['type' => 'metatag_node', 'name' => 'Test Content Type']);

    // Add a metatag field to the content type.
    $this->drupalGet('admin/structure/types/manage/metatag_node/fields/add-field');
    $this->assertSession()->statusCodeEquals(200);
    $edit = [
      'label' => 'Metatag',
      'field_name' => 'metatag_field',
      'new_storage_type' => 'metatag',
    ];
    $this->drupalPostForm(NULL, $edit, $this->t('Save and continue'));
    $this->drupalPostForm(NULL, [], $this->t('Save field settings'));
  }

  /**
   * Verify XSS injected in global config is not rendered.
   */
  public function testXssMetatagConfig() {
    $this->drupalGet('admin/config/search/metatag/global');
    $this->assertSession()->statusCodeEquals(200);
    $values = [
      'title' => $this->xssTitleString,
      'abstract' => $this->xssString,
      'image_src' => $this->xssImageString,
    ];
    $this->drupalPostForm(NULL, $values, 'Save');
    $this->assertText('Saved the Global Metatag defaults.');
    $this->rebuildAll();

    // Load the Views-based front page.
    $this->drupalGet('node');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertText($this->t('No front page content has been created yet.'));

    // Check for the title tag, which will have the HTML tags removed and then
    // be lightly HTML encoded.
    $this->assertEscaped(strip_tags($this->xssTitleString));
    $this->assertNoRaw($this->xssTitleString);

    // Check for the basic meta tag.
    $this->assertRaw($this->escapedXssTag);
    $this->assertNoRaw($this->xssString);

    // Check for the image meta tag.
    $this->assertRaw($this->escapedXssImageTag);
    $this->assertNoRaw($this->xssImageString);
  }

  /**
   * Verify XSS injected in the entity metatag override field is not rendered.
   */
  public function testXssEntityOverride() {
    $save_label = (floatval(\Drupal::VERSION) <= 8.3) ? $this->t('Save and publish') : $this->t('Save');

    $this->drupalGet('node/add/metatag_node');
    $this->assertSession()->statusCodeEquals(200);
    $edit = [
      'title[0][value]' => $this->randomString(32),
      'field_metatag_field[0][basic][title]' => $this->xssTitleString,
      'field_metatag_field[0][basic][abstract]' => $this->xssString,
      'field_metatag_field[0][advanced][image_src]' => $this->xssImageString,
    ];
    $this->drupalPostForm(NULL, $edit, $save_label);

    // Check for the title tag, which will have the HTML tags removed and then
    // be lightly HTML encoded.
    $this->assertEscaped(strip_tags($this->xssTitleString));
    $this->assertNoRaw($this->xssTitleString);

    // Check for the basic meta tag.
    $this->assertRaw($this->escapedXssTag);
    $this->assertNoRaw($this->xssString);

    // Check for the image meta tag.
    $this->assertRaw($this->escapedXssImageTag);
    $this->assertNoRaw($this->xssImageString);
  }

  /**
   * Verify XSS injected in the entity titles are not rendered.
   */
  public function testXssEntityTitle() {
    $save_label = (floatval(\Drupal::VERSION) <= 8.3) ? $this->t('Save and publish') : $this->t('Save');

    $this->drupalGet('node/add/metatag_node');
    $this->assertSession()->statusCodeEquals(200);
    $edit = [
      'title[0][value]' => $this->xssTitleString,
      'body[0][value]' => $this->randomString() . ' ' . $this->randomString(),
    ];
    $this->drupalPostForm(NULL, $edit, $save_label);

    // Check for the title tag, which will have the HTML tags removed and then
    // be lightly HTML encoded.
    $this->assertEscaped(strip_tags($this->xssTitleString));
    $this->assertNoRaw($this->xssTitleString);
  }

  /**
   * Verify XSS injected in the entity fields are not rendered.
   */
  public function testXssEntityBody() {
    $save_label = (floatval(\Drupal::VERSION) <= 8.3) ? $this->t('Save and publish') : $this->t('Save');

    $this->drupalGet('node/add/metatag_node');
    $this->assertSession()->statusCodeEquals(200);
    $edit = [
      'title[0][value]' => $this->randomString(),
      'body[0][value]' => $this->xssTitleString,
    ];
    $this->drupalPostForm(NULL, $edit, $save_label);

    // Check the body text.
    // {@code}
    // $this->assertNoTitle($this->xssTitleString);
    // {@endcode}
    $this->assertNoRaw($this->xssTitleString);
  }

}
