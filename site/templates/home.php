<?php namespace ProcessWire; ?>
<?php
$configuracion = $pages->get("name=configuracion");
?>
<region id="content">

	<div class="uk-container uk-margin-top">
			<div id="homepage-slider" uk-grid>
				<div class="uk-width-3-5@m">
					<?php foreach($configuracion->slider as $item):  ?>
					<div class="uk-inline">
						<img src="<?php echo $item->slider_image->first()->media->width(700)->url; ?>">
							<div class="uk-overlay uk-overlay-primary uk-position-bottom">
								<p><a href="<?php echo $item->slider_page->url ?>"><?php echo $item->slider_page->title ?></a></p>
							</div>
					</div>
					<?php endforeach; ?>
				</div>

				<div class="uk-width-2-5@m">
					<h3 class="uk-text-center"><span class="uk-text-underline">Versión impresa</span></h3>
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
					<p class="uk-margin-top uk-text-center" ><?php echo $issue->title ?></p>
				</div>
			</div>
		</div>

<!--otras notas-->
	<div class="uk-container uk-margin-top ">
		<h3 class="light-header ">Otras notas y artículos</h3>
		<hr>
	</div>

	<div class="uk-container uk-margin-top">
		<div class="uk-child-width-1-2@m uk-grid-match "uk-grid>

			<?php foreach ($configuracion->notas_resaltadas as $article): ?>
				<div>
					<div class="uk-card uk-card-default">
						<div class="uk-card-media-top uk-cover-container uk-height-medium">
							<img src="<?php echo $article->article_images->first()->media->size(300, 150)->url; ?>"
								 alt="" uk-cover>
						</div>

						<div class="uk-card-body">
							<a href="<?php echo $article->url ?>"><h3 id="titulo"
									class="uk-card-title"><?php echo $article->title ?></h3></a>

							<p>
								<?= $sanitizer->truncate($article->body, array(
									'type' => 'punctuation',
									'maxLength' => 150,
									'visible' => true,
									'more' => '...'
								)); ?>
							</p>
							<a class="read-more uk-flex uk-flex-right" href="<?php echo $article->url ?>">Leer más &#10161;</a>
						</div>
					</div>
				</div>
			<?php endforeach; ?>
		</div>
	</div>

<!--Notas recientes-->
	<div class="uk-container uk-margin-large-top ">
		<h3 class="light-header ">Notas recientes:</h3>
		<hr>
	</div>

	<div class="uk-container uk-margin-top">

		<div class="uk-slider-container" uk-slider>
			<div class="uk-position-relative uk-visible-toggle" tabindex="-1" >
				<ul class="uk-slider-items uk-child-width-1-4@m uk-grid-match uk-grid">

					<?php foreach($pages->find("template=articulo|carton, sort=-published, limit=5") as $article): ?>
						<li>

							<div class="uk-card uk-card-default uk-card-small">
								<div class="uk-card-media-top uk-cover-container uk-height-small">
									<img src="<?php echo $article->article_images->first()->media->size(300, 150)->url; ?>"
										 alt="" uk-cover>
								</div>

								<div class="uk-card-body">
									<a href="<?php echo $article->url ?>"><h3 id="titulo" class="uk-text-small uk-text-bolder uk-card-title"><?php echo $article->title ?></h3></a>
									<p class="uk-text-small">
										<?= $sanitizer->truncate($article->body, array(
											'type' => 'punctuation',
											'maxLength' => 150,
											'visible' => true,
											'more' => '...'
										)); ?>
									</p>
									<a class=" uk-flex uk-flex-right" href="<?php echo $article->url ?>">Leer más &#10161;</a>
								</div>
							</div>

						</li>
					<?php endforeach; ?>
				</ul>
				<a class="uk-position-center-left uk-position-medium uk-dark" href="#" uk-slidenav-previous uk-slider-item="previous"></a>
				<a class="uk-position-center-right uk-position-medium uk-dark" href="#" uk-slidenav-next uk-slider-item="next"></a>
			</div>
			<ul class="uk-slider-nav uk-dotnav uk-flex-center uk-margin"></ul>
		</div>
	</div>

<!--lo mas popular-->
	<div class="uk-container uk-margin-large-top ">
		<h3 class="light-header ">Lo más popular:</h3>
		<hr>
	</div>

	<div class="uk-container uk-margin-top">

		<div class="uk-slider-container" uk-slider>
			<div class="uk-position-relative uk-visible-toggle" tabindex="-1" >
				<ul class="uk-slider-items uk-child-width-1-4@m uk-grid-match uk-grid">

					<?php foreach ($configuracion->featured as $article): ?>
						<li>

							<div class="uk-card uk-card-default uk-card-small">
								<div class="uk-card-media-top uk-cover-container uk-height-small">
									<img src="<?php echo $article->article_images->first()->media->size(300, 150)->url; ?>"
										 alt="" uk-cover>
								</div>

								<div class="uk-card-body">
									<a href="<?php echo $article->url ?>"><h3 id="titulo" class="uk-text-small uk-text-bolder uk-card-title"><?php echo $article->title ?></h3></a>
									<p class="uk-text-small">
										<?= $sanitizer->truncate($article->body, array(
											'type' => 'punctuation',
											'maxLength' => 150,
											'visible' => true,
											'more' => '...'
										)); ?>
									</p>
									<a class=" uk-flex uk-flex-right" href="<?php echo $article->url ?>">Leer más &#10161;</a>
								</div>
							</div>

						</li>
					<?php endforeach; ?>
				</ul>
				<a class="uk-position-center-left uk-position-medium uk-dark" href="#" uk-slidenav-previous uk-slider-item="previous"></a>
				<a class="uk-position-center-right uk-position-medium uk-dark" href="#" uk-slidenav-next uk-slider-item="next"></a>
			</div>
			<ul class="uk-slider-nav uk-dotnav uk-flex-center uk-margin"></ul>
		</div>
	</div>










<!--	<div class="uk-container uk-margin-top">-->
<!--		<div class="uk-child-width-1-4@m uk-grid-match "uk-grid>-->
<!---->
<!--			--><?php //foreach ($configuracion->featured as $article): ?>
<!--				<div>-->
<!--					<div class="uk-card uk-card-default uk-card-small">-->
<!--						<div class="uk-card-media-top uk-cover-container uk-height-small">-->
<!--							<img src="--><?php //echo $article->article_images->first()->media->size(300, 150)->url; ?><!--"-->
<!--								 alt="" uk-cover>-->
<!--						</div>-->
<!---->
<!--						<div class="uk-card-body">-->
<!--							<a href="--><?php //echo $article->url ?><!--"><h3-->
<!--									class="uk-card-title">--><?php //echo $article->title ?><!--</h3></a>-->
<!---->
<!--							<p class="uk-text-small">-->
<!--								--><?php //= $sanitizer->truncate($article->body, array(
//									'type' => 'punctuation',
//									'maxLength' => 150,
//									'visible' => true,
//									'more' => '...'
//								)); ?>
<!--							</p>-->
<!--							<a class="read-more uk-flex uk-flex-right" href="--><?php //echo $article->url ?><!--">Leer más &#10161;</a>-->
<!--						</div>-->
<!--					</div>-->
<!--				</div>-->
<!--			--><?php //endforeach; ?>
<!--		</div>-->
<!--	</div>-->

</region>






