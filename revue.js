jQuery(function($){

    function validateEmail(email) {
        var re = /^(([^<>()\[\]\\.,;:\s@"]+(\.[^<>()\[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;
        return re.test(email);
    }

    $('.revue-subscribe button').click(function() {
        var container = $(this).parent();
        var $fldEmail = $('input[name="revue_email"]', container);

        var $fldFirstName = $('input[name="revue_first_name"]', container);
        var $fldLastName = $('input[name="revue_last_name"]', container);

        var email = $fldEmail.val();
        var firstName = $fldFirstName.val();
        var lastName = $fldLastName.val();

        if (!validateEmail(email)) {
            $fldEmail.css('border', '1px solid red');
            return;
        }

        var data = {
            action : 'revue_subscribe',
            email : email,
            first_name : firstName,
            last_name : lastName,
        };

        $.post(revue_ajaxurl, data, function(res) {
            container.html(res.thank_you);
        });
    });
});