
<div class="main-container uk-grid ">
  
  <div class="uk-width-medium-7-10">
    <div class="uk-width-medium-10-10 uk-margin-bottom">
      <h3 class="underlined-title">
	Estas viendo la sección de: <?php echo $page->title ?>
      </h3>
    </div>

    <!-- <ul class="tag-list uk-list-inline">
	 <?php foreach($page->tags as $tag): ?>
	 <li><a href="<?php echo $tag->url ?>"><?php echo $tag->title ?></a></li>
	 <?php endforeach ?>
	 </ul> -->

    
    <?php foreach($pages->get("name=ladosistv")->children() as $video):?>
      <div class="uk-margin-large-bottom uk-width-medium-1-1">
	<h2>
	  <a href="<?php echo $video->url ?>">
	    <?php echo $video->title ?>
	  </a>
	</h2> 
	<div class="video-container uk-width-medium-1-1">

	  <?php echo $video->tv_embed_code ?>
	</div>
	<?php echo $video->tv_video_descripcion ?>
	<div class="uk-width-medium-1-1">
	  <?php
	  $options = array(
	    "url" => "{$video->url}",
	    "title" => "{$vide->title}",
	    "text" => "{$video->tv_video_descripcion}",
	  );

	  ?>

	  <?php echo $modules->MarkupSocialShareButtons->render($options); ?>
	</div>
      </div>
    <?php endforeach ?>     
    
  </div>

  <div class="uk-width-medium-3-10">
    <?php echo $pages->get("name=sidebar")->render(); ?>
    
    <?php wireIncludeFile("includes/anuncios.php"); ?>
    
  </div>
   
    
  </div> 
  
  

  <div class="uk-width-medium-10-10">
  <div id="disqus_thread"></div>
  </div>
</div>
