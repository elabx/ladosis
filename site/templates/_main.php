<?php namespace ProcessWire;


\Less_Autoloader::register();

//Compile UIKit

$less_files = array();
$uikitFile = $config->paths->templates . 'css/uikit-custom.less';

$less_files = array(
    $uikitFile => $config->path->templates . 'css/uikit-custom.css'
);

$uikitOptions = array(
    'cache_dir'    => $config->paths->assets . 'cache/less/',
    'output'       => $config->paths->templates . 'css/build.css',
    'relativeUrls' => true
);

$uikitCustomFilename = \Less_Cache::Get($less_files, $uikitOptions);



?>

<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">


    <link rel="stylesheet" href="<?= $urls->templates ?>css/build.css" class="href">
    <link rel="stylesheet" href="<?= $urls->templates ?>styles/main.css" class="href">
    <script src="<?= $urls->templates ?>js/uikit.min.js"></script>
    <script src="<?= $urls->templates ?>js/uikit-icons.min.js"></script>



<!--    <!- UIkit CSS -->
<!--    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/uikit@3.16.19/dist/css/uikit.min.css" />-->
<!--    <link rel="stylesheet" type="text/css" href="/site/templates/styles/main.css">-->
<!---->
<!--    <!- UIkit JS -->
<!--    <script src="https://cdn.jsdelivr.net/npm/uikit@3.16.19/dist/js/uikit.min.js"></script>-->
<!--    <script src="https://cdn.jsdelivr.net/npm/uikit@3.16.19/dist/js/uikit-icons.min.js"></script>-->
    <title>Document</title>
</head>


<body>
<?php

use ProcessWire\RepeaterPage;

$configuracion = $pages->get("name=configuracion");
?>

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

<!--Menu-->
<div class="uk-container uk-margin-bottom" >
    <div>
        <nav class="navbar-ladosis" uk-navbar>
            <div class="uk-navbar-left uk-margin-small-left">
                <ul class="uk-navbar-nav">
                    <?php $menu = $configuracion->menu ?>
                    <?php foreach($menu as $menuItem): ?>

                        <?php if($menuItem->menu_render_submenu): ?>
                            <li>
                                <a  href="">
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
                                <a href="<?= $menuItem->menu_item->url; ?>">
                                    <?php
                                    if($menuItem->title){
                                        echo $menuItem->title;
                                    }else{
                                        // TODO Fix page field name
                                        echo $menuItem->menu_item->title;
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

    <main id="content"></main>

<footer class="uk-container-center uk-container uk-margin-large-top">
    <div class="uk-grid">
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

        <div class="uk-margin-large uk-width-1-1">
            <h5 style="text-align:center;">La Dosis -  Noticias de la comunidad psicoactiva © <?=date("Y",time())?></h5>
            <iframe data-aa="1289579" src="//ad.a-ads.com/1289579?size=320x50" scrolling="no" style="width:320px; height:50px; border:0px; padding:0; overflow:hidden;margin:auto;display:block;" allowtransparency="true"></iframe>
        </div>
    </div>


</footer>


</body>

</html>

