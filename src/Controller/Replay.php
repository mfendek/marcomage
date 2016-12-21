<?php
/**
 * Replay - replays related controller
 */

namespace Controller;

use ArcomageException as Exception;
use Db\Model\ForumThread;

class Replay extends ControllerAbstract
{
    /**
     * Select ascending order in game replays list
     */
    protected function replaysOrderAsc()
    {
        $request = $this->request();

        $this->result()
            ->changeRequest('replays_current_condition', $request['replays_order_asc'])
            ->changeRequest('replays_current_order', 'ASC')
            ->setCurrent('Replays');
    }

    /**
     * Select descending order in game replays list
     */
    protected function replaysOrderDesc()
    {
        $request = $this->request();

        $this->result()
            ->changeRequest('replays_current_condition', $request['replays_order_desc'])
            ->changeRequest('replays_current_order', 'DESC')
            ->setCurrent('Replays');
    }

    /**
     * Use filter in replays list
     */
    protected function replaysApplyFilters()
    {
        $this->result()
            ->changeRequest('replays_current_page', 0)
            ->setCurrent('Replays');
    }

    /**
     * Show only current player's replays
     */
    protected function showMyReplays()
    {
        $player = $this->getCurrentPlayer();

        $this->result()
            ->changeRequest('player_filter', $player->getUsername())
            ->changeRequest('hidden_cards', 'none')
            ->changeRequest('friendly_play', 'none')
            ->changeRequest('long_mode', 'none')
            ->changeRequest('victory_filter', 'none')
            ->changeRequest('ai_mode', 'none')
            ->changeRequest('challenge_filter', 'none')
            ->changeRequest('replays_current_page', 0)
            ->setCurrent('Replays');
    }

    /**
     * Select page (previous and next button)
     */
    protected function replaysSelectPage()
    {
        $request = $this->request();

        $this->result()
            ->changeRequest('replays_current_page', $request['replays_select_page'])
            ->setCurrent('Replays');
    }

    /**
     * Create new thread for specified replay
     * @throws Exception
     */
    protected function findReplayThread()
    {
        $request = $this->request();
        $player = $this->getCurrentPlayer();
        $dbEntityThread = $this->dbEntity()->forumThread();

        $this->result()->setCurrent('Replays');

        $replayId = $request['find_replay_thread'];

        // check access rights
        if (!$this->checkAccess('create_thread')) {
            throw new Exception('Access denied', Exception::WARNING);
        }

        $replay = $this->dbEntity()->replay()->getReplayAsserted($replayId);

        // find related forum thread
        $result = $dbEntityThread->replayThread($replayId);
        if ($result->isError()) {
            throw new Exception('Failed to find forum thread by replay id');
        }
        $threadId = ($result->isSuccess()) ? $result[0]['thread_id'] : 0;

        // check if attached thread doesn't already exist
        if ($threadId > 0) {
            $this->result()
                ->changeRequest('current_thread', $threadId)
                ->setCurrent('Forum_thread');

            throw new Exception('Thread already exists', Exception::WARNING);
        }

        // create thread title
        $threadName = $replay->getPlayer1() . ' vs ' . $replay->getPlayer2() . ' (' . $replayId . ')';

        $thread = $dbEntityThread->createThread(
            $threadName, $player->getUsername(), 'normal', ForumThread::REPLAYS_SECTION_ID, $replayId
        );
        if (!$thread->save()) {
            throw new Exception('Failed to create new thread');
        }

        $this->result()
            ->changeRequest('current_thread', $thread->getThreadId())
            ->setInfo('Thread created')
            ->setCurrent('Forum_thread');
    }
}
