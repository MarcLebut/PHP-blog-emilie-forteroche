<?php
$currentSort = $_GET['sort'] ?? 'date';
$currentOrder = $_GET['order'] ?? 'desc';

$active = function (string $col, string $order) use ($currentSort, $currentOrder): string {
    return ($currentSort === $col && $currentOrder === $order) ? 'active' : '';
};
?>
<h2>Tableau de bord</h2></br><a class="submit" href="index.php?action=admin">Gérer les articles</a></br>
<div class="adminArticle">
    <div class="articleLine" style="font-weight:600;">
        <div class="title">
            <h3>Titre</h3>
            <a class="sort-btn <?= $active('title', 'asc') ?>" href="index.php?action=adminDashboard&sort=title&order=asc" title="Trier Titre par ordre croissant">▲</a>
            <a class="sort-btn <?= $active('title', 'desc') ?>" href="index.php?action=adminDashboard&sort=title&order=desc" title="Trier Titre par ordre décroissant">▼<a>
        </div>

        <div class="title">
            <h3>Vue</h3>
            <a class="sort-btn <?= $active('views', 'asc') ?>" href="index.php?action=adminDashboard&sort=views&order=asc" title="Trier Vues croissant">▲</a>
            <a class="sort-btn <?= $active('views', 'desc') ?>" href="index.php?action=adminDashboard&sort=views&order=desc" title="Trier Vues décroissant">▼</a>
        </div>

        <div class="title centercontent">
            <h3>Commentaire</h3>
            <a class="sort-btn <?= $active('comments', 'asc') ?>" href="index.php?action=adminDashboard&sort=comments&order=asc" title="Trier Commentaires croissant">▲</a>
            <a class="sort-btn <?= $active('comments', 'desc') ?>" href="index.php?action=adminDashboard&sort=comments&order=desc" title="Trier Commentaires décroissant">▼</a>
        </div>

        <div class="title">
            <h3>Publication</h3>
            <a class="sort-btn <?= $active('date', 'asc') ?>" href="index.php?action=adminDashboard&sort=date&order=asc" title="Trier Date croissant">▲</a>
            <a class="sort-btn <?= $active('date', 'desc') ?>" href="index.php?action=adminDashboard&sort=date&order=desc" title="Trier Date décroissant">▼</a>
        </div>
    </div>
    <?php foreach ($articles as $article) { ?>
        <div class="articleLine">
            <div class="title"><?= htmlspecialchars($article->getTitle(), ENT_QUOTES, 'UTF-8') ?></div>
            <div class="title"><?= (int) $article->getCountViews() ?></div>
            <div class="title">
                <a href="index.php?action=showComments&id=<?= urlencode($article->getId()) ?>" 
                class="text-decoration-none" 
                title="Voir tous les commentaires de cet article">
                    <?= (int) $article->getCountComments() ?>
                </a>
            </div>
            <div class="content footer">
                <span class="info"><?= ucfirst(Utils::convertDateToFrenchFormat($article->getDateCreation())) ?></span>
            </div>
        </div>
    <?php } ?>
</div>

