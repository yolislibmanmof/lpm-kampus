<?php
declare(strict_types=1);

final class SentimentAnalyzer
{
    private static ?PDO $db = null;
    private static array $kamus = [];
    
    // Stopwords Indonesia
    private const STOPWORDS = [
        'yang', 'di', 'ke', 'dari', 'dan', 'atau', 'dengan', 'untuk', 'pada',
        'adalah', 'ini', 'itu', 'ada', 'tidak', 'juga', 'sudah', 'akan', 'bisa',
        'oleh', 'karena', 'agar', 'sebagai', 'oleh', 'kepada', 'saya', 'kami',
        'mereka', 'dia', 'kita', 'anda', 'sangat', 'sekali', 'paling', 'agak'
    ];

    private static function db(): PDO
    {
        if (self::$db === null) self::$db = Database::getInstance();
        return self::$db;
    }

    private static function loadKamus(): void
    {
        if (!empty(self::$kamus)) return;
        try {
            $q = self::db()->query("SELECT kata, bobot, kategori FROM sentimen_kamus WHERE bobot != 0");
            foreach ($q->fetchAll() as $r) {
                self::$kamus[strtolower($r['kata'])] = [
                    'bobot' => (float)$r['bobot'],
                    'kategori' => $r['kategori']
                ];
            }
        } catch (Throwable $e) {
            error_log('SentimentAnalyzer::loadKamus: ' . $e->getMessage());
        }
    }

    /**
     * Tokenisasi teks: lowercase, hapus tanda baca, pisah kata
     */
    public static function tokenize(string $text): array
    {
        $text = mb_strtolower($text, 'UTF-8');
        $text = preg_replace('/[^\p{L}\p{N}\s]/u', ' ', $text);
        $words = preg_split('/\s+/', $text, -1, PREG_SPLIT_NO_EMPTY);
        return array_values(array_diff($words, self::STOPWORDS));
    }

    /**
     * Analisis sentimen, return skor [-1, 1] + detail
     */
    public static function analyze(string $text): array
    {
        if (trim($text) === '') {
            return ['skor' => 0.0, 'kategori' => 'Netral', 'kata_kunci' => []];
        }

        self::loadKamus();
        $tokens = self::tokenize($text);
        
        $totalBobot = 0.0;
        $countHit = 0;
        $kataKunci = [];
        $kategoriHit = ['umum' => 0, 'pendidikan' => 0, 'perilaku' => 0];
        
        // Cek bigram (2 kata) dan unigram
        $n = count($tokens);
        for ($i = 0; $i < $n; $i++) {
            // Cek bigram dulu
            if ($i < $n - 1) {
                $bigram = $tokens[$i] . ' ' . $tokens[$i + 1];
                if (isset(self::$kamus[$bigram])) {
                    $totalBobot += self::$kamus[$bigram]['bobot'] * 1.3; // Bigram lebih kuat
                    $countHit++;
                    $kataKunci[] = ['kata' => $bigram, 'bobot' => self::$kamus[$bigram]['bobot']];
                    $kategoriHit[self::$kamus[$bigram]['kategori']] += self::$kamus[$bigram]['bobot'];
                    $i++; // Skip next
                    continue;
                }
            }
            // Cek unigram
            if (isset(self::$kamus[$tokens[$i]])) {
                $kata = $tokens[$i];
                $bobot = self::$kamus[$kata]['bobot'];
                $totalBobot += $bobot;
                $countHit++;
                $kataKunci[] = ['kata' => $kata, 'bobot' => $bobot];
                $kategoriHit[self::$kamus[$kata]['kategori']] += $bobot;
            }
        }

        if ($countHit === 0) {
            return ['skor' => 0.0, 'kategori' => 'Netral', 'kata_kunci' => [], 'kategori_hit' => $kategoriHit];
        }

        $skor = max(-1.0, min(1.0, $totalBobot / $countHit));
        
        if ($skor > 0.2) $kategori = 'Positif';
        elseif ($skor < -0.2) $kategori = 'Negatif';
        else $kategori = 'Netral';

        // Tentukan aspek terkuat/terlemah dari kategori hit
        arsort($kategoriHit);
        $keys = array_keys($kategoriHit);
        $aspekTerkuat = $keys[0] ?? null;
        $aspekTerlemah = end($keys);

        return [
            'skor' => round($skor, 2),
            'kategori' => $kategori,
            'kata_kunci' => $kataKunci,
            'kategori_hit' => $kategoriHit,
            'aspek_terkuat' => $aspekTerkuat !== false ? $aspekTerkuat : null,
            'aspek_terlemah' => $aspekTerlemah !== false ? $aspekTerlemah : null
        ];
    }

    /**
     * Simpan hasil analisis ke DB
     */
    public static function analyzeAndSave(int $idJawaban, int $idKelas, string $komentar): array
    {
        $result = self::analyze($komentar);
        
        try {
            $db = self::db();
            $db->prepare("INSERT INTO edom_sentimen 
                (id_kelas, id_jawaban, komentar, skor_sentimen, kategori, kata_kunci, aspek_terkuat, aspek_terlemah)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE 
                    skor_sentimen = VALUES(skor_sentimen),
                    kategori = VALUES(kategori),
                    kata_kunci = VALUES(kata_kunci),
                    aspek_terkuat = VALUES(aspek_terkuat),
                    aspek_terlemah = VALUES(aspek_terlemah)")
               ->execute([
                   $idKelas,
                   $idJawaban,
                   $komentar,
                   $result['skor'],
                   $result['kategori'],
                   json_encode($result['kata_kunci'], JSON_UNESCAPED_UNICODE),
                   $result['aspek_terkuat'],
                   $result['aspek_terlemah']
               ]);
        } catch (Throwable $e) {
            error_log('SentimentAnalyzer::save: ' . $e->getMessage());
        }
        
        return $result;
    }

    /**
     * Batch analyze semua komentar yang belum dianalisis
     */
    public static function batchAnalyze(int $limit = 100): int
    {
        try {
            $db = self::db();
            $q = $db->prepare("SELECT j.id_jawaban, j.id_kelas, j.komentar
                              FROM edom_jawaban j
                              LEFT JOIN edom_sentimen s ON j.id_jawaban = s.id_jawaban
                              WHERE s.id_sentimen IS NULL AND j.komentar IS NOT NULL AND j.komentar != ''
                              LIMIT ?");
            $q->execute([$limit]);
            $rows = $q->fetchAll();
            
            $count = 0;
            foreach ($rows as $r) {
                self::analyzeAndSave((int)$r['id_jawaban'], (int)$r['id_kelas'], (string)$r['komentar']);
                $count++;
            }
            return $count;
        } catch (Throwable $e) {
            error_log('SentimentAnalyzer::batch: ' . $e->getMessage());
            return 0;
        }
    }

    /**
     * Statistik agregat per kelas
     */
    public static function getClassStats(int $idKelas): array
    {
        try {
            $db = self::db();
            $q = $db->prepare("SELECT kategori, COUNT(*) cnt, AVG(skor_sentimen) avg
                              FROM edom_sentimen WHERE id_kelas = ? GROUP BY kategori");
            $q->execute([$idKelas]);
            $rows = $q->fetchAll();
            
            $stats = ['Positif' => 0, 'Netral' => 0, 'Negatif' => 0, 'avg_skor' => 0, 'total' => 0];
            foreach ($rows as $r) {
                $stats[$r['kategori']] = (int)$r['cnt'];
                $stats['total'] += (int)$r['cnt'];
            }
            
            $avgQ = $db->prepare("SELECT AVG(skor_sentimen) FROM edom_sentimen WHERE id_kelas = ?");
            $avgQ->execute([$idKelas]);
            $stats['avg_skor'] = round((float)$avgQ->fetchColumn(), 2);
            
            return $stats;
        } catch (Throwable $e) {
            return ['Positif' => 0, 'Netral' => 0, 'Negatif' => 0, 'avg_skor' => 0, 'total' => 0];
        }
    }

    /**
     * Word cloud data (top N kata)
     */
    public static function getWordCloud(int $idKelas, int $limit = 30): array
    {
        try {
            $db = self::db();
            $q = $db->prepare("SELECT kata_kunci FROM edom_sentimen WHERE id_kelas = ? AND kata_kunci IS NOT NULL");
            $q->execute([$idKelas]);
            $rows = $q->fetchAll(PDO::FETCH_COLUMN);
            
            $wordCount = [];
            foreach ($rows as $json) {
                $kata = json_decode($json, true);
                if (!is_array($kata)) continue;
                foreach ($kata as $k) {
                    $w = $k['kata'] ?? '';
                    $bobot = abs((float)($k['bobot'] ?? 0));
                    if ($w !== '') {
                        if (!isset($wordCount[$w])) $wordCount[$w] = ['count' => 0, 'weight' => 0];
                        $wordCount[$w]['count']++;
                        $wordCount[$w]['weight'] = max($wordCount[$w]['weight'], $bobot);
                    }
                }
            }
            
            arsort($wordCount);
            $wordCount = array_slice($wordCount, 0, $limit, true);
            
            $result = [];
            foreach ($wordCount as $word => $data) {
                $result[] = [
                    'text' => $word,
                    'size' => min(48, max(12, 12 + ($data['count'] * 4))),
                    'weight' => $data['weight']
                ];
            }
            return $result;
        } catch (Throwable $e) {
            return [];
        }
    }
}