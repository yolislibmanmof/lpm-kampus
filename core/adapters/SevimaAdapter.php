<?php
declare(strict_types=1);

final class SevimaAdapter extends ApiAdapter
{
    public function getSupportedSyncTypes(): array
    {
        return [
            'mahasiswa'  => 'Data Mahasiswa (Aktif, Lulus, DO)',
            'krs'        => 'Kartu Rencana Semester',
            'khs'        => 'Kartu Hasil Semester & IPK',
            'jadwal'     => 'Jadwal Kuliah',
            'nilai'      => 'Nilai per Mata Kuliah',
            'dosen'      => 'Data Dosen Pengampu',
            'kurikulum'  => 'Struktur Kurikulum',
        ];
    }

    public function testConnection(): array
    {
        try {
            $response = $this->httpGet($this->config['base_url'] . '/api/v1/health', [
                'X-Api-Key: ' . ($this->config['api_key'] ?? ''),
                'X-School-Code: ' . ($this->config['config_json']['school_code'] ?? '')
            ]);
            return $response['code'] === 200 
                ? ['success' => true, 'message' => 'Koneksi SEVIMA berhasil']
                : ['success' => false, 'message' => "HTTP {$response['code']}"];
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function sync(string $jenisSync, array $options = []): array
    {
        return match($jenisSync) {
            'mahasiswa' => $this->syncMahasiswa($options),
            'krs'       => $this->syncKrs($options),
            'khs'       => $this->syncKhs($options),
            'jadwal'    => $this->syncJadwal($options),
            'nilai'     => $this->syncNilai($options),
            'dosen'     => $this->syncDosen($options),
            'kurikulum' => $this->syncKurikulum($options),
            default     => throw new Exception("Jenis sync tidak didukung: $jenisSync")
        };
    }

    private function syncMahasiswa(array $options): array
    {
        $result = ['total' => 0, 'success' => 0, 'failed' => 0, 'skipped' => 0, 'errors' => []];
        $headers = [
            'X-Api-Key: ' . ($this->config['api_key'] ?? ''),
            'X-School-Code: ' . ($this->config['config_json']['school_code'] ?? '')
        ];

        try {
            $response = $this->httpGet($this->config['base_url'] . '/api/v1/mahasiswa', $headers);
            
            if ($response['code'] !== 200) {
                throw new Exception("API Error: HTTP {$response['code']}");
            }

            foreach ($response['body']['data'] ?? [] as $mhs) {
                $result['total']++;
                // Upsert ke tabel statistik_mahasiswa atau tabel baru
                // Implementasi detail sesuai struktur DB lokal
                $result['success']++;
            }
        } catch (Exception $e) {
            $result['failed']++;
            $result['errors'][] = $e->getMessage();
        }

        return $result;
    }

    private function syncKrs(array $options): array { return ['total' => 0, 'success' => 0, 'failed' => 0, 'skipped' => 0, 'errors' => []]; }
    private function syncKhs(array $options): array { return ['total' => 0, 'success' => 0, 'failed' => 0, 'skipped' => 0, 'errors' => []]; }
    private function syncJadwal(array $options): array { return ['total' => 0, 'success' => 0, 'failed' => 0, 'skipped' => 0, 'errors' => []]; }
    private function syncNilai(array $options): array { return ['total' => 0, 'success' => 0, 'failed' => 0, 'skipped' => 0, 'errors' => []]; }
    private function syncDosen(array $options): array { return ['total' => 0, 'success' => 0, 'failed' => 0, 'skipped' => 0, 'errors' => []]; }
    private function syncKurikulum(array $options): array { return ['total' => 0, 'success' => 0, 'failed' => 0, 'skipped' => 0, 'errors' => []]; }
}