<?php
/**
 * Template data factory
 * Provides resource caching and sugar functions
 */

namespace View;

use ArcomageException as Exception;
use Util\Rename;

class Factory extends \FactoryAbstract
{
    /**
     * @return array
     */
    private static function templates()
    {
        return [
            'generic' => [
                'layout',
            ],
            'card' => [
                'Cards',
                'Cards_details',
                'Cards_lookup',
                'Cards_keywords',
                'Cards_keyword_details'
            ],
            'concept' => [
                'Concepts',
                'Concepts_new',
                'Concepts_edit',
                'Concepts_details'
            ],
            'deck' => [
                'Decks_edit',
                'Decks_note',
                'Decks',
                'Decks_shared',
                'Decks_details'
            ],
            'forum' => [
                'Forum',
                'Forum_search',
                'Forum_section',
                'Forum_thread',
                'Forum_thread_new',
                'Forum_post_new',
                'Forum_thread_edit',
                'Forum_post_edit'
            ],
            'game' => [
                'Games',
                'Games_details',
                'Games_preview',
                'Decks_view',
                'Games_note'
            ],
            'message' => [
                'Messages',
                'Messages_details',
                'Messages_new'
            ],
            'misc' => [
                'Error',
                'Novels',
                'Settings',
                'Statistics'
            ],
            'player' => [
                'Players',
                'Players_details',
                'Players_achievements'
            ],
            'replay' => [
                'Replays',
                'Replays_details',
                'Replays_history'
            ],
            'webpage' => [
                'Webpage',
                'Help',
                'Registration'
            ],
        ];
    }

    /**
     * Create resource of specified name
     * @param string $resourceName
     */
    protected function createResource($resourceName)
    {
        // determine config key name
        $resourceKey = strtolower($resourceName);

        // add class name prefix
        $className = '\View\\'. Rename::underscoreToClassName($resourceName);

        $service = new $className($this->getDic());

        // store service to resource cache for future use
        $this->resources[$resourceKey] = $service;
    }

    /**
     * @param $section
     * @throws Exception
     * @return \View\TemplateDataAbstract
     */
    public function loadTemplate($section)
    {
        // find template name for section
        $name = '';
        foreach (self::templates() as $templateName => $sectionList) {
            // check if section is present in the list
            if (in_array($section, $sectionList)) {
                $name = $templateName;
                break;
            }
        }

        // validate template data
        if (empty($name)) {
            throw new Exception('template is not white-listed ' . $section, Exception::WARNING);
        }

        return $this->loadResource($name);
    }
}
