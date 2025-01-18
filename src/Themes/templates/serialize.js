(function ($) {

    $.fn.serialize = function (options) {
        let data = $.param(this.serializeArray(options));

        if ($(this).attr('data-docuid')) data = data + '&docuid=' +$(this).attr('data-docuid');

        $(':disabled[name]', this).each(function () {
            data = data + '&' + this.name + '=' + $(this).val();
        });

        $('option:selected', this).each(function () {
            const txt = $(this).text();
            const el = $(this).closest('select').attr('name');
            if (txt && el) {
                data = data + '&' + el + '_label=' + txt;
            }
        });

        $('input[type=checkbox]:not(:checked)',this).each(function() {
            if (data.indexOf(this.name) === -1) data = data + "&" + this.name + "=" + false;
        });

        return data;
//         return $.param(this.serializeArray(options));
    };

    $.fn.serializeArray = function (options) {
        const o = $.extend({
            checkboxesAsBools: false
        }, options || {});

        const rselectTextarea = /select|textarea/i;
        const rinput = /text|hidden|password|search/i;

        return this.map(function () {
            return this.elements ? $.makeArray(this.elements) : this;
        })
            .filter(function () {
                return this.name && !this.disabled &&
                    (this.checked
                        || (o.checkboxesAsBools && this.type === 'checkbox')
                        || rselectTextarea.test(this.nodeName)
                        || rinput.test(this.type));
            })
            .map(function (i, elem) {
                let val = $(this).val();
                return val == null ?
                    null :
                    $.isArray(val) ?
                        $.map(val, function (val, i) {
                            return {name: elem.name, value: val};
                        }) :
                        {
                            name: elem.name,
                            value: (o.checkboxesAsBools && this.type === 'checkbox') ? //moar ternaries!
                                (this.checked ? 'true' : 'false') :
                                val
                        };
            }).get();
    };

})(jQuery);

