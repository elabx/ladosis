<div class="uk-width-1-1 uk-container-center">
	<h1 class="uk-margin-large-top underlined-title">
		Los números
	</h1>
	<div class="uk-grid">
		<?php $issues =  $pages->find("template=periodico, sort=-sort")->not($page); ?>
		<?php foreach($issues as $issue): ?>

             <?php 
                 //$issue =  $page;
                 if($issue->files->count() > 1){
                     $issueUrl = $issue->files->first()->url;
                 } elseif($issue->periodico_pdf){
                     $issueUrl = $issue->periodico_pdf->url;
                 }

             if($issue->image){
                 $coverUrl = $issue->image->width(490)->url;
             } elseif($issue->periodico_pdf){
                 $coverUrl = $issue->periodico_pdf->toImage()->width(490)->url;
                 //$issueUrl = $issue->periodico_pdf->url;
             }
             ?>

			<div class="uk-width-1-1 uk-width-medium-1-3 uk-margin-top">
				
				<a href="<?php echo $issueUrl ?>">
					<img class="periodico-thumb" style="margin:auto;display:block;" class="uk-width-medium-1-1" 
                        src="<?php echo $coverUrl ?>">
				</a>
				<p class="uk-margin-top" style="text-align:center;margin:auto;">
                <?=$issue->title?></p>
			</div>
		<?php endforeach ?>
	</div> 

</div>
