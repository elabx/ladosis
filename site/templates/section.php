<?php 
if($page->template == "search"){
    $q = $sanitizer->text($input->get->q);
    $input->whitelist('q', $q);
	$articles = $pages->find("body|title%={$q},template=articulo,sort=-published, limit=20");

}
?>

<div class="main-container uk-grid">

	<div class="uk-width-medium-10-10 uk-margin-bottom">
		<?php if($page->template=="todas-secciones"):  ?>
			<h3 class="underlined-title">
				Todas las secciones del sitio.
			</h3>
		<?php elseif($page->template == "seccion"):?>
			<h3 class="underlined-title">
				Estas viendo la sección de: <span class="visiting-section-tag"><?php echo $page->title ?></span>
			</h3>
		<?php  elseif($page->template == "search"): ?>
			<h3 class="underlined-title">
				Encontramos <span class="visiting-section-tag"><?=$articles->getTotal()?></span> resultados para la búsqueda "<?=$input->get->q?>"
			</h3>
		<?php endif ?>
		
	</div>
	
	<div class="uk-width-small-1-1 uk-width-medium-7-10">
		
		<?php
		if($page->template != "search"){
			
			$current = $page->name;
			if($page->template == "todas-secciones"){
				$articles = $pages->find("parent=[name=articulos], limit=10")->sort("-published");
			} else {
				$articles = $pages->find("parent=[name=articulos], categories.name={$current}, limit=10")->sort("-published");
			}
		}
		?>

		<?php foreach($articles as $article): ?>
			<div class=" uk-margin-large-top uk-width-1-1">

				<article class=" ">
					<div class="uk-grid">
						<div class="uk-width-1-1">
							
							<h1>
								<a href="<?php echo $article->url ?>">
									<?php echo $article->title ?>
									
								</a> 
							</h1>
                            <p class="uk-margin-bottom article-published-date">
	                            <?php
	                            //echo  locale_get_default();
	                            if($article->date){
	                                echo "Publicado el " .  strftime('%e de %B de %Y', $article->getUnformatted('date'));
	                            } else {
	                                echo "Publicado el " . strftime('%e de %B de %Y', $article->getUnformatted('published'));
	                            }
	                            ?>
                            </p>
							
							<?php if($article->article_images): ?>
                                <?php if($article->article_images->count): ?>
								<img src="<?=$article->article_images->first()->media->size(750, 250)->httpUrl?>">
                                <?php endif ?>
							<?php endif ?>
							
							<p>
								<?php echo $article->wordLimiter("body", 500);  ?>
							</p>
							<a class="read-more" href="<?php echo $article->url ?>">Leer más &#10161;</a>
							<div style="clear: both;"></div> 
							
							
						</div>
						
					</div>
				</article>
				<?php if($article->tags): ?>
                    <?php if($article->tags->count): ?>
					    <ul class="tag-list uk-list-inline">
						    <?php foreach($article->tags as $tag): ?>
							    <li><a href="<?php echo $tag->url ?>"><?php echo $tag->title ?></a></li>
						    <?php endforeach ?>
					    </ul>
                    <?php endif ?>
				<?php endif ?>

			</div>
		<?php endforeach ?>
    </div>
	

	<div class="uk-width-small-1-1 uk-width-medium-3-10">
		<?php $pages->get("name=sidebar")->render; ?>

		<?php wireIncludeFile("inc/anuncios.php"); ?>
		
	</div> 

	<div class="pager">
        <?php echo $articles->renderPager() ?>
    </div>
	
</div>

