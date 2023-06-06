<?php
use GeoIp2\Database\Reader;
$path = "inc/GeoLite2-City.mmdb";

?>
<?php $type =  $pages->get("name=lateral-articulos-y-secciones") ?>

<?php

$reader = new Reader($path);
try{
    $record = $reader->city($session->getIP());
} catch(Exception $e){
    //$log->save("ipdebug", $e);
}
$estados = "";

foreach($record->subdivisions as $sub){
    $estados .= $sub->isoCode;
    if(!end($record->subdivisions)->isoCode == $sub->isoCode){
        $estados .= "|";
    }
}
//$log->save("ip-ads", $record->country->isoCode . " : " . $estados . " : " . $session->getIP());

//$geolocated = $pages->find("publicidad_estado.title=$estados");
//bd($record->country->isoCode);
$geolocated = $pages->find("publicidad_pais.title={$record->country->isoCode}");
$geolocated->append($pages->find("publicidad_estado.title=$estados")->not($geolocated));

if($geolocated->count() > 0){
    //$log->save("ip-ads", "gelocated ads! :" . $geolocated->implode(", ","title"));
	$geolocated->append($pages->find("nacional=true"));
} else{
	$allEstados = $pages->find("template=estado, include=all");
	
	/* $allEstados = $allEstados->implode(function($item){
	   if($item == $allEstados->last()){
	   return "publicidad_estado=$item";
	   }else{
	   return "publicidad_estado=$item,";
	   }
	   
	   }); */
	$geolocated = $pages->find("nacional=true, publicidad_ubicacion=$type")
		->append($pages->find("nacional=true")->slice(0,2));
}

?>

<?php if($geolocated->count() > 0): ?>
	<?php foreach($geolocated as $anuncio):  ?>
		
		<?php if($anuncio->publicidad_ubicacion == $type): ?>
			<a class="ad-single
                      uk-text-center
					  uk-display-block 
					  uk-margin-top" href="<?php echo $anuncio->publicidad_url ?>">
				<img class=""
							src="<?php echo $anuncio->publicidad_img->width(320)->url ?>">
			</a>
		<?php endif ?>
		
	<?php endforeach ?>
<?php endif ?>


<?php foreach($pages->get("name=publicidad,publicidad_estado.title=$estados")->children("id!={$geolocated}") as $anuncio):  ?>
	
	<?php if($anuncio->publicidad_ubicacion == $type): ?>
		
		<a class="ad-single uk-text-center  uk-display-block uk-margin-top"
			data-campaign="<?=$anuncio->title?>"
			href="<?php echo $anuncio->publicidad_url ?>">

			<img class=""
				src="<?php echo $anuncio->publicidad_img->width(320)->url ?>">
		</a>
	<?php endif ?>
	
<?php endforeach ?>





