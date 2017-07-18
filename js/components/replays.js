/******************************************
 * MArcomage JavaScript - Replays section *
 ******************************************/

import $ from 'jquery';

export default function () {
  // global timer
  let timer;

  /**
   * Replay slide show -> resume replay
   */
  function resumeReplay() {
    $('button[name="slideshow-play"]').hide();
    $('button[name="slideshow-pause"]').show();

    timer = window.setTimeout(function () {
      window.location.href = $('a#next').attr('href');
    }, 5000);
  }

  /**
   * Replay slide show -> pause replay
   */
  function pauseReplay() {
    $('button[name="slideshow-pause"]').hide();
    $('button[name="slideshow-play"]').show();
    window.clearTimeout(timer);
  }

  $(document).ready(function () {
    let dic = $.dic;

    if (!dic.bodyData().isSectionActive('replays')) {
      return;
    }

    // apply replay filters by pressing ENTER key
    $('input[name="player_filter"]').keypress(function (event) {
      if (event.keyCode === dic.KEY_ENTER) {
        event.preventDefault();
        $('button[name="replays_apply_filters"]').click();
      }
    });

    // apply only in replay section
    if ($('a#next').length === 1) {
      resumeReplay();
    }

    // pause replay
    $('button[name="slideshow-pause"]').click(function () {
      pauseReplay();
    });

    // resume replay
    $('button[name="slideshow-play"]').click(function () {
      resumeReplay();
    });
  });
}
