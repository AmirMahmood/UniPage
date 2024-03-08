/**
 * Created by Sina on 2/9/16.
 */

 $(document).ready(function(){

    if ($('#myBtn').length) {
    
    var scrollTrigger = 100, // px
        backToTop = function () {
            var scrollTop = $(window).scrollTop();

            if (scrollTop > scrollTrigger) {
                $('#myBtn').addClass('show');
            } else {
                $('#myBtn').removeClass('show');
            }
        };
    backToTop();
    $(window).on('scroll', function () {
        backToTop();

    });
    $('#myBtn').click(function (e) {
        
        e.preventDefault();
        $('html,body').animate({
            scrollTop: 0
        }, 700);
    });
};
  // Add smooth scrolling to all links
  $("a").on('click', function(event) {

    // Make sure this.hash has a value before overriding default behavior
    if (this.hash !== "") {
      // Prevent default anchor click behavior
      event.preventDefault();

      // Store hash
      var hash = this.hash;

      // Using jQuery's animate() method to add smooth page scroll
      // The optional number (800) specifies the number of milliseconds it takes to scroll to the specified area
      $('html, body').animate({
        scrollTop: $(hash).offset().top
      }, 800, function(){
   
        // Add hash (#) to URL when done scrolling (default click behavior)
        window.location.hash = hash;
      });
    } // End if



  });
});



 $(window).load(function () {

    var theWindow = $(window),
    header = $("#header"),
    box = $("#box"),
    content = $("#content"),
    nav = $("#nav"),
    dsd = $("#dsd"),
    menu = $("#credit ul"),
    ham_icon = $("#ham_icon");
    nav_ham_icon = $("#nav_ham_icon");

    nav_menu = $("#credit");

    menu_is_open = true;

    function resizeBg() {
        if (theWindow.width() > 800)
            header.css("height", theWindow.height() - nav.height());
        else
            header.css("height", theWindow.height());


        if (theWindow.width() > 320 && menu.is(":hidden")) {
            menu.removeAttr('style');
        }
    }

    menu.removeAttr('style');

    // $(window).scroll(function (event) {
    //     var scroll = $(window).scrollTop();

    //     if(scroll>='500') {
    //         if (menu_is_open) {
    //             nav_menu.css({top: '-50px'});
    //             menu_is_open = false;
    //             alert("OPEN");
    //         }
    //     }
    //     else if(scroll>=0 && scroll <'100') {
    //         if (!menu_is_open) {
    //             setInterval(function(){ nav_menu.animate({top: '0px'}); }, 10000);
    //             menu_is_open = true;
    //             alert("CLOSE");
    //         }
    //     }
    // });
    // setInterval(function(){ nav_menu.animate({top: '0px'}); }, 1000);

    // $(window).scroll(function (event) {
    //     var scroll = $(window).scrollTop();
    //     if(scroll==0 && scroll <=100){
    //         // setInterval(function(){ nav_menu.animate({top: '0px'}); }, 1000);
    //         // menu_is_open = true;
    //         // alert("open");
    //         // nav_menu.css({top: '0px'});
    //         nav_menu.animate({top: '0px'});

    //     }
    //     else{
    //         nav_menu.animate({top: '-50px'});            
    //         // menu_is_open = false;
    //         // alert("close");
    //     }
    // });
    



    // theWindow.resize(resizeBg).trigger("resize");

    ham_icon.click(function (e) {
        e.preventDefault();
        menu.slideToggle();
    });
    
    // nav_ham_icon.click(function (e) {
    //     e.preventDefault();
    //     if (menu_is_open) {
    //         nav_menu.animate({top: '-50px'});;
    //         nav_menu.animate({top: '-50px'});;
    //         menu_is_open = false;
    //     }
    //     else {
    //         nav_menu.animate({top: '0px'});;
    //         menu_is_open = true;
    //     }
    // });

    

    //font scale

    //$("#header_logo").fitText(0.6);
    //$("#head1").fitText(1.3);
    //$("#head2").fitText(1.3);

    // Used to toggle the menu on small screens when clicking on the menu button

});

function myFunction() {
    var nav = $("#navSmall");
    if(nav.hasClass("w3-show")){
        nav.removeClass("w3-show");
    }
    else {
        nav.addClass("w3-show"); 
    }

    // var x = document.getElementById("navSmall");
    // if (x.className.indexOf("w3-show") == -1) {
    //     x.className += " w3-show";
    // } else { 
    //     x.className = x.className.replace(" w3-show", "");
    // }
}