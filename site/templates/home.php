<?php namespace ProcessWire; ?>
<?php
$configuracion = $pages->get("name=configuracion");
?>
<region id="content">
	<div class="uk-container uk-margin">
		<div class="" uk-grid>
			<div class="uk-width-1-4@m">
				<img src="<?php echo $configuracion->logo->width(250)->url ?>">

			</div>

			<div class="uk-width-3-4@m">
				<h1 class="uk-text-center">Aqui va la publicidad</h1>
			</div>

		</div>
	</div>

	<div class="uk-container" >
			<div>
				<nav class="uk-navbar-container" uk-navbar>
					<div class="uk-navbar-center">
						<ul class="uk-navbar-nav">
							<?php $menu = $configuracion->menu ?>
							<?php foreach($menu as $menuItem): ?>

								<?php if($menuItem->menu_render_submenu): ?>
									<li>
										<a  href="#">
											<?php
											/** @var RepeaterPage $menuItem  */
											if($menuItem->title){
												echo $menuItem->title;
											}else{
												// TODO Fix page field name
												echo $menuItem->menu_item->title;
											}
											// How to do with $menuItem->if()
											// Can also be done with a hook, $wire->addNewMethod('Page::getMenuTitle', function(){)
											// echo $page->getMenuTitle();
											?>
										</a>
										<div class="uk-navbar-dropdown">
											<ul class="uk-nav uk-navbar-dropdown-nav">
												<?php foreach($menuItem->menu_item->children as $submenuItem): ?>
													<li>
														<a href="<?=$submenuItem->url?>">
															<?=$submenuItem->title?>
														</a>
													</li>
												<?php endforeach ?>
											</ul>
										</div>
									</li>
								<?php else: ?>

									<li class="">
										<a href="">
											<?php
											if($menuItem->title){
												echo $menuItem->title;
											}else{
												// TODO Fix page field name
												echo $menuItem->menu_item->title;
												bd($menuItem->menu_item);
											}
											?>
										</a>
									</li>
								<?php endif ?>
							<?php endforeach ?>
						</ul>
					</div>
				</nav>
			</div>
	</div>


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

	<div class="uk-container uk-margin-large-top ">
		<h3 class="light-header ">Notas recientes:</h3>
		<hr>
	</div>

	<div class="uk-container uk-margin-top">

		<div class="uk-slider-container" uk-slider>
			<div class="uk-position-relative uk-visible-toggle uk-light" tabindex="-1" >
				<ul class="uk-slider-items uk-child-width-1-4@m uk-grid-match uk-grid">

					<?php foreach ($configuracion->featured as $article): ?>
						<li>

							<div class="uk-card uk-card-default uk-card-small">
								<div class="uk-card-media-top uk-cover-container uk-height-small">
									<img src="<?php echo $article->article_images->first()->media->size(300, 150)->url; ?>"
										 alt="" uk-cover>
								</div>

								<div class="uk-card-body">
									<a href="<?php echo $article->url ?>"><h3 id="titulo"
																			  class="uk-card-title"><?php echo $article->title ?></h3></a>

									<a class="read-more uk-flex uk-flex-right" href="<?php echo $article->url ?>">Leer más &#10161;</a>
								</div>
							</div>

						</li>
					<?php endforeach; ?>
				</ul>
				<a class="uk-position-center-left uk-position-small uk-hidden-hover" href="#" uk-slidenav-previous uk-slider-item="previous"></a>
				<a class="uk-position-center-right uk-position-small uk-hidden-hover" href="#" uk-slidenav-next uk-slider-item="next"></a>
			</div>
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






