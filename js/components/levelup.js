/********************************************
 * MArcomage JavaScript - levelup functions *
 ********************************************/

import $ from 'jquery';

export default function () {

/**
 * Highlight specified section
 * @param {string}section
 */
function highlightSection(section)
{
    $('.menu-center > a:contains("' + section + '")').effect('bounce', {}, 1000);
    window.setTimeout(highlightSection, 1500, section);
}

$(document).ready(function() {
    let levelUpDialog = $('#level-up-dialog');

    // dialog is inactive
    if (levelUpDialog.length == 0) {
        return;
    }

    levelUpDialog.on('hidden.bs.modal', function() {
        // highlight newly unlocked section
        let unlockSection = $('input[name="unlock_section"]');
        if (unlockSection.length == 1) {
            // TODO doesn't work on <a> elements only buttons
            // highlightSection(unlockSection.val());
        }
    });

    levelUpDialog.modal();
});

}
