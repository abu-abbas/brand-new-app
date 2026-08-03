<?php

namespace App\Contracts;

interface ScopedResource
{
    /**
     * Mengambil kode wilayah dari resource (misal: '31', '3101', atau v_kolok).
     */
    public function getResourceWilayah(): ?string;

    /**
     * Mengambil kode unit spesifik dari resource (misal: '000003890' atau v_kolok).
     */
    public function getResourceUnit(): ?string;

    /**
     * Mengambil level role/kasta dari resource (jika bernilai int, misal pada User).
     */
    public function getResourceLevel(): ?int;
}
