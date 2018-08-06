# Updating Express Drops 8
Add the remote repo via the following command:

`git remote add upstream https://github.com/pantheon-systems/drops-8.git`

You can check to see if the remote repo was added using `git remote -v`

Then retrieve the branches from drops-8 in order to rebase:

`git fetch --all`

`git rebase upstream/master -Xtheirs`

Correct conflicts/etc. and then push up normally from there.

## Profile inheritance patch
As of 8.5.6 we're using: https://www.drupal.org/files/issues/drupal-n1356276-417-d8.5.%2A.patch

Check the issue queue for updates: https://www.drupal.org/project/drupal/issues/1356276

