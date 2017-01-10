/********************************************
 * MArcomage JavaScript - levelup functions *
 ********************************************/

'use strict';

/**
 * Highlight specified section
 * @param {string}section
 */
function highlightSection(section)
{
    $('div#menu-center > a:contains("' + section + '")').effect('highlight', {}, 1000);
    window.setTimeout(highlightSection, 1500, section);
}

$(document).ready(function() {
    var levelUpDialog = $('#level-up-dialog');

    levelUpDialog.bind('dialogclose', function() {
        // highlight newly unlocked section
        var unlockSection = $('input[name="unlock_section"]');
        if (unlockSection.length == 1) {
            highlightSection(unlockSection.val());
        }
    });

    // level up dialog
    levelUpDialog.dialog({
        autoOpen: true,
        show: 'fade',
        hide: 'fade',
        title: 'Level up!',
        width: $('.container').width(),
        modal: true,
        draggable: false,
        closeOnEscape: true,
        resizable: false,
        buttons: {
            'Close': function() {
                $('#level-up-dialog').dialog('close');
            }
        }
    });

});
