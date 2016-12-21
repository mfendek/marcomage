<?php
/**
 * Custom keyword embed BB code definition
 */

namespace JBBCode;

use Util\Encode;

class KeywordEmbed extends CodeDefinition
{
    /**
     * CardEmbed constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->setTagName('keyword');
        $this->setReplacementText('<a href="?location=Cards_keyword_details&amp;keyword={param}">{param}</a>');
        $this->setUseOption(false);
        $this->setParseContent(false);
    }

    /**
     * @param ElementNode $el
     * @return string
     */
    public function asHtml(ElementNode $el)
    {
        if (!$this->hasValidInputs($el)) {
            return $el->getAsBBCode();
        }

        $html = $this->getReplacementText();

        $content = $this->getContent($el);

        // split html into separate parts
        $html = explode('{param}', $html);

        // we are expecting exactly 3 parts (2 instances of param)
        if (count($html) != 3) {
            return '';
        }

        // put html string back together with params properly encoded
        $html = $html[0] . Encode::postEncode($content) . $html[1] . $content . $html[2];

        return $html;
    }
}
