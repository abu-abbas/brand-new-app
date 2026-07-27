<?php

namespace App\Core\ErrorDefinition\Exceptions;

use RuntimeException;
use Throwable;

final class DiscoveryException extends RuntimeException
{
  public static function composerNotFound(string $path, ?Throwable $previous = null): self
  {
    return new self("Root composer.json tidak ditemukan atau tidak valid di: {$path}", 0, $previous);
  }

  public static function unreadableAutoloadPath(string $path, ?Throwable $previous = null): self
  {
    return new self("Path autoload Composer tidak dapat dibaca: {$path}", 0, $previous);
  }

  public static function autoloadFailed(string $className, Throwable $previous): self
  {
    return new self("Composer autoloader gagal memuat candidate: {$className}", 0, $previous);
  }
}
