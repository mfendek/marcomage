/******************************************
 * MArcomage JavaScript - Replays section *
 ******************************************/

'use strict';

// global timer
var timer;

/**
 * Replay slide show -> resume replay
 */
function resumeReplay()
{
    $('button[name="slideshow-play"]').hide();
    $('button[name="slideshow-pause"]').show();
    timer = window.setTimeout("window.location.href = $('a#next').attr('href')", 5000);
}

/**
 * Replay slide show -> pause replay
 */
function pauseReplay()
{
    $('button[name="slideshow-pause"]').hide();
    $('button[name="slideshow-play"]').show();
    window.clearTimeout(timer);
}

$(document).ready(function() {

    // apply replay filters by pressing ENTER key
    $('input[name="player_filter"]').keypress(function(event) {
        if (event.keyCode == '13') {
            event.preventDefault();
            $('button[name="replays_apply_filters"]').click();
        }
    });

    // apply only in replay section
    if ($('a#next').length == 1) {
        resumeReplay();
    }

    // pause replay
    $('button[name="slideshow-pause"]').click(function() {
        pauseReplay();
    });

    // resume replay
    $('button[name="slideshow-play"]').click(function() {
        resumeReplay();
    });

});
