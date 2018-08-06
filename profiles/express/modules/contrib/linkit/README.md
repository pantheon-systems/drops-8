Linkit
===========
Linkit provides an **enriched linking experience for internal and external
linking** with editors by using an autocomplete field. Linkit has by default
support for nodes, users, taxonomy terms, files, comments and
**basic support for all types of entities** that defines a canonical link
template.


Installation
------------

* Normal module installation procedure. See
  https://www.drupal.org/documentation/install/modules-themes/modules-8


Configuration
------------

After the installation, you have to create a Linkit profile. The profile will
contain information about which plugins to use.
Profiles can be created at `/admin/config/content/linkit`

When you have created a profile, you need to enable the Linkit plugin on the
text format you want to use. Formats are found at
`admin/config/content/formats`.


Plugins examples
------------

There are plugin implementation examples in the linkit_test module bundled with
Linkit core.
