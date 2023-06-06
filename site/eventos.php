
<div class="uk-width-medium-10-10 uk-margin-bottom">
  
</div>

<?php foreach($pages->get("name=eventos")->children as $event):?>

  <div class=" uk-margin-large-bottom uk-width-1-1">

    <article class="uk-margin-top ">
      <h1>
	<a href="<?php echo event->url ?>">
	  <?php echo event->title ?>
	  
	</a> 
      </h1>
      
      <?php if(count(event->event_image > 0)): ?>
      <?php endif ?>
      <p>
	<?php echo event->wordLimiter("body", 500);  ?>
	<a class="read-more" href="<?php echo event->url ?>">Leer más &#10161;</a>
      </p> 
      
    </article>
    <?php if(event->tags->count() != 0): ?>
      <ul class="tag-list uk-list-inline">
	<?php foreach(event->tags as $tag): ?>
	  <li><a href="<?php echo $tag->url ?>"><?php echo $tag->title ?></a></li>
	<?php endforeach ?>
      </ul>
    <?php endif ?>

  </div>
  
<?php endforeach ?>
