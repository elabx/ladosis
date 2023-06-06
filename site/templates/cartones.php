<div class="uk-width-1-1 uk-container-center">
  <h1 class="uk-margin-large-top underlined-title">
          Cartones
  </h1>
  <div class="uk-grid">
    <?php $issues =  $pages->find("tags=carton"); ?>
    <?php if(!$issues): ?>
      <h3>Por el momento no hay contenido, ¡regresa después! ;)</h3>      
    <?php endif ?>
    <?php foreach($issues as $issue): ?>
      <div class="uk-width-1-3 uk-margin-top">
	
	<a href="<?php echo $issue->url ?>">
	  <img class="periodico-thumb" style="margin:auto;display:block;" class="uk-width-medium-1-1" src="<?php echo $issue->article_images->first()->media->width(500)->url ?>">
	</a>
	<p class="uk-margin-top" style="text-align:center;margin:auto;"><?php echo $issue->title ?></p>
      </div>
    <?php endforeach ?>
  </div> 

</div>
