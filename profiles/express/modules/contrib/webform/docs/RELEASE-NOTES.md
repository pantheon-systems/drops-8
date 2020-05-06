Steps for creating a new release
--------------------------------

  1. Review code
  2. Deprecated code
  3. Review accessibility
  4. Run tests
  5. Generate release notes
  6. Tag and create a new release
  7. Tag and create a hotfix release

1. Review code
--------------

    # Remove files that should never be reviewed.
    cd modules/sandbox/webform
    rm *.patch interdiff-*

[PHP](https://www.drupal.org/node/1587138)

    # Check Drupal PHP coding standards
    cd /var/www/sites/d8_webform/web
    phpcs --standard=Drupal --extensions=php,module,inc,install,test,profile,theme,css,info modules/sandbox/webform > ~/webform-php-coding-standards.txt
    cat ~/webform-php-coding-standards.txt

    # Check Drupal PHP best practices
    cd /var/www/sites/d8_webform/web
    phpcs --standard=DrupalPractice --extensions=php,module,inc,install,test,profile,theme,js,css,info modules/sandbox/webform > ~/webform-php-best-practice.txt
    cat ~/webform-php-best-practice.txt

[JavaScript](https://www.drupal.org/node/2873849)

    # Install Eslint. (One-time)
    cd /var/www/sites/d8_webform/web/core
    yarn install

    # Check Drupal JavaScript (ES5) legacy coding standards.
    cd /var/www/sites/d8_webform/web
    core/node_modules/.bin/eslint --no-eslintrc -c=core/.eslintrc.legacy.json --ext=.js modules/sandbox/webform > ~/webform-javascript-coding-standards.txt
    cat ~/webform-javascript-coding-standards.txt

[CSS](https://www.drupal.org/node/3041002)

    # Install Eslint. (One-time)
    cd /var/www/sites/d8_webform/web/core
    yarn install

    cd /var/www/sites/d8_webform/web/core
    yarn run lint:css ../modules/sandbox/webform/css --fix

[File Permissions](https://www.drupal.org/comment/reply/2690335#comment-form)

    # Files should be 644 or -rw-r--r--
    find * -type d -print0 | xargs -0 chmod 0755

    # Directories should be 755 or drwxr-xr-x
    find . -type f -print0 | xargs -0 chmod 0644

2. Deprecated code
------------------

[drupal-check](https://github.com/mglaman/drupal-check) - RECOMMENDED

`drupal-check` output can not be redirected to a file.

@see [Redirect output to a file #137](https://github.com/mglaman/drupal-check/issues/137)

    cd /var/www/sites/d8_webform/
    composer require mglaman/drupal-check
    # Deprecations.
    vendor/mglaman/drupal-check/drupal-check --no-progress -d web/modules/sandbox/webform
    # Analysis.
    vendor/mglaman/drupal-check/drupal-check --no-progress  -a web/modules/sandbox/webform


[phpstan-drupal](https://github.com/mglaman/phpstan-drupal)
[phpstan-drupal-deprecations](https://github.com/mglaman/phpstan-drupal-deprecations)

    cd /var/www/sites/d8_webform/
    composer require mglaman/phpstan-drupal
    composer require phpstan/phpstan-deprecation-rules

Create `/var/www/sites/d8_webform/phpstan.neon`

    parameters:
      customRulesetUsed: true
      reportUnmatchedIgnoredErrors: false
      # Ignore phpstan-drupal extension's rules.
      ignoreErrors:
        - '#\Drupal calls should be avoided in classes, use dependency injection instead#'
        - '#Plugin definitions cannot be altered.#'
        - '#Missing cache backend declaration for performance.#'
        - '#Plugin manager has cache backend specified but does not declare cache tags.#'
    includes:
      - vendor/mglaman/phpstan-drupal/extension.neon
      - vendor/phpstan/phpstan-deprecation-rules/rules.neon

Run PHPStan with memory limit increased

    cd /var/www/sites/d8_webform/
    ./vendor/bin/phpstan --memory-limit=1024M analyse web/modules/sandbox/webform > ~/webform-deprecated.txt
    cat ~/webform-deprecated.txt

3. Review accessibility
-----------------------

[Pa11y](http://pa11y.org/)

Pa11y is your automated accessibility testing pal.

Notes
- Requires node 8.x+


    # Enable accessibility examples.
    drush en -y webform_examples_accessibility

    # Text.
    mkdir -p /var/www/sites/d8_webform/web/modules/sandbox/webform/reports/accessiblity/text
    cd /var/www/sites/d8_webform/web/modules/sandbox/webform/reports/accessiblity/text
    pa11y http://localhost/wf/webform/example_accessibility_basic > example_accessibility_basic.txt
    pa11y http://localhost/wf/webform/example_accessibility_advanced > example_accessibility_advanced.txt
    pa11y http://localhost/wf/webform/example_accessibility_containers > example_accessibility_containers.txt
    pa11y http://localhost/wf/webform/example_accessibility_wizard > example_accessibility_wizard.txt
    pa11y http://localhost/wf/webform/example_accessibility_labels > example_accessibility_labels.txt

    # HTML.
    mkdir -p /var/www/sites/d8_webform/web/modules/sandbox/webform/reports/accessiblity/html
    cd /var/www/sites/d8_webform/web/modules/sandbox/webform/reports/accessiblity/html
    pa11y --reporter html http://localhost/wf/webform/example_accessibility_basic > example_accessibility_basic.html
    pa11y --reporter html http://localhost/wf/webform/example_accessibility_advanced > example_accessibility_advanced.html
    pa11y --reporter html http://localhost/wf/webform/example_accessibility_containers > example_accessibility_containers.html
    pa11y --reporter html http://localhost/wf/webform/example_accessibility_wizard > example_accessibility_wizard.html
    pa11y --reporter html http://localhost/wf/webform/example_accessibility_labels > example_accessibility_labels.html

    # Remove localhost from reports.
    cd /var/www/sites/d8_webform/web/modules/sandbox/webform/reports/accessiblity
    find . -name '*.html' -exec sed -i '' -e  's|http://localhost/wf/webform/|http://localhost/webform/|g' {} \;

    # PDF.
    mkdir -p /var/www/sites/d8_webform/web/modules/sandbox/webform/reports/accessiblity/pdf
    cd /var/www/sites/d8_webform/web/modules/sandbox/webform/reports/accessiblity/pdf
    wkhtmltopdf --dpi 384 ../html/example_accessibility_basic.html example_accessibility_basic.pdf
    wkhtmltopdf --dpi 384 ../html/example_accessibility_advanced.html example_accessibility_advanced.pdf
    wkhtmltopdf --dpi 384 ../html/example_accessibility_containers.html example_accessibility_containers.pdf
    wkhtmltopdf --dpi 384 ../html/example_accessibility_wizard.html example_accessibility_wizard.pdf
    wkhtmltopdf --dpi 384 ../html/example_accessibility_labels.html example_accessibility_labels.pdf


4. Run tests
------------

[SimpleTest](https://www.drupal.org/node/645286)

    # Run all tests
    cd /var/www/sites/d8_webform
    php core/scripts/run-tests.sh --suppress-deprecations --url http://localhost/wf --module webform --dburl mysql://drupal_d8_webform:drupal.@dm1n@localhost/drupal_d8_webform

    # Run single tests
    cd /var/www/sites/d8_webform
    php core/scripts/run-tests.sh --suppress-deprecations --url http://localhost/wf --verbose --class "Drupal\Tests\webform\Functional\WebformListBuilderTest"

[PHPUnit](https://www.drupal.org/node/2116263)

Notes
- Links to PHP Unit HTML responses are not being printed by PHPStorm

References
- [Issue #2870145: Set printerClass in phpunit.xml.dist](https://www.drupal.org/node/2870145)
- [Lesson 10.2 - Unit testing](https://docs.acquia.com/article/lesson-102-unit-testing)

    # Export database and base URL.
    export SIMPLETEST_DB=mysql://drupal_d8_webform:drupal.@dm1n@localhost/drupal_d8_webform;
    export SIMPLETEST_BASE_URL='http://localhost/wf';

    # Execute all Webform PHPUnit tests.
    cd /var/www/sites/d8_webform/web/core
    php ../../vendor/phpunit/phpunit/phpunit --printer="\Drupal\Tests\Listeners\HtmlOutputPrinter" --group webform

    # Execute individual PHPUnit tests.
    cd /var/www/sites/d8_webform/web/core

    # Functional test.
    php ../../vendor/phpunit/phpunit/phpunit --printer="\Drupal\Tests\Listeners\HtmlOutputPrinter" ../modules/sandbox/webform/tests/src/Functional/WebformExampleFunctionalTest.php

    # Kernal test.
    php ../../vendor/phpunit/phpunit/phpunit --printer="\Drupal\Tests\Listeners\HtmlOutputPrinter" ../modules/sandbox/webform/tests/src/Kernal/Utility/WebformDialogHelperTest.php

    # Unit test.
    php ../../vendor/phpunit/phpunit/phpunit --printer="\Drupal\Tests\Listeners\HtmlOutputPrinter" ../modules/sandbox/webform/tests/src/Unit/Utility/WebformYamlTest.php

    php ../../vendor/phpunit/phpunit/phpunit --printer="\Drupal\Tests\Listeners\HtmlOutputPrinter" ../modules/sandbox/webform/tests/src/Unit/Access/WebformAccessCheckTest


5. Generate release notes
-------------------------

[Git Release Notes for Drush](https://www.drupal.org/project/grn)

    drush release-notes --nouser 8.x-5.3-beta3 8.x-5.x


6. Tag and create a new release
-------------------------------

[Tag a release](https://www.drupal.org/node/1066342)

    git tag 8.x-5.0-VERSION
    git push --tags
    git push origin tag 8.x-5.0-VERSION

[Create new release](https://www.drupal.org/node/add/project-release/2640714)


7. Tag and create a hotfix release
----------------------------------

    # Creete hotfix branch
    git checkout 8.x-5.LATEST-VERSION
    git checkout -b 8.x-5.NEXT-VERSION-hotfix
    git push -u origin 8.x-5.NEXT-VERSION-hotfix

    # Apply and commit remote patch
    curl https://www.drupal.org/files/issues/[project_name]-[issue-description]-[issue-number]-00.patch | git apply -
    git commit -am 'Issue #[issue-number]: [issue-description]'
    git push

    # Tag hotfix release.
    git tag 8.x-5.NEXT-VERSION
    git push --tags
    git push origin tag 8.x-5.NEXT-VERSION

    # Merge hotfix release with HEAD.
    git checkout 8.x-5.x
    git merge 8.x-5.NEXT-VERSION-hotfix

    # Delete hotfix release.
    git branch -D 8.x-5.NEXT-VERSION-hotfix
    git push origin :8.x-5.NEXT-VERSION-hotfix
