<div class="tutorial">
    <?php if($page->tutoriales): ?>
    <?php foreach($page->tutoriales->get("title=$titulo")->pasos as $paso): ?>
        <div class="uk-margin-top uk-width-1-1">
            <img class="uk-width-1-1" src="<?=$paso->article_images[0]->media->width(700)->url?>">
            <div class="paso-text">
                <?=$paso->body_tutorial?>
            </div>
        </div>
    <?php endforeach ?>
    <?php endif ?>
</div>
