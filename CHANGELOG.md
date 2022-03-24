# Changelog

All notable changes specific to pantheon-upstreams/drupal-composer-managed are noted here.


## Pantheon Update #3 - 2022-03-23

### Changed

- Renamed repository from pantheon-upstreams/drupal-recommended to pantheon-upstreams/drupal-composer-managed
- Switched the default branch from 'master' to 'main'

### Added

- Created a Composer pre-update script to ensure that the version of the upstream-configuration path repository is always 'dev-main', regardless of what branch / multidev the "composer update" command was run on.
- Added a new command, "composer upstream-require", for adding dependencies to the upstream-configuration path repository in custom upstreams.


## Pantheon Update #2 - 2021-11-01

### Changed

- Renamed repository from pantheon-upstreams/drupal-project to pantheon-upstreams/drupal-recommended.
- Update .gitignore to make it easier to manage changes to the Drupal core scaffold files.
- Move most dependencies out of `upstream-configuration` to give more control to individual sites.
- Install contrib modules to `web/modules/contrib` rather than `web/modules/composer`.


## Pantheon Update #1 - 2021-02-03

### Added

- Add changelog to track Pantheon-specific changes to pantheon-upstreams/pantheon-project. (#10)
- Two repositories for release management. Pull requests accepted at https://github.com/pantheon-systems/drupal-project and releases located at https://github.com/pantheon-upstreams/drupal-project. (#4,#5)



### Changed

- Allow site-level customization of Drush version. (#5)
- Allow sites to downgrade to Drupal 8.8.  (#6)
- Use optimized autoloader in Test and Live, but not Dev or Multidev (#8)



### Removed

- Remove install check and MariaDB minimum version patches. (#3)

[The associated milestone in GitHub](https://github.com/pantheon-systems/drupal-project/milestone/1?closed=1) provides detailed information about all changes in this update.
