var serializeObject = function (form) {
    var obj = {};
    Array.prototype.slice.call(form.elements).forEach(function (field) {
        if (!field.name || field.disabled || ['file', 'reset', 'submit', 'button'].indexOf(field.type) > -1) return;
        if (field.type === 'select-multiple') {
            var options = [];
            Array.prototype.slice.call(field.options).forEach(function (option) {
                if (!option.selected) return;
                options.push(option.value);
            });
            if (options.length) {
                obj[field.name] = options;
            }
            return;
        }
        if (['checkbox', 'radio'].indexOf(field.type) >-1 && !field.checked) return;
        obj[field.name] = field.value;
    });
    return obj;
};

function serializeFormData(formName) {
    const form = document.querySelector('#' + formName);
    const str = JSON.stringify(serializeObject(form));
    return btoa(encodeURIComponent(str));
}
