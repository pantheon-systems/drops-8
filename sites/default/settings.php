<?php

/**
 * Override the SERVER_PORT value to insure that Symphony knows the right port
 * for sites running on Pantheon. 
 *
 * This is only a temporary override and will be able to removed in the future
 * after a custom header is added to the Pantheon NGINX configuration. 
 */
if (isset($_SERVER['PANTHEON_ENVIRONMENT'])) {
  $_SERVER['SERVER_PORT'] = (!isset($_SERVER['HTTP_X_SSL']) || $_SERVER['HTTP_X_SSL'] != 'ON') ? 80 : 443;
}
