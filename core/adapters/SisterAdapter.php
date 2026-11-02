<?php
declare(strict_types=1);

final class SisterAdapter extends ApiAdapter
{
    public function getSupportedSyncTypes(): array
    {
        return [
            'aktivitas_kinerja' => 'Aktivitas Kinerja Dosen (Kinerja Bulanan)',
            'pendidikan'        => 'Riwayat Pendidikan Dosen',
            'penelitian'        => 'Data Penelitian & Publikasi',
            'pengabdian'        => 'Data Pengabdian Masyarakat',
        ];
    }

    public function testConnection(): array
    {
        try {
            $response = $this->httpGet($this->config['base_url'] . '/v1/user/profile', [
                'X-Token: ' . ($this->config['api_key'] ?? '')
            ]);
            return $response['code'] === 200 
                ? ['success' => true, 'message' => 'Koneksi SISTER berhasil']
                : ['success' => false, 'message' => "HTTP {$response['code']}"];
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function sync(string $jenisSync, array $options = []): array
    {
        return match($jenisSync) {
            'aktivitas_kinerja' => $this->syncAktivitasKinerja($options),
            'pendidikan'        => $this->syncPendidikan($options),
            'penelitian'        => $this->syncPenelitian($options),
            'pengabdian'        => $this->syncPengabdian($options),
            default             => throw new Exception("Jenis sync tidak didukung: $jenisSync")
        };
    }

    private function syncAktivitasKinerja(array $options): array
    {
        // Implementasi sync aktivitas kinerja dari SISTER
        return ['total' => 0, 'success' => 0, 'failed' => 0, 'skipped' => 0, 'errors' => []];
    }

    private function syncPendidikan(array $options): array
    {
        return ['total' => 0, 'success' => 0, 'failed' => 0, 'skipped' => 0, 'errors' => []];
    }

    private function syncPenelitian(array $options): array
    {
        return ['total' => 0, 'success' => 0, 'failed' => 0, 'skipped' => 0, 'errors' => []];
    }

    private function syncPengabdian(array $options): array
    {
        return ['total' => 0, 'success' => 0, 'failed' => 0, 'skipped' => 0, 'errors' => []];
    }
}