<?php

namespace MakinaCorpus\Drupal\APubSub\Tests;

class DrupalHelper
{
    /**
     * Is a Drupal instance bootstrapped
     *
     * @var bool
     */
    static private $bootstrapped = false;

    /**
     * A database connection object from Drupal
     *
     * @var mixed
     */
    static private $databaseConnection;

    /**
     * Find if a Drupal instance is configured for testing and bootstrap it if
     * found.
     *
     * @return mixed The Drupal database connection object whose type depends
     *               on version major
     */
    static public function findDrupalDatabaseConnection()
    {
        if (self::$bootstrapped) {
            return self::$databaseConnection;
        } else {
            $variableName = 'DRUPAL_PATH';

            // Try to find out the right site root.
            $path = getenv($variableName);

            if ($path && is_dir($path) && file_exists($path . '/index.php')) {

                $bootstrapInc = $path . '/includes/bootstrap.inc';

                if (!is_file($bootstrapInc)) {

                    // It's configured, but wrongly configured, alert user
                    trigger_error(sprintf(
                        "Configured Drupal path is a not a Drupal installation" +
                        " or version mismatch: '%s' (version major: %d)",
                        $path, 7));

                    return null;
                }

                if (!$handle = fopen($bootstrapInc, 'r')) {
                    trigger_error(sprintf("Cannot open for reading: '%s'", $bootstrapInc));
                    return null;
                }

                $buffer = fread($handle, 512);
                fclose($handle);

                $matches = [];
                if (preg_match("/^\s*define\('VERSION', '([^']+)'/ims", $buffer, $matches)) {
                    list($parsedMajor) = explode('.', $matches[1]);
                }

                if (!isset($parsedMajor) || empty($parsedMajor)) {
                    trigger_error(sprintf("Could not parse core version in: '%s'", $bootstrapInc));
                    return null;
                }

                // We are OK to go
                define('DRUPAL_ROOT', $path);
                require_once $bootstrapInc;

                self::$bootstrapped = true;

                drupal_bootstrap(DRUPAL_BOOTSTRAP_DATABASE);
                return self::$databaseConnection = \Database::getConnection();
            }
        }

        return null;
    }
}
