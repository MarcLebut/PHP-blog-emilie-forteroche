<?php
/**
 * Contrôleur de la partie admin.
 */

class AdminController
{

    public function index(): void
    {
        // ⚠️ Si ton manager nécessite PDO, passe-le ici (ex: $pdo injecté ailleurs)
        $articleManager = new ArticleManager();

        // 1) Colonnes triables : clé (vue) -> colonne SQL (modèle)
        $COLS = [
            'title' => ['label' => 'Titre', 'sql' => 'title'],
            'views' => ['label' => 'Vues', 'sql' => 'views'],
            'comments' => ['label' => 'Commentaires', 'sql' => 'comments'],
            'date' => ['label' => 'Publication', 'sql' => 'date_creation'], // <-- important
        ];

        // 2) Lecture + sécurisation
        $sortKey = $_GET['sort'] ?? 'date';
        $order = $_GET['order'] ?? 'desc';
        $sortKey = array_key_exists($sortKey, $COLS) ? $sortKey : 'date';
        $order = strtolower($order) === 'asc' ? 'asc' : 'desc';

        // 3) Données triées
        $articles = $articleManager->findAllSorted($sortKey, $order);

        // 4) En-têtes (toggle + flèche)
        $mkUrl = static function (string $k, string $o): string {
            return 'index.php?action=adminDashboard&sort=' . urlencode($k) . '&order=' . urlencode($o);
        };

        $headers = [];
        foreach ($COLS as $key => $meta) {
            $isCurrent = ($key === $sortKey);
            $nextOrder = ($isCurrent && $order === 'asc') ? 'desc' : 'asc';
            $arrow = $isCurrent ? ($order === 'asc' ? ' ▲' : ' ▼') : '';
            $headers[] = [
                'label' => $meta['label'] . $arrow,
                'href' => $mkUrl($key, $nextOrder),
            ];
        }

        // 5) Vue
        $view = new View("Tableau de bord");
        $view->render("dashboard", [
            'articles' => $articles,
            'headers' => $headers,   // <-- passe les entêtes à la vue
        ]);
    }


    /**
     * Affiche la page de gestion des articles.
     * @return void
     */
    public function showAdmin(): void
    {
        // On vérifie que l'utilisateur est connecté.
        $this->checkIfUserIsConnected();

        // On récupère les articles.
        $articleManager = new ArticleManager();
        $articles = $articleManager->getAllArticles();

        // On affiche la page d'administration.
        $view = new View("Administration");
        $view->render("admin", [
            'articles' => $articles
        ]);
    }

    /**
     * Affiche la page d'administration (tableau de bord).
     * @return void
     */

    /*public function showDashboard(): void
    {
        // 1) Sécurité : on s'assure que l'utilisateur est connecté
        $this->checkIfUserIsConnected();

        // 2) Récupération des articles (objets Article)
        $articleManager = new ArticleManager();
        $commentManager = new CommentManager();
        $articles = $articleManager->getAllArticles();

        $commentsByArticle = [];


        // 3) Hydratation du nombre de commentaires pour chaque article
        //(POO : aucune logique dans la vue)
        foreach ($articles as $article) {
            // Article a bien un getId()
            $count = $commentManager->countByArticleId($article->getId());

            $article->setCountComments($count);

        }

        // 4) Rendu de la vue (affichage pur)
        $view = new View("Tableau de bord");
        $view->render("dashboard", [
            'articles' => $articles
        ]);
    }*/
    public function showDashboard(): void
    {
        // 1) Sécurité : on s'assure que l'utilisateur est connecté
        $this->checkIfUserIsConnected();

        // 2) Managers
        $articleManager = new ArticleManager();
        $commentManager = new CommentManager();

        // 3) Récupération des articles (objets Article)
        $articles = $articleManager->getAllArticles();

        // Option de secours si l'entité Article ne possède pas setComments()
        $commentsByArticle = [];

        // 4) Hydratation : nb de commentaires + liste complète
        foreach ($articles as $article) {
            $articleId = (int) $article->getId();

            // a) Nombre de commentaires
            $count = $commentManager->countByArticleId($articleId);
            $article->setCountComments($count);

            // b) Liste complète des commentaires de l'article
            $comments = $commentManager->findByArticleId($articleId);

            // Si votre entité Article a un mutateur setComments(), on l'utilise :
            if (method_exists($article, 'setComments')) {
                $article->setComments($comments);
            } else {
                // Sinon on prépare une structure à passer à la vue
                $commentsByArticle[$articleId] = $comments;
            }
        }

        // 5) Rendu de la vue (affichage pur)
        $view = new View("Tableau de bord");
        $view->render("dashboard", [
            'articles' => $articles,
            // Utile seulement si Article n'a pas setComments()
            'commentsByArticle' => $commentsByArticle
        ]);
    }
    public function showComments(): void
    {
        // 1) Sécurité
        $this->checkIfUserIsConnected();

        // 2) Récupération de l'id article depuis la requête
        $articleId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
        if ($articleId <= 0) {
            // Id manquant ou invalide -> redirection ou message propre
            // À adapter selon ton routeur/flash
            throw new \InvalidArgumentException("Identifiant d'article invalide.");
        }

        // 3) Managers
        $articleManager = new ArticleManager();
        $commentManager = new CommentManager();

        // 4) Charger l'article demandé
        $article = $articleManager->getArticleById($articleId);
        if (!$article) {
            throw new \RuntimeException("Article introuvable (id: {$articleId}).");
        }

        // 5) Charger uniquement les commentaires de CET article
        $comments = $commentManager->findByArticleId($articleId);

        // (Optionnel) hydrater dans l'entité si tu as setComments()
        if (method_exists($article, 'setComments')) {
            $article->setComments($comments);
        }
        // (Optionnel) hydrater le count si tu l’affiches dans la vue
        if (method_exists($article, 'setCountComments')) {
            $article->setCountComments($commentManager->countByArticleId($articleId));
        }

        // 6) Rendu : on passe l'article ciblé et ses commentaires
        $view = new View("Commentaires de l'article");
        $view->render("comments", [
            'article'  => $article,
            'comments' => $comments,
        ]);
    }



    /**
     * Vérifie que l'utilisateur est connecté.
     * @return void
     */
    private function checkIfUserIsConnected(): void
    {
        // On vérifie que l'utilisateur est connecté.
        if (!isset($_SESSION['user'])) {
            Utils::redirect("connectionForm");
        }
    }

    /**
     * Affichage du formulaire de connexion.
     * @return void
     */
    public function displayConnectionForm(): void
    {
        $view = new View("Connexion");
        $view->render("connectionForm");
    }

    /**
     * Connexion de l'utilisateur.
     * @return void
     */
    public function connectUser(): void
    {
        // On récupère les données du formulaire.
        $login = Utils::request("login");
        $password = Utils::request("password");

        // On vérifie que les données sont valides.
        if (empty($login) || empty($password)) {
            throw new Exception("Tous les champs sont obligatoires. 1");
        }

        // On vérifie que l'utilisateur existe.
        $userManager = new UserManager();
        $user = $userManager->getUserByLogin($login);
        if (!$user) {
            throw new Exception("L'utilisateur demandé n'existe pas.");
        }

        // On vérifie que le mot de passe est correct.
        if (!password_verify($password, $user->getPassword())) {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            throw new Exception("Le mot de passe est incorrect : $hash");
        }

        // On connecte l'utilisateur.
        $_SESSION['user'] = $user;
        $_SESSION['idUser'] = $user->getId();

        // On redirige vers la page d'administration.
        Utils::redirect("admin");
    }

    /**
     * Déconnexion de l'utilisateur.
     * @return void
     */
    public function disconnectUser(): void
    {
        // On déconnecte l'utilisateur.
        unset($_SESSION['user']);

        // On redirige vers la page d'accueil.
        Utils::redirect("home");
    }

    /**
     * Affichage du formulaire d'ajout d'un article.
     * @return void
     */
    public function showUpdateArticleForm(): void
    {
        $this->checkIfUserIsConnected();

        // On récupère l'id de l'article s'il existe.
        $id = Utils::request("id", -1);

        // On récupère l'article associé.
        $articleManager = new ArticleManager();
        $article = $articleManager->getArticleById($id);

        // Si l'article n'existe pas, on en crée un vide. 
        if (!$article) {
            $article = new Article();
        }

        // On affiche la page de modification de l'article.
        $view = new View("Edition d'un article");
        $view->render("updateArticleForm", [
            'article' => $article
        ]);
    }

    /**
     * Ajout et modification d'un article. 
     * On sait si un article est ajouté car l'id vaut -1.
     * @return void
     */
    public function updateArticle(): void
    {
        $this->checkIfUserIsConnected();

        // On récupère les données du formulaire.
        $id = Utils::request("id", -1);
        $title = Utils::request("title");
        $content = Utils::request("content");

        // On vérifie que les données sont valides.
        if (empty($title) || empty($content)) {
            throw new Exception("Tous les champs sont obligatoires. 2");
        }

        // On crée l'objet Article.
        $article = new Article([
            'id' => $id, // Si l'id vaut -1, l'article sera ajouté. Sinon, il sera modifié.
            'title' => $title,
            'content' => $content,
            'id_user' => $_SESSION['idUser']
        ]);

        // On ajoute l'article.
        $articleManager = new ArticleManager();
        $articleManager->addOrUpdateArticle($article);

        // On redirige vers la page d'administration.
        Utils::redirect("admin");
    }


    /**
     * Suppression d'un article.
     * @return void
     */
    public function deleteArticle(): void
    {
        $this->checkIfUserIsConnected();

        $id = Utils::request("id", -1);

        // On supprime l'article.
        $articleManager = new ArticleManager();
        $articleManager->deleteArticle($id);

        // On redirige vers la page d'administration.
        Utils::redirect("admin");
    }
}