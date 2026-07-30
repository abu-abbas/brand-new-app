<?php

namespace App\Traits;

use App\Support\Obfuscator;
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
        $keyName = $field ?? $this->getRouteKeyName();

        // Jika route key name bukan primary key (misal v_alias atau slug)
        if ($keyName !== $this->getKeyName()) {
            return $this->where($keyName, $value)->first();
        }

        // Coba decode sebagai Hashid
        $decodedId = Obfuscator::decode((string) $value);
        if ($decodedId !== null) {
            $model = $this->where($this->getKeyName(), $decodedId)->first();
            if ($model) {
                return $model;
            }
        }

        // Fallback jika berupa numeric ID langsung
        if (is_numeric($value)) {
            return $this->where($this->getKeyName(), (int) $value)->first();
        }

        return null;
    }
}
