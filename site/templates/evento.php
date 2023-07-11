
<region id="content">

<div class="uk-width-1-1@m uk-container">

    <div class="uk-grid uk-margin-large-top">

        <div class="uk-width-1-1@m">
            <h1 class="event-title uk-text-center underlined-title">
                <?=$page->title?>
            </h1>
        </div>
        <div class="uk-width-1-1@m
		            uk-flex
		            uk-flex-middle
		            uk-flex-center">

            <span class="uk-flex uk-flex-middle uk-flex-center date-time">
	            <span>
	                <span class="start-date"><?=strftime("%e de",$page->evento_fecha_inicio)?></span>
	                <span class="start-date"><?=strtoupper(strftime("%B",$page->evento_fecha_inicio))?></span>
	            </span>
	            <span>
	                al
	            </span>
	            <span>
	                <span class="start-date"><?=strftime("%e",$page->evento_fecha_fin)?></span>
	                <span class="start-date"><?=strtoupper(strftime("%B",$page->evento_fecha_fin))?></span>
	            </span>

            </span>


        </div>


    </div>
    <div class="uk-grid">
        <div class="uk-width-1-2@m uk-flex uk-flex-right">
            <a href="<?php echo $page->url ?>">
                <img class="periodico-thumb uk-box-shadow-large" style="margin:auto;display:block;"  src="<?php echo $page->evento_imagenes->first()->media->width(500)->url ?>">
            </a>
        </div>
        <div class="uk-width-2-5@m uk-margin-top">
            <div class="uk-grid" data-uk-grid-margin >
                <div class="uk-width-1-1">
                    <h3>Links al evento:</h3>
                    <?php if($page->evento_urls): ?>
                    <ul>
                        <?php foreach($page->evento_urls as $url): ?>
                            <a href="<?=$url->evento_url?>"><li><?=$url->title?></li></a>
                        <?php endforeach ?>
                        <?php endif ?>
                    </ul>
                </div>
                <div class="uk-width-1-1">
                    <h3>Horarios:</h3><p> <?=$page->evento_horarios ?></p>
                </div>
            </div>
            <?php setlocale(LC_TIME,'es_MX.utf8') ?>
            <div class="event-date">
            </div>
            <?=$page->evento_descripcion?>
        </div>
    </div>
    <div class="uk-grid">
        <div class="uk-width-1-1">
            <?php $type = $pages->get("name=encabezado") ?>
            <?php foreach( $pages->get("name=publicidad")->children as $anuncio):  ?>
                <?php if($anuncio->publicidad_ubicacion == $type): ?>
                    <!--   <a class="" href="<?php echo $anuncio->publicidad_url ?>">
	                    <img class="img-responsive"
	                        src="<?php echo $anuncio->publicidad_img->url ?>">
	                </a>
                  -->
                <?php endif ?>
            <?php endforeach ?>
        </div>
    </div>
</div>


</region>