<?php

namespace App\Services;

class EncryptionService
{
    protected $encrypter;

    public function __construct()
    {
        $this->encrypter = \Config\Services::encrypter();
    }

    public function encryptPII(string $data): string
    {
        return bin2hex($this->encrypter->encrypt($data));
    }

    public function decryptPII(string $data): string
    {
        return $this->encrypter->decrypt(hex2bin($data));
    }

    public function hashForSearch(string $data): string
    {
        return hash('sha256', $data . config('Encryption')->key);
    }
}
