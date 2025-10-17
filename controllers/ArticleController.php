<?php

class ArticleController
{
    /**
     * Affiche la page d'accueil.
     * @return void
     */
    public function showHome(): void
    {
        $articleManager = new ArticleManager();
        $articles = $articleManager->getAllArticles();

        $view = new View("Accueil");
        $view->render("home", ['articles' => $articles]);
    }

    public function showArticle()
    {
        $id = (int) $_GET['id'];

        $articleManager = new ArticleManager();
        $article = $articleManager->getArticleById($id);

        // 🆕 Incrémenter le compteur de vues ici
        $articleManager->incrementViews($id);

        $commentManager = new CommentManager();
        $comments = $commentManager->getAllCommentsByArticleId($id);

        $view = new View("Détail de l'article");
        $view->render("detailArticle", [
            'article' => $article,
            'comments' => $comments
        ]);
    }

    /**
     * Affiche le formulaire d'ajout d'un article.
     * @return void
     */
    public function addArticle(): void
    {
        $view = new View("Ajouter un article");
        $view->render("addArticle");
    }

    /**
     * Affiche la page "à propos".
     * @return void
     */
    public function showApropos()
    {
        $view = new View("A propos");
        $view->render("apropos");
    }
}