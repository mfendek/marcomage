<?php

	/* -------------------------------- *
	 * | MARCOMAGE CONFIGURATION FILE | *
	 * -------------------------------- */

	// database configuration
	$server = "localhost";
	$username = "arcomage";
	$password = "";
	$database = "arcomage";

	// constants
	define("MAX_GAMES", 15);
	define("DECK_SLOTS", 8);
	define("BONUS_GAME_SLOTS", 4); // add bonus game slot every 4th level
	define("BONUS_DECK_SLOTS", 6); // add bonus deck slot every 6th level
	define("MESSAGE_LENGTH", 1000);
	define("CHALLENGE_LENGTH", 250);
	define("SYSTEM_NAME", "MArcomage"); // user name for system notification
	define("NUM_THREADS", 4); // number of threads per section in the forum main page
	define("THREADS_PER_PAGE", 30);
	define("POSTS_PER_PAGE", 20);
	define("POST_LENGTH", 4000);
	define("PLAYERS_PER_PAGE", 50);
	define("MESSAGES_PER_PAGE", 15);
	define("CARDS_PER_PAGE", 20);
	define("EFFECT_LENGTH", 150);
	define("EFFECT_LINES", 8);
	define("HOBBY_LENGTH", 300);
	define("REPLAYS_PER_PAGE", 20); // game replays

	// game configuration
	$game_config['init_tower'] = 30; // starting tower height
	$game_config['max_tower'] = 100; // maximum tower height
	$game_config['init_wall'] = 25; // starting wall height
	$game_config['max_wall'] = 150; // maximum wall height
	$game_config['res_victory'] = 400; // sum of all resources
	$game_config['time_victory'] = 250; // maximum number of rounds

?>
