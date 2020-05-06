<?php

namespace Drupal\Tests\webform\Traits;

use Behat\Mink\Element\NodeElement;
use Behat\Mink\Exception\ExpectationException;
use Behat\Mink\Selector\Xpath\Escaper;
use Drupal\Component\Render\FormattableMarkup;
use Drupal\Component\Utility\Xss;

/**
 * Provides convenience methods for assertions in browser tests.
 *
 * Copies of legacy traits without deprecated warnings.
 *
 * @see \Drupal\KernelTests\AssertLegacyTrait
 * @see \Drupal\FunctionalTests\AssertLegacyTrait
 * @see http://blog.fclement.info/convert-simpletest-to-phpunit
 * @see https://www.drupal.org/node/2735005
 */
trait WebformAssertLegacyTrait {

  /**
   * @see \Drupal\simpletest\TestBase::assertEqual()
   */
  protected function assertEqual($expected, $actual, $message = '') {
    $this->assertEquals($expected, $actual, $message);
  }

  /**
   * @see \Drupal\simpletest\TestBase::assertNotEqual()
   */
  protected function assertNotEqual($expected, $actual, $message = '') {
    $this->assertNotEquals($expected, $actual, $message);
  }

  /**
   * @see \Drupal\simpletest\TestBase::assertIdentical()
   */
  protected function assertIdentical($expected, $actual, $message = '') {
    $this->assertSame($expected, $actual, $message);
  }

  /**
   * @see \Drupal\simpletest\TestBase::assertNotIdentical()
   */
  protected function assertNotIdentical($expected, $actual, $message = '') {
    $this->assertNotSame($expected, $actual, $message);
  }

  /**
   * @see \Drupal\simpletest\TestBase::assertIdenticalObject()
   */
  protected function assertIdenticalObject($expected, $actual, $message = '') {
    // Note: ::assertSame checks whether its the same object. ::assertEquals
    // though compares
    $this->assertEquals($expected, $actual, $message);
  }

  /**
   * @see \Drupal\simpletest\TestBase::pass()
   */
  protected function pass($message) {
    $this->assertTrue(TRUE, $message);
  }

  /**
   * @see \Drupal\simpletest\TestBase::verbose()
   */
  protected function verbose($message) {
    if (in_array('--debug', $_SERVER['argv'], TRUE)) {
      // Write directly to STDOUT to not produce unexpected test output.
      // The STDOUT stream does not obey output buffering.
      fwrite(STDOUT, $message . "\n");
    }
  }

  /**
   * Asserts that the element with the given CSS selector is present.
   *
   * @param string $css_selector
   *   The CSS selector identifying the element to check.
   */
  protected function assertElementPresent($css_selector) {
    $this->assertSession()->elementExists('css', $css_selector);
  }

  /**
   * Asserts that the element with the given CSS selector is not present.
   *
   * @param string $css_selector
   *   The CSS selector identifying the element to check.
   */
  protected function assertElementNotPresent($css_selector) {
    $this->assertSession()->elementNotExists('css', $css_selector);
  }

  /**
   * Passes if the page (with HTML stripped) contains the text.
   *
   * Note that stripping HTML tags also removes their attributes, such as
   * the values of text fields.
   *
   * @param string $text
   *   Plain text to look for.
   */
  protected function assertText($text) {
    // Cast MarkupInterface to string.
    $text = (string) $text;

    $content_type = $this->getSession()->getResponseHeader('Content-type');
    // In case of a Non-HTML response (example: XML) check the original
    // response.
    if (strpos($content_type, 'html') === FALSE) {
      $this->assertSession()->responseContains($text);
    }
    else {
      $this->assertTextHelper($text, FALSE);
    }
  }

  /**
   * Passes if the page (with HTML stripped) does not contains the text.
   *
   * Note that stripping HTML tags also removes their attributes, such as
   * the values of text fields.
   *
   * @param string $text
   *   Plain text to look for.
   */
  protected function assertNoText($text) {
    // Cast MarkupInterface to string.
    $text = (string) $text;

    $content_type = $this->getSession()->getResponseHeader('Content-type');
    // In case of a Non-HTML response (example: XML) check the original
    // response.
    if (strpos($content_type, 'html') === FALSE) {
      $this->assertSession()->responseNotContains($text);
    }
    else {
      $this->assertTextHelper($text);
    }
  }

  /**
   * Helper for assertText and assertNoText.
   *
   * @param string $text
   *   Plain text to look for.
   * @param bool $not_exists
   *   (optional) TRUE if this text should not exist, FALSE if it should.
   *   Defaults to TRUE.
   *
   * @return bool
   *   TRUE on pass, FALSE on fail.
   */
  protected function assertTextHelper($text, $not_exists = TRUE) {
    $args = ['@text' => $text];
    $message = $not_exists ? new FormattableMarkup('"@text" not found', $args) : new FormattableMarkup('"@text" found', $args);

    $raw_content = $this->getSession()->getPage()->getContent();
    // Trying to simulate what the user sees, given that it removes all text
    // inside the head tags, removes inline Javascript, fix all HTML entities,
    // removes dangerous protocols and filtering out all HTML tags, as they are
    // not visible in a normal browser.
    $raw_content = preg_replace('@<head>(.+?)</head>@si', '', $raw_content);
    $page_text = Xss::filter($raw_content, []);

    $actual = $not_exists == (strpos($page_text, (string) $text) === FALSE);
    $this->assertTrue($actual, $message);

    return $actual;
  }

  /**
   * Passes if the text is found ONLY ONCE on the text version of the page.
   *
   * The text version is the equivalent of what a user would see when viewing
   * through a web browser. In other words the HTML has been filtered out of
   * the contents.
   *
   * @param string|\Drupal\Component\Render\MarkupInterface $text
   *   Plain text to look for.
   * @param string $message
   *   (optional) A message to display with the assertion. Do not translate
   *   messages with t(). If left blank, a default message will be displayed.
   */
  protected function assertUniqueText($text, $message = NULL) {
    // Cast MarkupInterface objects to string.
    $text = (string) $text;

    $message = $message ?: "'$text' found only once on the page";
    $page_text = $this->getSession()->getPage()->getText();
    $nr_found = substr_count($page_text, $text);
    $this->assertSame(1, $nr_found, $message);
  }

  /**
   * Passes if the text is found MORE THAN ONCE on the text version of the page.
   *
   * The text version is the equivalent of what a user would see when viewing
   * through a web browser. In other words the HTML has been filtered out of
   * the contents.
   *
   * @param string|\Drupal\Component\Render\MarkupInterface $text
   *   Plain text to look for.
   * @param string $message
   *   (optional) A message to display with the assertion. Do not translate
   *   messages with t(). If left blank, a default message will be displayed.
   */
  protected function assertNoUniqueText($text, $message = '') {
    // Cast MarkupInterface objects to string.
    $text = (string) $text;

    $message = $message ?: "'$text' found more than once on the page";
    $page_text = $this->getSession()->getPage()->getText();
    $nr_found = substr_count($page_text, $text);
    $this->assertGreaterThan(1, $nr_found, $message);
  }

  /**
   * Asserts the page responds with the specified response code.
   *
   * @param int $code
   *   Response code. For example 200 is a successful page request. For a list
   *   of all codes see http://www.w3.org/Protocols/rfc2616/rfc2616-sec10.html.
   */
  protected function assertResponse($code) {
    $this->assertSession()->statusCodeEquals($code);
  }

  /**
   * Asserts that a field exists with the given name and value.
   *
   * @param string $name
   *   Name of field to assert.
   * @param string $value
   *   (optional) Value of the field to assert. You may pass in NULL (default)
   *   to skip checking the actual value, while still checking that the field
   *   exists.
   */
  protected function assertFieldByName($name, $value = NULL) {
    $this->assertFieldByXPath($this->constructFieldXpath('name', $name), $value);
  }

  /**
   * Asserts that a field does not exist with the given name and value.
   *
   * @param string $name
   *   Name of field to assert.
   * @param string $value
   *   (optional) Value for the field, to assert that the field's value on the
   *   page does not match it. You may pass in NULL to skip checking the
   *   value, while still checking that the field does not exist. However, the
   *   default value ('') asserts that the field value is not an empty string.
   */
  protected function assertNoFieldByName($name, $value = '') {
    $this->assertNoFieldByXPath($this->constructFieldXpath('name', $name), $value);
  }

  /**
   * Asserts that a field exists with the given ID and value.
   *
   * @param string $id
   *   ID of field to assert.
   * @param string|\Drupal\Component\Render\MarkupInterface $value
   *   (optional) Value for the field to assert. You may pass in NULL to skip
   *   checking the value, while still checking that the field exists.
   *   However, the default value ('') asserts that the field value is an empty
   *   string.
   *
   * @throws \Behat\Mink\Exception\ElementNotFoundException
   */
  protected function assertFieldById($id, $value = '') {
    $this->assertFieldByXPath($this->constructFieldXpath('id', $id), $value);
  }

  /**
   * Asserts that a field exists with the given name or ID.
   *
   * @param string $field
   *   Name or ID of field to assert.
   */
  protected function assertField($field) {
    $this->assertFieldByXPath($this->constructFieldXpath('name', $field) . '|' . $this->constructFieldXpath('id', $field));
  }

  /**
   * Asserts that a field does NOT exist with the given name or ID.
   *
   * @param string $field
   *   Name or ID of field to assert.
   */
  protected function assertNoField($field) {
    $this->assertNoFieldByXPath($this->constructFieldXpath('name', $field) . '|' . $this->constructFieldXpath('id', $field));
  }

  /**
   * Passes if the raw text IS found on the loaded page, fail otherwise.
   *
   * Raw text refers to the raw HTML that the page generated.
   *
   * @param string $raw
   *   Raw (HTML) string to look for.
   */
  protected function assertRaw($raw) {
    $actual = $this->getRawContent();
    $message = sprintf('The string "%s" was not found anywhere in the HTML response of the current page.', $raw);

    $this->assert(strpos($actual, (string) $raw) !== false, $message);
  }

  /**
   * Passes if the raw text IS not found on the loaded page, fail otherwise.
   *
   * Raw text refers to the raw HTML that the page generated.
   *
   * @param string $raw
   *   Raw (HTML) string to look for.
   */
  protected function assertNoRaw($raw) {
    $actual = $this->getRawContent();
    $message = sprintf('The string "%s" was not found anywhere in the HTML response of the current page.', $raw);

    $this->assert(strpos($actual, (string) $raw) === false, $message);
  }

  /**
   * Pass if the page title is the given string.
   *
   * @param string $expected_title
   *   The string the page title should be.
   */
  protected function assertTitle($expected_title) {
    // Cast MarkupInterface to string.
    $expected_title = (string) $expected_title;
    return $this->assertSession()->titleEquals($expected_title);
  }

  /**
   * Passes if a link with the specified label is found.
   *
   * An optional link index may be passed.
   *
   * @param string|\Drupal\Component\Render\MarkupInterface $label
   *   Text between the anchor tags.
   * @param int $index
   *   Link position counting from zero.
   */
  protected function assertLink($label, $index = 0) {
    return $this->assertSession()->linkExists($label, $index);
  }

  /**
   * Passes if a link with the specified label is not found.
   *
   * @param string|\Drupal\Component\Render\MarkupInterface $label
   *   Text between the anchor tags.
   */
  protected function assertNoLink($label) {
    return $this->assertSession()->linkNotExists($label);
  }

  /**
   * Passes if a link containing a given href (part) is found.
   *
   * @param string $href
   *   The full or partial value of the 'href' attribute of the anchor tag.
   * @param int $index
   *   Link position counting from zero.
   */
  protected function assertLinkByHref($href, $index = 0) {
    $this->assertSession()->linkByHrefExists($href, $index);
  }

  /**
   * Passes if a link containing a given href (part) is not found.
   *
   * @param string $href
   *   The full or partial value of the 'href' attribute of the anchor tag.
   */
  protected function assertNoLinkByHref($href) {
    $this->assertSession()->linkByHrefNotExists($href);
  }

  /**
   * Asserts that a field does not exist with the given ID and value.
   *
   * @param string $id
   *   ID of field to assert.
   * @param string $value
   *   (optional) Value for the field, to assert that the field's value on the
   *   page doesn't match it. You may pass in NULL to skip checking the value,
   *   while still checking that the field doesn't exist. However, the default
   *   value ('') asserts that the field value is not an empty string.
   *
   * @throws \Behat\Mink\Exception\ExpectationException
   */
  protected function assertNoFieldById($id, $value = '') {
    $this->assertNoFieldByXPath($this->constructFieldXpath('id', $id), $value);
  }

  /**
   * Passes if the internal browser's URL matches the given path.
   *
   * @param \Drupal\Core\Url|string $path
   *   The expected system path or URL.
   */
  protected function assertUrl($path) {
    $this->assertSession()->addressEquals($path);
  }

  /**
   * Asserts that a select option in the current page exists.
   *
   * @param string $id
   *   ID of select field to assert.
   * @param string $option
   *   Option to assert.
   */
  protected function assertOption($id, $option) {
    return $this->assertSession()->optionExists($id, $option);
  }

  /**
   * Asserts that a select option with the visible text exists.
   *
   * @param string $id
   *   The ID of the select field to assert.
   * @param string $text
   *   The text for the option tag to assert.
   */
  protected function assertOptionByText($id, $text) {
    return $this->assertSession()->optionExists($id, $text);
  }

  /**
   * Asserts that a select option does NOT exist in the current page.
   *
   * @param string $id
   *   ID of select field to assert.
   * @param string $option
   *   Option to assert.
   */
  protected function assertNoOption($id, $option) {
    return $this->assertSession()->optionNotExists($id, $option);
  }

  /**
   * Asserts that a select option in the current page is checked.
   *
   * @param string $id
   *   ID of select field to assert.
   * @param string $option
   *   Option to assert.
   * @param string $message
   *   (optional) A message to display with the assertion. Do not translate
   *   messages with t(). If left blank, a default message will be displayed.
   */
  protected function assertOptionSelected($id, $option, $message = NULL) {
    $option_field = $this->assertSession()->optionExists($id, $option);
    $message = $message ?: "Option $option for field $id is selected.";
    $this->assertTrue($option_field->hasAttribute('selected'), $message);
  }

  /**
   * Asserts that a checkbox field in the current page is checked.
   *
   * @param string $id
   *   ID of field to assert.
   */
  protected function assertFieldChecked($id) {
    $this->assertSession()->checkboxChecked($id);
  }

  /**
   * Asserts that a checkbox field in the current page is not checked.
   *
   * @param string $id
   *   ID of field to assert.
   */
  protected function assertNoFieldChecked($id) {
    $this->assertSession()->checkboxNotChecked($id);
  }

  /**
   * Asserts that a field exists in the current page by the given XPath.
   *
   * @param string $xpath
   *   XPath used to find the field.
   * @param string $value
   *   (optional) Value of the field to assert. You may pass in NULL (default)
   *   to skip checking the actual value, while still checking that the field
   *   exists.
   * @param string $message
   *   (optional) A message to display with the assertion. Do not translate
   *   messages with t().
   */
  protected function assertFieldByXPath($xpath, $value = NULL, $message = '') {
    $fields = $this->xpath($xpath);

    $this->assertFieldsByValue($fields, $value, $message);
  }

  /**
   * Asserts that a field does not exist or its value does not match, by XPath.
   *
   * @param string $xpath
   *   XPath used to find the field.
   * @param string $value
   *   (optional) Value of the field, to assert that the field's value on the
   *   page does not match it.
   * @param string $message
   *   (optional) A message to display with the assertion. Do not translate
   *   messages with t().
   *
   * @throws \Behat\Mink\Exception\ExpectationException
   */
  protected function assertNoFieldByXPath($xpath, $value = NULL, $message = '') {
    $fields = $this->xpath($xpath);

    if (!empty($fields)) {
      if (isset($value)) {
        $found = FALSE;
        try {
          $this->assertFieldsByValue($fields, $value);
          $found = TRUE;
        }
        catch (\Exception $e) {
        }

        if ($found) {
          throw new ExpectationException(sprintf('The field resulting from %s was found with the provided value %s.', $xpath, $value), $this->getSession()->getDriver());
        }
      }
      else {
        throw new ExpectationException(sprintf('The field resulting from %s was found.', $xpath), $this->getSession()->getDriver());
      }
    }
  }

  /**
   * Asserts that a field exists in the current page with a given Xpath result.
   *
   * @param \Behat\Mink\Element\NodeElement[] $fields
   *   Xml elements.
   * @param string $value
   *   (optional) Value of the field to assert. You may pass in NULL (default) to skip
   *   checking the actual value, while still checking that the field exists.
   * @param string $message
   *   (optional) A message to display with the assertion. Do not translate
   *   messages with t().
   */
  protected function assertFieldsByValue($fields, $value = NULL, $message = '') {
    // If value specified then check array for match.
    $found = TRUE;
    if (isset($value)) {
      $found = FALSE;
      if ($fields) {
        foreach ($fields as $field) {
          if ($field->getAttribute('type') == 'checkbox') {
            if (is_bool($value)) {
              $found = $field->isChecked() == $value;
            }
            else {
              $found = TRUE;
            }
          }
          elseif ($field->getAttribute('value') == $value) {
            // Input element with correct value.
            $found = TRUE;
          }
          elseif ($field->find('xpath', '//option[@value = ' . (new Escaper())->escapeLiteral($value) . ' and @selected = "selected"]')) {
            // Select element with an option.
            $found = TRUE;
          }
          elseif ($field->getTagName() === 'textarea' && $field->getValue() == $value) {
            // Text area with correct text. Use getValue() here because
            // getText() would remove any newlines in the value.
            $found = TRUE;
          }
          elseif ($field->getTagName() !== 'input' && $field->getText() == $value) {
            $found = TRUE;
          }
        }
      }
    }
    $this->assertTrue($fields && $found, $message);
  }

  /**
   * Passes if the raw text IS found escaped on the loaded page, fail otherwise.
   *
   * Raw text refers to the raw HTML that the page generated.
   *
   * @param string $raw
   *   Raw (HTML) string to look for.
   */
  protected function assertEscaped($raw) {
    $this->assertSession()->assertEscaped($raw);
  }

  /**
   * Passes if the raw text is not found escaped on the loaded page.
   *
   * Raw text refers to the raw HTML that the page generated.
   *
   * @param string $raw
   *   Raw (HTML) string to look for.
   */
  protected function assertNoEscaped($raw) {
    $this->assertSession()->assertNoEscaped($raw);
  }

  /**
   * Triggers a pass if the Perl regex pattern is found in the raw content.
   *
   * @param string $pattern
   *   Perl regex to look for including the regex delimiters.
   */
  protected function assertPattern($pattern) {
    $this->assertSession()->responseMatches($pattern);
  }

  /**
   * Triggers a pass if the Perl regex pattern is not found in the raw content.
   *
   * @param string $pattern
   *   Perl regex to look for including the regex delimiters.
   *
   * @see https://www.drupal.org/node/2864262
   */
  protected function assertNoPattern($pattern) {
    $this->assertSession()->responseNotMatches($pattern);
  }

  /**
   * Asserts whether an expected cache tag was present in the last response.
   *
   * @param string $expected_cache_tag
   *   The expected cache tag.
   */
  protected function assertCacheTag($expected_cache_tag) {
    $this->assertSession()->responseHeaderContains('X-Drupal-Cache-Tags', $expected_cache_tag);
  }

  /**
   * Asserts whether an expected cache tag was absent in the last response.
   *
   * @param string $cache_tag
   *   The cache tag to check.
   *
   * @see https://www.drupal.org/node/2864029
   */
  protected function assertNoCacheTag($cache_tag) {
    $this->assertSession()->responseHeaderNotContains('X-Drupal-Cache-Tags', $cache_tag);
  }

  /**
   * Checks that current response header equals value.
   *
   * @param string $name
   *   Name of header to assert.
   * @param string $value
   *   Value of the header to assert.
   */
  protected function assertHeader($name, $value) {
    $this->assertSession()->responseHeaderEquals($name, $value);
  }

  /**
   * Returns WebAssert object.
   *
   * @param string $name
   *   (optional) Name of the session. Defaults to the active session.
   *
   * @return \Drupal\Tests\WebAssert|\Drupal\FunctionalJavascriptTests\WebDriverWebAssert
   *   A new web-assert option for asserting the presence of elements with.
   */
  abstract public function assertSession($name = NULL);

  /**
   * Builds an XPath query.
   *
   * Builds an XPath query by replacing placeholders in the query by the value
   * of the arguments.
   *
   * XPath 1.0 (the version supported by libxml2, the underlying XML library
   * used by PHP) doesn't support any form of quotation. This function
   * simplifies the building of XPath expression.
   *
   * @param string $xpath
   *   An XPath query, possibly with placeholders in the form ':name'.
   * @param array $args
   *   An array of arguments with keys in the form ':name' matching the
   *   placeholders in the query. The values may be either strings or numeric
   *   values.
   *
   * @return string
   *   An XPath query with arguments replaced.
   */
  protected function buildXPathQuery($xpath, array $args = []) {
    return $this->assertSession()->buildXPathQuery($xpath, $args);
  }

  /**
   * Helper: Constructs an XPath for the given set of attributes and value.
   *
   * @param string $attribute
   *   Field attributes.
   * @param string $value
   *   Value of field.
   *
   * @return string
   *   XPath for specified values.
   */
  protected function constructFieldXpath($attribute, $value) {
    $xpath = '//textarea[@' . $attribute . '=:value]|//input[@' . $attribute . '=:value]|//select[@' . $attribute . '=:value]';
    return $this->buildXPathQuery($xpath, [':value' => $value]);
  }

  /**
   * Gets the current raw content.
   */
  protected function getRawContent() {
    return $this->getSession()->getPage()->getContent();
  }

  /**
   * Get all option elements, including nested options, in a select.
   *
   * @param \Behat\Mink\Element\NodeElement $element
   *   The element for which to get the options.
   *
   * @return \Behat\Mink\Element\NodeElement[]
   *   Option elements in select.
   */
  protected function getAllOptions(NodeElement $element) {
    return $element->findAll('xpath', '//option');
  }

}
