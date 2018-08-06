Development Notes
-----------------

Below are useful commands that make it a little easier for
me to maintain the Webform module.

### Patching

**[Create and manage patches](https://www.drupal.org/node/707484)**

```bash
# Create and checkout issue branch
git checkout -b [issue-number]-[issue-description]

# Push issue branch to D.O. (optional)
git push -u origin [issue-number]-[issue-description]

# Create patch by comparing (current) issue branch with 8.x-5.x branch 
git diff 8.x-5.x > [project_name]-[issue-description]-[issue-number]-[comment-number]-[drupal-version].patch
```

**Ignoring *.patch, *.diff, and .gitignore files**

```bash
cat >> .gitignore <<'EOF'
.gitignore
*.patch
*.diff
EOF
```
**[Apply patch](https://www.drupal.org/node/1399218)**

```bash
curl https://www.drupal.org/files/[patch-name].patch | git apply -
```

**[Revert patch](https://www.drupal.org/patch/reverse)**

```bash
curl https://www.drupal.org/files/[patch-name].patch | git apply -R -
```

### Branching

**Merge branch**

```bash
# Merge branch with all commits
git checkout 8.x-5.x
git merge [issue-number]-[issue-description]
git push

# Merge branch as a single new commit
git checkout 8.x-5.x
git merge --squash [issue-number]-[issue-description]
git commit -m 'Issue #[issue-number]: [issue-description]'
git push
```
**Exporting a branch**

```bash
# Create a zip archive for a branch
git archive --format zip --output webform-[issue-number]-[issue-description].zip [issue-number]-[issue-description]
```

**Reverting a branch**

```bash
# Remove anything staged but not committed:
git reset --hard

# Adding changes to the last commit
git commit --amendd ../

# Unstage a file about to be committed
git reset HEAD <file>...

# Revert (in SVN terms) an uncommitted file to the copy in your latest commit
git checkout -- filename
```

**Delete issue branch**

```bash
# Delete local issue branch.
git branch -d [issue-number]-[issue-description] 

# Delete remote issue branch.
git push origin :[issue-number]-[issue-description]
```

### [Interdiff](https://www.drupal.org/documentation/git/interdiff)

```bash
interdiff \
  [issue-number]-[old-comment-number].patch \
  [issue-number]-[new-comment-number].patch \
  > interdiff-[issue-number]-[old-comment-number]-[new-comment-number].txt
```

### Drush 

**Execute Webform update hook **

```bash
drush php-eval 'module_load_include('install', 'webform'); webform_update_N();';
```

**Reinstall Webform module.**

```bash
drush php-eval 'module_load_include('install', 'webform'); webform_uninstall();'; drush cron;
drush php-eval 'module_load_include('install', 'webform_node'); webform_node_uninstall();'; drush cron; 
drush webform-purge --all -y; drush pmu -y webform_test; drush pmu -y webform_devel; drush pmu -y webform_examples; drush pmu -y webform_templates; drush pmu -y webform_ui; drush pmu -y webform_node; drush pmu -y webform; 
drush en -y webform webform_ui webform_devel webform_examples webform_templates webform_node;

# Optional.
drush en -y webform_test;
drush en -y webform_test_third_party_settings;
drush en -y webform_test_translation;
drush pmu -y webform_test_third_party_settings webform_test_translation;
```

**Reinstall Webform Test module.**

```bash
drush webform-purge --all -y; drush pmu -y webform_test; drush en -y webform_test;
```

**Install extra modules.**

```bash
drush en -y webform captcha image_captcha honeypot validators;
```

**Create test roles and users.**

```bash
# developer
drush role-create developer
drush role-add-perm developer '
  view the administration theme,access toolbar,access administration pages,access content overview,administer blocks,administer nodes,
  access webform overview,administer webform,edit webform assets'
drush user-create developer --password="developer"
drush user-add-role developer developer

# admin
drush role-create admin
drush role-add-perm admin '
  view the administration theme,access toolbar,access administration pages,access content overview,
  access webform overview,
  administer webform submission'
drush user-create admin --password="admin"
drush user-add-role admin admin

# manager
drush role-create manager
drush role-add-perm manager '
  view the administration theme,access toolbar,access administration pages,access content overview,
  access webform overview'
drush user-create manager --password="manager"
drush user-add-role manager manager

# viewer
drush role-create viewer
drush role-add-perm viewer '
  view the administration theme,access toolbar,access administration pages,access content overview,
  access webform overview,
  view any webform submission'
drush user-create viewer --password="viewer"
drush user-add-role viewer viewer

# user
drush role-create user
drush user-create user --password="user"
drush user-add-role user user

# any
drush role-create any
drush user-create any --password="any"
drush role-add-perm any '
  view the administration theme,access toolbar,access administration pages,
  access webform overview,edit webform assets,
  create webform,edit any webform,delete any webform,
  view any webform submission, edit any webform submission, delete any webform submission,
  view webform submissions any node,edit webform submissions any node,delete webform submissions any node'
drush user-add-role any any

# own
drush role-create own
drush user-create own --password="own"
drush role-add-perm own '
  view the administration theme,,access toolbaraccess administration pages,
   access webform overview,edit webform assets,
  create webform,edit own webform,delete own webform,
  view own webform submission, edit own webform submission, delete own webform submission,
  view webform submissions own node,edit webform submissions own node,delete webform submissions own node'
drush user-add-role own own

# anonymous
drush role-add-perm anonymous '
  view own webform submission, edit own webform submission, delete own webform submission'
```

**Create test submissions for 'Contact' and 'Example: Elements' webform.**

```bash
drush webform-generate contact
drush webform-generate example_elements
```

**Test update hooks**

```bash
drush php-eval 'module_load_include('install', 'webform'); ($message = webform_update_8001()) ? drupal_set_message($message) : NULL;'
```

**Access developer information**

```bash
drush role-add-perm anonymous 'access devel information'
drush role-add-perm authenticated 'access devel information'
```

**Reinstall**

```bash 
drush -y site-install\
  --account-mail="example@example.com"\
  --account-name="webmaster"\
  --account-pass="drupal.admin"\
  --site-mail="example@example.com"\
  --site-name="Drupal 8 (Webform)";

# Enable core modules
drush -y pm-enable\
  book\
  simpletest\
  telephone\
  language\
  locale\
  content_translation\
  config_translation;

# Disable core modules
drush -y pm-uninstall\
  update;

# Enable contrib modules
drush -y pm-enable\
  devel\
  devel_generate\
  kint\
  webprofiler\
  webform\
  webform_devel\
  webform_examples\
  webform_node\
  webform_templates\
  webform_test\
  webform_test_translation;
```

### How to take a screencast

**Setup**

- Drupal
    - Install Drupal locally.
    - Remove all blocks in first sidebar.  
      http://localhost/d8_dev/admin/structure/block
- Desktop
    - Switch to laptop.
    - Turn 'Hiding on' in the Dock System Preferences.
    - Set screen display to 'Large Text'
- Chrome
    - Hide Bookmarks.
    - Hide Extra Icons.
    - Always Show Toolbar in Full Screen.
    - Delete all webform.* keys from local storage.

**Generate list of screencasts**

```php
$help = _webform_help();
print '<pre>';
foreach ($help as $name => $info) {
  print "webform-" . $name . PHP_EOL;
  print 'Webform Help: ' . $info['title'] . PHP_EOL;
  print PHP_EOL;
}
print '</pre>'; exit;
```

**Uploading**

- Title : Webform Help: {title} [v01]
- Tags: Drupal 8,Webform,Form Builder
- Privacy: Unlisted
