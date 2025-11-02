<?php

/**
 * Cette classe sert à gérer les commentaires. 
 */
class CommentManager extends AbstractEntityManager
{
    /**
     * Récupère tous les commentaires d'un article.
     * @param int $idArticle : l'id de l'article.
     * @return array : un tableau d'objets Comment.
     */
    public function getAllCommentsByArticleId(int $idArticle) : array
    {
        $sql = "SELECT * FROM comment WHERE id_article = :idArticle";
        $result = $this->db->query($sql, ['idArticle' => $idArticle]);
        $comments = [];

        while ($comment = $result->fetch()) {
            $comments[] = new Comment($comment);
        }
        return $comments;
    }

    /**
     * Compte les commentaires d'un article.
     * @param int $articleId
     * @return int
     */
    public function countByArticleId(int $articleId): int
    {
        // version harmonisée avec le reste de la classe :
        $sql = 'SELECT COUNT(*) FROM comment WHERE id_article = :id';
        $result = $this->db->query($sql, ['id' => $articleId]);
        return (int) $result->fetchColumn();
    }

    /**
     * Récupère un commentaire par son id.
     * @param int $id : l'id du commentaire.
     * @return Comment|null : un objet Comment ou null si le commentaire n'existe pas.
     */
    public function getCommentById(int $id) : ?Comment
    {
        $sql = "SELECT * FROM comment WHERE id = :id";
        $result = $this->db->query($sql, ['id' => $id]);
        $comment = $result->fetch();
        if ($comment) {
            return new Comment($comment);
        }
        return null;
    }

    /**
     * Ajoute un commentaire.
     * @param Comment $comment : l'objet Comment à ajouter.
     * @return bool : true si l'ajout a réussi, false sinon.
     */
    public function addComment(Comment $comment) : bool
    {
        $sql = "INSERT INTO comment (pseudo, content, id_article, date_creation) VALUES (:pseudo, :content, :idArticle, NOW())";
        $result = $this->db->query($sql, [
            'pseudo' => $comment->getPseudo(),
            'content' => $comment->getContent(),
            'idArticle' => $comment->getIdArticle()
        ]);
        return $result->rowCount() > 0;
    }

    /**
     * Supprime un commentaire.
     * @param Comment $comment : l'objet Comment à supprimer.
     * @return bool : true si la suppression a réussi, false sinon.
     */
    public function deleteComment(Comment $comment) : bool
    {
        $sql = "DELETE FROM comment WHERE id = :id";
        $result = $this->db->query($sql, ['id' => $comment->getId()]);
        return $result->rowCount() > 0;
    }

    /**
     * Récupère tous les commentaires liés à un article.
     *
     * @param int $articleId ID de l'article.
     * @return Comment[] Tableau d'objets Comment (peut être vide).
     */
    public function findByArticleId(int $articleId): array
{
    $sql = "SELECT id, id_article, pseudo, content, date_creation
            FROM comment
            WHERE id_article = :id
            ORDER BY id ASC";

    $result = $this->db->query($sql, ['id' => $articleId]);

    $comments = [];
    while ($row = $result->fetch(\PDO::FETCH_ASSOC)) {
        $comment = new Comment();
        $comment->setId((int)$row['id']);
        $comment->setidArticle((int)$row['id_article']);
        $comment->setPseudo((string)($row['pseudo'] ?? ''));
        $comment->setContent((string)($row['content'] ?? ''));
        $comment->setDateCreation($row['date_creation'] ?? null);
        $comments[] = $comment;
    }
    return $comments;
    }
}
