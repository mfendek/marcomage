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
import notificationsManager from './components/notifications_manager';
import registration from './components/registration';
import apiManager from './components/api_manager';
import bodyData from './components/body_data';
import concepts from './components/concepts';
import messages from './components/messages';
import settings from './components/settings';
import tutorial from './components/tutorial';
import levelup from './components/levelup';
import players from './components/players';
import replays from './components/replays';
import bbCode from './components/bb_code';
import cards from './components/cards';
import decks from './components/decks';
import intro from './components/intro';
import forum from './components/forum';
import games from './components/games';
import utils from './components/utils';
import dic from './components/dic';

notificationsManager();
registration();
apiManager();
bodyData();
concepts();
messages();
settings();
tutorial();
levelup();
players();
replays();
bbCode();
cards();
decks();
intro();
forum();
games();
utils();
dic();
