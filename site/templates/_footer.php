</div>
<footer class="uk-container-center uk-container uk-margin-large-top">
	<div class="uk-grid 
				">
		<div class="uk-width-small-1-1 uk-width-medium-3-6"></div>

		<div class="uk-width-1-1">

			<h2 class="light-header underlined-title">Nuestras secciones</h2>
			<div class="uk-grid">
				<?php
				$sections = $pages->get("name=secciones")->children()->getArray();
				$sectionChunks = array_chunk($sections, intdiv(count($sections), 2));
				//echo intdiv(count($sections), 2);
				?>

				<?php foreach($sectionChunks as $chunk): ?>
					<div class="uk-width-1-3">
						<ul class="uk-list uk-list-line">
							<?php foreach($chunk as $seccion): ?>
								<li>
									<h4>
										<a href="<?php echo $seccion->url ?>">
											<?php echo $seccion->title ?>
										</a>
									</h4>
								</li>
							<?php endforeach ?>
						</ul>
					</div>
				<?php endforeach ?>
			</div>
			
			
		</div>
		
		<!-- <div class="uk-width-2-6">
			 <h2 class="light-header underlined-title">Últimos comentarios</h2>

			 <?php
			 /* $rss = $modules->get("MarkupLoadRSS");
			 * $rss->load("https://ladosis-org.disqus.com/latest.rss");

			 * foreach($rss as $item) { 
			 echo "<p>";
			 echo "<a href='{$item->url}'>{$item->title}</a> ";
			 echo $item->date . "<br /> ";
			 echo $item->description;
			 echo $item->creator; 
			 echo "</p>";
			 
			 * }

			 * $this->wire("log")->save("dosis-log", print_r($item, TRUE));*/
			 
			 ?>
			 </div> -->

		<div class="uk-margin-large uk-width-1-1">
			<h5 style="text-align:center;">La Dosis -  Noticias de la comunidad psicoactiva © <?=date("Y",time())?></h5>
            <iframe data-aa="1289579" src="//ad.a-ads.com/1289579?size=320x50" scrolling="no" style="width:320px; height:50px; border:0px; padding:0; overflow:hidden;margin:auto;display:block;" allowtransparency="true"></iframe>            
		</div>
	</div>
    

</footer>



<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.1.0/jquery.min.js"></script>
<script async src="<?php echo $config->urls->templates?>js/uikit.min.js"></script>
<script async src="<?php echo $config->urls->templates?>js/components/search.min.js"></script>
<script src="<?php echo $config->urls->templates?>js/slick.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/js-cookie/2.1.3/js.cookie.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/fitvids/1.1.0/jquery.fitvids.min.js"></script>
<script src="<?php echo $config->urls->templates?>dist/modulobox.min.js"></script>
<script src="<?php echo $config->urls->templates?>js/main.js"></script>

<script type="text/javascript">
var mantis = mantis || [];
mantis.push(['display', 'load', {
	property: '5df275cceb4a5a000748bf47'
}]);
</script>

<script type="text/javascript" data-cfasync="false" src="https://assets.mantisadnetwork.com/mantodea.min.js" async></script>

</body>
</html>

<style>
[data-mantis-zone]{
    text-align:center;
}
</style>


<!-- Hotjar Tracking Code for http://ladosis.org -->




<script>
(function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){
	(i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),
	m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)
})(window,document,'script','https://www.google-analytics.com/analytics.js','ga');

ga('create', 'UA-86842140-1', 'auto');
ga('send', 'pageview');



</script>




