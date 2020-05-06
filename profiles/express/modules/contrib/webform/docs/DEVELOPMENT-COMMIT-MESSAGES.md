Development Commit Messages
---------------------------

Webform uses Drupal's standard commit message format but also prepends the 
type and scope to each commit message to make it easier to find the type and 
scope  of a commit as well know if the commit contains a minor, major, 
or b/c breaking change.

```
[type]([scope]): #[issue number] by [comma-separated usernames]: [Short summary of the change]
```

**Type**

Can be one of the following:

- feat (feature)
- fix (bug fix)
- docs (documentation)
- style (formatting, missing semi colons, …)
- refactor
- test (when adding missing tests)
- chore (maintain)


**Scope**

The scope is the enitity or plugin that the commit affects. 

For Webform that can be the entity name  
(example: "webform", "webform_submission", "webform_option") 
or plugin name ("element", "handler", "exporter").

Scope can also be used to denote "MAJOR", "B/C BREAKING", 
"B/C BREAKING POSSIBLE" changes which will be uppercased.


**Example**

```
refactor(b/c breaking): Issue #3105878 by jrockowitz: Move all webform element properties to static definitions and allow them to be altered and cached
```


**References** 

- [[Experimental] Commit messages - providing history and credit](https://www.drupal.org/node/2825448)
- [[meta] Use the Git commit message format from AngularJS](https://www.drupal.org/project/drupal/issues/2802947)
