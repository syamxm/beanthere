<?php

// Prepended to every request by php/production.ini, so it runs before any
// session_start(). Local HTTP dev leaves SESSION_COOKIE_SECURE unset.
if (getenv('SESSION_COOKIE_SECURE') === '1') {
  ini_set('session.cookie_secure', '1');
}
