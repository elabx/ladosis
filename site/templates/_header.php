<?php
//$modules->get("AdminLinksInFrontend")->render();

$captcha = $modules->get("MarkupGoogleRecaptcha");
?>

<?php

setlocale(LC_TIME, "es_ES");
$configuracion = $pages->get("name=configuracion");

?>
<?php include("inc/helpers.php") ?>
<!doctype html>
<html>
	<head>
		<meta property="fb:pages" content="1433619063615731" />
		
		<?php echo $captcha->getScript(); ?>
		<link type="text/css" rel="stylesheet" href="<?php echo $config->urls->templates ?>css/uikit.min.css">
		<link type="text/css" rel="stylesheet" href="<?php echo $config->urls->templates ?>css/components/search.css">
		<link type="text/css" rel="stylesheet" href="<?php echo $config->urls->templates ?>dist/modulobox.min.css">
		<link type="text/css" rel="stylesheet" href="<?php echo $config->urls->templates ?>css/slick.css">
		<link type="text/css" rel="stylesheet" href="<?php echo $config->urls->templates ?>css/slick-theme.css">
		<link type="text/css" rel="stylesheet" href="<?php echo $config->urls->templates ?>css/main.css">

		



		<meta name="viewport" content="width=device-width, initial-scale=1">
		<meta property="og:site_name" content="La Dosis - Noticias de la comunidad psicoactiva"/>

		<?php if ($page->template == "articulo" || $page->template == "carton"): ?>
            <title>La Dosis - <?=$page->seo_title ? $page->seo_title : $page->title?></title>
            <meta property="description" name="<?=$page->seo_description ? $page->seo_description : $sanitizer->truncate($page->body)?>">
			<meta property="og:type" content="article"/>
			<meta property="og:title" content="<?php echo $page->title ?>"/>
            
			<meta property="og:description" content="<?php echo $page->seo_description ? $page->body : '' ?>"/>
			<meta property="og:url" content="<?php echo $page->httpUrl ?>"/>
			<meta property="og:image" content="<?php 
											   if($page->article_images->count() > 0){
												   if($page->article_images[0]->media->width > 1200){
													   echo $page->article_images[0]->media->size(600,315)->httpUrl;
												   } else {
													   echo $page->article_images[0]->media->httpUrl;
												   }
											   }
											   ?>"/>

			<meta name="twitter:card" content="summary_large_image">
			<meta name="twitter:title" content="<?= $page->title ?>">   
			<meta name="twitter:site" content="@LaDosisMx">
			<meta name="twitter:creator" content="@LaDosisMx">
			<meta name="twitter:image" content="<?php 
												if($page->article_images->count() > 0){
													if($page->article_images[0]->media->width > 1200){
														echo $page->article_images[0]->media->width(600,315)->httpUrl;
													} else {
														echo $page->article_images[0]->media->httpUrl;
													}
												}
												?>"/>


			
		<?php endif ?>
		


		<?php if ($page->template == "ladosis-tv-video"): ?>
			<meta property='og:video' content="<?php echo 'https://youtube.com/v/' . getVideoId($page->tv_video_plain_url) ?>"  />
			<meta property="og:url" content="<?php echo $page->tv_video_plain_url ?>"/>

			<meta property="og:type" content="video.other"/>

		<?php endif ?>

		

		<link rel="manifest" href="/manifest.json" />
		<script src="https://cdn.onesignal.com/sdks/OneSignalSDK.js" async=""></script>
		<script>
		var OneSignal = window.OneSignal || [];
		OneSignal.push(["init", {
			appId: "9f09192c-8e13-4107-b348-eaf82974b4fb",
			notifyButton: {
                enable: true,
                size: 'medium',
				prenotify:true,
                showCredit: false, /* Hide the OneSignal logo */
				text:{
			        'tip.state.unsubscribed': 'Suscríbete a las notificaciones',
           	        'tip.state.subscribed': "Estas suscrito!",
       	 	        'tip.state.blocked': "Has bloqueado las notificaciones",
                    'message.prenotify': 'Click para suscribirte a las notificaciones',
                    'message.action.subscribed': "¡Gracias por suscribirte!",
                    'message.action.resubscribed': "¡Gracias por suscribirte!",
                    'message.action.unsubscribed': "No volverás a recibir notificaciones",
                    'dialog.main.title': 'Manage Site Notifications',
                    'dialog.main.button.subscribe': 'Suscribirse',
                    'dialog.main.button.unsubscribe': 'Cancelar suscripción',
                    'dialog.blocked.title': 'Desbloquear notificaciones',
                    'dialog.blocked.message': "Sigue estas instrucciones para habilitar las notificaciones:"
				},
                colors: { // Customize the colors of the main button and dialog popup button
                    'circle.background': 'rgb(221, 134, 53)',
                    'circle.foreground': 'white',
                    'badge.background': 'rgb(221, 134, 53)',
                    'badge.foreground': 'white',
                    'badge.bordercolor': 'white',
                    'pulse.color': 'white',
                    'dialog.button.background.hovering': 'rgb(188, 113, 43)',
                    'dialog.button.background.active': 'rgb(140, 87, 37)',
                    'dialog.button.background': 'rgb(221, 134, 53)',
                    'dialog.button.foreground': 'white'
                },
			}
		}])
		
		</script>
	</head>

	
	<body> 
		
		<div id="fb-root"></div>
		<script>(function(d, s, id) {
			var js, fjs = d.getElementsByTagName(s)[0];
			if (d.getElementById(id)) return;
			js = d.createElement(s); js.id = id;
			js.src = "//connect.facebook.net/en_US/sdk.js#xfbml=1&version=v2.8&appId=368955370106873";
			fjs.parentNode.insertBefore(js, fjs);
		}(document, 'script', 'facebook-jssdk'));</script>
		
		<?php //echo $modules->isInstalled("AdminLinksInFrontend") ? $modules->get("AdminLinksInFrontend")->render() : "asdasdads";?>
		

		<div id="over18-cover" style="">
			<div class="uk-grid">
				<div class="uk-margin-large-top uk-width-1-1">
					<p>El contenido de este sitio es para mayores de 18 años</p>
					<p>¿Estas de acuerdo en entrar?</p>
					<button id="confirmAge" class="uk-button-large uk-button" type="button">Entrar</button>
					<button id="exitAge" class="uk-button-large uk-button" type="button">Salir</button>
				</div>
			</div>
		</div>
		
		<div class="uk-container uk-container-center">
			<div class=" uk-grid ">

				<div class="uk-width-small-1-1
							uk-width-medium-2-10
							uk-flex 
							uk-flex-middle
							uk-flex-center">
					<a class="uk-hidden-small top-logo" href="<?php echo $pages->get("/")->url ?>">
						<img class=""
									src="<?php echo $pages->get("name=configuracion")->logo->width(350)->url ?>">
					</a>
				</div>
				
				<div class="uk-width-small-1-1 uk-width-medium-8-10">

                    <div id="slider-publicidad">

						<?php 
						use GeoIp2\Database\Reader;
						$path = "inc/GeoLite2-City.mmdb";
						
						$reader = new Reader($path);
						try{
							$record = $reader->city($session->getIP());
						} catch(Exception $e){
							$log->save("ipdebug", $e);
						}
						$estados = "";
                        
						foreach($record->subdivisions as $sub){
							$estados .= $sub->isoCode;
							if(!end($record->subdivisions)->isoCode == $sub->isoCode){
								$estados .= "|";
							}
						}
						
						$geolocated = $pages->find("publicidad_pais.title={$record->country->isoCode}");
						$geolocated->append($pages->find("publicidad_estado.title=$estados, publicidad_ubicacion=encabezado"));
						
						if($geolocated->count() > 0){
                            $log->save("ip-ads-encabezado", "gelocated ads! : {$geolocated}");
							$log->save("ip-ads-encabezado", print_r($record, true));
                            $log->save("ip-ads-encabezado", print_r($city, true));
							$geolocated->append($pages->find("nacional=true"));
						} else{
							//$allEstados = $pages->find("template=estado, include=all");
							
							/* $allEstados = $allEstados->implode(function($item){
							   if($item == $allEstados->last()){
							   return "publicidad_estado=$item";
							   }else{
							   return "publicidad_estado=$item,";
							   }
							   
							   }); */
							$geolocated = $pages->find("nacional=true, publicidad_ubicacion=encabezado")
                                ->append($pages->find("nacional=true")->slice(0,2));
						}
						?>

						<?php if($geolocated->count()): ?>
							<?php /*$anuncioGeo = $geolocated->getRandom();*/ ?>
							<?php foreach($geolocated->shuffle()  as $anuncio):?>
								<a class="ad-single"
									      data-campaign="<?=$anuncio->title?>"
									      href="<?php echo $anuncio->publicidad_url ?>">
									<img class="img-responsive"
												src="<?php echo $anuncio->publicidad_img->url ?>">
								</a>
							<?php endforeach ?>
						<?php else: ?>
							<?php
							$anuncios = $pages->find("publicidad_ubicacion=encabezado, publicidad_estado.title=$estados"); 
							if($anuncios->count() == 0){
								$anuncios = $pages->find("publicidad_ubicacion=encabezado,nacional=1");
							}
							?>
							<?php foreach($anuncios  as $anuncio):?>
								<a class="ad-single"
									data-campaign="<?=$anuncio->title?>"
									href="<?php echo $anuncio->publicidad_url ?>">
									<img class="img-responsive"
												src="<?php echo $anuncio->publicidad_img->url ?>">
								</a>
							<?php endforeach ?>
						<?php endif ?>
                        
                        
						
					</div>

					<!-- <div data-mantis-zone="header"></div> -->
                    
				</div> 
                <div class="uk-visible-small uk-width-1-1">
                    <div data-mantis-zone="header"></div>
                </div>
			</div>
			
			
			
			<div class="uk-flex uk-flex-middle uk-grid">

				<div class="uk-visible-small uk-width-1-2">
					<a class="top-logo" href="<?php echo $pages->get("/")->url ?>">
						<img class=""
									src="<?php echo $pages->get("name=configuracion")->logo->width(350)->url ?>">
					</a>
				</div>

				<div class="uk-width-1-2  uk-width-medium-1-1">
					<nav class="navbar-ladosis uk-navbar">
						
						<ul class="uk-hidden-small uk-navbar-nav">
							<?php wireIncludeFile("inc/menu-items.php", array("navbar" => true)) ?>
						</ul>
						
						
						
						<div class="uk-navbar-flip">
							<div class="uk-navbar-content">
								<form action="<?=$pages->get("name=buscar")->url?>" class="uk-hidden-small uk-search" data-uk-search>
									<input class="uk-search-field" value="<?=$input->get->q?>" name="q" type="text" placeholder="Buscar">
								</form>
								
								<a href="#sidebar-menu" class="uk-navbar-toggle uk-visible-small" data-uk-offcanvas>
									
								</a>
							</div>
							
						</div>
					</nav>
					
				</div>

			</div>

			

			<div id="sidebar-menu" class="uk-offcanvas">
				<div class="uk-offcanvas-bar">

					<div class="uk-grid uk-visible-small">
						<div class="uk-width-1-1">
							<form action="<?=$pages->get("name=buscar")->url?>" class=" uk-search" data-uk-search>
								<input class="uk-search-field" value="<?=$input->get->q?>" name="q" type="text" placeholder="Buscar">
							</form>
						</div>
					</div>
					
					<ul class="uk-margin-top uk-nav uk-nav-parent-icon">
						<?php  wireIncludeFile("inc/menu-items.php", array("sidebar" => true)) ?>
					</ul>

					<ul>
						<form action="https://www.paypal.com/cgi-bin/webscr" method="post" target="_top">
							<input type="hidden" name="cmd" value="_s-xclick">
							<input type="hidden" name="hosted_button_id" value="93UH6BXV8VG6Q">
							<input type="image" src="https://www.paypalobjects.com/es_XC/MX/i/btn/btn_donateCC_LG.gif" border="0" name="submit" alt="PayPal, la forma más segura y rápida de pagar en línea.">
							<img alt="" border="0" src="https://www.paypalobjects.com/es_XC/i/scr/pixel.gif" width="1" height="1">
						</form>
					</ul>
				</div>
			</div>

