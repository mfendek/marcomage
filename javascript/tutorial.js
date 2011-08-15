/*********************************************
 * MArcomage JavaScript - tutorial functions *
 *********************************************/

$(document).ready(function() {

  // starting tutorial dialog
  $("#tutorial_dialog_start").dialog({
    autoOpen: true,
    show: "fade",
    hide: "fade",
    width: 500,
    modal: true,
    closeOnEscape: false,
    buttons: {
      'Skip tutorial': function()
        {
          $("#tutorial_dialog_start").dialog("close");
          $("div#menu_float_right button[name='reset_notification']").click();
        },
      'Next': function()
        {
          $("#tutorial_dialog_start").dialog("close");
          window.location.href = $("div#menu_center > a:contains('Games')").attr('href');
        }
    }
  });

  // ending tutorial dialog
  $("#tutorial_dialog_finish").dialog({
    autoOpen: true,
    show: "fade",
    hide: "fade",
    width: 500,
    modal: true,
    closeOnEscape: false,
    buttons: {
      'Previous': function()
        {
          $("#tutorial_dialog_finish").dialog("close");
          window.location.href = $("div#menu_center > a:contains('Concepts')").attr('href');
        },
      'Finish tutorial': function()
        {
          $("#tutorial_dialog_finish").dialog("close");
          $("div#menu_float_right button[name='reset_notification']").click();
        }
    }
  });

  // standard tutorial dialog
  $("#tutorial_dialog_standard").dialog({
    autoOpen: true,
    show: "fade",
    hide: "fade",
    width: 500,
    modal: true,
    closeOnEscape: false,
    buttons: {
      'Skip tutorial': function()
        {
          $("#tutorial_dialog_standard").dialog("close");
          $("div#menu_float_right button[name='reset_notification']").click();
        },
      'Previous': function()
        {
          $("#tutorial_dialog_standard").dialog("close");

          // determine current section
          var current_tutorial = $("div#tutorial_dialog_standard > input[name='current_tutorial']").val();

          // redirect to previous page
          if (current_tutorial == 'games')
          {
            window.location.href = $("div#menu_center > a:contains('Webpage')").attr('href');
          }
          else if (current_tutorial == 'games_details')
          {
            window.location.href = $("div#menu_center > a:contains('Games')").attr('href');
          }
          else if (current_tutorial == 'decks')
          {
            window.location.href = $("div#menu_center > a:contains('Games')").attr('href');
          }
          else if (current_tutorial == 'decks_edit')
          {
            window.location.href = $("div#menu_center > a:contains('Decks')").attr('href');
          }
          else if (current_tutorial == 'messages')
          {
            window.location.href = $("div#menu_center > a:contains('Decks')").attr('href');
          }
          else if (current_tutorial == 'players')
          {
            window.location.href = $("div#menu_center > a:contains('Messages')").attr('href');
          }
          else if (current_tutorial == 'replays')
          {
            window.location.href = $("div#menu_center > a:contains('Players')").attr('href');
          }
          else if (current_tutorial == 'forum')
          {
            window.location.href = $("div#menu_center > a:contains('Replays')").attr('href');
          }
          else if (current_tutorial == 'cards')
          {
            window.location.href = $("div#menu_center > a:contains('Forum')").attr('href');
          }
          else if (current_tutorial == 'concepts')
          {
            window.location.href = $("div#menu_center > a:contains('Cards')").attr('href');
          }
          else if (current_tutorial == 'settings')
          {
            window.location.href = $("div#menu_center > a:contains('Concepts')").attr('href');
          }
        },
      'Next': function()
        {
          $("#tutorial_dialog_standard").dialog("close");
  
          // determine current section
          var current_tutorial = $("div#tutorial_dialog_standard > input[name='current_tutorial']").val();
  
          // redirect to next page
          if (current_tutorial == 'games')
          {
            if ($("div#active_games table a.button").length == 0)
              { $("div#games button[name='quick_game']").click(); }
            else
              { window.location.href = $("div#active_games table a.button:first").attr('href'); }
          }
          else if (current_tutorial == 'games_details')
          {
            window.location.href = $("div#menu_center > a:contains('Decks')").attr('href');
          }
          else if (current_tutorial == 'decks')
          {
            window.location.href = $("div#decks table a.button:first").attr('href');
          }
          else if (current_tutorial == 'decks_edit')
          {
            window.location.href = $("div#menu_center > a:contains('Messages')").attr('href');
          }
          else if (current_tutorial == 'messages')
          {
            window.location.href = $("div#menu_center > a:contains('Players')").attr('href');
          }
          else if (current_tutorial == 'players')
          {
            window.location.href = $("div#menu_center > a:contains('Replays')").attr('href');
          }
          else if (current_tutorial == 'replays')
          {
            window.location.href = $("div#menu_center > a:contains('Forum')").attr('href');
          }
          else if (current_tutorial == 'forum')
          {
            window.location.href = $("div#menu_center > a:contains('Cards')").attr('href');
          }
          else if (current_tutorial == 'cards')
          {
            window.location.href = $("div#menu_center > a:contains('Concepts')").attr('href');
          }
          else if (current_tutorial == 'concepts')
          {
            window.location.href = $("div#menu_center > a:contains('Settings')").attr('href');
          }
        }
    }
  });

});
