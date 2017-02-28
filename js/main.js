jQuery(document).ready(function ($) {
    var cptList = $('div.evo_cpt_list');
    if (cptList != null && cptList.length > 0) {
        cptList.sortable({
            items: '.current_cpts',
            cursor: 'move',
            containment: 'parent',
            placeholder: 'my_placeholder'
        });
    }

    var evo_remove_block = function (e) {
        $(this).closest('.current_cpts').remove();
        $('div.evo_remove').unbind('click').on('click', evo_remove_block);
    };

    $('div.evo_remove').on('click', evo_remove_block);

    $('div.evo_select_callout div.evo_add_cpt').on('click', function (e) {
        var select = $(this).siblings("select");
        if (select.val() !== 'select') {
            select = $(this).siblings("select");
            var new_item = '<div class="current_cpts">' + select.children(' option:selected').text() + '<input type="hidden" name="' + $(this).data('field-name') + '" value="' + select.val() + '" /><div class="evo_remove"></div></div>';
            $(this).parents(".postbox").find('div.evo_cpt_list').append(new_item);

            $('div.evo_remove').on('click', evo_remove_block);
            select.children("option[value='select']").prop('selected', true);
        }
    });

});
