<?php
declare(strict_types=1);

final class PddiktiAdapter extends ApiAdapter
{
    public function getSupportedSyncTypes(): array
    {
        return [
            'dosen'       => 'Data Dosen (NIDN, Jabatan)',
            'mahasiswa'   => 'Data Mahasiswa Aktif',
            'prodi'       => 'Data Program Studi',
            'akreditasi'  => 'Status Akreditasi',
        ];
    }

    public function testConnection(): array
    {
        try {
            $result = $this->httpGet($this->config['base_url'] . '/api/pt/profile', [
                'Authorization: Bearer ' . ($this->config['token'] ?? '')
            ]);
            
            if ($result['code'] === 200) {
                return ['success' => true, 'message' => 'Koneksi ke PDDIKTI berhasil', 'data' => $result['body']];
            }
            return ['success' => false, 'message' => "HTTP {$result['code']}"];
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function sync(string $jenisSync, array $options = []): array
    {
        return match($jenisSync) {
            'dosen'      => $this->syncDosen($options),
            'mahasiswa'  => $this->syncMahasiswa($options),
            'prodi'      => $this->syncProdi($options),
            'akreditasi' => $this->syncAkreditasi($options),
            default      => throw new Exception("Jenis sync tidak didukung: $jenisSync")
        };
    }

    private function syncDosen(array $options): array
    {
        $result = ['total' => 0, 'success' => 0, 'failed' => 0, 'skipped' => 0, 'errors' => [], 'detail' => []];
        
        // Ambil daftar prodi yang akan di-sync
        $prodiIds = $options['prodi_ids'] ?? [];
        $prodiFilter = !empty($prodiIds) ? " AND id_prodi IN (" . implode(',', array_map('intval', $prodiIds)) . ")" : "";
        $prodiList = $this->db->query("SELECT id_prodi, kode_prodi FROM prodi WHERE 1=1 $prodiFilter")->fetchAll();

        foreach ($prodiList as $prodi) {
            try {
                // Panggil API PDDIKTI untuk dosen per prodi
                $response = $this->httpGet(
                    $this->config['base_url'] . '/api/dosen/list?kode_prodi=' . urlencode($prodi['kode_prodi']),
                    ['Authorization: Bearer ' . ($this->config['token'] ?? '')]
                );

                if ($response['code'] !== 200 || empty($response['body']['data'])) {
                    $result['skipped']++;
                    continue;
                }

                foreach ($response['body']['data'] as $dosenRemote) {
                    $result['total']++;
                    
                    // Cek apakah sudah ada di lokal
                    $existing = $this->db->prepare("SELECT id_dosen FROM dosen WHERE nidn = :nidn");
                    $existing->execute([':nidn' => $dosenRemote['nidn'] ?? '']);
                    $local = $existing->fetch();

                    if ($local) {
                        // Update data
                        $update = $this->db->prepare("UPDATE dosen SET nama_dosen = :nama, jabatan_fungsional = :jabatan WHERE id_dosen = :id");
                        $update->execute([
                            ':nama' => $dosenRemote['nama_dosen'] ?? '',
                            ':jabatan' => $dosenRemote['id_sdm_jabatan_fungsional'] ?? null,
                            ':id' => $local['id_dosen']
                        ]);
                    } else {
                        // Insert baru
                        $insert = $this->db->prepare("INSERT INTO dosen (id_prodi, nidn, nama_dosen, jabatan_fungsional, status) 
                                                      VALUES (:prodi, :nidn, :nama, :jabatan, 'Aktif')");
                        $insert->execute([
                            ':prodi' => $prodi['id_prodi'],
                            ':nidn' => $dosenRemote['nidn'],
                            ':nama' => $dosenRemote['nama_dosen'],
                            ':jabatan' => $dosenRemote['id_sdm_jabatan_fungsional'] ?? null
                        ]);
                    }
                    $result['success']++;
                }
            } catch (Exception $e) {
                $result['failed']++;
                $result['errors'][] = "Prodi {$prodi['kode_prodi']}: " . $e->getMessage();
            }
        }

        return $result;
    }

    private function syncMahasiswa(array $options): array
    {
        // Implementasi serupa untuk mahasiswa
        return ['total' => 0, 'success' => 0, 'failed' => 0, 'skipped' => 0, 'errors' => [], 'detail' => []];
    }

    private function syncProdi(array $options): array
    {
        return ['total' => 0, 'success' => 0, 'failed' => 0, 'skipped' => 0, 'errors' => [], 'detail' => []];
    }

    private function syncAkreditasi(array $options): array
    {
        return ['total' => 0, 'success' => 0, 'failed' => 0, 'skipped' => 0, 'errors' => [], 'detail' => []];
    }
}