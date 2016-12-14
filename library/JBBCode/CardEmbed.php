<?php
/**
 * Custom card embed BB code definition
 */

namespace JBBCode;

class CardEmbed extends CodeDefinition
{
    /**
     * CardEmbed constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->setTagName('card');
        $this->setReplacementText(
            '<span class="card-lookup-{option}"><a href="?location=Cards_details&amp;card={option}">{param}</a></span>'
        );
        $this->setUseOption(true);
        $this->setParseContent(false);
    }

    /**
     * @param ElementNode $el
     * @return string
     */
    public function asHtml(ElementNode $el)
    {
        $defEntityFactory = \Dic::defEntityFactory()->card();

        if (!$this->hasValidInputs($el)) {
            return $el->getAsBBCode();
        }

        $html = $this->getReplacementText();

        $options = $el->getAttribute();
        $vals = array_values($options);
        $cardId = array_pop($vals);

        // load card data
        $data = $defEntityFactory->getData([$cardId], false);
        // data not found - output nothing
        if (empty($data)) {
            return '';
        }

        $html = str_ireplace('{option}', $cardId, $html);

        $content = $this->getContent($el);

        // in case of empty card name we will add card name
        if (empty($content)) {
            $defEntityFactory = \Dic::defEntityFactory()->card();

            // load card data
            $data = $defEntityFactory->getData([$cardId], false);

            // card not found
            if (empty($data)) {
                return '';
            }

            $data = array_pop($data);
            $content = $data['name'];
        }

        $html = str_ireplace('{param}', $content, $html);

        return $html;
    }
}
