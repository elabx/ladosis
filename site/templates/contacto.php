<?php if($config->ajax): ?>

  <?php /* if($input->post){
	   if ($captcha->verifyResponse() === true){
	   $mail = wireMail();
	   $mail->to(array('eduardo@croma.mx', 'ladosisinformativa@gmail.com'))->from("{$pages->get('name=configuracion')->config_contact_mail}");
	   $mail->subject('Contacto desde LaDosis.org');
	   $mail->bodyHTML(wireRenderFile('mail/contact_mail.php', array('i' => $input->post)));
	   $mail->send();
	   }
	   }*/ ?>
  
<?php else: ?>
<div class="main-container uk-grid">


  <div class="uk-width-medium-10-10 uk-margin-bottom">

    <h3 class="underlined-title">
      <?php echo $page->title ?>
    </h3>
    
  </div>

  <div class="uk-width-medium-10-10 uk-margin-bottom">
    <p>Escríbenos un correo:</p>
    <a href="mailto:ladosisinformativa@gmail.com">ladosisinformativa@gmail.com</a>
    <p>O envíanos un mensaje por aquí:</p>
  </div>
  <?php echo $forms->embed('contacto'); ?>

  <!-- <form id="contact-form" class="uk-width-10-10 uk-form">
       
       
       <fieldset>
       <legend class="uk-margin-top">Nombre</legend>
       <div class="uk-form-row">
       <input required class="uk-width-10-10" name="fullName" type="text" placeholder=""></div>
       <legend class="uk-margin-top">Mail</legend>
       <div class="uk-form-row">
       <input name="mail" class="uk-width-10-10" type="mail" placeholder=""></div>
       <legend class="uk-margin-top">Mensaje</legend>
       <div class="uk-form-row">
       <textarea required name="message" rows="10" class="uk-width-10-10" placeholder="Tu mensaje..."></textarea>
       </div>
       <div class="uk-form-row">
       <?php echo $captcha->render()  ?>
       
       <button class="uk-button">Enviar</button>

       </fieldset>
       </form> -->

</div>
<?php endif ?>
