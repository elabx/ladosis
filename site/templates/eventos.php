<region id="content">

<div class="uk-container">
    <div>
        <h1 class="uk-margin-large-top underlined-title">
            <?=$page->title ?>
        </h1>
    </div>

    <div class="uk-grid">
        <?php $events =  $pages->find("template=evento, evento_imagenes.count>0"); ?>
        <?php if(!$events): ?>
            <h3>Por el momento no hay contenido, ¡regresa después! ;)</h3>
        <?php else: ?>
            <?php foreach($events as $event): ?>
                <div class="uk-width-1-2 uk-margin-top">
                    <div class="uk-card" style="position:relative;">
	    <span class="event-date-card">
	      <span class="start-date"><?=strftime("%e ",$event->evento_fecha_inicio)?></span>
	      <span class="start-date"><?=strtoupper(strftime("%b",$event->evento_fecha_inicio))?></span>
	    </span>
                        <a href="<?php echo $event->url ?>">
                            <img class="poster-thumb" style="margin:auto;display:block;" class="uk-width-medium-1-1" src="<?php echo $event->evento_imagenes->count ? $event->evento_imagenes->first()->media->width(500)->url:'' ?>">
                            <h2 class="uk-margin-top" style="text-align:center;margin:auto;"><?php echo $event->title ?></h2>

                        </a>
                        <?php echo $event->evento_descripcion_corta ?>
                    </div>

                </div>
            <?php endforeach ?>
        <?php endif ?>
    </div>

</div>

</region>