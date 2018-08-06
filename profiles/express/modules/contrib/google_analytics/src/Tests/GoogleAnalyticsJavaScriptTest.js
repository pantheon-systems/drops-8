/**
 * @file
 * Google Analytics javascript tests.
 */

(function ($, Drupal, drupalSettings) {

  /* eslint no-console: 0 */

  'use strict';

  /**
   * This file is for developers only.
   *
   * This tests are made for the javascript functions used in GA module.
   * These tests verify if the return values are properly working.
   *
   * Hopefully this can be added somewhere else once Drupal core has JavaScript
   * unit testing integrated.
   */

  Drupal.google_analytics.test = {};

  Drupal.google_analytics.test.assertSame = function (value1, value2, message) {
    if (value1 === value2) {
      console.info(message);
    }
    else {
      console.error(message);
    }
  };

  Drupal.google_analytics.test.assertNotSame = function (value1, value2, message) {
    if (value1 !== value2) {
      console.info(message);
    }
    else {
      console.error(message);
    }
  };

  Drupal.google_analytics.test.assertTrue = function (value1, message) {
    if (value1 === true) {
      console.info(message);
    }
    else {
      console.error(message);
    }
  };

  Drupal.google_analytics.test.assertFalse = function (value1, message) {
    if (value1 === false) {
      console.info(message);
    }
    else {
      console.error(message);
    }
  };

  /**
   * Run javascript tests against the GA module.
   */

  // JavaScript debugging.
  var base_url = window.location.protocol + '//' + window.location.host;
  var base_path = window.location.pathname;
  console.dir(Drupal);

  console.group("Test 'isDownload':");
  Drupal.google_analytics.test.assertFalse(Drupal.google_analytics.isDownload(base_url + drupalSettings.path.baseUrl + 'node/8'), "Verify that '/node/8' url is not detected as file download.");
  Drupal.google_analytics.test.assertTrue(Drupal.google_analytics.isDownload(base_url + drupalSettings.path.baseUrl + 'files/foo1.zip'), "Verify that '/files/foo1.zip' url is detected as a file download.");
  Drupal.google_analytics.test.assertTrue(Drupal.google_analytics.isDownload(base_url + drupalSettings.path.baseUrl + 'files/foo1.zip#foo'), "Verify that '/files/foo1.zip#foo' url is detected as a file download.");
  Drupal.google_analytics.test.assertTrue(Drupal.google_analytics.isDownload(base_url + drupalSettings.path.baseUrl + 'files/foo1.zip?foo=bar'), "Verify that '/files/foo1.zip?foo=bar' url is detected as a file download.");
  Drupal.google_analytics.test.assertTrue(Drupal.google_analytics.isDownload(base_url + drupalSettings.path.baseUrl + 'files/foo1.zip?foo=bar#foo'), "Verify that '/files/foo1.zip?foo=bar#foo' url is detected as a file download.");
  Drupal.google_analytics.test.assertFalse(Drupal.google_analytics.isDownload(base_url + drupalSettings.path.baseUrl + 'files/foo2.ddd'), "Verify that '/files/foo2.ddd' url is not detected as file download.");
  console.groupEnd();

  console.group("Test 'isInternal':");
  Drupal.google_analytics.test.assertTrue(Drupal.google_analytics.isInternal(base_url + drupalSettings.path.baseUrl + 'node/1'), "Link '" + base_url + drupalSettings.path.baseUrl + "node/2' has been detected as internal link.");
  Drupal.google_analytics.test.assertTrue(Drupal.google_analytics.isInternal(base_url + drupalSettings.path.baseUrl + 'node/1#foo'), "Link '" + base_url + drupalSettings.path.baseUrl + "node/1#foo' has been detected as internal link.");
  Drupal.google_analytics.test.assertTrue(Drupal.google_analytics.isInternal(base_url + drupalSettings.path.baseUrl + 'node/1?foo=bar'), "Link '" + base_url + drupalSettings.path.baseUrl + "node/1?foo=bar' has been detected as internal link.");
  Drupal.google_analytics.test.assertTrue(Drupal.google_analytics.isInternal(base_url + drupalSettings.path.baseUrl + 'node/1?foo=bar#foo'), "Link '" + base_url + drupalSettings.path.baseUrl + "node/1?foo=bar#foo' has been detected as internal link.");
  Drupal.google_analytics.test.assertTrue(Drupal.google_analytics.isInternal(base_url + drupalSettings.path.baseUrl + 'go/foo'), "Link '" + base_url + drupalSettings.path.baseUrl + "go/foo' has been detected as internal link.");
  Drupal.google_analytics.test.assertFalse(Drupal.google_analytics.isInternal('http://example.com/node/3'), "Link 'http://example.com/node/3' has been detected as external link.");
  console.groupEnd();

  console.group("Test 'isInternalSpecial':");
  Drupal.google_analytics.test.assertTrue(Drupal.google_analytics.isInternalSpecial(base_url + drupalSettings.path.baseUrl + 'go/foo'), "Link '" + base_url + drupalSettings.path.baseUrl + "go/foo' has been detected as special internal link.");
  Drupal.google_analytics.test.assertFalse(Drupal.google_analytics.isInternalSpecial(base_url + drupalSettings.path.baseUrl + 'node/1'), "Link '" + base_url + drupalSettings.path.baseUrl + "node/1' has been detected as special internal link.");
  console.groupEnd();

  console.group("Test 'getPageUrl':");
  Drupal.google_analytics.test.assertSame(base_path, Drupal.google_analytics.getPageUrl(base_url + drupalSettings.path.baseUrl + 'node/1'), "Absolute internal URL '" + drupalSettings.path.baseUrl + "node/1' has been extracted from full qualified url '" + base_url + base_path + "'.");
  Drupal.google_analytics.test.assertSame(base_path, Drupal.google_analytics.getPageUrl(drupalSettings.path.baseUrl + 'node/1'), "Absolute internal URL '" + drupalSettings.path.baseUrl + "node/1' has been extracted from absolute url '" + base_path + "'.");
  Drupal.google_analytics.test.assertSame('http://example.com/node/2', Drupal.google_analytics.getPageUrl('http://example.com/node/2'), "Full qualified external url 'http://example.com/node/2' has been extracted.");
  Drupal.google_analytics.test.assertSame('//example.com/node/2', Drupal.google_analytics.getPageUrl('//example.com/node/2'), "Full qualified external url '//example.com/node/2' has been extracted.");
  console.groupEnd();

  console.group("Test 'getDownloadExtension':");
  Drupal.google_analytics.test.assertSame('zip', Drupal.google_analytics.getDownloadExtension(base_url + drupalSettings.path.baseUrl + '/files/foo1.zip'), "Download extension 'zip' has been found in '" + base_url + drupalSettings.path.baseUrl + "files/foo1.zip'.");
  Drupal.google_analytics.test.assertSame('zip', Drupal.google_analytics.getDownloadExtension(base_url + drupalSettings.path.baseUrl + '/files/foo1.zip#foo'), "Download extension 'zip' has been found in '" + base_url + drupalSettings.path.baseUrl + "files/foo1.zip#foo'.");
  Drupal.google_analytics.test.assertSame('zip', Drupal.google_analytics.getDownloadExtension(base_url + drupalSettings.path.baseUrl + '/files/foo1.zip?foo=bar'), "Download extension 'zip' has been found in '" + base_url + drupalSettings.path.baseUrl + "files/foo1.zip?foo=bar'.");
  Drupal.google_analytics.test.assertSame('zip', Drupal.google_analytics.getDownloadExtension(base_url + drupalSettings.path.baseUrl + '/files/foo1.zip?foo=bar#foo'), "Download extension 'zip' has been found in '" + base_url + drupalSettings.path.baseUrl + "files/foo1.zip?foo=bar'.");
  Drupal.google_analytics.test.assertSame('', Drupal.google_analytics.getDownloadExtension(base_url + drupalSettings.path.baseUrl + '/files/foo2.dddd'), "No download extension found in '" + base_url + drupalSettings.path.baseUrl + "files/foo2.dddd'.");
  console.groupEnd();

  // List of top-level domains: example.com, example.net
  console.group("Test 'isCrossDomain' (requires cross domain configuration with 'example.com' and 'example.net'):");
  if (drupalSettings.google_analytics.trackCrossDomains) {
    console.dir(drupalSettings.google_analytics.trackCrossDomains);
    Drupal.google_analytics.test.assertTrue(Drupal.google_analytics.isCrossDomain('example.com', drupalSettings.google_analytics.trackCrossDomains), "URL 'example.com' has been found in cross domain list.");
    Drupal.google_analytics.test.assertTrue(Drupal.google_analytics.isCrossDomain('example.net', drupalSettings.google_analytics.trackCrossDomains), "URL 'example.com' has been found in cross domain list.");
    Drupal.google_analytics.test.assertFalse(Drupal.google_analytics.isCrossDomain('www.example.com', drupalSettings.google_analytics.trackCrossDomains), "URL 'www.example.com' not found in cross domain list.");
    Drupal.google_analytics.test.assertFalse(Drupal.google_analytics.isCrossDomain('www.example.net', drupalSettings.google_analytics.trackCrossDomains), "URL 'www.example.com' not found in cross domain list.");
  }
  else {
    console.warn('Cross domain tracking is not enabled. Tests skipped.');
  }
  console.groupEnd();

})(jQuery, Drupal, drupalSettings);
