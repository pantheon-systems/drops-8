ABOUT MENU BLOCK
----------------

Like Drupal core, the Menu Block module allows you to create blocks of menu
items. However, Menu Block's blocks are much more configurable than Drupal
core's.


ADDING MENU BLOCKS
------------------

To add new menu blocks:
 1. Install module.
 2. Go to /admin/structure/block.
 3. Click the "Place block" button in the desired region.
 4. Choose a block from the "Menus" category.
 5. In the form that appears, configure the options desired and then click the
    "Save block" button.


CONFIGURING MENU BLOCKS
-----------------------

When adding or configuring a menu block, several configuration options are
available:

Basic Options:

  Title
    The default block title will be the menu name.

  Display title
    Checkbox to have the block title visible or not. If unchecked, the block
    title will remain accessible, but hidden visually.

Menu levels:

  Initial menu level
    The menu will only be visible if the menu item for the current page is at or
    below the selected starting level. Select level 1 to always keep this menu
    visible.

  Maximum number of menu levels to display
    The maximum number of menu levels to show, starting from the initial menu
    level. For example: with an initial level 2 and a maximum number of 3, menu
    levels 2, 3 and 4 can be displayed.

Advanced options:

  Expand all menu links
    All menu links that have children will "Show as expanded".

  Fixed parent item
    Alter the options in “Menu levels” to be relative to the fixed parent item.
    The block will only contain children of the selected menu link.

HTML and style options:

  Theme hook suggestion
    A theme hook suggestion can be used to override the default HTML and CSS
    classes for menus found in menu.html.twig.
