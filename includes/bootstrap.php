<?php
/**
 * Shared bootstrap for page-based entrypoints.
 *
 * Keeps the existing URL structure stable while centralizing config,
 * security helpers, auth helpers, and secure session startup.
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/security.php';
require_once __DIR__ . '/auth.php';

init_secure_session();
