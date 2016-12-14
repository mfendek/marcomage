<?php
/**
 * View - output generation
 */

namespace View;

use ArcomageException as Exception;
use Util\Encode;
use Util\Xslt;

class View
{
    /**
     * DIC reference
     * @var \Dic
     */
    protected $dic;

    /**
     * @param string $section
     * @param array $input
     * @throws Exception
     * @return array
     */
    private function prepareTemplateData($section, array $input)
    {
        $data = array();
        $subsectionName = '';

        $sectionData = $this->getDic()->viewFactory()->loadTemplate($section)->getData(
            $section, $input
        );

        // add data from template
        if (!empty($sectionData->data())) {
            $data = array_merge($data, $sectionData->data());
        }

        // extract subsection name id present
        if (!empty($sectionData->subsection())) {
            $subsectionName = $sectionData->subsection();
        }

        return ['data' => $data, 'subsection' => $subsectionName];
    }

    /**
     * @param string $name
     * @param array $data
     * @param bool [$includeLayout]
     * @return string
     */
    private function renderTemplate($name, array $data, $includeLayout = true)
    {
        $dic = $this->getDic();

        // determine if layout is needed
        $data['main']['include_layout'] = ($includeLayout) ? 'yes' : 'no';

        // determine which section to display
        $data['main']['section'] = $name;
        $sectionName = preg_replace("/_.*/i", '', $name);
        $data['main']['section_name'] = $sectionName;
        $data['navbar']['section_name'] = $sectionName;
        $name = 'templates/' . strtolower($sectionName) . '.xsl';

        // HTML code generation
        $logicEnd = microtime(true);
        $xsltStart = $logicEnd;

        $html = Xslt::transform($name, $data);

        $xsltEnd = microtime(true);

        $db = $dic->dbUtilFactory()->pdo();

        $query = (int)(1000 * $db->questionsTime());
        $logic = (int)(1000 * ($logicEnd - $dic->scriptStartTime())) - $query;
        $transform = (int)(1000 * ($xsltEnd - $xsltStart));
        $total = (int)(1000 * ($xsltEnd - $dic->scriptStartTime()));

        // add script duration debug
        if ($includeLayout) {
            $html.= sprintf('<!-- Page generated in %d (php:%d + sql:%d + xslt:%d) ms. %d queries used. -->',
                    $total,
                    $logic,
                    $query,
                    $transform,
                    $db->questions()
                ) . "\n";
        }

        // HTML5 specific conversion
        $html = str_replace(
            [
                '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">',
                ' xmlns="http://www.w3.org/1999/xhtml"',
                ' xmlns:am="http://arcomage.net"',
                '<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />',
            ],
            [
                '<!DOCTYPE html>',
                '',
                '',
                '<meta charset="UTF-8" />',
            ],
            $html
        );

        // layout-less specific conversion
        if (!$includeLayout) {
            $html = str_replace(
                [
                    '<!DOCTYPE html>' . "\n",
                    '<html lang="en" xml:lang="en">' . "\n",
                    '</html>' . "\n",
                ],
                [
                    '',
                    '',
                    '',
                ],
                $html
            );
        }

        return $html;
    }

    /**
     * @return \Dic
     */
    protected function getDic()
    {
        return $this->dic;
    }

    /**
     * @param \Dic $dic
     */
    public function __construct(\Dic $dic)
    {
        $this->dic = $dic;
    }

    /**
     * @param string $section
     * @param array [$input]
     * @throws Exception
     * @return string
     */
    public function renderTemplateWithoutLayout($section, array $input = [])
    {
        $data = $this->prepareTemplateData($section, $input);

        return $this->renderTemplate($section, $data['data'], false);
    }

    /**
     * @param string $section
     * @param array [$input]
     * @return string
     */
    public function renderTemplateWithLayout($section, array $input = [])
    {
        $dic = $this->getDic();

        $templates = [
            // generic template data (common layout)
            'generic' => 'layout',
            // specific template data
            'specific' => $section,
        ];

        $data = array();
        $subsectionName = '';

        try {
            // merge all template data
            foreach ($templates as $currentTemplate => $currentSection) {
                $sectionData = $this->prepareTemplateData($currentSection, $input);

                // add data from template
                $data = array_merge($data, $sectionData['data']);

                // extract subsection name id present
                $subsectionName = $sectionData['subsection'];
            }
        }
        catch (Exception $e) {
            // log error if necessary
            if ($e->getCode() == Exception::ERROR) {
                $dic->logger()->logException($e);
            }

            // redirect to error screen
            $dic->setFlags(['current' => 'Error', 'error' => $e->getMessage()]);
            $data['error']['message'] = Encode::htmlEncode($e->getMessage());
            $section = $dic->currentSection();
        }

        $data['main']['subsection'] = Encode::htmlEncode($subsectionName);

        return $this->renderTemplate($section, $data);
    }
}
