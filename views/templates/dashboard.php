<?php 
/**
 * Vue dashboard (affichage uniquement)
 * - Utilise le routeur via ?action=...
 * - Affiche : Titre, Vues, Commentaires, Publication
 * - Les liens de tri laissent au contrôleur le soin d'appliquer l'ORDER BY
 *
 * Variables attendues :
 * - $articles : tableau d'objets Article avec :
 *   getTitle(), getCountViews(), getCountComments(), getDateCreation()
 */
?>

<h2>Tableau de bord </br></br><a class="submit" href="index.php?action=admin">Gérer les articles</a></h2>

<div class="adminArticle">

    <!-- En-tête cliquable pour demander un tri (le contrôleur lit sort/order) -->
    <div class="articleLine" style="font-weight:600;">
        <div class="title">
            <a href="index.php?action=adminDashboard&sort=title&order=asc" style="text-decoration:none;color:inherit;">Titre</a>
        </div>
        <div class="title">
            <a href="index.php?action=adminDashboard&sort=views&order=desc" style="text-decoration:none;color:inherit;">Vues</a>
        </div>
        <div class="title">
            <a href="index.php?action=adminDashboard&sort=comments&order=desc" style="text-decoration:none;color:inherit;">Commentaires</a>
        </div>
        <div class="title">
            <a href="index.php?action=adminDashboard&sort=date&order=desc" style="text-decoration:none;color:inherit;">Publication</a>
        </div>
    </div>

    <?php foreach ($articles as $article) { ?>
        <div class="articleLine">
            <!-- Titre -->
            <div class="title"><?= $article->getTitle() ?></div>

            <!-- Nombre de vues -->
            <div class="title">
                <?= (int)$article->getCountViews() ?>
                
            </div>

            <!-- Nombre de commentaires -->
            <div class="title">
                <?= (int)$article->getCountComments() ?>
                
            </div>

            <!-- Date de publication -->
            <div class="footer">
                <span class="info"><?= ucfirst(Utils::convertDateToFrenchFormat($article->getDateCreation())) ?></span>
            </div>
        </div>
    <?php } ?>

</div>
