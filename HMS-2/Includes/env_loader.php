<?php
/**
 * Yadav Living - Lightweight Zero-Dependency Environment Loader
 * 
 * Safely parses .env and .env.local files without external dependencies.
 * Designed for team clarity, zero setup friction, and security.
 * 
 * Precedence Order (highest to lowest):
 *  1. Existing System Environment Variables (Docker, Cloud Run, CLI export)
 *  2. .env.local (Local developer credentials override, strictly git-ignored)
 *  3. .env (Shared base environment configuration, if present)
 *  4. Application hardcoded fallback defaults
 */

if (!function_exists('hms_load_env_file')) {
    /**
     * Parses a key=value formatted environment file and loads it into PHP environment.
     *
     * @param string $filePath Absolute or relative path to .env file
     * @param bool $override Whether to overwrite variables already present in getenv()
     * @return bool True if file was found and processed, false otherwise
     */
    function hms_load_env_file($filePath, $override = false) {
        if (!file_exists($filePath) || !is_readable($filePath) || is_dir($filePath)) {
            return false;
        }

        $lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($lines === false) {
            return false;
        }

        foreach ($lines as $line) {
            $line = trim($line);
            
            // Skip empty lines and comment lines (# or ;)
            if ($line === '' || strpos($line, '#') === 0 || strpos($line, ';') === 0) {
                continue;
            }

            // Must contain an '=' separator
            $equalPos = strpos($line, '=');
            if ($equalPos === false) {
                continue;
            }

            $key = trim(substr($line, 0, $equalPos));
            $val = trim(substr($line, $equalPos + 1));

            // Clean inline comments if not inside quotes
            if (!empty($val) && ($val[0] !== '"' && $val[0] !== "'")) {
                $hashPos = strpos($val, '#');
                if ($hashPos !== false) {
                    $val = trim(substr($val, 0, $hashPos));
                }
            }

            // Strip enclosing double or single quotes
            $len = strlen($val);
            if ($len >= 2) {
                $first = $val[0];
                $last = $val[$len - 1];
                if (($first === '"' && $last === '"') || ($first === "'" && $last === "'")) {
                    $val = substr($val, 1, -1);
                }
            }

            // Normalize boolean and null string literals
            $lower = strtolower($val);
            if ($lower === 'true') {
                $val = 'true';
            } elseif ($lower === 'false') {
                $val = 'false';
            } elseif ($lower === 'null') {
                $val = '';
            }

            // Populate environment if key is valid
            if ($key !== '') {
                $alreadySet = (getenv($key) !== false || isset($_ENV[$key]) || isset($_SERVER[$key]));
                if ($override || !$alreadySet) {
                    putenv("{$key}={$val}");
                    $_ENV[$key] = $val;
                    $_SERVER[$key] = $val;
                }
            }
        }

        return true;
    }
}

if (!function_exists('hms_init_environment')) {
    /**
     * Initializes environment variables from .env and .env.local across workspace and HMS-2 root.
     */
    function hms_init_environment() {
        static $initialized = false;
        if ($initialized) {
            return;
        }
        $initialized = true;

        // Search directories: Root repository, HMS-2 application directory, and current directory
        $candidateDirs = array_unique([
            dirname(__DIR__, 2), // e.g. /workspace/Society-Management-System
            dirname(__DIR__),    // e.g. /workspace/Society-Management-System/HMS-2
            __DIR__,             // e.g. /workspace/Society-Management-System/HMS-2/Includes
            getcwd(),            // Current working directory of the request/CLI
        ]);

        // 1. First, load .env (base configuration) if present (no override)
        foreach ($candidateDirs as $dir) {
            if (!$dir || !is_dir($dir)) continue;
            $basePath = rtrim($dir, '/\\') . DIRECTORY_SEPARATOR . '.env';
            hms_load_env_file($basePath, false);
        }

        // 2. Second, load .env.local (developer personal overrides) with override = true
        foreach ($candidateDirs as $dir) {
            if (!$dir || !is_dir($dir)) continue;
            $localPath = rtrim($dir, '/\\') . DIRECTORY_SEPARATOR . '.env.local';
            hms_load_env_file($localPath, true);
        }
    }
}

if (!function_exists('hms_env')) {
    /**
     * Reads an environment variable with a safe default fallback.
     *
     * @param string $key Variable name
     * @param mixed $default Fallback value if variable is undefined
     * @return mixed
     */
    function hms_env($key, $default = null) {
        $val = getenv($key);
        if ($val !== false) {
            return $val;
        }
        if (isset($_ENV[$key])) {
            return $_ENV[$key];
        }
        if (isset($_SERVER[$key])) {
            return $_SERVER[$key];
        }
        return $default;
    }
}

// Bootstrap automatically upon include
hms_init_environment();
