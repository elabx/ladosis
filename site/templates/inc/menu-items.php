<?php $menu = $pages->get("name=configuracion")->menu ?>
<?php foreach($menu as $menuItem): ?>
	<?php if($menuItem->menu_render_submenu): ?>
		<li class="uk-parent <?=$page == $menuItem->menu_item ? ' selected "' : '"'?> <?php echo  $navbar ? ' data-uk-dropdown ': '' ?>">
				   <a href="<?php echo $menuItem->menu_item->url; ?>">
			<?php echo $menuItem->title != '' ? $menuItem->title : $menuItem->menu_item->title ?>
      </a>
      <?php if($navbar): ?>
		  <div class="uk-dropdown <?php echo $navbar ? 'uk-dropdown-navbar uk-dropdown-bottom' : ''?>">
      <?php endif ?>
      <ul class="<?php echo $navbar ? 'uk-nav uk-nav-navbar' : ($sidebar  ? 'uk-nav-sub' : '' ) ?>">
		  <?php if($menuItem->menu_item->name == "secciones"): ?>
			  <li><a href="<?php echo $pages->get("name=secciones")->url; ?>">Todos los artículos</a></li>
		  <?php endif ?>
		  <?php foreach($menuItem->menu_item->children as $submenuItem): ?>
			  <li><a href="<?=$submenuItem->url?>"><?=$submenuItem->title?></a></li>
		  <?php endforeach ?>
      </ul>
      <?php if($navbar): ?>
		  </div>
      <?php endif ?>
		</li>
	<?php else: ?>
		<li class="<?=$page == $menuItem->menu_item ? 'selected ' : ''?> <?=$menuItem->menu_item->highlight ?  'highlighted ' : ''?>">
			<a href="<?=$menuItem->menu_item->url?>">
				<?php echo $menuItem->title != '' ? $menuItem->title : $menuItem->menu_item->title ?>
			</a>
		</li>
	<?php endif ?>
<?php endforeach ?>
