/******************************************
 * MArcomage JavaScript - intro functions *
 ******************************************/

$(document).ready(function() {

	$("#intro_dialog").bind( "dialogclose", function() {
		// case 1: there are no active games available - start a new game
		if ($("div#active_games table a.button").length == 0)
		{
			$("div#games button[name='quick_game']").click();
		}
		// case 2: there are some active game available - enter the first game
		else
		{
			window.location.href = $("div#active_games table a.button:first").attr('href');
		}
	});

	// introduction dialog
	$("#intro_dialog").dialog({
		autoOpen: true,
		show: "fade",
		hide: "fade",
		width: 500,
		modal: true,
		draggable: false,
		closeOnEscape: true,
		resizable: false,
		buttons: {
			'Play now': function()
				{
					$("#intro_dialog").dialog("close");
				}
		}
	});

});
