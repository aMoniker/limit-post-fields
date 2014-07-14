jQuery(function($) {

if (!window.limitpostfields || !window.limitpostfields.settings) { return; }

var LimitedField = Class.extend({
     bar_element: undefined
    ,element: undefined
    ,progress_bar_html: [
        '<div class="progress-bar limitpostfields">',
            '<span class="progress-count">',
                '<span class="progress-used">0</span><span class="progress-total"></span>',
            '</span>',
        '</div>',
    ].join('\n')
    ,init: function(max_length) {
        this.max_length = max_length;

        var $progress_bar = $(this.progress_bar_html).progressbar({
            max: this.max_length,
            value: 0,
            create: $.proxy(this.create_callback || $.noop, this)
        });

        $progress_bar.find('.progress-total').text(this.max_length);

        $(this.element).after($progress_bar);

        this.update_watcher($.proxy(function(length) {
            length = +length;
            $progress_bar
                .progressbar('value', length)
                .toggleClass('empty', length === 0)
                .toggleClass('full', length === this.max_length)
                .toggleClass('overfilled', length > this.max_length)
                .find('.progress-used').text(length)
                ;
        }, this));
    }
    ,validate_length: function(length) {
        if (!length)                  { return true;  }
        if (!this.max_length)         { return true;  }
        if (length > this.max_length) { return false; }
        return true;
    }
    ,create_callback: function(e, ui) {}
    ,update_watcher: function(callback) {}
});

var LimitedTitleField = LimitedField.extend({
     element: '#title'
    ,create_callback: function(e, ui) {
        $(this.element).attr('maxlength', this.max_length);
        this._super();
    }
    ,update_watcher: function(callback) {
        $(this.element)
            .on('change keyup', function() {
                callback($(this).val().length);
            })
            .trigger('keyup')
            ;
    }
});

var LimitedContentField = LimitedField.extend({
     element: '#postdivrich'
    ,text_element: '#content'
    ,create_callback: function(e, ui) {
        var $element = $(this.element);
        var bottom_margin = $element.css('margin-bottom');

        $element.css({ 'margin-bottom': '0' });

        console.log('create callback', e, ui);
        $(e.target).css('margin-bottom', bottom_margin);
        $(this.text_element).attr('maxlength', this.max_length);

        this._super();
    }
    ,update_watcher: function(callback) {
        this.update_callback = callback;

        var tinymce_interval;
        tinymce_interval = setInterval($.proxy(function() {
            if (tinyMCE.editors && tinyMCE.editors.length) {
                if (this.init_postcontent_watcher()) {
                    clearInterval(tinymce_interval);
                }
            }
        }, this), 100);

        $(this.text_element)
            .on('change keyup', function() {
                callback($(this).val().length);
            })
            .trigger('keyup')
            ;
    }
    ,init_postcontent_watcher: function() {
        var editor_hooked = false;

        $.each(tinyMCE.editors, $.proxy(function(i, ed) {
            if (!ed.id || ed.id !== 'content') { return; }

            editor_hooked = true;

            var unique_dom_id = tinymce.DOM.uniqueId();
            var $ed_dom = $(ed.dom.doc);

            var old_length = this.tinymce_get_trimmed_length(ed);
            var last_content;
            var keyup_finished = true;

            ed.on('KeyDown', $.proxy(function(e) {
                if (e.metaKey
                 || e.shiftKey
                 || e.ctrlKey
                 || e.altKey
                 || e.altGraphKey
                ) {
                    return;
                }

                this.tinymce_update_handler(ed);

                if (!keyup_finished) { return; }
                keyup_finished = false;

                ed.insertContent( // add the cursor hack
                    '<span class="' +unique_dom_id+ '" '
                  + 'style="display:none;">placeholder</span>'
                );

                last_content = ed.getContent();
            }, this));

            ed.on('KeyUp', $.proxy(function(e) {
                var new_length = this.tinymce_get_trimmed_length(ed);
                if (new_length > this.max_length && new_length > old_length) {
                    ed.setContent(last_content);

                    ed.focus(); // hack puts the cursor back
                    ed.selection.select(ed.dom.select('.'+unique_dom_id)[0]);
                    ed.selection.collapse(0);
                }

                $ed_dom.find('.' + unique_dom_id).remove(); // remove cursor hack

                last_content = ed.getContent();
                old_length = this.tinymce_get_trimmed_length(ed);

                keyup_finished = true;
                this.tinymce_update_handler(ed);
            }, this));

            ed.on('Paste', $.proxy(function(e) {
                this.tinymce_update_handler(ed);
            }, this));

            this.tinymce_update_handler(ed); // init
        }, this));

        return editor_hooked;
    }
    ,tinymce_update_handler: function(ed, e) {
        this.update_callback(this.tinymce_get_trimmed_length(ed));
    }
    ,tinymce_get_trimmed_length: function(ed) {
        var content = ed.getContent({ format: 'text' });
        content = content.replace(/\n/, '');
        content = content.replace(/^\s+/, '');
        content = content.replace(/\s+$/, '');
        return content.length;
    }
});

var LimitedExcerptField = LimitedField.extend({
     element: '#postexcerpt'
    ,text_element: '#excerpt'
    ,create_callback: function(e, ui) {
        $(this.text_element).attr('maxlength', this.max_length);
        var bottom_margin = $(this.element).css('margin-bottom');
        $(this.element).css({ 'margin-bottom': '0' });
        $(e.target).css('margin-bottom', bottom_margin);
    }
    ,update_watcher: function(callback) {
        $(this.text_element)
            .on('change keyup', function() {
                callback($(this).val().length);
            })
            .trigger('keyup')
            ;
    }
});


// initialize all the progressbars accoring to
// the fields defined in the global object
$.each(window.limitpostfields.settings, function(field, limit) {
    limit = +limit;
    if (!limit) { return; }

    if (field.match(/^field_/)) { // acf field
        var $field = $('[name="fields[' +field+ ']"]');
        console.log('field', $field);
    }

    switch (field) {
        case 'title':
            new LimitedTitleField(limit); break;
        case 'editor':
            new LimitedContentField(limit); break;
        case 'excerpt':
            new LimitedExcerptField(limit); break;
        default: break;
    }
});

});
