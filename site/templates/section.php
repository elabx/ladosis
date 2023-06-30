<?php
if($page->template == "search"){
    $q = $sanitizer->text($input->get->q);
    $input->whitelist('q', $q);
    $articles = $pages->find("body|title%={$q},template=articulo,sort=-published, limit=20");

}
?>
<region id="content">

<!--Nombre de la sección-->
<div class="uk-container">

    <div class="uk-container uk-width-1-2@m uk-margin uk-flex uk-flex-left" uk-grid>
        <?php if($page->template=="todas-secciones"):  ?>
            <h3 class="underlined-title">
                Todas las secciones del sitio.
            </h3>
        <?php elseif($page->template == "seccion"):?>
            <h3 class="underlined-title">
                Estas viendo la sección de: <span class="visiting-section-tag"><?php echo $page->title ?></span>
            </h3>
        <?php  elseif($page->template == "search"): ?>
            <h3 class="underlined-title">
                Encontramos <span class="visiting-section-tag"><?=$articles->getTotal()?></span> resultados para la búsqueda "<?=$input->get->q?>"
            </h3>
        <?php endif ?>
    </div>

    <hr>

<!--Sección de noticias  -->
    <div class="uk-container uk-width-1-1@m " uk-grid>

        <?php
        if($page->template != "search"){

            $current = $page->name;
            if($page->template == "todas-secciones"){
                $articles = $pages->find("parent=[name=articulos], limit=10")->sort("-published");
            } else {
                $articles = $pages->find("parent=[name=articulos], categories.name={$current}, limit=10")->sort("-published");
            }
        }
        ?>


        <?php foreach($articles as $article): ?>
            <div class="uk-margin-top-large uk-width-1-2@m">
                <article class=" ">
                    <div>
                        <a href="<?php echo $article->url ?>">
                            <h2 class="titulo"><?php echo $article->title ?></h2>
                        </a>

                        <p class="uk-margin-bottom article-published-date">
                            <?php

                            if($article->date){
                                echo "Publicado el " .  strftime('%e de %B de %Y', $article->getUnformatted('date'));
                            } else {
                                echo "Publicado el " . strftime('%e de %B de %Y', $article->getUnformatted('published'));
                            }
                            ?>
                        </p>

                        <div class="">
                        <?php if($article->article_images): ?>
                            <?php if($article->article_images->count): ?>
                                <img class="uk-border-rounded" src="<?=$article->article_images->first()->media->size(750, 250)->httpUrl?>">
                            <?php endif ?>
                        <?php endif ?>
                        </div>

                        <p>
                            <?= $sanitizer->truncate($article->body, array(
                                'type' => 'punctuation',
                                'maxLength' => 150,
                                'visible' => true,
                                'more' => '...'
                            )); ?>
                        </p>
                        <a class="read-more uk-flex uk-flex-right" href="<?php echo $article->url ?>">Leer más &#10161;</a>
                        <div style="clear: both;"></div>
                    </div>
                </article>

                <?php if($article->tags): ?>
                    <?php if($article->tags->count): ?>
                        <ul class="tag-list uk-list-inline uk-margin-large-bottom">
                            <?php foreach($article->tags as $tag): ?>
                                <li><a href="<?php echo $tag->url ?>"><?php echo $tag->title ?></a></li>
                            <?php endforeach ?>
                        </ul>
                    <?php endif ?>
                <?php endif ?>


            </div>
        <?php endforeach ?>
    </div>


<!--    <div class="uk-width-small-1-1 uk-width-medium-3-10">-->
<!--        --><?php //$pages->get("name=sidebar")->render; ?>
<!---->
<!--        --><?php //wireIncludeFile("inc/anuncios.php"); ?>
<!---->
<!--    </div>-->

    <div class="pager uk-text-bold">
        <?php echo $articles->renderPager(array(
            'numPageLinks' => 5,
            'listMarkup' => "<ul class='uk-pagination'>{out}</ul>",
            'itemMarkup' => "<li class='{class}'>{out}</li>",
            'linkMarkup' => "<a href='{url}'>{out}</a>",
            'nextItemLabel' => '<span uk-icon="icon: chevron-right">Siguiente</span>',
            'previousItemLabel' => '<span uk-icon="icon: chevron-left"></span><span>Anterior</span>',
            'separatorItemLabel' => '<span>...</span>',
            'currentItemClass' => 'uk-active',
        )); ?>
    </div>

</div>
</region>