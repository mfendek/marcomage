/******************************************
 * MArcomage JavaScript - Replays section *
 ******************************************/

function ResumeReplay() // replay slideshow -> resume replay
{
	$("button[name='slideshow']").html('Pause');
	timer = window.setTimeout("window.location.href = $('a#next').attr('href')", 5000);
}

function PauseReplay() // replay slideshow -> pause replay
{
	$("button[name='slideshow']").html('Play');
	window.clearTimeout(timer);
}

$(document).ready(function() {

	// apply replay filters by pressing ENTER key
	$("input[name='PlayerFilter']").keypress(function(event) {
		if (event.keyCode == '13') { event.preventDefault(); $("button[name='filter_replays']").click(); }
	});

	// replay slideshow initialization
	var timer; // global timer

	if ($("a#next").length == 1) // apply only in replay section
	{
		ResumeReplay();
	}

	$("button[name='slideshow']").click(function() {
		if ($("button[name='slideshow']").html() == 'Pause')
		{
			PauseReplay();
		}
		else if ($("button[name='slideshow']").html() == 'Play')
		{
			ResumeReplay();
		}
	});

	// start discussion confirmation
	$("button[name='card_thread'], button[name='concept_thread'], button[name='replay_thread']").click(function() {
		return confirm("Are you sure you want to start a discussion?");
	});

});
