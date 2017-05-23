var bg, body, loginBox, loginForm, mainwrap;
jQuery(document).ready(function($) {
    bg = $('.bg');
    body = $('body');
    loginBox = $('.loginBox');
    loginForm = $('#loginForm');
    mainwrap = $("#mainwrap");
    if (mainwrap.length === 0){
        bg.fadeIn(2000);
        loginBox.fadeIn(1000);
    }

    loginForm.submit(function(e) {
        e.preventDefault();
        $.post("api.php", loginForm.serialize(), function(data) {
            if (data !== 'ERROR') {
                console.log("successful!");
                var html = body.html();
                body.html(data + html);

                var loginBox = $('.loginBox');
                if (loginBox.css('display') !== 'none') {
                    console.log("Hiding login box.");
                    loginBox.hide("slide", {direction: "up"}, 1000);
                    loginBox.remove();
                }
            }
        });
    });

});