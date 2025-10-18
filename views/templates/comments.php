<?php 
    /** 
     * Affichage de la partie admin : liste des articles avec un bouton "modifier" pour chacun. 
     * Et un formulaire pour ajouter un article. 
     */
?>

<h2>Gestion des commentaires</h2>

<div class="adminArticle">
    <h2>Commentaires pour : <?= htmlspecialchars($article->getTitle()) ?></h2>

    <?php if (!empty($comments)): ?>
        <!-- En-têtes -->
        <div class="articleLine">
            <div class="title">Auteur</div>
            <div class="content">Commentaire</div>
            <div class="title centercontent">Date</div>
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
                    ? (method_exists($comment,'getCreatedAt') ? ($comment->getCreatedAt() ?? '') : '')
                    : ($comment['created_at'] ?? '');
            ?>
            <div class="articleLine">
                <div class="title"><?= htmlspecialchars($pseudo) ?></div>
                <div class="content"><?= nl2br(htmlspecialchars($texte)) ?></div>
                <div class="title centercontent"><?= htmlspecialchars($date) ?></div>
            </div>
        <?php endforeach; ?>

    <?php else: ?>
        <!-- État vide dans le même gabarit -->
        <div class="articleLine">
            <div class="title">Auteur</div>
            <div class="content">Commentaire</div>
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