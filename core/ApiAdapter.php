<?php
declare(strict_types=1);

abstract class ApiAdapter
{
    protected array $config = [];
    protected ?PDO $db = null;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function setConfig(array $config): void
    {
        $this->config = $config;
    }

    /**
     * Test koneksi ke API
     */
    abstract public function testConnection(): array;

    /**
     * Jalankan sinkronisasi
     */
    abstract public function sync(string $jenisSync, array $options = []): array;

    /**
     * Ambil daftar jenis sync yang didukung
     */
    abstract public function getSupportedSyncTypes(): array;

    /**
     * HTTP Request helper
     */
    protected function httpGet(string $url, array $headers = []): array
    {
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTPHEADER => array_merge([
                'Accept: application/json',
                'User-Agent: SIM-Mutu-LPM/4.0'
            ], $headers),
            CURLOPT_SSL_VERIFYPEER => true,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            throw new Exception("cURL Error: $error");
        }

        return [
            'code' => $httpCode,
            'body' => json_decode($response, true) ?? [],
            'raw' => $response
        ];
    }

    protected function httpPost(string $url, array $data = [], array $headers = []): array
    {
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTPHEADER => array_merge([
                'Content-Type: application/json',
                'Accept: application/json'
            ], $headers),
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return [
            'code' => $httpCode,
            'body' => json_decode($response, true) ?? []
        ];
    }
}