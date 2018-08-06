History:
  Field_group was originally written when drupal 7 was released. For drupal 6, the module is
  located in the CCK module (http://drupal.org/project/cck).
  As drupal core has a fields API drupal > 6, the field_group module
  is considered a contribution.

Description:
  field_group is a module that will group a set of fields. In Drupal8,
  with fields, one means all fields that come from fieldable entities.
  You can add fieldgroups in several types with their own format settings.
  field_group uses plugins to add your own formatter and rendering for
  it.
  One of the biggest improvements to previous versions, is that fieldgroups
  have unlimited nesting, better display control.
  Note that field_group will only group fields, it can not be used to hide
  certain fields since this a permission matter.

Module project page:
  http://drupal.org/project/field_group

Documentation page (D7 version):
  http://drupal.org/node/1017838
  http://drupal.org/node/1017962

Available group types:
  - Html element
  - Fieldsets
  - Tabs (horizontal and vertical)
  - Accordions
  - Details (Use this if you want collapsible fieldsets)

To submit bug reports and feature suggestions, or to track changes:
  http://drupal.org/project/issues/field_group

-- Configuration --
1. You can configure the field groups for different displays like, in managed_form_display and managed_display of the entity type.
2. You can create different field groups under managed_form_display by adding a new group under "Add new group" label and the format the grouping using the desired formatter for displaying the same.
3. Same thing can be done in managed_display.
4. The field grouping done in managed display will be reflected on the view detail page of the entity, while that done in the managed_form_display will be
reflected in the add/edit form of the entity.

-- MAINTAINERS --

stalski - http://drupal.org/user/322618
zuuperman - http://drupal.org/user/361625
swentel - http://drupal.org/user/107403

-- INSPIRATORS --

yched - http://drupal.org/user/39567