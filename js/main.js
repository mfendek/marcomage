// Define dependency imports

/* eslint-disable */
import transition from 'bootstrap-sass/assets/javascripts/bootstrap/transition';
import tooltip from 'bootstrap-sass/assets/javascripts/bootstrap/tooltip';
import modal from 'bootstrap-sass/assets/javascripts/bootstrap/modal';
import highlight from 'jquery-ui/ui/effects/effect-highlight';
import scrollto from './thirdparty/scrollto';
import cookie from './thirdparty/cookie';
/* eslint-enable */

// Define local components
import registration from './components/registration';
import concepts from './components/concepts';
import messages from './components/messages';
import services from './components/services';
import settings from './components/settings';
import tutorial from './components/tutorial';
import levelup from './components/levelup';
import players from './components/players';
import replays from './components/replays';
import cards from './components/cards';
import decks from './components/decks';
import intro from './components/intro';
import forum from './components/forum';
import games from './components/games';
import utils from './components/utils';

registration();
concepts();
messages();
services();
settings();
tutorial();
levelup();
players();
replays();
cards();
decks();
intro();
forum();
games();
utils();
