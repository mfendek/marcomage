<?php
/**
 * Encode - content encoding
 */

namespace Util;

use JBBCode\CardEmbed;
use JBBCode\DefaultCodeDefinitionSet;
use JBBCode\KeywordEmbed;
use JBBCode\Parser;

class Encode
{
    /**
     * @var Parser
     */
    private static $bbCodeParserBasic = null;

    /**
     * @var Parser
     */
    private static $bbCodeParserExtended = null;

    /**
     * @param bool $extended
     * @return Parser
     */
    private static function initBBCodeParser($extended = false)
    {
        $parser = new Parser();

        $parser
            // basic set
            ->addCodeDefinitionSet(new DefaultCodeDefinitionSet())

            // custom BB code
            ->addCodeDefinition(new CardEmbed())
            ->addCodeDefinition(new KeywordEmbed())

            // internal link
            ->addBBCode('link', '<a href="{param}">{param}</a>', false, false)
            ->addBBCode('link', '<a href="{option}">{param}</a>', true, false)

            // concept link
            ->addBBCode(
                'concept',
                '<a href="?location=Concepts_details&amp;current_concept={option}">{param}</a>', true, false
            );

        // add extended options if necessary
        if ($extended) {
            // quote
            $parser
                ->addBBCode('quote', '<blockquote><div><br/>{param}</div></blockquote>', false, true, 5)
                ->addBBCode(
                    'quote',
                    '<blockquote><div><cite>{option} wrote:</cite><br/>{param}</div></blockquote>', true, true, 5
                );
        }

        return $parser;
    }

    /**
     * @param bool $extended
     * @return Parser
     */
    private static function getBBCodeParser($extended = false)
    {
        // extended parser
        if ($extended) {
            if (empty(self::$bbCodeParserExtended)) {
                self::$bbCodeParserExtended = self::initBBCodeParser(true);
            }

            return self::$bbCodeParserExtended;
        }

        // standard parser
        if (empty(self::$bbCodeParserBasic)) {
            self::$bbCodeParserBasic = self::initBBCodeParser();
        }

        return self::$bbCodeParserBasic;
    }

    /**
     * @param string $string
     * @return string
     */
    public static function htmlEncode($string)
    {
        return htmlspecialchars($string, ENT_COMPAT, 'UTF-8');
    }

    /**
     * @param string $string
     * @return string
     */
    public static function htmlDecode($string)
    {
        return htmlspecialchars_decode($string, ENT_COMPAT);
    }

    /**
     * @param string $string
     * @return string
     */
    public static function postEncode($string)
    {
        return rawurlencode($string);
    }

    /**
     * @param string $string
     * @return string
     */
    public static function postDecode($string)
    {
        return rawurldecode($string);
    }

    /**
     * @param string $content
     * @param bool $extended
     * @return mixed
     */
    public static function bbDecode($content, $extended = false)
    {
        // This is the best place to take care of escaping HTML injection stuff. We
        // have to explicitly not escape in our xslt.
        $content = self::htmlEncode($content);
        $content = str_replace("\n", "<br/>", $content);

        // case 1: extended
        if ($extended) {
            $parser = self::getBBCodeParser(true);
        }
        // case 2: basic
        else {
            $parser = self::getBBCodeParser();
        }

        // replace singular card tags with empty card tags
        $content = preg_replace_callback(
            '/\[card=(\d+)\/\]/',
            function ($matches) {
                $cardId = $matches[1];

                return '[card=' . $cardId . '][/card]';
            },
            $content
        );

        // parse content
        $parser->parse($content);

        return $parser->getAsHTML();
    }

    /**
     * Parse card effect and replace card references
     * @param string $cardEffect
     * @param string [$option]
     * @return string
     */
    public static function cardDecode($cardEffect, $option = '')
    {
        $defEntityFactory = \Dic::defEntityFactory()->card();

        // find and replace card references

        // card reference with id only
        $cardEffect = preg_replace_callback(
            '/\[card=(\d+)\/\]/',
            function ($matches) use ($defEntityFactory, $option) {
                $cardId = $matches[1];

                // load card data
                $data = $defEntityFactory->getData([$cardId], false);

                // card not found
                if (empty($data)) {
                    return '';
                }

                $data = array_pop($data);

                return ($option == 'plain_text')
                    ? $data['name']
                    : '<span data-card-lookup="' . $cardId . '">' . $data['name'] . '</span>';
            },
            $cardEffect
        );

        // card reference with id and a custom name
        $cardEffect = preg_replace_callback(
            '/\[card=(\d+)\]([\w| ]+)\[\/card\]/',
            function ($matches) {
                $cardId = $matches[1];
                $cardName = $matches[2];

                return '<span data-card-lookup="' . $cardId . '">' . $cardName . '</span>';
            },
            $cardEffect
        );

        return $cardEffect;
    }
}