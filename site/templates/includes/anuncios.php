<?php $type = $pages->get("name=lateral-articulos-y-secciones") ?>
<?php foreach($pages->get("name=publicidad")->children as $anuncio):  ?>
  <?php if($anuncio->publicidad_ubicacion == $type): ?>
    <a class="uk-display-block uk-margin-top" href="<?php echo $anuncio->publicidad_url ?>">
      <img class=""
	   src="<?php echo $anuncio->publicidad_img->width(320)->url ?>">
    </a>
  <?php endif ?>
<?php endforeach ?>


