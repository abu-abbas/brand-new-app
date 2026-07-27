<?php

namespace App\Support\Scramble;

use Dedoc\Scramble\Support\Generator\Components;
use Dedoc\Scramble\Support\Generator\OpenApi;
use Dedoc\Scramble\Support\Generator\Reference;
use Dedoc\Scramble\Support\Generator\Response;
use Dedoc\Scramble\Support\Generator\Schema;
use Dedoc\Scramble\Support\Generator\Types\ArrayType;
use Dedoc\Scramble\Support\Generator\Types\BooleanType;
use Dedoc\Scramble\Support\Generator\Types\IntegerType;
use Dedoc\Scramble\Support\Generator\Types\ObjectType;
use Dedoc\Scramble\Support\Generator\Types\StringType;

/**
 * Scramble afterOpenApiGenerated hook yang mengekstrak inline pagination
 * schema (meta & links) menjadi shared $ref di components/schemas.
 *
 * Tanpa extension ini, setiap endpoint paginated menghasilkan type duplikat
 * di OpenAPI spec, sehingga Orval men-generate file TypeScript terpisah
 * per endpoint untuk shape yang sebenarnya identik.
 */
class PaginationSchemaExtension
{
  private const SCHEMA_META = 'PaginationMeta';

  private const SCHEMA_LINKS = 'PaginationLinks';

  private const SCHEMA_META_LINKS_ITEM = 'PaginationMetaLinksItem';

  /**
   * Property yang wajib ada di `meta` agar terdeteksi sebagai Laravel paginator.
   */
  private const META_FINGERPRINT = [
    'current_page',
    'last_page',
    'per_page',
    'total',
  ];

  /**
   * Property yang wajib ada di `links` agar terdeteksi sebagai Laravel paginator.
   */
  private const LINKS_FINGERPRINT = [
    'first',
    'last',
    'prev',
    'next',
  ];

  public function handle(OpenApi $openApi): void
  {
    $this->registerSharedSchemas($openApi->components);
    $this->replaceInlinePaginationSchemas($openApi);
  }

  /**
   * Daftarkan PaginationMeta, PaginationLinks, dan PaginationMetaLinksItem
   * sebagai named component di components/schemas.
   */
  private function registerSharedSchemas(Components $components): void
  {
    // PaginationMetaLinksItem — { url: string|null, label: string, active: boolean }
    if (! $components->hasSchema(self::SCHEMA_META_LINKS_ITEM)) {
      $metaLinksItemType = (new ObjectType)
        ->addProperty('url', (new StringType)->nullable(true))
        ->addProperty('label', new StringType)
        ->addProperty('active', new BooleanType);
      $metaLinksItemType->setRequired(['url', 'label', 'active']);

      $components->addSchema(
        self::SCHEMA_META_LINKS_ITEM,
        Schema::fromType($metaLinksItemType),
      );
    }

    // PaginationMeta — standard Laravel LengthAwarePaginator meta
    if (! $components->hasSchema(self::SCHEMA_META)) {
      $metaLinksItemRef = new Reference('schemas', self::SCHEMA_META_LINKS_ITEM, $components);

      $metaType = (new ObjectType)
        ->addProperty('current_page', (new IntegerType)->setMin(1))
        ->addProperty('from', (new IntegerType)->nullable(true)->setMin(1))
        ->addProperty('last_page', (new IntegerType)->setMin(1))
        ->addProperty('links', (new ArrayType)->setItems($metaLinksItemRef)->setDescription('Generated paginator links.'))
        ->addProperty('path', (new StringType)->nullable(true)->setDescription('Base path for paginator generated URLs.'))
        ->addProperty('per_page', (new IntegerType)->setMin(0)->setDescription('Number of items shown per page.'))
        ->addProperty('to', (new IntegerType)->nullable(true)->setMin(1)->setDescription('Number of the last item in the slice.'))
        ->addProperty('total', (new IntegerType)->setMin(0)->setDescription('Total number of items being paginated.'));
      $metaType->setRequired(['current_page', 'from', 'last_page', 'links', 'path', 'per_page', 'to', 'total']);

      $components->addSchema(
        self::SCHEMA_META,
        Schema::fromType($metaType),
      );
    }

    // PaginationLinks — { first, last, prev, next } (semua string|null)
    if (! $components->hasSchema(self::SCHEMA_LINKS)) {
      $linksType = (new ObjectType)
        ->addProperty('first', (new StringType)->nullable(true))
        ->addProperty('last', (new StringType)->nullable(true))
        ->addProperty('prev', (new StringType)->nullable(true))
        ->addProperty('next', (new StringType)->nullable(true));
      $linksType->setRequired(['first', 'last', 'prev', 'next']);

      $components->addSchema(
        self::SCHEMA_LINKS,
        Schema::fromType($linksType),
      );
    }
  }

  /**
   * Walk semua path → operation → response 200 dan ganti inline
   * pagination meta/links dengan $ref ke shared schema.
   */
  private function replaceInlinePaginationSchemas(OpenApi $openApi): void
  {
    foreach ($openApi->paths as $path) {
      foreach ($path->operations as $operation) {
        if (! $operation->responses) {
          continue;
        }

        foreach ($operation->responses as $response) {
          if ($response instanceof Reference) {
            continue;
          }

          $this->processResponse($response, $openApi->components);
        }
      }
    }
  }

  /**
   * Cek apakah response ini paginated (punya data + links + meta),
   * lalu replace inline links/meta dengan $ref.
   */
  private function processResponse(Response $response, Components $components): void
  {
    if (! isset($response->content['application/json'])) {
      return;
    }

    $schema = $response->content['application/json'];

    // Schema bisa berupa Schema atau Reference
    if ($schema instanceof Reference) {
      return;
    }

    $type = $schema->type;

    if (! $type instanceof ObjectType) {
      return;
    }

    // Deteksi apakah ini paginated response: harus punya data, links, dan meta
    if (! $type->hasProperty('data') || ! $type->hasProperty('links') || ! $type->hasProperty('meta')) {
      return;
    }

    // Verifikasi meta punya fingerprint Laravel paginator
    $metaType = $type->getProperty('meta');
    if (! $metaType instanceof ObjectType || ! $this->hasFingerprint($metaType, self::META_FINGERPRINT)) {
      return;
    }

    // Verifikasi links punya fingerprint Laravel paginator
    $linksType = $type->getProperty('links');
    if (! $linksType instanceof ObjectType || ! $this->hasFingerprint($linksType, self::LINKS_FINGERPRINT)) {
      return;
    }

    // Replace inline meta → $ref PaginationMeta
    $type->addProperty(
      'meta',
      new Reference('schemas', self::SCHEMA_META, $components),
    );

    // Replace inline links → $ref PaginationLinks
    $type->addProperty(
      'links',
      new Reference('schemas', self::SCHEMA_LINKS, $components),
    );
  }

  /**
   * Cek apakah ObjectType memiliki semua property dari fingerprint.
   *
   * @param  string[]  $fingerprint
   */
  private function hasFingerprint(ObjectType $type, array $fingerprint): bool
  {
    foreach ($fingerprint as $property) {
      if (! $type->hasProperty($property)) {
        return false;
      }
    }

    return true;
  }
}
