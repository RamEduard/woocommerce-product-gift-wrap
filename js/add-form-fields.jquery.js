(function($) {
    // Add form field jQuery function
    $.fn.addFormField = function(options) {
        // Field var
        var field    = null;
        var label    = null;
        var template = null;

        // This is the easiest way to have default options.
        var settings = $.extend({
            // These are the defaults.
            attr: [],
            type: 'input',
            label: false,
            template: '<div class="row">{label}{field}</div>'
        }, options );

        if ('input' != settings.type && 'select' != settings.type) {
            console.error('Field type not valid. input or select expected. Given {type: ' + settings.type + '}');
            return
        }

        if ('input' == settings.type)
            field = document.createElement('input');
        if ('select' == settings.type)
            field = document.createElement('select');
        if ('textarea' == settings.type)
            field = document.createElement('textarea');

        if (settings.length > 0)
            $(field).attr(settings.attr);

        if (settings.label) {
            label = $(document.createElement('label')).text(settings.label);
            template = settings.template.replace(/\{label\}/, $(label).prop('outerHTML'));
        } else {
            template = settings.template.replace(/\{label\}/, "");
        }

        template = template.replace(/\{field\}/, $(field).prop('outerHTML'));

        return this.append(template);
    };
})(jQuery);
