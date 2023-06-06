<?php

function getVideoId($url) {
  $parts = parse_url($url);

  // Make sure $url had a query string
  if (!array_key_exists('query', $parts))
    return null;

  parse_str($parts['query']);

  // Return the 'v' parameter if it existed
  return isset($v) ? $v : null;
}

function wordLimiter(HookEvent $event){
  $field = $event->arguments[0]; // first argument
  $limit = $event->arguments[1];
  $endstr = isset($event->arguments[2]) ? $event->arguments[2] : ' …';
  $page = $event->object; // the page
  $str = $page->get($field);

  $str = strip_tags($str);
  if(strlen($str) <= $limit) return;
  $out = substr($str, 0, $limit);
  $pos = strrpos($out, " ");
  if ($pos>0) {
    $out = substr($out, 0, $pos);
  }
  return $event->return = $out .= $endstr;
}

wire()->addHook("Page::wordLimiter", null, "wordLimiter");

function checkIfActivePage($page){

}

?>
