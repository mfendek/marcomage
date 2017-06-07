<?php

/**
 * Class CardCodeTest
 */
class CardCodeTest extends PHPUnit_Framework_TestCase
{
    public function testCardsNormalMode()
    {
        $dic = Dic::getInstance();
        $serviceGameTest = $dic->serviceFactory()->gameTest();

        $result = $serviceGameTest->runCardTests(false, false);

        $this->assertEquals(0, count($result['errors']));
        $this->assertGreaterThan(0, count($result['log']));
    }

    public function testCardHiddenMode()
    {
        $dic = Dic::getInstance();
        $serviceGameTest = $dic->serviceFactory()->gameTest();

        $result = $serviceGameTest->runCardTests(false, true);

        $this->assertEquals(0, count($result['errors']));
        $this->assertGreaterThan(0, count($result['log']));
    }

    public function testKeywordsNormalMode()
    {
        $dic = Dic::getInstance();
        $serviceGameTest = $dic->serviceFactory()->gameTest();

        $result = $serviceGameTest->runKeywordTests(false, false);

        $this->assertEquals(0, count($result['errors']));
        $this->assertGreaterThan(0, count($result['log']));
    }

    public function testKeywordsHiddenMode()
    {
        $dic = Dic::getInstance();
        $serviceGameTest = $dic->serviceFactory()->gameTest();

        $result = $serviceGameTest->runKeywordTests(false, true);

        $this->assertEquals(0, count($result['errors']));
        $this->assertGreaterThan(0, count($result['log']));
    }
}
