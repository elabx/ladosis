
<div class="main-container uk-grid">
  <div class="uk-width-medium-7-10">
    <div class="uk-width-medium-1-1">

      <h1><?php echo $page->title ?></h1>
      <ul class="tag-list uk-list-inline">
	<?php foreach($page->tags as $tag): ?>
	  <li><a href="<?php echo $tag->url ?>"><?php echo $tag->title ?></a></li>
	<?php endforeach ?> 
      </ul>
      <div class="video-container">
	<?php echo $page->tv_embed_code ?>
      </div>
      <?php echo $page->tv_video_descripcion ?>
      
      <?php echo $modules->MarkupSocialShareButtons->render(); ?>
    </div>
  </div>
  
  <div class="uk-width-medium-3-10">
    <?php echo $pages->get("name=sidebar")->render(); ?>

    <?php wireIncludeFile("includes/anuncios.php"); ?>

  </div>

  <div class="uk-width-medium-10-10">
    <div id="disqus_thread"></div>
  </div>
</div>

<script>

 /**
  *  RECOMMENDED CONFIGURATION VARIABLES: EDIT AND UNCOMMENT THE SECTION BELOW TO INSERT DYNAMIC VALUES FROM YOUR PLATFORM OR CMS.
  *  LEARN WHY DEFINING THESE VARIABLES IS IMPORTANT: https://disqus.com/admin/universalcode/#configuration-variables */
 
 var disqus_config = function () {
   this.page.url = "<?php echo $page->httpUrl ?>"; 
   this.page.identifier = <?php echo $page->id ?>; 
 };

 (function() { // DON'T EDIT BELOW THIS LINE
   var d = document, s = d.createElement('script');
   s.src = '//ladosis-org.disqus.com/embed.js';
   s.setAttribute('data-timestamp', +new Date());
   (d.head || d.body).appendChild(s);
 })();
</script>
<noscript>Please enable JavaScript to view the <a href="https://disqus.com/?ref_noscript">comments powered by Disqus.</a></noscript>


 
 
