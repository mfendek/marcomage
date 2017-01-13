<?php
/**
 * Misc - misc related controller
 */

namespace Controller;

use ArcomageException as Exception;

class Misc extends ControllerAbstract
{
    /**
     * Back to top button
     */
    protected function backToTop()
    {
        $request = $this->request();

        $this->result()->setCurrent($request['back_to_top']);
    }

    /**
     * Reset notification
     * @throws Exception
     */
    protected function resetNotification()
    {
        $request = $this->request();

        $player = $this->getCurrentPlayer();

        $this->result()->setCurrent($request['reset_notification']);

        $player->setNotification($player->getLastActivity());
        if (!$player->save()) {
            throw new Exception('Failed to clear notification');
        }

        $this->result()->setInfo('Notification successfully cleared');
    }

    /**
     * View card statistics
     */
    protected function cardStatistics()
    {
        $this->result()->setCurrent('Statistics');
    }

    /**
     * View other statistics
     */
    protected function otherStatistics()
    {
        $this->result()->setCurrent('Statistics');
    }

    /**
     * Test card effects effects
     * @throws Exception
     */
    protected function testCards()
    {
        $request = $this->request();

        $this->result()->setCurrent('Statistics');

        // check access rights
        // TODO we should add extra access right for running card tests
        if (!$this->checkAccess('change_rights')) {
            throw new Exception('Access denied', Exception::WARNING);
        }

        // determine output type
        $testSummary = isset($request['test_summary']);

        $file = $this->service()->gameTest()->runCardTests(
            $testSummary, isset($request['hidden_cards'])
        );

        // case 1: test summary enabled - raw file output
        if ($testSummary) {
            $contentType = 'text/csv';
            $fileName = 'card_tests.csv';
            $fileLength = mb_strlen($file);

            $this->result()->setRawOutput($file, [
                'Content-Type: ' . $contentType . '',
                'Content-Disposition: attachment; filename="' . $fileName . '"',
                'Content-Length: ' . $fileLength
            ]);
        }
        // case 2: simple message
        else {
            $this->result()->setInfo(
                'Cards successfully tested, no errors found' . ((isset($request['hidden_cards'])) ? ' (hidden cards)' : '')
            );
        }
    }

    /**
     * Test keyword effects effects
     * @throws Exception
     */
    protected function testKeywords()
    {
        $request = $this->request();

        $this->result()->setCurrent('Statistics');

        // check access rights
        // TODO we should add extra access right for running card tests
        if (!$this->checkAccess('change_rights')) {
            throw new Exception('Access denied', Exception::WARNING);
        }

        // determine output type
        $testSummary = isset($request['test_summary']);

        $file = $this->service()->gameTest()->runKeywordTests(
            $testSummary, isset($request['hidden_cards'])
        );

        // case 1: test summary enabled - raw file output
        if ($testSummary) {
            $contentType = 'text/csv';
            $fileName = 'keyword_tests.csv';
            $fileLength = mb_strlen($file);

            $this->result()->setRawOutput($file, [
                'Content-Type: ' . $contentType . '',
                'Content-Disposition: attachment; filename="' . $fileName . '"',
                'Content-Length: ' . $fileLength,
            ]);
        }
        // case 2: simple message
        else {
            $this->result()->setInfo(
                'Keywords successfully tested, no errors found' . ((isset($request['hidden_cards'])) ? ' (hidden cards)' : '')
            );
        }
    }
}
