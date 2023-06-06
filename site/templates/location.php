<?php
require 'inc/geoip2.phar';
use GeoIp2\Database\Reader;

$path = "inc/GeoLite2-City.mmdb";

$reader = new Reader($path);

// Replace "city" with the appropriate method for your database, e.g.,
// "country".
$record = $reader->city($session->getIP());

echo $session->getIP() . PHP_EOL;
echo "<pre>";
print_r( $record->country->isoCode) . PHP_EOL;
echo "</pre>";

print_r($session->getIP(), TRUE);
echo "<pre>";
print_r($record);
$divisions = "";
foreach($record->subdivisions as $sub){
  $divisions .= $sub->isoCode;
  if(!end($record->subdivisions)->isoCode == $sub->isoCode){
    $divisions .= "|";
  }
  print_r( $sub->isoCode . PHP_EOL);  
}

echo "</pre>";
print_r($record->city->name);
//print_r($record->city->name);
$log->save("custom", $record->city->name . " : " . $divisions . " : " . $input->get->name . ":" . $record->location->latitude . ", " . $record->location->longitude  );


?>
