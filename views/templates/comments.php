<?php 
    /** 
     * Affichage de la partie admin : liste des articles avec un bouton "modifier" pour chacun. 
     * Et un formulaire pour ajouter un article. 
     */
?>

<h2 class="">Gestion des commentaires</h2>

<div class="adminArticle">
    <h2 class="title "style="color:white">Commentaires pour : <?= htmlspecialchars($article->getTitle()) ?></h2>

    <?php if (!empty($comments)): ?>
        <!-- En-têtes -->
        <div class="articleLine">
            <div class="title">Auteur</div>
            <div class="title content centercontent">Commentaire</div>
            <div class="title centercontent">Date</div>
            <div class="title centercontent">Action</div>
        </div>

        <!-- Lignes -->
        <?php foreach ($comments as $comment): ?>
            <?php
                // Tolère objet OU array, purement pour l’affichage
                $pseudo  = is_object($comment)
                    ? (method_exists($comment,'getPseudo') ? $comment->getPseudo() : 'Anonyme')
                    : ($comment['pseudo'] ?? 'Anonyme');

                $texte   = is_object($comment)
                    ? (method_exists($comment,'getContent') ? $comment->getContent() : '')
                    : ($comment['content'] ?? '');

                $date    = is_object($comment)
                    ? (method_exists($comment,'getDateCreation') ? $comment->getDateCreation() : '')
                    : ($comment['date_creation'] ?? '');
            ?>
            <div class="articleLine">
                <div class="title centercontent"><?= htmlspecialchars($pseudo) ?></div>
                <div class="content centercontent"><?= nl2br(htmlspecialchars($texte)) ?></div>
                <div class="title centercontent"><span class="info"> <?= ucfirst(Utils::convertDateToFrenchFormat($date)) ?></span></div>
                <div class="title centercontent"><a class="submit" href="index.php?action=deleteComment&id=<?= $comment->getId() ?>" <?= Utils::askConfirmation("Êtes-vous sûr de vouloir supprimer ce commentaire ?") ?> >Supprimer</a></div>
            </div>
        <?php endforeach; ?>

    <?php else: ?>
        <!-- État vide dans le même gabarit -->
        <div class="articleLine">
            <div class="title">Auteur</div>
            <div class=" title content centercontent">Commentaire</div>
            <div class="title centercontent">Date</div>
        </div>
        <div class="articleLine">
            <div class="content" style="width:100%; text-align:center; padding:40px;">
                <em>Aucun commentaire pour cet article.</em>
            </div>
        </div>
    <?php endif; ?>
</div>




<a class="submit" href="index.php?action=showUpdateArticleForm">Ajouter un article</a>