(function () {
    'use strict';
    const escape = value => String(value || '').replace(/[&<>"']/g, char => ({'&':'&amp;', '<':'&lt;', '>':'&gt;', '"':'&quot;', "'":'&#39;'}[char]));
    window.omoPropertyTypes = {
        firstCreatable(options) {
            return (options || []).find(option => option.canCreate)?.id || '';
        },
        render(property, options, label, editable) {
            const current = String(property.type || 'type1');
            const existing = Number(property.id || 0) > 0;
            const choices = (options || []).filter(option => option.canCreate || (existing && option.id === current));
            const html = choices.map(option => '<option value="' + escape(option.id) + '"' + (option.id === current ? ' selected' : '') + '>' + escape(option.name) + '</option>').join('');
            return '<label class="generic-form-field"><span class="generic-form-label">' + escape(label) + '</span><select class="generic-form-control" data-property-type' + (editable && choices.length > 0 ? '' : ' disabled') + '>' + html + '</select></label>';
        },
        read(row) {
            return row.querySelector('[data-property-type]')?.value || row.dataset.propertyType || 'type1';
        }
    };
}());
