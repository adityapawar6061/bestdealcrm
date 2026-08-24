#!/usr/bin/env php
<?php
/**
 * Root index.php - routes everything to public/index.php
 * This file exists at the project root level.
 * When there's no .htaccess, this catches requests to /bestdealcrm/
 */

// Include the real front controller
require_once __DIR__ . '/public/index.php';
