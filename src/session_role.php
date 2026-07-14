<?php

// Users and admins share one PHP session cookie, so logging in as one role
// must drop everything the other role left behind.
const USER_SESSION_KEYS = ['current_user', 'theme', 'accent', 'message', 'success', 'old'];
const ADMIN_SESSION_KEYS = ['current_admin', 'message', 'success', 'old', 'status_updated', 'flash_success', 'flash_error'];

function clear_admin_session(): void
{
  foreach (ADMIN_SESSION_KEYS as $key) {
    unset($_SESSION[$key]);
  }
}

function clear_user_session(): void
{
  foreach (USER_SESSION_KEYS as $key) {
    unset($_SESSION[$key]);
  }
}
