Steps for testing Drush 8.x and 9.x commands
--------------------------------------------

# Drush 8.x and below

```bash
# Version.
drush --version

# Help.
drush help --filter=webform

# Submissions.
drush webform-generate contact
drush webform-export contact
drush webform-purge -y contact

# Option.
drush webform-generate --entity-type=node --entity-id={ENTER_NID} contact
drush webform-export --delimiter="\t" --header-format="key" contact

# Libraries.
drush webform-libraries-status
drush webform-libraries-remove
drush webform-libraries-download
drush webform-libraries-make
drush webform-libraries-composer

# Tidy.
drush webform-tidy

# Repair.
drush webform-repair -y

# Docs.
drush en -y readme
drush webform-docs

# Composer.
drush webform-composer-update

# Commands.
drush webform-generate-commands
```

# Drush 9.x and above

```bash
# Version.
drush --version

# Help.
drush list --filter=webform

# Submissions.
drush webform:generate contact
drush webform:export contact
drush webform:purge -y contact

# Options.
drush webform:generate --entity-type=node --entity-id={ENTER_NID} contact
drush webform:export --delimiter="\t" --header-format=key contact

# Libraries.
drush webform:libraries:status
drush webform:libraries:remove
drush webform:libraries:download
drush webform:libraries:make
drush webform:libraries:composer

# Tidy.
drush webform:tidy

# Repair.
drush webform:repair -y

# Docs.
drush en -y readme
drush webform:docs

# Composer.
drush webform:composer:update

# Commands.
drush webform:generate:commands
```
