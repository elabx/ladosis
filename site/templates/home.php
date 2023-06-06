<?php 
$configuracion = $pages->get("name=configuracion");
?>
<div class="uk-margin-top uk-grid">
	<div class="uk-margin-top uk-width-medium-2-3">
		<div id="homepage-slider">
			<?php foreach($configuracion->slider as $item):  ?>
				<div class="uk-cover-background main-slider-item"
					style="background-image:url('<?php 
												 if($item->slider_image->count()){
													 echo $item->slider_image->first()->media->width(650)->url;
												 }else{
													 echo ""; 
												 }
												 ?>');">
					
					<div class="slider-title">
						<h1><a href="<?php echo $item->slider_page->url ?>"><?php echo $item->slider_page->title ?></a></h1>
					</div>
				</div>
			<?php endforeach; ?>
		</div> 
	</div>
	
	<div id="ladosistv-column" class="uk-margin-top uk-width-medium-1-3">
		<!-- <div id="ladosistv" class="video-container">
			 <?php $video =  $pages->find("template=ladosis-tv-video")->sort('-published')->first(); ?>
			 <h3 class="light-header underlined-title">La Dosis TV: <?php echo $video->tv_video_title ?></h3>
			 <?php echo $video->tv_embed_code ?>
			 <div class="video-description">
			 <?php
			 if($video->tv_video_descripcion_corta == ''){
			 echo $video->tv_video_descripcion;
			 } else{
			 echo $video->tv_video_descripcion_corta;
			 }
			 ?>
			 </div> 
			 </div> -->

		

	
		
		<h3 class="light-header underlined-title">Versión impresa</h3>
		<?php $issue =  $pages->find("template=periodico")->sort('-published')->first(); ?>
		<a href="<?php echo $issue->url ?>">
            <?php if($issue->image): ?>
                <img class="periodico-thumb" style="margin:auto;display:block;"
				    class="uk-width-medium-6-10"
				    src="<?php echo $issue->image->height(230)->url ?>">
            <?php else: ?>
                <img class="periodico-thumb" style="margin:auto;display:block;"
				    class="uk-width-medium-6-10"
				    src="<?php echo $issue->periodico_pdf->toImage()->height(230)->url ?>">
            <?php endif ?>
			
		</a>
		<p class="uk-margin-top" ><?php echo $issue->title ?></p>
		

		
		
	</div>


	<div class="uk-width-1-1 uk-margin-top ">
		<h3 class="light-header underlined-title">Otras notas y artículos</h3>
	</div>
	<?php foreach($configuracion->notas_resaltadas as $article): ?>
		<div class="article-list 
					uk-margin-top
					uk-width-small-5-10 
					uk-width-medium-5-10">
			<a  href="<?php echo $article->url ?>">   <h3 class=""><?php echo $article->title ?></h3></a>
			<?php if(count($article->article_images)):?>
				<a class="article-image" href="<?php echo $article->url?>">
                    <?php if($article->article_images->count): ?>
					<img class="img-responsive"
						src="
								<?php
								//$log->save("slider", $article->article_images->count());
								if($article->article_images->count()){
									echo $article->article_images->first()->media->size(500, 200,['cropping' => 'center'])->url;
								}else{
									echo "";
								}
								
								?>
								">
                    <?php endif ?>
				</a>
			<?php endif; ?>
			
			<p>
				<?php
				//if(count($article->article_images) == 0)
				echo $article->wordLimiter("body", 100);
				?>
				
			</p>
			<a class="read-more" href="<?php echo $article->url ?>">Leer más &#10161;</a>
		</div> 
	<?php endforeach; ?>

	<?php/* wireIncludeFile("inc/news-item.php", array("article" => $article)); */?>
	
	
	<div class="uk-width-1-1 uk-margin-top ">
		<h3 class="light-header underlined-title">Notas recientes:</h3>
	</div>
	
	<?php foreach($pages->find("template=articulo|carton, sort=-published, limit=5") as $article): ?>
		<?php wireIncludeFile("inc/article-item.php", array("article" => $article)); ?>
	<?php endforeach ?>
	

    <?php $mostPopular = $configuracion->featured; ?>
    
    <?php if($mostPopular->count): ?>
	    <div class="uk-width-1-1 uk-margin-top ">
		    <h3 class="light-header underlined-title">Lo más popular</h3>
	    </div> 
	    
	    
	    <?php
        foreach($mostPopular as $i => $article){
		    if ($i == 10) break;
		    echo wireRenderFile("inc/article-item.php", array("article" => $article));
		    
	    }
	    
	    ?>
    <?php endif ?>
	
</div>






