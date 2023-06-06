<script type="application/ld+json">
{
    "@context": "http://schema.org",
    "@type": "Article",
    "author": "<?=$page->author?>",
    "name": "<?=$page->title?>"
}
</script>

<div class="uk-grid">
    <div class="uk-width-medium-7-10">
        <article class="uk-margin-large-top">

            <h1><?php echo $page->title ?></h1>
            
            <p class="article-published-date">
	            <?php
	            //echo  locale_get_default();
	            if($page->date){
	                echo "Publicado el " .  strftime('%e de %B de %Y', $page->getUnformatted('date'));
	            } else {
	                echo "Publicado el " . strftime('%e de %B de %Y', $page->getUnformatted('published'));
	            }
	            ?>
            </p>
            <?php if($page->author): ?>
	            <p class="author-top">
	                Por:<span><?php echo $page->author->title ?>
	                </span>
	            </p>
	            <?php if($page->author->social_media->count() > 0): ?>
	                <ul class="author-social-media">
	                    <?php foreach($page->author->social_media as $media): ?>
	                        <li>
	                            <a href="<?=$media->social_media_url?>">
		                            <i class="uk-icon-button uk-icon-<?php echo $media->social_media_icon ?>"></i> 
	                            </a>
	                        </li>
	                    <?php endforeach ?>
	                </ul>
	            <?php endif ?>
            <?php endif ?>
            <div class="article-body">
	            <?php echo $page->body ?>
            </div>
            <span class="share-social-text" >Comparte en redes:</span> <?php echo $modules->MarkupSocialShareButtons->render(); ?>
            
            <ul class="tag-list uk-list-inline">
	            <?php foreach($page->tags as $tag): ?>
	                <li><a href="<?php echo $tag->url ?>">
	                    <?php echo $tag->title ?>
	                </a></li>
	                
	            <?php endforeach ?>
            </ul>
        </article>
        <div class="uk-grid">
            <div class="uk-width-medium-1-1">
	            <h2 class="light-header uk-margin-large-top uk-margin-bottom underlined">Otros artículos...</h2>
            </div>
            <div class="uk-width-medium-1-2">
	            <h2>
	                <a href="<?php echo $page->next->httpUrl?>">
	                    <?php echo $page->next->title ?>
	                </a>
	            </h2>
	            <p>
	            <p>
	                <?php echo  $page->next->wordLimiter("body", 200);  ?>
	            </p>
	            <a class="read-more" href="<?php echo $article->url ?>">Leer más &#10161;</a>
	            </p> 
            </div>
            <div class="uk-width-medium-1-2">
	            <h2>
	                <a href="<?php echo $page->prev->httpUrl?>"><?php echo $page->prev->title ?>
	                </a>
	            </h2>
	            <p>
	                <?php echo  $page->prev->wordLimiter("body", 200);  ?>
	            </p>
	            <a class="read-more" href="<?php echo $article->url ?>">Leer más &#10161;</a>


            </div> 
        </div>
    </div> 
    
    <div class="uk-width-medium-3-10">

        <div class="uk-grid uk-margin-top uk-margin-bottom">

            <div class="uk-width-5-10 uk-margin-top uk-margin-bottom">
	            <h5 onclick="$('.paypal-button input[name=submit]').click()" style="border-radius:5px;padding:5px;background:darkgreen;color:white;cursor:pointer;text-align:center;">Apoya al periodismo psicoactivo</h5>
            </div>
            <div class="paypal-button uk-width-5-10">
	            <form action="https://www.paypal.com/cgi-bin/webscr" method="post" target="_top">
	                <input type="hidden" name="cmd" value="_s-xclick">
	                <input type="hidden" name="hosted_button_id" value="93UH6BXV8VG6Q">
	                <input type="image" src="https://www.paypalobjects.com/es_XC/MX/i/btn/btn_donateCC_LG.gif" border="0" name="submit" alt="PayPal, la forma más segura y rápida de pagar en línea.">
	                <img alt="" border="0" src="https://www.paypalobjects.com/es_XC/i/scr/pixel.gif" width="1" height="1">
	            </form>
            </div>
            
        </div>
        <!--   <?php/* echo $pages->get("name=sidebar")->render(); */?> -->
        <iframe data-aa="1289579" src="//ad.a-ads.com/1289579?size=320x50" scrolling="no" style="width:320px; height:50px; border:0px; padding:0; overflow:hidden" allowtransparency="true"></iframe>

        
        <?php wireIncludeFile("inc/anuncios.php"); ?>
        
        <div data-mantis-zone="article"></div>
        
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



