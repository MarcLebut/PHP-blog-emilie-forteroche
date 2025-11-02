<?php
/**
 * Contrôleur de la partie admin.
 */

class AdminController
{

    public function index(): void
    {

        // On vérifie que l'utilisateur est connecté.
        $this->checkIfUserIsConnected();

        $commentManager = new CommentManager();
        $articleManager = new ArticleManager();

        // 1) Colonnes triables : clé (vue) -> colonne SQL (modèle)
        $COLS = [
            'title' => ['label' => 'Titre', 'sql' => 'title'],
            'views' => ['label' => 'Vues', 'sql' => 'views'],
            'comments' => ['label' => 'Commentaires', 'sql' => 'countComments'],
            'date' => ['label' => 'Publication', 'sql' => 'date_creation'], // <-- important
        ];

        // 2) Lecture + sécurisation
        $sortKey = Utils::request('sort') ?? 'date';
        $order = Utils::request('order') ?? 'desc';
        $sortKey = array_key_exists($sortKey, $COLS) ? $sortKey : 'date';
        $order = strtolower($order) === 'asc' ? 'ASC' : 'DESC';

        // 3) Données triées
        $articles = $articleManager->findAllSorted($COLS[$sortKey]['sql'], $order);

        foreach ($articles as $article) {
            // Si la méthode existe et que la valeur semble manquante, on calcule
            if (method_exists($article, 'getCountComments')) {
                $current = (int) $article->getCountComments();
                if ($current === 0) {
                    // On peut vérifier s'il y a potentiellement des commentaires
                    // pour ne pas faire de requête inutile — ici on fait simple :
                    $count = (int) $commentManager->countByArticleId($article->getId());
                    if ($count !== $current && method_exists($article, 'setCountComments')) {
                        $article->setCountComments($count);
                    }
                }
            }
        }

        // 4) En-têtes (toggle + flèche)
        $mkUrl = static function (string $k, string $o): string {
            return 'index.php?action=dashboard&sort=' . urlencode($k) . '&order=' . urlencode($o);
        };

        $headers = [];
        foreach ($COLS as $key => $meta) {
            $isCurrent = $key === $sortKey;
            $nextOrder = ($isCurrent && $order === 'ASC') ? 'desc' : 'asc';
            $arrow = $isCurrent ? ($order === 'ASC' ? ' ▲' : ' ▼') : '';
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

    public function showAdmin():void{
        $articleManager = new ArticleManager();
        $articles = $articleManager->getAllArticles();

        $view = new View("Page Admin");
        $view->render("admin", [
            'articles' => $articles,
        ]);
        
    }
    /**
     * Affiche la page d'administration (tableau de bord).
     * @return void
     */
    public function showDashboard(): void
    {
        // 1) Sécurité : on s'assure que l'utilisateur est connecté
        $this->checkIfUserIsConnected();

        // 2) Récupération des articles (objets Article)
        $articleManager = new ArticleManager();
        $commentManager = new CommentManager();
        $articles = $articleManager->getAllArticles();

        // 3) Hydratation du nombre de commentaires pour chaque article
        foreach ($articles as $article) {
            // Article a bien un getId()
            $count = $commentManager->countByArticleId($article->getId());
            $article->setCountComments($count);
        }

        // 4) Rendu de la vue (affichage pur)
        $view = new View("Tableau de bord");
        $view->render("dashboard", [
            'articles' => $articles,
        ]);
    }

    public function showComments(): void
    {
        // 1) Sécurité
        $this->checkIfUserIsConnected();

        // 2) Récupération de l'id article depuis la requête
        $articleId = utils::request('id');
        if ($articleId <= 0) {
            // Id manquant ou invalide -> redirection ou message propre

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

        // (Optionnel) hydrater dans l'entité : setComments()
        if (method_exists($article, 'setComments')) {
            $article->setComments($comments);
        }
        // (Optionnel) hydrater le count affiché dans la vue
        if (method_exists($article, 'setCountComments')) {
            $article->setCountComments($commentManager->countByArticleId($articleId));
        }

        // 6) Rendu : on passe l'article ciblé et ses commentaires
        $view = new View("Commentaires de l'article");
        $view->render("comments", [
            'article' => $article,
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
        Utils::redirect("dashboard");
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

    /**
     * Suppression d'un commentaire.
     * @return void
     */
    public function deleteComment(): void
    {
        $this->checkIfUserIsConnected();

        $id = Utils::request("id", -1);
        if ($id <= 0) {
            Utils::redirect("dashboard");
            exit;
        }

        $commentManager = new CommentManager();
        $comment = $commentManager->getCommentById($id);
        $commentSupp = $commentManager->deleteComment($comment);
        
        $articleId = (int) $comment->getIdArticle();
        
        Utils::redirect("showComments",['id'=>(int)$articleId]);
        exit;
    }
}