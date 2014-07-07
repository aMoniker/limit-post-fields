jQuery(function($) {

if (!window.limitpostfields || !window.limitpostfields.settings) { return; }

var field_rules = {
    title: {
        element: '#title',
        after_element: '#title',
        create_callback: function(e, ui) {
            $('#title').attr('maxlength', get_max_length('title'));
        },
        update_watcher: function(update_callback) {
            $('#title').on('change keyup', function() {
                update_callback('title', $(this).val().length);
            });
            $('#title').trigger('keyup');
        }
    },
    editor: {
        after_element: '#postdivrich',
        create_callback: function(e, ui) {
            var bottom_margin = $('#postdivrich').css('margin-bottom');
            $('#postdivrich').css({ 'margin-bottom': '0' });
            $(this).css('margin-bottom', bottom_margin);
            $('#content').attr('maxlength', get_max_length('editor'));
        },
        update_watcher: function(update_callback) {
            var tinymce_interval;
            tinymce_interval = setInterval(function() {
                if (tinyMCE.editors && tinyMCE.editors.length) {
                    if (init_postcontent_watcher()) {
                        clearInterval(tinymce_interval);
                    }
                }
            }, 100);

            var tinyMCE_handler = function(ed, e) {
                update_callback('editor', get_trimmed_length(ed));
            };

            var get_trimmed_length = function(ed) {
                var content = ed.getContent({ format: 'text' });
                content = content.replace(/\n/, '');
                return content.length;
            };

            var init_postcontent_watcher = function() {
                var editor_hooked = false;
                $.each(tinyMCE.editors, function(i, ed) {
                    if (ed.id && ed.id === 'content') {
                        editor_hooked = true;

                        var unique_dom_id = tinymce.DOM.uniqueId();
                        var $ed_dom = $(ed.dom.doc);

                        var max_length = get_max_length('editor');
                        var old_length = get_trimmed_length(ed);
                        var last_content;
                        var keyup_finished = true;

                        ed.on('KeyUp', function(e) {
                            var new_length = get_trimmed_length(ed);
                            if (new_length > max_length && new_length > old_length) {
                                ed.setContent(last_content);

                                // hack to put the cursor back into the right place
                                ed.focus();
                                ed.selection.select(ed.dom.select('.'+unique_dom_id)[0]);
                                ed.selection.collapse(0);
                            }

                            // remove the cursor helper
                            $ed_dom.find('.' + unique_dom_id).remove();

                            last_content = ed.getContent();
                            old_length = get_trimmed_length(ed);

                            keyup_finished = true;
                            tinyMCE_handler(ed);
                        });

                        ed.on('KeyDown', function(e) {
                            tinyMCE_handler(ed);

                            if (!keyup_finished) { return; }
                            keyup_finished = false;

                            // add the cursor helper
                            ed.insertContent(
                                '<span class="' +unique_dom_id+ '" '
                              + 'style="display:none;">placeholder</span>'
                            );

                            last_content = ed.getContent();
                        });

                        ed.on('Paste', function(e) {
                            tinyMCE_handler(ed);
                        });

                        tinyMCE_handler(ed);
                    }
                });

                return editor_hooked;
            }

            $('#content').on('change keyup', function() {
                update_callback('editor', $(this).val().length);
            });
            $('#content').trigger('keyup');
        }
    },
    excerpt: {
        after_element: '#postexcerpt',
        create_callback: function(e, ui) {
            $('#excerpt').attr('maxlength', get_max_length('excerpt'));
            var bottom_margin = $('#postexcerpt').css('margin-bottom');
            $('#postexcerpt').css({ 'margin-bottom': '0' });
            $(this).css('margin-bottom', bottom_margin);
        },
        update_watcher: function(update_callback) {
            $('#excerpt').on('change keyup', function() {
                update_callback('excerpt', $(this).val().length);
            });
            $('#excerpt').trigger('keyup');
        }
    }
};


// utility function to check if the current length is valid
var validate_field_length = function(field_name, length) {
    var max_length = get_max_length(field_name);

    if (!length)              { return true;  }
    if (!max_length)          { return true;  }
    if (length > +max_length) { return false; }

    return true;
};

var get_max_length = function(field_name) {
    if (!field_name) { return undefined; }

    var max_length = +window.limitpostfields.settings[field_name];
    if (!max_length) { return undefined; }

    return max_length;
};

// the progress bar template
var $progress_bar = $([
    '<div class="progress-bar limitpostfields">',
        '<span class="progress-count">',
            '<span class="progress-used">0</span><span class="progress-total"></span>',
        '</span>',
    '</div>',
].join('\n'));

// initialize all the progressbars accoring to
// the fields defined in the global object
$.each(window.limitpostfields.settings, function(field, limit) {
    limit = +limit;
    if (!limit) { return; }

    var rules = field_rules[field];
    if (!rules) { return; }

    var $bar = $progress_bar.clone().progressbar({
        max: limit,
        value: 0,
        create: rules.create_callback || $.noop
    });

    $bar.find('.progress-total').text(limit);

    $(rules.after_element).after($bar);

    rules.update_watcher(function(field_name, value) {
        value = +value;
        var maxlength = get_max_length(field_name);
        $bar.progressbar('value', value);
        $bar.find('.progress-used').text(value);
        $bar.toggleClass('empty', value === 0);
        $bar.toggleClass('full', value === maxlength);
        $bar.toggleClass('overfilled', value > maxlength);
    });
});

});
