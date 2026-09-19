<?php

// Only needed when this public/ directory itself is the live document root
// (i.e. your host won't let you point the domain's document root at a
// public/ subfolder). In that case, app/, database/ and config/ must be
// deployed to a sibling directory OUTSIDE the web root, so they can never
// be downloaded directly (e.g. https://yourdomain/config/config.php would
// otherwise leak your database password).
//
// Copy this file to app-root.php (git-ignored, same directory) and return
// the absolute path to that sibling directory. .cpanel.yml deploys app/,
// database/ and routes.php there.
//
// Not needed for local development, or for hosting where the document
// root is <repo>/public -- index.php falls back to the repo root
// automatically when app-root.php doesn't exist.

return '/home1/de2shrnx/hirehelper-app';
