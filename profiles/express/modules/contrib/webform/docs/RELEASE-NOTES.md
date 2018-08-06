
Steps for creating a new release
--------------------------------

  1. Cleanup code
  2. Export configuration
  3. Review code
  4. Run tests
  5. Generate release notes
  6. Tag and create a new release
  7. Upload screencast to YouTube

1. Cleanup code
---------------

[Convert to short array syntax](https://www.drupal.org/project/short_array_syntax)

    drush short-array-syntax webform

Tidy YAML files

    @see DEVELOPMENT-CHEATSHEET.md


2. Export configuration
-----------------------

    @see DEVELOPMENT-CHEATSHEET.md


3. Review code
--------------

[Online](http://pareview.sh)

    http://git.drupal.org/project/webform.git 8.x-5.x

[Commandline](https://www.drupal.org/node/1587138)

    # Check Drupal coding standards
    phpcs --standard=Drupal --extensions=php,module,inc,install,test,profile,theme,css,info modules/sandbox/webform
    
    # Check Drupal best practices
    phpcs --standard=DrupalPractice --extensions=php,module,inc,install,test,profile,theme,js,css,info modules/sandbox/webform

[File Permissions](https://www.drupal.org/comment/reply/2690335#comment-form)

    # Files should be 644 or -rw-r--r--
    find * -type d -print0 | xargs -0 chmod 0755

    # Directories should be 755 or drwxr-xr-x
    find . -type f -print0 | xargs -0 chmod 0644


4. Run tests
------------

[SimpleTest](https://www.drupal.org/node/645286)

    # Run all tests
    cd /var/www/sites/d8_webform
    php core/scripts/run-tests.sh --url http://localhost/wf --module webform --dburl mysql://drupal_d8_webform:drupal.@dm1n@localhost/drupal_d8_webform

    # Run single tests
    cd /var/www/sites/d8_webform
    php core/scripts/run-tests.sh --verbose --class "Drupal\webform\Tests\WebformSubmissionStorageTest"

[PHPUnit](https://www.drupal.org/node/2116263)
     
Notes
- Links to PHP Unit HTML responses are not being printed by PHPStrom

References 
- [Issue #2870145: Set printerClass in phpunit.xml.dist](https://www.drupal.org/node/2870145) 
- [Lesson 10.2 - Unit testing](https://docs.acquia.com/article/lesson-102-unit-testing)


    # Execute all Webform PHPUnit tests.
    cd /var/www/sites/d8_webform/core
    php ../vendor/phpunit/phpunit/phpunit --printer="\Drupal\Tests\Listeners\HtmlOutputPrinter" --group webform

    cd /var/www/sites/d8_webform/core

    # Execute individual PHPUnit tests.
    export SIMPLETEST_DB=mysql://drupal_d8_webform:drupal.@dm1n@localhost/drupal_d8_webform;

    # Functional test.    
    php ../vendor/phpunit/phpunit/phpunit --printer="\Drupal\Tests\Listeners\HtmlOutputPrinter" ../modules/sandbox/webform/tests/src/Functional/WebformExampleFunctionalTest.php

    # Kernal test.    
    php ../vendor/phpunit/phpunit/phpunit --printer="\Drupal\Tests\Listeners\HtmlOutputPrinter" ../modules/sandbox/webform/tests/src/Kernal/Utility/WebformDialogHelperTest.php

    # Unit test.
    php ../vendor/phpunit/phpunit/phpunit --printer="\Drupal\Tests\Listeners\HtmlOutputPrinter"  ../modules/sandbox/webform/tests/src/Unit/Utility/WebformYamlTest.php


5. Generate release notes
-------------------------

[Git Release Notes for Drush](https://www.drupal.org/project/grn)

    drush release-notes --nouser 8.x-5.0-VERSION 8.x-5.x


6. Tag and create a new release
-------------------------------

[Tag a release](https://www.drupal.org/node/1066342)

    git tag 8.x-5.0-VERSION
    git push --tags
    git push origin tag 8.x-5.0-VERSION

[Create new release](https://www.drupal.org/node/add/project-release/2640714)


7. Upload screencast to YouTube
-------------------------------

- Title : Webform 8.x-5.x-betaXX
- Tags: Drupal 8,Webform,Form Builder
- Privacy: listed
