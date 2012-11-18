/********************************************
 * MArcomage JavaScript - levelup functions *
 ********************************************/

function highlightSection(section) // highlight specified section
{
	$("div#menu_center > a:contains('" + section + "')").effect('highlight', {}, 1000);
	window.setTimeout('highlightSection("' + section + '")', 1500);
}

$(document).ready(function() {

	$("#levelup_dialog").bind( "dialogclose", function() {
		// highlight newly unlocked section
		if ($("input[name='unlock_section']").length == 1)
		{
			highlightSection($("input[name='unlock_section']").val());
		}
	});

	// levelup dialog
	$("#levelup_dialog").dialog({
		autoOpen: true,
		show: "fade",
		hide: "fade",
		width: 1050,
		modal: true,
		draggable: false,
		closeOnEscape: true,
		resizable: false,
		buttons: {
			'Close': function()
				{
					$("#levelup_dialog").dialog("close");
				}
		}
	});

});
