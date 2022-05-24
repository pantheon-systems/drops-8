<?php

namespace Drupal\FunctionalJavascriptTests;

use Behat\Mink\Driver\Selenium2Driver;
use Behat\Mink\Exception\DriverException;
use WebDriver\Exception;
use WebDriver\Exception\UnknownError;
use WebDriver\ServiceFactory;

/**
 * Provides a driver for Selenium testing.
 */
class DrupalSelenium2Driver extends Selenium2Driver {

  /**
   * {@inheritdoc}
   */
  public function __construct($browserName = 'firefox', $desiredCapabilities = NULL, $wdHost = 'http://localhost:4444/wd/hub') {
    parent::__construct($browserName, $desiredCapabilities, $wdHost);
    ServiceFactory::getInstance()->setServiceClass('service.curl', WebDriverCurlService::class);
  }

  /**
   * {@inheritdoc}
   */
  public function setCookie($name, $value = NULL) {
    if ($value === NULL) {
      $this->getWebDriverSession()->deleteCookie($name);
      return;
    }

    $cookieArray = [
      'name' => $name,
      'value' => urlencode($value),
      'secure' => FALSE,
      // Unlike \Behat\Mink\Driver\Selenium2Driver::setCookie we set a domain
      // and an expire date, as otherwise cookies leak from one test site into
      // another.
      'domain' => parse_url($this->getWebDriverSession()->url(), PHP_URL_HOST),
      'expires' => time() + 80000,
    ];

    $this->getWebDriverSession()->setCookie($cookieArray);
  }

  /**
   * {@inheritdoc}
   */
  public function attachFile($xpath, $path) {
    $element = $this->getWebDriverSession()->element('xpath', $xpath);

    if ('input' !== strtolower($element->name()) || 'file' !== strtolower($element->attribute('type'))) {
      $message = 'Impossible to %s the element with XPath "%s" as it is not a %s input';

      throw new DriverException(sprintf($message, 'attach a file on', $xpath, 'file'));
    }

    // Upload the file to Selenium and use the remote path. This will
    // ensure that Selenium always has access to the file, even if it runs
    // as a remote instance.
    try {
      $remotePath = $this->uploadFileAndGetRemoteFilePath($path);
    }
    catch (\Exception $e) {
      // File could not be uploaded to remote instance. Use the local path.
      $remotePath = $path;
    }

    $element->postValue(['value' => [$remotePath]]);
  }

  /**
   * Uploads a file to the Selenium instance and returns the remote path.
   *
   * \Behat\Mink\Driver\Selenium2Driver::uploadFile() is a private method so
   * that can't be used inside a test, but we need the remote path that is
   * generated when uploading to make sure the file reference exists on the
   * container running selenium.
   *
   * @param string $path
   *   The path to the file to upload.
   *
   * @return string
   *   The remote path.
   *
   * @throws \Behat\Mink\Exception\DriverException
   *   When PHP is compiled without zip support, or the file doesn't exist.
   * @throws \WebDriver\Exception\UnknownError
   *   When an unknown error occurred during file upload.
   * @throws \Exception
   *   When a known error occurred during file upload.
   */
  public function uploadFileAndGetRemoteFilePath($path) {
    if (!is_file($path)) {
      throw new DriverException('File does not exist locally and cannot be uploaded to the remote instance.');
    }

    if (!class_exists('ZipArchive')) {
      throw new DriverException('Could not compress file, PHP is compiled without zip support.');
    }

    // Selenium only accepts uploads that are compressed as a Zip archive.
    $tempFilename = tempnam('', 'WebDriverZip');

    $archive = new \ZipArchive();
    $result = $archive->open($tempFilename, \ZipArchive::OVERWRITE);
    if (!$result) {
      throw new DriverException('Zip archive could not be created. Error ' . $result);
    }
    $result = $archive->addFile($path, basename($path));
    if (!$result) {
      throw new DriverException('File could not be added to zip archive.');
    }
    $result = $archive->close();
    if (!$result) {
      throw new DriverException('Zip archive could not be closed.');
    }

    try {
      $remotePath = $this->getWebDriverSession()->file(['file' => base64_encode(file_get_contents($tempFilename))]);

      // If no path is returned the file upload failed silently.
      if (empty($remotePath)) {
        throw new UnknownError();
      }
    }
    catch (\Exception $e) {
      throw $e;
    }
    finally {
      unlink($tempFilename);
    }

    return $remotePath;
  }

  /**
   * {@inheritdoc}
   */
  public function click($xpath) {
    /** @var \Exception $not_clickable_exception */
    $not_clickable_exception = NULL;
    $result = $this->waitFor(10, function () use (&$not_clickable_exception, $xpath) {
      try {
        parent::click($xpath);
        return TRUE;
      }
      catch (Exception $exception) {
        if (!JSWebAssert::isExceptionNotClickable($exception)) {
          // Rethrow any unexpected exceptions.
          throw $exception;
        }
        $not_clickable_exception = $exception;
        return NULL;
      }
    });
    if ($result !== TRUE) {
      throw $not_clickable_exception;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function setValue($xpath, $value) {
    /** @var \Exception $not_clickable_exception */
    $not_clickable_exception = NULL;
    $result = $this->waitFor(10, function () use (&$not_clickable_exception, $xpath, $value) {
      try {
        parent::setValue($xpath, $value);
        return TRUE;
      }
      catch (Exception $exception) {
        if (!JSWebAssert::isExceptionNotClickable($exception) && !str_contains($exception->getMessage(), 'invalid element state')) {
          // Rethrow any unexpected exceptions.
          throw $exception;
        }
        $not_clickable_exception = $exception;
        return NULL;
      }
    });
    if ($result !== TRUE) {
      throw $not_clickable_exception;
    }
  }

  /**
   * Waits for a callback to return a truthy result and returns it.
   *
   * @param int|float $timeout
   *   Maximal allowed waiting time in seconds.
   * @param callable $callback
   *   Callback, which result is both used as waiting condition and returned.
   *   Will receive reference to `this driver` as first argument.
   *
   * @return mixed
   *   The result of the callback.
   */
  private function waitFor($timeout, callable $callback) {
    $start = microtime(TRUE);
    $end = $start + $timeout;

    do {
      $result = call_user_func($callback, $this);

      if ($result) {
        break;
      }

      usleep(10000);
    } while (microtime(TRUE) < $end);

    return $result;
  }

}
