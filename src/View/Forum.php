<?php
/**
 * Forum - forum related view module
 */

namespace View;

use ArcomageException as Exception;
use Db\Model\ForumPost as ForumPostModel;
use Db\Model\ForumThread as ForumThreadModel;
use Util\Input;

class Forum extends TemplateDataAbstract
{
    /**
     * @throws Exception
     * @return Result
     */
    protected function forum()
    {
        $data = array();

        $player = $this->getCurrentPlayer();

        $setting = $this->getCurrentSettings();

        // list forum sections with related threads
        $sections = $this->service()->forum()->listSections();

        // group sections
        $groups = array();
        foreach (array_chunk($sections, 5) as $i => $group) {
            $groups[]= ['sections' => $group];
        }

        // determine session state
        $data['is_logged_in'] = ($this->isSession()) ? 'yes' : 'no';
        $data['groups'] = $groups;
        $data['notification'] = $player->getNotification();
        $data['timezone'] = $setting->getSetting('timezone');

        return new Result(['forum_overview' => $data]);
    }

    /**
     * @throws Exception
     * @return Result
     */
    protected function forumSearch()
    {
        $data = array();
        $input = $this->input();

        $player = $this->getCurrentPlayer();
        $dbEntityForumSection = $this->dbEntity()->forumSection();

        $setting = $this->getCurrentSettings();

        // initialize search options
        $data['phrase'] = $phrase = Input::defaultValue($input, 'phrase');
        $data['target'] = $target = Input::defaultValue($input, 'target', 'all');
        $data['section'] = $section = Input::defaultValue($input, 'section', 'any');

        // search for phrase
        $threads = array();
        if (trim($phrase) != '') {
            $result = $dbEntityForumSection->search($phrase, $target, $section);
            if ($result->isError()) {
                throw new Exception('Failed to search forum');
            }
            $threads = $result->data();
        }

        // list target sections
        $result = $dbEntityForumSection->listTargetSections();
        if ($result->isError()) {
            throw new Exception('Failed to list target sections');
        }

        $sections = array();
        foreach ($result->data() as $sectionData) {
            $sections[$sectionData['section_id']] = $sectionData;
        }

        $data['threads'] = $threads;
        $data['sections'] = $sections;
        $data['notification'] = $player->getNotification();
        $data['timezone'] = $setting->getSetting('timezone');

        return new Result(['forum_search' => $data], 'Search');
    }

    /**
     * @throws Exception
     * @return Result
     */
    protected function forumSection()
    {
        $data = array();
        $input = $this->input();

        $player = $this->getCurrentPlayer();
        $dbEntityForumSection = $this->dbEntity()->forumSection();
        $dbEntityForumThread = $this->dbEntity()->forumThread();

        // validate current section
        $this->assertInputNonEmpty(['current_section']);
        if (!is_numeric($input['current_section']) || $input['current_section'] <= 0) {
            throw new Exception('Invalid forum section id', Exception::WARNING);
        }
        $sectionId = $input['current_section'];

        // validate current page
        $currentPage = Input::defaultValue($input, 'section_current_page', 0);
        if (!is_numeric($currentPage) || $currentPage < 0) {
            throw new Exception('Invalid forum section page', Exception::WARNING);
        }

        // determine session state
        $data['is_logged_in'] = ($this->isSession()) ? 'yes' : 'no';

        // load current forum section
        $result = $dbEntityForumSection->getSection($sectionId);
        if ($result->isError()) {
            throw new Exception('Failed to load forum section data');
        }
        if ($result->isNoEffect()) {
            throw new Exception('Forum section not found ' . $sectionId, Exception::WARNING);
        }
        $section = $result[0];

        // list threads in current forum section
        $result = $dbEntityForumThread->listThreads($sectionId, $currentPage);
        if ($result->isError()) {
            throw new Exception('Failed to list threads for section');
        }
        $threadList = $result->data();

        // count pages for threads list
        $result = $dbEntityForumThread->countPages($sectionId);
        if ($result->isErrorOrNoEffect()) {
            throw new Exception('Failed to count pages for thread list');
        }
        $pages = ceil($result[0]['count'] / ForumThreadModel::THREADS_PER_PAGE);

        $setting = $this->getCurrentSettings();

        $data['section'] = $section;
        $data['threads'] = $threadList;
        $data['pages'] = $pages;
        $data['current_page'] = $currentPage;
        $data['create_thread'] = ($this->checkAccess('create_thread')) ? 'yes' : 'no';
        $data['notification'] = $player->getNotification();
        $data['timezone'] = $setting->getSetting('timezone');

        return new Result(['forum_section' => $data], $section['section_name']);
    }

    /**
     * @throws Exception
     * @return Result
     */
    protected function forumThread()
    {
        $data = array();
        $input = $this->input();

        $config = $this->getDic()->config();
        $player = $this->getCurrentPlayer();
        $dbEntityForumSection = $this->dbEntity()->forumSection();
        $dbEntityForumPost = $this->dbEntity()->forumPost();

        // validate thread id
        $this->assertInputNonEmpty(['current_thread']);
        if (!is_numeric($input['current_thread']) || $input['current_thread'] <= 0) {
            throw new Exception('Missing forum thread id', Exception::WARNING);
        }
        $threadId = $input['current_thread'];

        // validate current page
        $currentPage = Input::defaultValue($input, 'thread_current_page', 0);
        if (!is_numeric($currentPage) || $currentPage < 0) {
            throw new Exception('Invalid forum thread page', Exception::WARNING);
        }

        $thread = $this->dbEntity()->forumThread()->getThreadAsserted($threadId);

        // check if thread isn't deleted
        if ($thread->getIsDeleted() == 1) {
            throw new Exception('Forum thread was already deleted ' . $threadId, Exception::WARNING);
        }

        // list posts for forum thread
        $result = $dbEntityForumPost->listPosts($threadId, $currentPage);
        if ($result->isError()) {
            throw new Exception('Failed to list posts for forum thread');
        }
        $postList = $result->data();

        // load section data
        $result = $dbEntityForumSection->getSection($thread->getSectionId());
        if ($result->isError()) {
            throw new Exception('Failed to load forum section data');
        }
        if ($result->isNoEffect()) {
            throw new Exception('Forum section not found ' . $thread->getSectionId(), Exception::WARNING);
        }
        $section = $result[0];

        // count pages in current thread
        $result = $dbEntityForumPost->countPages($threadId);
        if ($result->isErrorOrNoEffect()) {
            throw new Exception('Failed to count pages for posts list');
        }
        $pages = ceil($result[0]['count'] / ForumPostModel::POSTS_PER_PAGE);

        $setting = $this->getCurrentSettings();

        $data['thread_data'] = $thread->getData();
        $data['section_data'] = $section;
        $data['pages_count'] = $pages;
        $data['current_page'] = $currentPage;
        $data['post_list'] = $postList;
        $data['delete_thread'] = (isset($input['thread_delete'])) ? 'yes' : 'no';
        $data['delete_post'] = Input::defaultValue($input, 'delete_post', 0);
        $data['player_name'] = $player->getUsername();
        $data['notification'] = $player->getNotification();
        $data['timezone'] = $setting->getSetting('timezone');
        $data['avatar_path'] = $config['upload_dir']['avatar'];

        $data['posts_per_page'] = ForumPostModel::POSTS_PER_PAGE;
        $data['is_logged_in'] = ($this->isSession()) ? 'yes' : 'no';

        $data['lock_thread'] = ($this->checkAccess('lock_thread')) ? 'yes' : 'no';
        $data['del_all_thread'] = ($this->checkAccess('del_all_thread')) ? 'yes' : 'no';
        $data['edit_thread'] = ($this->checkAccess('edit_all_thread')
            || ($this->checkAccess('edit_own_thread') && $thread->getAuthor() == $player->getUsername())) ? 'yes' : 'no';
        $data['create_post'] = ($this->checkAccess('create_post')) ? 'yes' : 'no';
        $data['del_all_post'] = ($this->checkAccess('del_all_post')) ? 'yes' : 'no';
        $data['edit_all_post'] = ($this->checkAccess('edit_all_post')) ? 'yes' : 'no';
        $data['edit_own_post'] = ($this->checkAccess('edit_own_post')) ? 'yes' : 'no';

        return new Result(['forum_thread' => $data], $thread->getTitle());
    }

    /**
     * @throws Exception
     * @return Result
     */
    protected function forumThreadNew()
    {
        $data = array();
        $input = $this->input();
        $dbEntityForumSection = $this->dbEntity()->forumSection();

        // validate section id
        $this->assertInputNonEmpty(['current_section']);
        if (!is_numeric($input['current_section']) || $input['current_section'] <= 0) {
            throw new Exception('Invalid forum section id', Exception::WARNING);
        }

        // load current forum section
        $result = $dbEntityForumSection->getSection($input['current_section']);
        if ($result->isError()) {
            throw new Exception('Failed to load forum section data');
        }
        if ($result->isNoEffect()) {
            throw new Exception('Forum section not found ' . $input['current_section'], Exception::WARNING);
        }
        $section = $result[0];

        $data['section_data'] = $section;
        $data['content'] = Input::defaultValue($input, 'content');
        $data['title'] = Input::defaultValue($input, 'title');
        $data['change_priority'] = ($this->checkAccess('change_priority')) ? 'yes' : 'no';

        return new Result(['forum_thread_new' => $data], 'New thread');
    }

    /**
     * @throws Exception
     * @return Result
     */
    protected function forumPostNew()
    {
        $data = array();
        $input = $this->input();

        // validate thread id
        $this->assertInputNonEmpty(['current_thread']);
        if (!is_numeric($input['current_thread']) || $input['current_thread'] <= 0) {
            throw new Exception('Invalid forum thread id', Exception::WARNING);
        }

        $threadId = $input['current_thread'];
        $thread = $this->dbEntity()->forumThread()->getThreadAsserted($threadId);

        // check if thread isn't deleted
        if ($thread->getIsDeleted() == 1) {
            throw new Exception('Forum thread was already deleted ' . $threadId, Exception::WARNING);
        }

        $data['thread_data'] = $thread->getData();
        $quotedContent = '';
        if (isset($input['quote_post'])) {
            $post = $this->dbEntity()->forumPost()->getPostAsserted($input['quote_post']);

            $quotedContent = '[quote=' . $post->getAuthor() . ']' . $post->getContent() . '[/quote]';
        }
        $data['content'] = Input::defaultValue($input, 'content', (($quotedContent) ?: ''));

        // validate current page
        $currentPage = Input::defaultValue($input, 'thread_current_page', 0);;
        if (!is_numeric($currentPage) || $currentPage < 0) {
            throw new Exception('Invalid forum section page', Exception::WARNING);
        }
        $data['current_page'] = $currentPage;

        return new Result(['forum_post_new' => $data], $thread->getTitle());
    }

    /**
     * @throws Exception
     * @return Result
     */
    protected function forumThreadEdit()
    {
        $data = array();
        $input = $this->input();

        $dbEntityForumSection = $this->dbEntity()->forumSection();

        // validate thread id
        $this->assertInputNonEmpty(['current_thread']);
        if (!is_numeric($input['current_thread']) || $input['current_thread'] <= 0) {
            throw new Exception('Invalid forum thread id', Exception::WARNING);
        }

        $threadId = $input['current_thread'];
        $thread = $this->dbEntity()->forumThread()->getThreadAsserted($threadId);

        // check if thread isn't deleted
        if ($thread->getIsDeleted() == 1) {
            throw new Exception('Forum thread was already deleted ' . $threadId, Exception::WARNING);
        }

        // load current forum section
        $result = $dbEntityForumSection->getSection($thread->getSectionId());
        if ($result->isError()) {
            throw new Exception('Failed to load forum section data');
        }
        if ($result->isNoEffect()) {
            throw new Exception('Forum section not found ' . $thread->getSectionId(), Exception::WARNING);
        }
        $section = $result[0];

        // list target sections
        $result = $dbEntityForumSection->listTargetSections($thread->getSectionId());
        if ($result->isError()) {
            throw new Exception('Failed to list target sections');
        }

        $sections = array();
        foreach ($result->data() as $sectionData) {
            $sections[$sectionData['section_id']] = $sectionData;
        }

        $data['thread_data'] = $thread->getData();
        $data['section_data'] = $section;
        $data['SectionList'] = $sections;
        $data['change_priority'] = ($this->checkAccess('change_priority')) ? 'yes' : 'no';
        $data['move_thread'] = ($this->checkAccess('move_thread')) ? 'yes' : 'no';

        return new Result(['forum_thread_edit' => $data], $thread->getTitle());
    }

    /**
     * @throws Exception
     * @return Result
     */
    protected function forumPostEdit()
    {
        $data = array();
        $input = $this->input();

        $dbEntityForumThread = $this->dbEntity()->forumThread();

        // validate post id
        $this->assertInputNonEmpty(['current_post']);
        if (!is_numeric($input['current_post']) || $input['current_post'] <= 0) {
            throw new Exception('Invalid forum post id', Exception::WARNING);
        }

        // validate current page
        $currentPage = Input::defaultValue($input, 'thread_current_page', 0);;
        if (!is_numeric($currentPage) || $currentPage < 0) {
            throw new Exception('Invalid forum section page', Exception::WARNING);
        }

        $post = $this->dbEntity()->forumPost()->getPostAsserted($input['current_post']);

        // check if post isn't deleted
        if ($post->getIsDeleted() == 1) {
            throw new Exception('Forum post was already deleted ' . $post->getPostId(), Exception::WARNING);
        }

        // list treads that can be used as a target for post when moving
        $result = $dbEntityForumThread->listTargetThreads($post->getThreadId());
        if ($result->isError()) {
            throw new Exception('Failed to list target threads');
        }
        $threads = $result->data();

        $thread = $this->dbEntity()->forumThread()->getThreadAsserted($post->getThreadId());

        // check if thread isn't deleted
        if ($thread->getIsDeleted() == 1) {
            throw new Exception('Forum thread was already deleted ' . $thread->getThreadId(), Exception::WARNING);
        }

        $data['post_data'] = $post->getData();
        $data['current_page'] = $currentPage;
        $data['thread_list'] = $threads;
        $data['thread_data'] = $thread->getData();
        $data['content'] = Input::defaultValue($input, 'content', $post->getContent());
        $data['move_post'] = ($this->checkAccess('move_post')) ? 'yes' : 'no';

        return new Result(['forum_post_edit' => $data], $thread->getTitle());
    }
}
