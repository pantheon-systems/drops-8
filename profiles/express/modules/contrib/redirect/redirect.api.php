<?php

/**
 * @file
 * Hooks provided by the Redirect module.
 */

/**
 * @defgroup redirect_api_hooks Redirect API Hooks
 * @{
 * During redirect operations (create, update, view, delete, etc.), there are
 * several sets of hooks that get invoked to allow modules to modify the
 * redirect operation:
 * - All-module hooks: Generic hooks for "redirect" operations. These are
 *   always invoked on all modules.
 * - Entity hooks: Generic hooks for "entity" operations. These are always
 *   invoked on all modules.
 *
 * Here is a list of the redirect and entity hooks that are invoked, and other
 * steps that take place during redirect operations:
 * - Creating a new redirect (calling redirect_save() on a new redirect):
 *   - hook_redirect_presave() (all)
 *   - Redirect written to the database
 *   - hook_redirect_insert() (all)
 *   - hook_entity_insert() (all)
 * - Updating an existing redirect (calling redirect_save() on an existing redirect):
 *   - hook_redirect_presave() (all)
 *   - Redirect written to the database
 *   - hook_redirect_update() (all)
 *   - hook_entity_update() (all)
 * - Loading a redirect (calling redirect_load(), redirect_load_multiple(), or
 *   entity_load() with $entity_type of 'redirect'):
 *   - Redirect information is read from database.
 *   - hook_entity_load() (all)
 *   - hook_redirect_load() (all)
 * - Deleting a redirect (calling redirect_delete() or redirect_delete_multiple()):
 *   - Redirect is loaded (see Loading section above)
 *   - Redirect information is deleted from database
 *   - hook_redirect_delete() (all)
 *   - hook_entity_delete() (all)
 * - Preparing a redirect for editing (note that if it's
 *   an existing redirect, it will already be loaded; see the Loading section
 *   above):
 *   - hook_redirect_prepare() (all)
 * - Validating a redirect during editing form submit (calling
 *   redirect_form_validate()):
 *   - hook_redirect_validate() (all)
 * @}
 */

/**
 * @addtogroup hooks
 * @{
 */

/**
 * Act on redirects being loaded from the database.
 *
 * This hook is invoked during redirect loading, which is handled by
 * entity_load(), via classes RedirectController and
 * DrupalDefaultEntityController. After the redirect information is read from
 * the database or the entity cache, hook_entity_load() is invoked on all
 * implementing modules, and then hook_redirect_load() is invoked on all
 * implementing modules.
 *
 * This hook should only be used to add information that is not in the redirect
 * table, not to replace information that is in that table (which could
 * interfere with the entity cache). For performance reasons, information for
 * all available redirects should be loaded in a single query where possible.
 *
 * The $types parameter allows for your module to have an early return (for
 * efficiency) if your module only supports certain redirect types.
 *
 * @param $redirects
 *   An array of the redirects being loaded, keyed by rid.
 * @param $types
 *   An array containing the types of the redirects.
 *
 * @ingroup redirect_api_hooks
 */
function hook_redirect_load(array &$redirects, $types) {

}

/**
 * Alter the list of redirects matching a certain source.
 *
 * @param $redirects
 *   An array of redirect objects.
 * @param $source
 *   The source request path.
 * @param $context
 *   An array with the following key/value pairs:
 *   - language: The language code of the source request.
 *   - query: An array of the source request query string.
 *
 * @see redirect_load_by_source()
 * @ingroup redirect_api_hooks
 */
function hook_redirect_load_by_source_alter(array &$redirects, $source, array $context) {
  foreach ($redirects as $rid => $redirect) {
    if ($redirect->source !== $source) {
      // If the redirects to do not exactly match $source (e.g. case
      // insensitive matches), then remove them from the results.
      unset($redirects[$rid]);
    }
  }
}

/**
 * Act on a redirect object about to be shown on the add/edit form.
 *
 * This hook is invoked from redirect_create().
 *
 * @param $redirect
 *   The redirect that is about to be shown on the add/edit form.
 *
 * @ingroup redirect_api_hooks
 */
function hook_redirect_prepare($redirect) {

}

/**
 * Act on a redirect being redirected.
 *
 * This hook is invoked from redirect_redirect() before the redirect callback
 * is invoked.
 *
 * @param $redirect
 *   The redirect that is being used for the redirect.
 *
 * @see redirect_redirect()
 * @see drupal_page_is_cacheable()
 * @ingroup redirect_api_hooks
 */
function hook_redirect_alter($redirect) {
}

/**
 * @} End of "addtogroup hooks".
 */
