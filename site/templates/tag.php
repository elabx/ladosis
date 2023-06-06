<div class="main-container uk-grid">

  <div class="uk-width-medium-10-10 uk-margin-bottom">
    <?php if($page->template=="secciones"):  ?>
    <h3 class="underlined-title">
      Todas las secciones del sitio.
    </h3>
    <?php else:?>
    <h3 class="underlined-title">
      Estas viendo la sección de: <?php echo $page->title ?>
    </h3>
    <?php  endif ?>
    
  </div>
  
  <div class="uk-width-small-1-1 uk-width-medium-7-10">
    
    <?php
   
    $current = $page->name;
    
    $postsByCategory = $pages->find("parent=articulos, tags.name={$current}")->sort('-published');
   
    ?>

    <?php foreach($postsByCategory as $article): ?>
      <div class=" uk-margin-large-bottom uk-width-1-1">

      <article class="uk-margin-top ">
	<h1>
	  <a href="<?php echo $article->url ?>">
	    <?php echo $article->title ?>
	   
	  </a> 
	</h1>
	

	<p>
	  <?php echo $article->wordLimiter("body", 500);  ?>
	  <?php echo $page->body ?>
	  <a class="uk-margin-top read-more" href="<?php echo $article->url ?>">Leer más &#10161;</a>
	</p> 
	
      </article>
      <?php if($article->tags->count() != 0): ?>
      <ul class="tag-list uk-list-inline">
	  <?php foreach($article->tags as $tag): ?>
	    <li><a href="<?php echo $tag->url ?>"><?php echo $tag->title ?></a></li>
	  <?php endforeach ?>
      </ul>
      <?php endif ?>

      </div>
    <?php endforeach ?>
    </div>
  

  <div class="uk-width-small-1-1 uk-width-medium-3-10">
    <?php $pages->get("name=sidebar")->render; ?>
  </div> 

  
  
</div>
 
