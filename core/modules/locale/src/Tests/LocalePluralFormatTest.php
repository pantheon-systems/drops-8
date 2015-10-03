<?php

/**
 * @file
 * Contains \Drupal\locale\Tests\LocalePluralFormatTest.
 */

namespace Drupal\locale\Tests;

use Drupal\Core\StringTranslation\PluralTranslatableMarkup;
use Drupal\simpletest\WebTestBase;

/**
 * Tests plural handling for various languages.
 *
 * @group locale
 */
class LocalePluralFormatTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('locale');

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $admin_user = $this->drupalCreateUser(array('administer languages', 'translate interface', 'access administration pages'));
    $this->drupalLogin($admin_user);
  }

  /**
   * Tests locale_get_plural() and \Drupal::translation()->formatPlural()
   * functionality.
   */
  public function testGetPluralFormat() {
    // Import some .po files with formulas to set up the environment.
    // These will also add the languages to the system.
    $this->importPoFile($this->getPoFileWithSimplePlural(), array(
      'langcode' => 'fr',
    ));
    $this->importPoFile($this->getPoFileWithComplexPlural(), array(
      'langcode' => 'hr',
    ));

    // Attempt to import some broken .po files as well to prove that these
    // will not overwrite the proper plural formula imported above.
    $this->importPoFile($this->getPoFileWithMissingPlural(), array(
      'langcode' => 'fr',
      'overwrite_options[not_customized]' => TRUE,
    ));
    $this->importPoFile($this->getPoFileWithBrokenPlural(), array(
      'langcode' => 'hr',
      'overwrite_options[not_customized]' => TRUE,
    ));

    // Reset static caches from locale_get_plural() to ensure we get fresh data.
    drupal_static_reset('locale_get_plural');
    drupal_static_reset('locale_get_plural:plurals');
    drupal_static_reset('locale');

    // Expected plural translation strings for each plural index.
    $plural_strings = array(
      // English is not imported in this case, so we assume built-in text
      // and formulas.
      'en' => array(
        0 => '1 hour',
        1 => '@count hours',
      ),
      'fr' => array(
        0 => '@count heure',
        1 => '@count heures',
      ),
      'hr' => array(
        0 => '@count sat',
        1 => '@count sata',
        2 => '@count sati',
      ),
      // Hungarian is not imported, so it should assume the same text as
      // English, but it will always pick the plural form as per the built-in
      // logic, so only index -1 is relevant with the plural value.
      'hu' => array(
        0 => '1 hour',
        -1 => '@count hours',
      ),
    );

    // Expected plural indexes precomputed base on the plural formulas with
    // given $count value.
    $plural_tests = array(
      'en' => array(
        1 => 0,
        0 => 1,
        5 => 1,
        123 => 1,
        235 => 1,
      ),
      'fr' => array(
        1 => 0,
        0 => 0,
        5 => 1,
        123 => 1,
        235 => 1,
      ),
      'hr' => array(
        1 => 0,
        21 => 0,
        0 => 2,
        2 => 1,
        8 => 2,
        123 => 1,
        235 => 2,
      ),
      'hu' => array(
        1 => -1,
        21 => -1,
        0 => -1,
      ),
    );

    foreach ($plural_tests as $langcode => $tests) {
      foreach ($tests as $count => $expected_plural_index) {
        // Assert that the we get the right plural index.
        $this->assertIdentical(locale_get_plural($count, $langcode), $expected_plural_index, 'Computed plural index for ' . $langcode . ' for count ' . $count . ' is ' . $expected_plural_index);
        // Assert that the we get the right translation for that. Change the
        // expected index as per the logic for translation lookups.
        $expected_plural_index = ($count == 1) ? 0 : $expected_plural_index;
        $expected_plural_string = str_replace('@count', $count, $plural_strings[$langcode][$expected_plural_index]);
        $this->assertIdentical(\Drupal::translation()->formatPlural($count, '1 hour', '@count hours', array(), array('langcode' => $langcode))->render(), $expected_plural_string, 'Plural translation of 1 hours / @count hours for count ' . $count . ' in ' . $langcode . ' is ' . $expected_plural_string);
        // DO NOT use translation to pass translated strings into
        // PluralTranslatableMarkup::createFromTranslatedString() this way. It
        // is designed to be used with *already* translated text like settings
        // from configuration. We use PHP translation here just because we have
        // the expected result data in that format.
        $translated_string = \Drupal::translation()->translate('1 hour' . PluralTranslatableMarkup::DELIMITER . '@count hours', array(), array('langcode' => $langcode));
        $plural = PluralTranslatableMarkup::createFromTranslatedString($count, $translated_string, array(), array('langcode' => $langcode));
        $this->assertIdentical($plural->render(), $expected_plural_string);
      }
    }
  }

  /**
   * Tests plural editing and export functionality.
   */
  public function testPluralEditExport() {
    // Import some .po files with formulas to set up the environment.
    // These will also add the languages to the system.
    $this->importPoFile($this->getPoFileWithSimplePlural(), array(
      'langcode' => 'fr',
    ));
    $this->importPoFile($this->getPoFileWithComplexPlural(), array(
      'langcode' => 'hr',
    ));

    // Get the French translations.
    $this->drupalPostForm('admin/config/regional/translate/export', array(
      'langcode' => 'fr',
    ), t('Export'));
    // Ensure we have a translation file.
    $this->assertRaw('# French translation of Drupal', 'Exported French translation file.');
    // Ensure our imported translations exist in the file.
    $this->assertRaw("msgid \"Monday\"\nmsgstr \"lundi\"", 'French translations present in exported file.');
    // Check for plural export specifically.
    $this->assertRaw("msgid \"1 hour\"\nmsgid_plural \"@count hours\"\nmsgstr[0] \"@count heure\"\nmsgstr[1] \"@count heures\"", 'Plural translations exported properly.');

    // Get the Croatian translations.
    $this->drupalPostForm('admin/config/regional/translate/export', array(
      'langcode' => 'hr',
    ), t('Export'));
    // Ensure we have a translation file.
    $this->assertRaw('# Croatian translation of Drupal', 'Exported Croatian translation file.');
    // Ensure our imported translations exist in the file.
    $this->assertRaw("msgid \"Monday\"\nmsgstr \"Ponedjeljak\"", 'Croatian translations present in exported file.');
    // Check for plural export specifically.
    $this->assertRaw("msgid \"1 hour\"\nmsgid_plural \"@count hours\"\nmsgstr[0] \"@count sat\"\nmsgstr[1] \"@count sata\"\nmsgstr[2] \"@count sati\"", 'Plural translations exported properly.');

    // Check if the source appears on the translation page.
    $this->drupalGet('admin/config/regional/translate');
    $this->assertText("1 hour");
    $this->assertText("@count hours");

    // Look up editing page for this plural string and check fields.
    $path = 'admin/config/regional/translate/';
    $search = array(
      'langcode' => 'hr',
    );
    $this->drupalPostForm($path, $search, t('Filter'));
    // Labels for plural editing elements.
    $this->assertText('Singular form');
    $this->assertText('First plural form');
    $this->assertText('2. plural form');
    $this->assertNoText('3. plural form');

    // Plural values for langcode hr.
    $this->assertText('@count sat');
    $this->assertText('@count sata');
    $this->assertText('@count sati');

    // Edit langcode hr translations and see if that took effect.
    $lid = db_query("SELECT lid FROM {locales_source} WHERE source = :source AND context = ''", array(':source' => "1 hour" . LOCALE_PLURAL_DELIMITER . "@count hours"))->fetchField();
    $edit = array(
      "strings[$lid][translations][1]" => '@count sata edited',
    );
    $this->drupalPostForm($path, $edit, t('Save translations'));

    $search = array(
      'langcode' => 'fr',
    );
    $this->drupalPostForm('admin/config/regional/translate', $search, t('Filter'));
    // Plural values for the langcode fr.
    $this->assertText('@count heure');
    $this->assertText('@count heures');
    $this->assertNoText('2. plural form');

    // Edit langcode fr translations and see if that took effect.
    $edit = array(
      "strings[$lid][translations][0]" => '@count heure edited',
    );
    $this->drupalPostForm($path, $edit, t('Save translations'));

    // Inject a plural source string to the database. We need to use a specific
    // langcode here because the language will be English by default and will
    // not save our source string for performance optimization if we do not ask
    // specifically for a language.
    \Drupal::translation()->formatPlural(1, '1 day', '@count days', array(), array('langcode' => 'fr'))->render();
    $lid = db_query("SELECT lid FROM {locales_source} WHERE source = :source AND context = ''", array(':source' => "1 day" . LOCALE_PLURAL_DELIMITER . "@count days"))->fetchField();
    // Look up editing page for this plural string and check fields.
    $search = array(
      'string' => '1 day',
      'langcode' => 'fr',
    );
    $this->drupalPostForm('admin/config/regional/translate', $search, t('Filter'));

    // Save complete translations for the string in langcode fr.
    $edit = array(
      "strings[$lid][translations][0]" => '1 jour',
      "strings[$lid][translations][1]" => '@count jours',
    );
    $this->drupalPostForm($path, $edit, t('Save translations'));

    // Save complete translations for the string in langcode hr.
    $search = array(
      'string' => '1 day',
      'langcode' => 'hr',
    );
    $this->drupalPostForm('admin/config/regional/translate', $search, t('Filter'));

    $edit = array(
      "strings[$lid][translations][0]" => '@count dan',
      "strings[$lid][translations][1]" => '@count dana',
      "strings[$lid][translations][2]" => '@count dana',
    );
    $this->drupalPostForm($path, $edit, t('Save translations'));

    // Get the French translations.
    $this->drupalPostForm('admin/config/regional/translate/export', array(
      'langcode' => 'fr',
    ), t('Export'));
    // Check for plural export specifically.
    $this->assertRaw("msgid \"1 hour\"\nmsgid_plural \"@count hours\"\nmsgstr[0] \"@count heure edited\"\nmsgstr[1] \"@count heures\"", 'Edited French plural translations for hours exported properly.');
    $this->assertRaw("msgid \"1 day\"\nmsgid_plural \"@count days\"\nmsgstr[0] \"1 jour\"\nmsgstr[1] \"@count jours\"", 'Added French plural translations for days exported properly.');

    // Get the Croatian translations.
    $this->drupalPostForm('admin/config/regional/translate/export', array(
      'langcode' => 'hr',
    ), t('Export'));
    // Check for plural export specifically.
    $this->assertRaw("msgid \"1 hour\"\nmsgid_plural \"@count hours\"\nmsgstr[0] \"@count sat\"\nmsgstr[1] \"@count sata edited\"\nmsgstr[2] \"@count sati\"", 'Edited Croatian plural translations exported properly.');
    $this->assertRaw("msgid \"1 day\"\nmsgid_plural \"@count days\"\nmsgstr[0] \"@count dan\"\nmsgstr[1] \"@count dana\"\nmsgstr[2] \"@count dana\"", 'Added Croatian plural translations exported properly.');
  }

  /**
   * Imports a standalone .po file in a given language.
   *
   * @param string $contents
   *   Contents of the .po file to import.
   * @param array $options
   *   Additional options to pass to the translation import form.
   */
  public function importPoFile($contents, array $options = array()) {
    $name = tempnam('temporary://', "po_") . '.po';
    file_put_contents($name, $contents);
    $options['files[file]'] = $name;
    $this->drupalPostForm('admin/config/regional/translate/import', $options, t('Import'));
    drupal_unlink($name);
  }

  /**
   * Returns a .po file with a simple plural formula.
   */
  public function getPoFileWithSimplePlural() {
    return <<< EOF
msgid ""
msgstr ""
"Project-Id-Version: Drupal 8\\n"
"MIME-Version: 1.0\\n"
"Content-Type: text/plain; charset=UTF-8\\n"
"Content-Transfer-Encoding: 8bit\\n"
"Plural-Forms: nplurals=2; plural=(n > 1);\\n"

msgid "1 hour"
msgid_plural "@count hours"
msgstr[0] "@count heure"
msgstr[1] "@count heures"

msgid "Monday"
msgstr "lundi"
EOF;
  }

  /**
   * Returns a .po file with a complex plural formula.
   */
  public function getPoFileWithComplexPlural() {
    return <<< EOF
msgid ""
msgstr ""
"Project-Id-Version: Drupal 8\\n"
"MIME-Version: 1.0\\n"
"Content-Type: text/plain; charset=UTF-8\\n"
"Content-Transfer-Encoding: 8bit\\n"
"Plural-Forms: nplurals=3; plural=n%10==1 && n%100!=11 ? 0 : n%10>=2 && n%10<=4 && (n%100<10 || n%100>=20) ? 1 : 2;\\n"

msgid "1 hour"
msgid_plural "@count hours"
msgstr[0] "@count sat"
msgstr[1] "@count sata"
msgstr[2] "@count sati"

msgid "Monday"
msgstr "Ponedjeljak"
EOF;
  }

  /**
   * Returns a .po file with a missing plural formula.
   */
  public function getPoFileWithMissingPlural() {
    return <<< EOF
msgid ""
msgstr ""
"Project-Id-Version: Drupal 8\\n"
"MIME-Version: 1.0\\n"
"Content-Type: text/plain; charset=UTF-8\\n"
"Content-Transfer-Encoding: 8bit\\n"

msgid "Monday"
msgstr "lundi"
EOF;
  }

  /**
   * Returns a .po file with a broken plural formula.
   */
  public function getPoFileWithBrokenPlural() {
    return <<< EOF
msgid ""
msgstr ""
"Project-Id-Version: Drupal 8\\n"
"MIME-Version: 1.0\\n"
"Content-Type: text/plain; charset=UTF-8\\n"
"Content-Transfer-Encoding: 8bit\\n"
"Plural-Forms: broken, will not parse\\n"

msgid "Monday"
msgstr "Ponedjeljak"
EOF;
  }
}
