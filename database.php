<?php
/**
 * Backward-compatible database wrapper.
 *
 * New code should include config/database.php or includes/bootstrap.php.
 * This file intentionally delegates to the environment-aware config so
 * hardcoded credentials are no longer active in the application.
 */
require_once __DIR__ . '/config/database.php';
