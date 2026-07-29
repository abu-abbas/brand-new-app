<?php

namespace App\Core\ErrorDefinition;

use App\Core\ErrorDefinition\Exceptions\DiscoveryException;
use App\Core\ErrorDefinition\Traits\HasErrorDefinitions;
use Illuminate\Foundation\Http\FormRequest;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ReflectionClass;
use Throwable;

class ErrorDefinitionDiscovery
{
    private ?DiscoveryResult $snapshot = null;

    private string $basePath;

    public function __construct(?string $basePath = null)
    {
        $this->basePath = rtrim($basePath ?? base_path(), '/\\');
    }

    public function discover(): DiscoveryResult
    {
        if ($this->snapshot !== null) {
            return $this->snapshot;
        }

        $composerPath = $this->basePath.'/composer.json';
        if (! file_exists($composerPath) || ! is_readable($composerPath)) {
            throw DiscoveryException::composerNotFound($composerPath);
        }

        $contents = file_get_contents($composerPath);
        if ($contents === false) {
            throw DiscoveryException::composerNotFound($composerPath);
        }

        $composerData = json_decode($contents, true);
        if (! is_array($composerData)) {
            throw DiscoveryException::composerNotFound($composerPath);
        }

        $autoload = $composerData['autoload'] ?? [];
        $candidates = [];

        // 1. Process PSR-4 mapping
        if (isset($autoload['psr-4']) && is_array($autoload['psr-4'])) {
            foreach ($autoload['psr-4'] as $prefix => $paths) {
                $pathList = is_array($paths) ? $paths : [$paths];
                foreach ($pathList as $relPath) {
                    $candidates = array_merge($candidates, $this->discoverPsr4Candidates($prefix, $relPath));
                }
            }
        }

        // 2. Process classmap mapping
        if (isset($autoload['classmap']) && is_array($autoload['classmap'])) {
            foreach ($autoload['classmap'] as $relPath) {
                $candidates = array_merge($candidates, $this->discoverClassmapCandidates($relPath));
            }
        }

        $errorEnums = [];
        $formRequests = [];

        $candidates = array_unique($candidates);

        foreach ($candidates as $fqcn) {
            try {
                if (! class_exists($fqcn) && ! enum_exists($fqcn)) {
                    continue;
                }

                $ref = new ReflectionClass($fqcn);
            } catch (Throwable $e) {
                throw DiscoveryException::autoloadFailed($fqcn, $e);
            }

            // Target Filter 1: Error Enum
            if ($ref->isEnum() && $ref->implementsInterface(ErrorCode::class)) {
                $errorEnums[] = $fqcn;
            }

            // Target Filter 2: FormRequest with HasErrorDefinitions trait
            if (! $ref->isAbstract() && $ref->isSubclassOf(FormRequest::class)) {
                $uses = class_uses_recursive($fqcn);
                if (in_array(HasErrorDefinitions::class, $uses, true)) {
                    $formRequests[] = $fqcn;
                }
            }
        }

        sort($errorEnums, SORT_STRING);
        sort($formRequests, SORT_STRING);

        $this->snapshot = new DiscoveryResult(
            errorEnums: array_values($errorEnums),
            formRequests: array_values($formRequests),
        );

        return $this->snapshot;
    }

    /**
     * Discover candidate FQCNs from PSR-4 mapping.
     *
     * @return list<string>
     */
    private function discoverPsr4Candidates(string $prefix, string $relativeDir): array
    {
        $absDir = $this->basePath.'/'.trim($relativeDir, '/\\');
        if (! is_dir($absDir) || ! is_readable($absDir)) {
            throw DiscoveryException::unreadableAutoloadPath($absDir);
        }

        $normalizedPrefix = rtrim($prefix, '\\').'\\';
        $candidates = [];

        $phpFiles = $this->scanPhpFiles($absDir);
        foreach ($phpFiles as $file) {
            $relFilePath = ltrim(substr($file, strlen($absDir)), '/\\');
            $classRelative = str_replace(['/', '\\'], '\\', substr($relFilePath, 0, -4));
            $candidates[] = $normalizedPrefix.$classRelative;
        }

        return $candidates;
    }

    /**
     * Discover candidate FQCNs from classmap mapping.
     *
     * @return list<string>
     */
    private function discoverClassmapCandidates(string $relativePath): array
    {
        $absPath = $this->basePath.'/'.trim($relativePath, '/\\');
        if (! file_exists($absPath) || ! is_readable($absPath)) {
            throw DiscoveryException::unreadableAutoloadPath($absPath);
        }

        $candidates = [];

        if (is_dir($absPath)) {
            $files = $this->scanPhpFiles($absPath);
            foreach ($files as $file) {
                $fqcn = $this->parseFqcnFromTokens($file);
                if ($fqcn !== null) {
                    $candidates[] = $fqcn;
                }
            }
        } elseif (str_ends_with($absPath, '.php')) {
            $fqcn = $this->parseFqcnFromTokens($absPath);
            if ($fqcn !== null) {
                $candidates[] = $fqcn;
            }
        }

        return $candidates;
    }

    /**
     * Scan directory recursively for .php files.
     *
     * @return list<string>
     */
    private function scanPhpFiles(string $dir): array
    {
        $files = [];
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS)
        );

        foreach ($iterator as $info) {
            if ($info->isFile() && $info->getExtension() === 'php') {
                $files[] = $info->getPathname();
            }
        }

        return $files;
    }

    /**
     * Extract FQCN from PHP tokens without requiring the file.
     */
    private function parseFqcnFromTokens(string $filePath): ?string
    {
        $contents = file_get_contents($filePath);
        if ($contents === false) {
            return null;
        }

        $tokens = token_get_all($contents);
        $namespace = '';
        $classOrEnum = null;
        $count = count($tokens);

        for ($i = 0; $i < $count; $i++) {
            if ($tokens[$i][0] === T_NAMESPACE) {
                $i++;
                while ($i < $count && (
                    $tokens[$i][0] === T_STRING ||
                    $tokens[$i][0] === T_NAME_QUALIFIED ||
                    $tokens[$i][0] === T_NS_SEPARATOR
                )) {
                    $namespace .= $tokens[$i][1];
                    $i++;
                }
            }

            if ($tokens[$i][0] === T_CLASS || $tokens[$i][0] === T_ENUM) {
                if ($i > 0 && is_array($tokens[$i - 1]) && $tokens[$i - 1][0] === T_DOUBLE_COLON) {
                    continue;
                }
                $i++;
                while ($i < $count && is_array($tokens[$i]) && $tokens[$i][0] === T_WHITESPACE) {
                    $i++;
                }
                if ($i < $count && is_array($tokens[$i]) && $tokens[$i][0] === T_STRING) {
                    $classOrEnum = $tokens[$i][1];
                    break;
                }
            }
        }

        if ($classOrEnum === null) {
            return null;
        }

        return $namespace !== '' ? $namespace.'\\'.$classOrEnum : $classOrEnum;
    }
}
