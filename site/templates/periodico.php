<div class="uk-margin-large-top uk-margin-large-bottom uk-grid">
	<div class="uk-width-1-1 uk-width-1-2-medium uk-container-center">
		<h3 class="underlined-title">
			<?php echo $page->title ?> <a href="<?php echo $page->periodico_pdf->url ?>" class="uk-align-right piwik_download uk-button" style="text-align:center;margin:auto;">Descargar <i class="uk-icon-arrow-circle-down"></i></a>

		</h3>
		<?php
        $issue =  $page;
        if($page->files->count() > 0){
            $issueUrl = $page->files->first()->url;
        } elseif($issue->periodico_pdf){
            $issueUrl = $issue->periodico_pdf->url;
        }

        if($page->image){
            $coverUrl = $page->image->width(490)->url;
        } elseif($issue->periodico_pdf){
            $coverUrl = $issue->periodico_pdf->toImage()->width(490)->url;
            //$issueUrl = $issue->periodico_pdf->url;
        }
        
        ?>
		<a href="<?php echo $issueUrl ?>">
			<img class="periodico-thumb"  style="margin:auto;display:block;" class="uk-width-medium-1-1"
                src="<?php echo $coverUrl ?>">
		</a>
		<!-- <a class="uk-margin-top uk-button" style="text-align:center;margin:auto;"><?php echo $issue->title ?><i class="uk-icon-arrow-circle-down"></i></a> -->
	</div>

	<div class="uk-width-1-2-small uk-width-1-2-medium uk-container-center">
		<h3 class="underlined-title">
			Otros números
		</h3>
		<div class="uk-grid">
			<?php $issues =  $pages->find("template=periodico")->not($page); ?>
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
				<div class="uk-width-1-2 uk-width-1-3-medium">
					
					<a class="periodico-link"  href="<?php echo $issueUrl ?>">
						<img class="periodico-thumb" style="margin:auto;display:block;" class="uk-width-medium-1-1" 
                            src="<?php echo $coverUrl ?>">
					    
						<p class="uk-margin-top" style="text-align:center;margin:auto;">
                            <?php echo $issue->title ?>
                        </p>
					</a>
				</div>
			<?php endforeach ?>
		</div>

	</div>
	
</div>
