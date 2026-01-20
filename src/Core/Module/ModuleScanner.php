<?php

declare(strict_types=1);

namespace Core\Module;

use ReflectionClass;

/**
 * Scans module Boot.php files for event listener declarations.
 *
 * Reads the static $listens property from Boot classes without
 * instantiating them, enabling lazy loading of modules.
 *
 * Usage:
 *     $scanner = new ModuleScanner();
 *     $mappings = $scanner->scan([app_path('Core'), app_path('Mod')]);
 *     // Returns: [EventClass => [ModuleClass => 'methodName']]
 */
class ModuleScanner
{
    /**
     * Namespace mappings for path resolution.
     *
     * Maps directory names to their PSR-4 namespaces.
     */
    protected array $namespaceMap = [];

    /**
     * Set custom namespace mappings.
     *
     * @param  array<string, string>  $map  Directory name => namespace prefix
     */
    public function setNamespaceMap(array $map): self
    {
        $this->namespaceMap = $map;

        return $this;
    }

    /**
     * Scan directories for Boot.php files with $listens declarations.
     *
     * @param  array<string>  $paths  Directories to scan
     * @return array<string, array<string, string>> Event => [Module => method] mappings
     */
    public function scan(array $paths): array
    {
        $mappings = [];

        foreach ($paths as $path) {
            if (! is_dir($path)) {
                continue;
            }

            foreach (glob("{$path}/*/Boot.php") as $file) {
                $class = $this->classFromFile($file, $path);

                if (! $class || ! class_exists($class)) {
                    continue;
                }

                $listens = $this->extractListens($class);

                foreach ($listens as $event => $method) {
                    $mappings[$event][$class] = $method;
                }
            }
        }

        return $mappings;
    }

    /**
     * Extract the $listens array from a class without instantiation.
     *
     * @return array<string, string> Event => method mappings
     */
    public function extractListens(string $class): array
    {
        try {
            $reflection = new ReflectionClass($class);

            if (! $reflection->hasProperty('listens')) {
                return [];
            }

            $prop = $reflection->getProperty('listens');

            if (! $prop->isStatic() || ! $prop->isPublic()) {
                return [];
            }

            $listens = $prop->getValue();

            if (! is_array($listens)) {
                return [];
            }

            return $listens;
        } catch (\ReflectionException) {
            return [];
        }
    }

    /**
     * Derive class name from file path.
     *
     * Converts: app/Mod/Commerce/Boot.php => Mod\Commerce\Boot
     * Converts: app/Core/Cdn/Boot.php => Core\Cdn\Boot
     */
    private function classFromFile(string $file, string $basePath): ?string
    {
        // Normalise paths
        $file = str_replace('\\', '/', realpath($file) ?: $file);
        $basePath = str_replace('\\', '/', realpath($basePath) ?: $basePath);

        // Get relative path from base
        if (! str_starts_with($file, $basePath)) {
            return null;
        }

        $relative = substr($file, strlen($basePath) + 1);

        // Remove .php extension
        $relative = preg_replace('/\.php$/', '', $relative);

        // Convert path separators to namespace separators
        $namespace = str_replace('/', '\\', $relative);

        // Check custom namespace map first
        $dirName = basename($basePath);
        if (isset($this->namespaceMap[$dirName])) {
            return $this->namespaceMap[$dirName].'\\'.$namespace;
        }

        // Default namespace detection based on common patterns
        if (str_contains($basePath, '/Core')) {
            return "Core\\{$namespace}";
        }

        if (str_contains($basePath, '/Mod')) {
            return "Mod\\{$namespace}";
        }

        if (str_contains($basePath, '/Website')) {
            return "Website\\{$namespace}";
        }

        if (str_contains($basePath, '/Plug')) {
            return "Plug\\{$namespace}";
        }

        // Fallback - use directory name as namespace
        return "{$dirName}\\{$namespace}";
    }
}
