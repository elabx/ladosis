<div class="article-list uk-margin-top uk-width-small-1-2 uk-width-medium-2-10">
  <a href="<?php echo $article->url ?>">
      <h4 class=""><?php echo $article->title ?></h4>
  </a>
  <?php if($article->article_images):?>
      <?php if($article->article_images->count):?>
          <a class="article-image" href="<?php echo $article->url?>">
        
      <img class="img-responsive"
	   src="
		<?php
		if(count($article->article_images) > 0)
		  echo $article->article_images->first()->media->size(200, 100,['cropping' => 'center'])->url;
		
		?>
		">
    </a>
      <?php endif ?>
  <?php endif; ?>
  
  <p>
    <?php
    //if(count($article->article_images) == 0)
    echo $article->wordLimiter("body", 100);
    ?>
    
  </p>
  <a class="read-more" href="<?php echo $article->url ?>">Leer más &#10161;</a>

</div>
 
