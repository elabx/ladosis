$(document).ready(function(){
		var galeria = new ModuloBox({
				// options
				mediaSelector : '.galeria',
				controls : ['zoom', 'play', 'fullScreen', 'download','close'],
				
				scrollToZoom  : true,
				shareButtons  : ['facebook', 'googleplus', 'twitter', 'pinterest', 'linkedin'],
		});

		// initialize the instance	
		galeria.init();

        var imagenesArticulo = new ModuloBox({
				// options
				mediaSelector : '.article-body a',
				controls : ['zoom', 'play', 'fullScreen', 'download','close'],
				
				scrollToZoom  : true,
				shareButtons  : ['facebook', 'googleplus', 'twitter', 'pinterest', 'linkedin'],
		});

        imagenesArticulo.init();
        

    $("a.ad-single").click(function(){
	ga('send', {
	    hitType: 'event',
	    eventCategory: "Publicidad",
	    eventAction: 'click',
	    eventLabel: $(this).data("campaign")
	});
    });
    
  if(Cookies.get("over18") == undefined){
    Cookies.set("over18", "false", { expires: 365 });
    $("#over18-cover").addClass("unconfirmed");
  }

  if(Cookies.get("over18") == "false"){
    $("#over18-cover").addClass("unconfirmed");
  }

  $("#confirmAge").click(function(e){
    Cookies.set("over18", "true", { expires: 365 });
    $("#over18-cover").addClass("confirmed");
    $("#over18-cover").removeClass("unconfirmed");
  });
  
  $("#contact-form button").click(function(e){
    e.preventDefault();
    sendForm();
  });
  
  $("#contact-form button").prop('disabled', true);
  
  $("#homepage-slider").slick({
    arrows: true,
    adaptiveHeight:true,
    autoplay:true,
    autoplaySpeed: 4000
  });

  $("#slider-publicidad").slick({
    arrows: false,
    autoplay:true,
    autoplaySpeed: 5000
  });
  
  
  $(".video-container").fitVids();
});

function sendForm(){
  $.ajax({
    method: "post",
    url:"/contacto/",
    data: $("#contact-form").serialize(),
    success: function(){
      var m = "<div class='uk-alert-success'>Gracias por enviar tu mensaje</div>";
      $("#contact-form").append(m);
    },
    error: function(){
      var m = "<div class='uk-alert-error'>Tu mensaje no ha podido ser enviado, vuelve a intenar o escríbenos un correo electrónico</div>";
      $("#contact-form").append(m);
    }
  });
}

function enableBtn(){
  $("#contact-form button").prop('disabled', false);
}


