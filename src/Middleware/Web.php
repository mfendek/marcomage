<?php
/**
 * Web middleware
 */

namespace Middleware;

use ArcomageException as Exception;
use Controller\Response;
use Controller\Result;
use Db\Model\Player;
use ReCaptcha\ReCaptcha;
use Util\Ip;

class Web extends MiddlewareAbstract
{
    /**
     * @param Result $controllerResult
     * @return Response
     */
    protected function processResult(Result $controllerResult = null)
    {
        $dic = $this->getDic();

        return new Response(
            $dic->view()->renderTemplateWithLayout($dic->currentSection(), $dic->request()),
            ['Content-Type: text/html']
        );
    }

    /**
     * @throws Exception
     */
    public function execute()
    {
        $request = $this->request();
        $server = $this->getDic()->server();
        $dbEntityPlayer = $this->dbEntity()->player();

        // default section
        $this->result()->setCurrent((isset($request['location'])) ? $request['location'] : 'Webpage');
        $publicSections = ['Webpage', 'Help', 'Novels', 'Forum', 'Players', 'Cards', 'Concepts'];

        $this->beginSession();

        // player is not logged in yet
        if (!$this->isSession()) {
            // case 1: login button was pressed
            if (isset($request['login'])) {
                $this->result()
                    ->setCurrent('Webpage')
                    ->setInfo('Login failed');
            }
            // case 2: registration button was pressed
            elseif (isset($request['registration'])) {
                $this->result()->setCurrent('Registration');
            }
            // case 3: return to login button was pressed
            elseif (isset($request['ReturnToLogin'])) {
                $this->result()
                    ->setCurrent('Webpage')
                    ->setInfo('Please log in');
            }
            // case 4: register button was pressed
            elseif (isset($request['register'])) {
                // case 1: not all registration inputs were filled
                if (!isset($request['new_username']) || !isset($request['new_password'])
                    || !isset($request['confirm_password']) || trim($request['new_username']) == ''
                    || trim($request['new_password']) == '' || trim($request['confirm_password']) == '') {
                    $this->result()->setCurrent('Registration');
                    throw new Exception('Please enter all required inputs', Exception::WARNING);
                }
                // case 2: password inputs don't match
                elseif ($request['new_password'] != $request['confirm_password']) {
                    $this->result()->setCurrent('Registration');
                    throw new Exception("The two passwords don't match", Exception::WARNING);
                }
                // case 3: register name is already taken
                elseif (!empty($dbEntityPlayer->getPlayer($request['new_username']))
                    || strtolower(trim($request['new_username'])) == strtolower(Player::SYSTEM_NAME)) {
                    $this->result()->setCurrent('Registration');
                    throw new Exception('Username (' . $request['new_username'] . ') is already taken', Exception::WARNING);
                }
                // case 4: register new user
                else {
                    $config = $this->getDic()->config();

                    // validate CAPTCHA if enabled
                    if ($config['captcha']['enabled']) {
                        if (empty($request['g-recaptcha-response'])) {
                            throw new Exception('CAPTCHA field is missing', Exception::WARNING);
                        }

                        $captcha = new ReCaptcha($config['captcha']['private_key']);
                        $resp = $captcha->verify($request['g-recaptcha-response'], Ip::getIp());

                        if (!$resp->isSuccess()) {
                            throw new Exception('CAPTCHA validation failed', Exception::WARNING);
                        }
                    }

                    $playerName = $request['new_username'];
                    $password = $request['new_password'];

                    // check flood prevention
                    $result = $dbEntityPlayer->validateIp(Ip::getIp());
                    if ($result->isError()) {
                        $this->result()->setCurrent('Registration');
                        throw new Exception('Failed to validate IP');
                    }
                    if ($result->isSuccess()) {
                        $this->result()->setCurrent('Registration');
                        throw new Exception('Player creation is temporarily disabled from current IP', Exception::WARNING);
                    }

                    // set current in case of failure
                    $this->result()->setCurrent('Registration');

                    // register new player
                    $this->service()->player()->register($playerName, $password);

                    // log user automatically right after registration
                    $request['login'] = 1;

                    // pass new user data to login
                    $this->beginSession(['username' => $request['new_username'], 'password' => $request['new_password']]);

                    // proceed only is case user was successfully logged in
                    if ($this->isSession()) {
                        // mark user as newly created
                        $this->result()
                            ->enableNewUser()
                            ->setCurrent('Webpage')
                            ->setInfo('User registered');
                    }
                }
            }
            // case 5: no button was pressed
            else {
                $sectionName = preg_replace("/_.*/i", '', $this->result()->currentSection());

                // case 1: section requires authentication to be viewed
                if (!in_array($sectionName, $publicSections)) {
                    $this->result()->setCurrent('Webpage');
                    throw new Exception('Authentication is required to view this page', Exception::WARNING);
                }
                // case 2: section is public
                else {
                    $this->result()->setInfo('Please log in');
                }
            }
        }

        // player is already logged in
        if ($this->isSession()) {
            $player = $this->getCurrentPlayer();

            // verify login privilege
            if (!$this->checkAccess('login')) {
                $this->result()->setCurrent('Webpage');

                $this->destroySession();

                $player->logout();
                if (!$player->save()) {
                    throw new Exception('Failed to save player data');
                }

                throw new Exception('This user is not permitted to log in', Exception::WARNING);
            }

            // login page messages (user pushed login or registration button or is submitting login data without pushing either button)
            // some browser produce this behaviour when they save login data and auto-fill the form
            if (isset($request['login']) || isset($request['registration']) || (isset($request['username']) && isset($request['password']))) {
                // default section is 'Games' for new users, 'Webpage' for everyone else
                $this->result()->setCurrent(($this->result()->newUser()) ? 'Games' : 'Webpage');
            }
            // navigation bar messages
            elseif (isset($request['Logout'])) {
                $this->result()->setCurrent('Webpage');

                $this->destroySession();

                $player->logout();
                if (!$player->save()) {
                    throw new Exception('Failed to save player data');
                }

                $this->result()->setInfo('You have successfully logged out');
            }
            // inner-page messages (POST processing), omitted in case of a GET request
            elseif ($server['REQUEST_METHOD'] == 'POST') {
                // this indicates that we want to proceed to controllers
                $this->result()->enableControllerProcessing();
            }
        }
    }
}
