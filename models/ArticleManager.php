<?php

/**
 * Classe qui gère les articles.
 */
class ArticleManager extends AbstractEntityManager
{

    /**
     * Retourne les articles triés selon la clé demandée et l'ordre.
     *
     * @param string $sortKey  'title'|'views'|'comments'|'date'
     * @param string $order    'asc'|'desc'
     * @return Article[]       Liste d'objets Article hydratés
     */
    public function findAllSorted(string $sortKey = 'date', string $order = 'desc'): array
    {
        // 1) Whitelist (clé vue -> colonne SQL réelle)
        $SORT_MAP = [
            'title' => 'a.title',
            'views' => 'a.views',
            'comments' => 'comments',        // alias calculé par la sous-requête
            'date' => 'a.date_creation',
        ];

        $sortColumn = $SORT_MAP[$sortKey] ?? 'a.date_creation';
        $order = (strtolower($order) === 'asc') ? 'ASC' : 'DESC';

        // 2) SQL : compter les commentaires par article, puis LEFT JOIN
        $sql = "SELECT a.*, COALESCE(c.cnt, 0) AS countComments FROM article a LEFT JOIN (  SELECT id_article, COUNT(*) AS cnt FROM comment GROUP BY id_article) c ON c.id_article = a.id ORDER BY {$sortColumn} {$order}  ";

        $result = $this->db->query($sql);

        // 3) Hydrater les entités Article
        $articles = [];
        while ($row = $result->fetch(\PDO::FETCH_ASSOC)) {
            $article = new Article($row);

            // Alimente proprement les compteurs si les mutateurs existent
            if (method_exists($article, 'setCountViews')) {
                $article->setCountViews((int) ($row['views'] ?? 0));
            }
            if (method_exists($article, 'setCountComments')) {
                $article->setCountComments((int) ($row['comments'] ?? 0));
            }

            $articles[] = $article;
        }

        return $articles;
    }

    /**
     * Incrémente le compteur de vues d’un article.
     * @param int $articleId
     * @return void
     */
    public function incrementViews(int $articleId): void
    {
        $sql = "UPDATE article SET views = views + 1 WHERE id = :id";
        $this->db->query($sql, ['id' => $articleId]);
    }
    
    /**
     * Récupère tous les articles.
     * @return array<Article> : un tableau d'objets Article.
     */
    public function getAllArticles(): array
    {
        $sql = "SELECT * FROM article";
        $result = $this->db->query($sql);

        $articles = [];
        while ($row = $result->fetch()) {
            // Hydratation de base (titre, contenu, date, etc.)
            $article = new Article($row);

            // IMPORTANT : on alimente la propriété utilisée par la vue
            // (la vue appelle getCountViews(), donc on set explicitement depuis la colonne SQL 'views')
            if (method_exists($article, 'setCountViews')) {
                $article->setCountViews((int) ($row['views'] ?? 0));
            }

            $articles[] = $article;
        }

        return $articles;
    }

    /**
     * Récupère un article par son id.
     * @param int $id : l'id de l'article.
     * @return Article|null : un objet Article ou null si l'article n'existe pas.
     */
    public function getArticleById(int $id): ?Article
    {
        $sql = "SELECT * FROM article WHERE id = :id";
        $result = $this->db->query($sql, ['id' => $id]);
        $article = $result->fetch();
        if ($article) {
            return new Article($article);
        }
        return null;
    }

    /**
     * Ajoute ou modifie un article.
     * On sait si l'article est un nouvel article car son id sera -1.
     * @param Article $article : l'article à ajouter ou modifier.
     * @return void
     */
    public function addOrUpdateArticle(Article $article): void
    {
        if ($article->getId() == -1) {
            $this->addArticle($article);
        } else {
            $this->updateArticle($article);
        }
    }

    /**
     * Ajoute un article.
     * @param Article $article : l'article à ajouter.
     * @return void
     */
    public function addArticle(Article $article): void
    {
        $sql = "INSERT INTO article (id_user, title, content, date_creation) VALUES (:id_user, :title, :content, NOW())";
        $this->db->query($sql, [
            'id_user' => $article->getIdUser(),
            'title' => $article->getTitle(),
            'content' => $article->getContent()
        ]);
    }

    /**
     * Modifie un article.
     * @param Article $article : l'article à modifier.
     * @return void
     */
    public function updateArticle(Article $article): void
    {
        $sql = "UPDATE article SET title = :title, content = :content, date_update = NOW() WHERE id = :id";
        $this->db->query($sql, [
            'title' => $article->getTitle(),
            'content' => $article->getContent(),
            'id' => $article->getId()
        ]);
    }

    /**
     * Supprime un article.
     * @param int $id : l'id de l'article à supprimer.
     * @return void
     */
    public function deleteArticle(int $id): void
    {
        $sql = "DELETE FROM article WHERE id = :id";
        $this->db->query($sql, ['id' => $id]);
    }
}