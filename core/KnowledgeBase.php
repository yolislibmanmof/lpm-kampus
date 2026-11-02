<?php
declare(strict_types=1);

final class KnowledgeBase
{
    /**
     * Pencarian full-text
     */
    public static function search(string $keyword, ?string $modulFilter = null): array
    {
        $db = Database::getInstance();
        $sql = "SELECT id_kb, kategori, judul, slug, modul_terkait, views,
                       MATCH(judul, konten) AGAINST(:kw IN BOOLEAN MODE) AS relevansi
                FROM knowledge_base
                WHERE is_published = 1 
                  AND MATCH(judul, konten) AGAINST(:kw2 IN BOOLEAN MODE)";
        $params = [':kw' => "+{$keyword}*", ':kw2' => "+{$keyword}*"];
        
        if ($modulFilter) {
            $sql .= " AND modul_terkait = :modul";
            $params[':modul'] = $modulFilter;
        }
        
        $sql .= " ORDER BY relevansi DESC LIMIT 20";
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Ambil artikel berdasarkan slug
     */
    public static function getBySlug(string $slug): ?array
    {
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT kb.*, u.nama_lengkap AS author 
                              FROM knowledge_base kb 
                              LEFT JOIN users u ON kb.id_author = u.id_user 
                              WHERE kb.slug = :slug AND kb.is_published = 1");
        $stmt->execute([':slug' => $slug]);
        $article = $stmt->fetch();
        
        if ($article) {
            // Increment views
            $db->prepare("UPDATE knowledge_base SET views = views + 1 WHERE id_kb = :id")
               ->execute([':id' => $article['id_kb']]);
        }
        return $article ?: null;
    }

    /**
     * Artikel terkait berdasarkan modul atau kategori
     */
    public static function getRelated(int $idKb, int $limit = 5): array
    {
        $db = Database::getInstance();
        $current = $db->prepare("SELECT kategori, modul_terkait FROM knowledge_base WHERE id_kb = :id");
        $current->execute([':id' => $idKb]);
        $data = $current->fetch();
        if (!$data) return [];

        $sql = "SELECT id_kb, judul, slug FROM knowledge_base 
                WHERE id_kb != :id AND is_published = 1 
                AND (modul_terkait = :modul OR kategori = :kat) 
                ORDER BY views DESC LIMIT :lim";
        $stmt = $db->prepare($sql);
        $stmt->bindValue(':id', $idKb, PDO::PARAM_INT);
        $stmt->bindValue(':modul', $data['modul_terkait']);
        $stmt->bindValue(':kat', $data['kategori']);
        $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Statistik KB
     */
    public static function getStats(): array
    {
        $db = Database::getInstance();
        return [
            'total' => $db->query("SELECT COUNT(*) FROM knowledge_base WHERE is_published=1")->fetchColumn(),
            'total_views' => $db->query("SELECT COALESCE(SUM(views),0) FROM knowledge_base")->fetchColumn(),
            'per_kategori' => $db->query("SELECT kategori, COUNT(*) as jml FROM knowledge_base WHERE is_published=1 GROUP BY kategori")->fetchAll(),
        ];
    }
}