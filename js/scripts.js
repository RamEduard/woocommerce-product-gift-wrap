(function($) {

    $(document).ready(function() {

        $.fn.disableFormFields = function() {
            this.find('input').attr('disabled', 'disabled');
        };
        $.fn.enableFormFields = function() {
            this.find('input').removeAttr('disabled');
        };

        $('input[name=gift_wrap]').change(function() {
            if ($(this).prop('checked')) {
                $('.extra-fields-container').removeClass('unchecked');
                    $('.extra-fields-container').enableFormFields();
            } else {
                $('.extra-fields-container').addClass('unchecked');
                $('.extra-fields-container').disableFormFields();
            }
        });

    });
})(jQuery);
