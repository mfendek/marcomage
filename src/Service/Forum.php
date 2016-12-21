<?php
/**
 * Forum
 */

namespace Service;

use ArcomageException as Exception;
use Util\Date;

class Forum extends ServiceAbstract
{
    /**
     * List forum sections
     * @throws Exception
     * @return array
     */
    public function listSections()
    {
        $dbEntityForumSection = $this->dbEntity()->forumSection();
        $dbEntityForumThread = $this->dbEntity()->forumThread();

        // get section list with thread count, ordered by custom order (alphabetical order is not suited for our needs)
        $result = $dbEntityForumSection->listSections();
        if ($result->isError()) {
            throw new Exception('failed to list forum sections');
        }

        // reindex section data by section id
        $sections = array();
        foreach ($result->data() as $data) {
            $sections[$data['section_id']] = $data;
        }

        // add thread list to each section
        foreach ($sections as $sectionId => $data) {
            // get threads list for current section
            $result = $dbEntityForumThread->listSectionThreads($sectionId);
            if ($result->isError()) {
                throw new Exception('failed to list forum threads for section '.$sectionId);
            }

            $sections[$sectionId]['threadlist'] = $result->data();
        }

        return $sections;
    }

    /**
     * Get last post for specified thread
     * @param $threadId
     * @throws Exception
     * @return array
     */
    public function getLastPost($threadId)
    {
        // load last post data
        $result = $this->dbEntity()->forumThread()->getLastPost($threadId);
        if ($result->isError()) {
            throw new Exception('Failed to find latest post in thread');
        }

        $lastPost = ($result->isSuccess()) ? $result[0] : ['author' => '', 'created_at' => Date::DATETIME_ZERO];

        return $lastPost;
    }
}
