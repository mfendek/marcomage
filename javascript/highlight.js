/*************************************************
 * MArcomage JavaScript - highlighting functions *
 *************************************************/

function highlightQuickButton() // highlight quick game vs AI button
{
	$("div#games button[name='quick_game']").effect('highlight', {}, 1000);
	window.setTimeout('highlightQuickButton()', 1500);
}

function highlightLeaveButton() // highlight leave game button
{
	$("div#game button[name='Confirm']").effect('highlight', {}, 1000);
	window.setTimeout('highlightLeaveButton()', 1500);
}

function highlightCards() // highlight playable cards in hand
{
	// case 1: single play card button mode is active
	if ($("div#game button[name='play_card']").length == 1 && $("div#game button[name='play_card']").val() == 0)
	{
		// case 1: highlight playable cards
		if ($("div#game tr.hand:first-child div.selected_card").length == 0)
		{
			$("div#game tr.hand:first-child div[class!='unplayable'] > div.karta").animate({ borderColor: "#000000" }, 500, function() {$(this).animate({ borderColor: "#ffffff" }, 500);});
			window.setTimeout('highlightCards()', 5000);
		}
		// case 2: highlight play button
		else if ($("div#game button[name='play_card']").length == 1)
		{
			$("div#game button[name='play_card']").effect('highlight', {}, 1000);
			window.setTimeout('highlightCards()', 1500);
		}
	}
	// case 2: multiple play card button mode is active
	else
	{
		$("div#game button[name='play_card']").effect('highlight', {}, 1000);
		window.setTimeout('highlightCards()', 1500);
	}
}

$(document).ready(function() {

	// highlight quick game vs AI button in games section
	if ($("div#games button[name='quick_game']").length > 0)
	{
		highlightQuickButton();
	}

	// highlight selectable cards
	if ($("div#game button[name='play_card']").length > 0)
	{
		highlightCards();
	}

	// highlight leave game button
	if ($("div#game button[name='Confirm']").length > 0)
	{
		highlightLeaveButton();
	}
	

});
