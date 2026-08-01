<?php

namespace App\Traits;

use App\Support\Obfuscator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

trait HasObfuscatedId
{
    /**
     * Accessor untuk $model->hash_id
     */
    public function getHashIdAttribute(): string
    {
        return Obfuscator::encode($this->getKey());
    }

    /**
     * Parameter default untuk Route Model Binding.
     */
    public function getRouteKey(): string
    {
        return $this->hash_id;
    }

    /**
     * Resolve model dari URL parameter (Support Hashid atau integer ID asli).
     *
     * @param  mixed  $value
     * @param  string|null  $field
     * @return Model|null
     */
    public function resolveRouteBinding($value, $field = null)
    {
        return $this->resolveRouteBindingQuery($this, $value, $field)->first();
    }

    /**
     * Resolve soft-deleted model dari URL parameter jika route menggunakan withTrashed().
     *
     * @param  mixed  $value
     * @param  string|null  $field
     * @return Model|null
     */
    public function resolveSoftDeletableRouteBinding($value, $field = null)
    {
        return $this->resolveRouteBindingQuery($this, $value, $field)->withTrashed()->first();
    }

    /**
     * Build query untuk Route Model Binding.
     *
     * @param  Builder  $query
     * @param  mixed  $value
     * @param  string|null  $field
     * @return Builder
     */
    public function resolveRouteBindingQuery($query, $value, $field = null)
    {
        $keyName = $field ?? $this->getRouteKeyName();

        if ($keyName !== $this->getKeyName()) {
            return $query->where($keyName, $value);
        }

        $decodedId = Obfuscator::decode((string) $value);
        if ($decodedId !== null) {
            return $query->where($this->getKeyName(), $decodedId);
        }

        if (is_numeric($value)) {
            return $query->where($this->getKeyName(), (int) $value);
        }

        return $query->whereRaw('1 = 0');
    }
}
