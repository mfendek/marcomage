<?php
/**
 * Forum - forum related controller
 */

namespace Controller;

use ArcomageException as Exception;
use Db\Model\ForumPost as ForumPostModel;
use Util\Date;
use Util\Input;

class Forum extends ControllerAbstract
{
    /**
     * Section -> new thread
     * @throws Exception
     */
    protected function newThread()
    {
        // check access rights
        if (!$this->checkAccess('create_thread')) {
            $this->result()->setCurrent('Forum_section');
            throw new Exception('Access denied', Exception::WARNING);
        }

        $this->result()->setCurrent('Forum_thread_new');
    }

    /**
     * Section -> new thread -> create new thread
     * @throws Exception
     */
    protected function createThread()
    {
        $request = $this->request();

        $player = $this->getCurrentPlayer();
        $db = $this->getDb();
        $dbEntityForumThread = $this->dbEntity()->forumThread();
        $dbEntityForumPost = $this->dbEntity()->forumPost();

        $this->result()->setCurrent('Forum_section');

        $this->assertParamsNonEmpty(['current_section', 'priority', 'title', 'content']);

        $sectionId = $request['current_section'];

        // validate forum section id
        if (!is_numeric($sectionId)) {
            throw new Exception('Section id is missing', Exception::WARNING);
        }

        // check access rights
        if (!$this->checkAccess('create_thread') || (!$this->checkAccess('change_priority')
                && ($request['priority'] != 'normal'))) {
            throw new Exception('Access denied', Exception::WARNING);
        }

        // validate user input
        if (trim($request['title']) == '' || trim($request['content']) == '') {
            $this->result()->setCurrent('Forum_thread_new');
            throw new Exception('Invalid input', Exception::WARNING);
        }

        // validate content
        if (trim($request['content']) == '') {
            $this->result()->setCurrent('Forum_thread_new');
            throw new Exception('Invalid post text', Exception::WARNING);
        }

        // validate message length
        if (mb_strlen($request['content']) > ForumPostModel::POST_LENGTH) {
            $this->result()->setCurrent('Forum_thread_new');
            throw new Exception('Thread text is too long', Exception::WARNING);
        }

        // find a thread with specified title
        $result = $dbEntityForumThread->checkThreadTitle($request['title']);
        if ($result->isError()) {
            $this->result()->setCurrent('Forum_thread_new');
            throw new Exception('Failed to check thread title');
        }

        $threadId = ($result->isSuccess()) ? $result[0]['thread_id'] : 0;

        // thread already exists - redirect to that thread
        if ($threadId > 0) {
            $this->result()
                ->changeRequest('current_thread', $threadId)
                ->setCurrent('Forum_thread');
            throw new Exception('Thread already exists', Exception::WARNING);
        }

        $db->beginTransaction();

        // create new thread
        $thread = $dbEntityForumThread->createThread($request['title'], $player->getUsername(), $request['priority'], $sectionId);
        if (!$thread->save()) {
            $db->rollBack();
            throw new Exception('Failed to create new thread');
        }

        $threadId = $thread->getThreadId();

        // create opening post
        $post = $dbEntityForumPost->createPost($threadId, $player->getUsername(), $request['content']);
        if (!$post->save()) {
            $db->rollBack();
            throw new Exception('Failed to create new post');
        }

        // update post count, last author and last post
        $thread
            ->setPostCount(1)
            ->setLastAuthor($player->getUsername())
            ->setLastPost(Date::timeToStr());

        if (!$thread->save()) {
            $db->rollBack();
            throw new Exception('Failed to save forum thread');
        }

        $db->commit();

        $this->result()->setInfo('Thread created');
    }

    /**
     * Search
     */
    protected function forumSearch()
    {
        $this->result()->setCurrent('Forum_search');
    }

    /**
     * Section -> thread -> lock thread
     * @throws Exception
     */
    protected function threadLock()
    {
        $request = $this->request();

        $this->result()->setCurrent('Forum_section');

        $this->assertParamsNonEmpty(['current_thread']);
        $threadId = $request['current_thread'];

        $thread = $this->dbEntity()->forumThread()->getThreadAsserted($threadId);

        // check if thread isn't deleted
        if ($thread->getIsDeleted() == 1) {
            throw new Exception('Forum thread was already deleted ' . $threadId, Exception::WARNING);
        }

        $this->result()->setCurrent('Forum_thread');

        // check access rights
        if (!$this->checkAccess('lock_thread')) {
            throw new Exception('Access denied', Exception::WARNING);
        }

        // check if thread isn't already locked
        if ($thread->getLocked() == 1) {
            throw new Exception('Thread is already locked', Exception::WARNING);
        }

        // lock thread
        $thread->setLocked(1);
        if (!$thread->save()) {
            throw new Exception('Failed to lock thread');
        }

        $this->result()->setCurrent('Forum_thread');
    }

    /**
     * Section -> thread -> unlock thread
     * @throws Exception
     */
    protected function threadUnlock()
    {
        $request = $this->request();

        $this->result()->setCurrent('Forum_section');

        $this->assertParamsNonEmpty(['current_thread']);
        $threadId = $request['current_thread'];

        $thread = $this->dbEntity()->forumThread()->getThreadAsserted($threadId);

        // check if thread isn't deleted
        if ($thread->getIsDeleted() == 1) {
            throw new Exception('Forum thread was already deleted ' . $threadId, Exception::WARNING);
        }

        $this->result()->setCurrent('Forum_thread');

        // check access rights
        if (!$this->checkAccess('lock_thread')) {
            throw new Exception('Access denied', Exception::WARNING);
        }

        // check if thread isn't already unlocked
        if ($thread->getLocked() == 0) {
            throw new Exception('Thread is already locked', Exception::WARNING);
        }

        // unlock thread
        $thread->setLocked(0);
        if (!$thread->save()) {
            throw new Exception('Failed to unlock thread');
        }

        $this->result()->setInfo('Thread unlocked');
    }

    /**
     * Section -> thread -> delete thread
     * @throws Exception
     */
    protected function threadDelete()
    {
        // only symbolic functionality... rest is handled below
        $this->result()->setCurrent('Forum_thread');

        // check access rights
        if (!$this->checkAccess('del_all_thread')) {
            throw new Exception('Access denied', Exception::WARNING);
        }
    }

    /**
     * Section -> thread -> confirm delete thread
     * @throws Exception
     */
    protected function threadDeleteConfirm()
    {
        $request = $this->request();

        $dbEntityForumPost = $this->dbEntity()->forumPost();

        $this->result()->setCurrent('Forum_section');

        $this->assertParamsNonEmpty(['current_thread']);
        $threadId = $request['current_thread'];

        $thread = $this->dbEntity()->forumThread()->getThreadAsserted($threadId);

        // check if thread isn't deleted
        if ($thread->getIsDeleted() == 1) {
            throw new Exception('Forum thread was already deleted ' . $threadId, Exception::WARNING);
        }

        $this->result()->setCurrent('Forum_thread');

        // check access rights
        if (!$this->checkAccess('del_all_thread')) {
            throw new Exception('Access denied', Exception::WARNING);
        }

        // delete thread
        $thread->setIsDeleted(1);
        if (!$thread->save()) {
            $this->result()->setCurrent('Forum_thread');
            throw new Exception('Failed to delete thread');
        }

        // delete posts contained in the thread
        $result = $dbEntityForumPost->deleteThreadPosts($threadId);
        if ($result->isError()) {
            throw new Exception('Failed to delete thread posts');
        }

        $this->result()
            ->setInfo('Thread deleted')
            ->setCurrent('Forum_section');
    }

    /**
     * Section -> thread -> new post
     * @throws Exception
     */
    protected function newPost()
    {
        $request = $this->request();

        $this->result()->setCurrent('Forum_section');

        $this->assertParamsNonEmpty(['current_thread']);
        $threadId = $request['current_thread'];

        $thread = $this->dbEntity()->forumThread()->getThreadAsserted($threadId);

        // check if thread isn't deleted
        if ($thread->getIsDeleted() == 1) {
            throw new Exception('Forum thread was already deleted ' . $threadId, Exception::WARNING);
        }

        $this->result()->setCurrent('Forum_thread');

        // check if thread is locked
        if ($thread->getLocked() == 1) {
            throw new Exception('Thread is locked', Exception::WARNING);
        }

        // check access rights
        if (!$this->checkAccess('create_post')) {
            throw new Exception('Access denied', Exception::WARNING);
        }

        $this->result()->setCurrent('Forum_post_new');
    }

    /**
     * Section -> thread -> create new post
     * @throws Exception
     */
    protected function createPost()
    {
        $request = $this->request();

        $player = $this->getCurrentPlayer();
        $dbEntityPost = $this->dbEntity()->forumPost();

        $this->result()->setCurrent('Forum_section');

        $this->assertParamsNonEmpty(['current_thread']);
        $threadId = $request['current_thread'];

        $thread = $this->dbEntity()->forumThread()->getThreadAsserted($threadId);

        // check if thread isn't deleted
        if ($thread->getIsDeleted() == 1) {
            throw new Exception('Forum thread was already deleted ' . $threadId, Exception::WARNING);
        }

        $this->result()->setCurrent('Forum_thread');

        // check if thread is locked
        if ($thread->getLocked() == 1) {
            throw new Exception('Thread is locked', Exception::WARNING);
        }

        // check access rights
        if (!$this->checkAccess('create_post')) {
            throw new Exception('Access denied', Exception::WARNING);
        }

        $this->assertParamsNonEmpty(['content']);

        // validate user input
        if (trim($request['content']) == '') {
            $this->result()->setCurrent('Forum_post_new');
            throw new Exception('Invalid post text', Exception::WARNING);
        }

        // check post length
        if (mb_strlen($request['content']) > ForumPostModel::POST_LENGTH) {
            $this->result()->setCurrent('Forum_post_new');
            throw new Exception('Post text is too long', Exception::WARNING);
        }

        // determine latest post creation
        $result = $dbEntityPost->getLatestPost($player->getUsername());
        if ($result->isError()) {
            $this->result()->setCurrent('Forum_post_new');
            throw new Exception('Failed to check latest post creation');
        }
        $latestPost = ($result->isSuccess()) ? $result[0]['created_at'] : 0;

        // anti-spam protection (user is allowed to create posts at most every 5 seconds)
        if ($latestPost && (time() - Date::strToTime($latestPost)) <= 5) {
            $this->result()->setCurrent('Forum_post_new');
            throw new Exception('Post creation cool-down is in progress', Exception::WARNING);
        }

        $post = $dbEntityPost->createPost($threadId, $player->getUsername(), $request['content']);
        if (!$post->save()) {
            throw new Exception('Failed to create new post');
        }

        // update post count, last author and last post
        $thread
            ->setPostCount($thread->getPostCount() + 1)
            ->setLastAuthor($player->getUsername())
            ->setLastPost(Date::timeToStr());

        if (!$thread->save()) {
            throw new Exception('Failed to save forum thread');
        }

        $this->result()
            ->changeRequest('CurrentPage', max(ceil($thread->getPostCount() / ForumPostModel::POSTS_PER_PAGE) - 1, 0))
            ->setInfo('Post created');
    }

    /**
     * Section -> thread -> quote post
     * @throws Exception
     */
    protected function quotePost()
    {
        $request = $this->request();

        $this->result()->setCurrent('Forum_section');

        $this->assertParamsNonEmpty(['current_thread']);
        $threadId = $request['current_thread'];

        $thread = $this->dbEntity()->forumThread()->getThreadAsserted($threadId);

        // check if thread isn't deleted
        if ($thread->getIsDeleted() == 1) {
            throw new Exception('Forum thread was already deleted ' . $threadId, Exception::WARNING);
        }

        $this->result()->setCurrent('Forum_thread');

        // check if thread is locked and if you have access to unlock it
        if ($thread->getLocked() == 1 && !$this->checkAccess('lock_thread')) {
            throw new Exception('Thread is locked', Exception::WARNING);
        }

        // check access rights
        if (!$this->checkAccess('create_post')) {
            throw new Exception('Access denied', Exception::WARNING);
        }

        $this->result()->setCurrent('Forum_post_new');
    }

    /**
     * Section -> thread -> edit thread
     * @throws Exception
     */
    protected function editThread()
    {
        $request = $this->request();
        $player = $this->getCurrentPlayer();

        $this->result()->setCurrent('Forum_section');

        $this->assertParamsNonEmpty(['current_thread']);
        $threadId = $request['current_thread'];

        $thread = $this->dbEntity()->forumThread()->getThreadAsserted($threadId);

        // check if thread isn't deleted
        if ($thread->getIsDeleted() == 1) {
            throw new Exception('Forum thread was already deleted ' . $threadId, Exception::WARNING);
        }

        $this->result()->setCurrent('Forum_thread');

        // check if thread is locked and if you have access to unlock it
        if ($thread->getLocked() == 1 && !$this->checkAccess('lock_thread')) {
            throw new Exception('Thread is locked', Exception::WARNING);
        }

        // check access rights
        if (!$this->checkAccess('create_post')) {
            throw new Exception('Access denied', Exception::WARNING);
        }

        // check access rights
        if (!($this->checkAccess('edit_all_thread') || ($this->checkAccess('edit_own_thread') && $thread->getAuthor() == $player->getUsername()))) {
            throw new Exception('Access denied', Exception::WARNING);
        }

        $this->result()->setCurrent('Forum_thread_edit');
    }

    /**
     * Section -> thread -> modify thread
     * @throws Exception
     */
    protected function modifyThread()
    {
        $request = $this->request();

        $player = $this->getCurrentPlayer();

        $this->result()->setCurrent('Forum_section');

        $this->assertParamsNonEmpty(['current_thread']);
        $threadId = $request['current_thread'];

        $thread = $this->dbEntity()->forumThread()->getThreadAsserted($threadId);

        // check if thread isn't deleted
        if ($thread->getIsDeleted() == 1) {
            throw new Exception('Forum thread was already deleted ' . $threadId, Exception::WARNING);
        }

        $this->result()->setCurrent('Forum_thread');

        // check if thread is locked and if you have access to unlock it
        if ($thread->getLocked() == 1 && !$this->checkAccess('lock_thread')) {
            throw new Exception('Thread is locked', Exception::WARNING);
        }

        // check access rights
        if (!($this->checkAccess('edit_all_thread') || ($this->checkAccess('edit_own_thread')
                && $thread->getAuthor() == $player->getUsername()))) {
            throw new Exception('Access denied', Exception::WARNING);
        }

        // validate thread title
        $this->assertParamsNonEmpty(['title']);
        if (trim($request['title']) == '') {
            throw new Exception('Invalid title', Exception::WARNING);
        }

        // priority input is optional
        $newPriority = Input::defaultValue($request, 'priority', $thread->getPriority());

        // validate priority
        if (!in_array($newPriority, ['normal', 'important', 'sticky'])) {
            $this->result()->setCurrent('Forum_thread_edit');
            throw new Exception('Invalid thread priority', Exception::WARNING);
        }

        // check access rights
        if (!$this->checkAccess('change_priority') && $newPriority != $thread->getPriority()) {
            throw new Exception('Access denied', Exception::WARNING);
        }

        $thread
            ->setTitle($request['title'])
            ->setPriority($newPriority);

        if (!$thread->save()) {
            throw new Exception('Failed to edit thread');
        }

        $this->result()->setInfo('Changes saved');
    }

    /**
     * Section -> thread -> edit thread -> move thread to a new section
     * @throws Exception
     */
    protected function moveThread()
    {
        $request = $this->request();

        $dbEntityForumSection = $this->dbEntity()->forumSection();

        $this->result()->setCurrent('Forum_section');

        $this->assertParamsNonEmpty(['current_thread']);
        $threadId = $request['current_thread'];

        $thread = $this->dbEntity()->forumThread()->getThreadAsserted($threadId);

        // check if thread isn't deleted
        if ($thread->getIsDeleted() == 1) {
            throw new Exception('Forum thread was already deleted ' . $threadId, Exception::WARNING);
        }

        $this->result()->setCurrent('Forum_thread');

        $this->assertParamsNonEmpty(['section_select']);
        $newSection = $request['section_select'];

        // check access rights
        if (!$this->checkAccess('move_thread')) {
            throw new Exception('Access denied', Exception::WARNING);
        }

        // validate section
        $result = $dbEntityForumSection->getSection($newSection);
        if ($result->isError()) {
            throw new Exception('Failed to load forum section');
        }

        if ($result->isNoEffect()) {
            throw new Exception('Forum section not found ' . $newSection, Exception::WARNING);
        }

        // assign thread to new section
        $thread->setSectionId($newSection);
        if (!$thread->save()) {
            $this->result()->setCurrent('Forum_thread_edit');
            throw new Exception('Failed to change sections');
        }

        $this->result()
            ->setInfo('Section changed')
            ->setCurrent('Forum_thread_edit');
    }

    /**
     * Section -> thread -> edit post
     * @throws Exception
     */
    protected function editPost()
    {
        $request = $this->request();
        $player = $this->getCurrentPlayer();

        $this->result()->setCurrent('Forum_section');

        $this->assertParamsNonEmpty(['current_thread']);
        $threadId = $request['current_thread'];

        $thread = $this->dbEntity()->forumThread()->getThreadAsserted($threadId);

        // check if thread isn't deleted
        if ($thread->getIsDeleted() == 1) {
            throw new Exception('Forum thread was already deleted ' . $threadId, Exception::WARNING);
        }

        $this->result()->setCurrent('Forum_thread');

        // check if thread is locked and if you have access to unlock it
        if ($thread->getLocked() == 1 && !$this->checkAccess('lock_thread')) {
            throw new Exception('Thread is locked', Exception::WARNING);
        }

        $postId = $request['edit_post'];

        $post = $this->dbEntity()->forumPost()->getPostAsserted($postId);


        // check if post isn't deleted
        if ($post->getIsDeleted() == 1) {
            $this->result()->setCurrent('Forum_section');
            throw new Exception('Forum post was already deleted ' . $post->getPostId(), Exception::WARNING);
        }

        if (!($this->checkAccess('edit_all_post') || ($this->checkAccess('edit_own_post')
                && $post->getAuthor() == $player->getUsername()))) {
            throw new Exception('Access denied', Exception::WARNING);
        }

        $this->result()
            ->changeRequest('current_post', $postId)
            ->setCurrent('Forum_post_edit');
    }

    /**
     * Section -> thread -> save edited post
     * @throws Exception
     */
    protected function modifyPost()
    {
        $request = $this->request();

        $player = $this->getCurrentPlayer();

        $this->result()->setCurrent('Forum_section');

        $this->assertParamsNonEmpty(['current_thread']);
        $threadId = $request['current_thread'];

        $thread = $this->dbEntity()->forumThread()->getThreadAsserted($threadId);

        // check if thread isn't deleted
        if ($thread->getIsDeleted() == 1) {
            throw new Exception('Forum thread was already deleted ' . $threadId, Exception::WARNING);
        }

        $this->result()->setCurrent('Forum_thread');

        $this->assertParamsNonEmpty(['current_post', 'content']);
        $postId = $request['current_post'];

        // check if thread is locked and if you have access to unlock it
        if ($thread->getLocked() == 1 && !$this->checkAccess('lock_thread')) {
            throw new Exception('Thread is locked', Exception::WARNING);
        }

        $post = $this->dbEntity()->forumPost()->getPostAsserted($postId);

        // check if post isn't deleted
        if ($post->getIsDeleted() == 1) {
            throw new Exception('Forum post was already deleted ' . $post->getPostId(), Exception::WARNING);
        }

        if (!($this->checkAccess('edit_all_post') || ($this->checkAccess('edit_own_post')
                && $post->getAuthor() == $player->getUsername()))) {
            throw new Exception('Access denied', Exception::WARNING);
        }

        // validate post text
        if (trim($request['content']) == '') {
            $this->result()->setCurrent('Forum_post_edit');
            throw new Exception('Invalid post text', Exception::WARNING);
        }

        // validate message length
        if (mb_strlen($request['content']) > ForumPostModel::POST_LENGTH) {
            $this->result()->setCurrent('Forum_post_edit');
            throw new Exception('Post text is too long', Exception::WARNING);
        }

        // edit post content
        $post->setContent($request['content']);
        if (!$post->save()) {
            throw new Exception('Failed to edit post');
        }

        $this->result()->setInfo('Changes saved');
    }

    /**
     * Section -> thread -> delete post
     * @throws Exception
     */
    protected function deletePost()
    {
        $request = $this->request();

        // only symbolic functionality... rest is handled below
        $this->result()->setCurrent('Forum_section');

        $this->assertParamsNonEmpty(['current_thread']);
        $threadId = $request['current_thread'];

        $thread = $this->dbEntity()->forumThread()->getThreadAsserted($threadId);

        // check if thread isn't deleted
        if ($thread->getIsDeleted() == 1) {
            throw new Exception('Forum thread was already deleted ' . $threadId, Exception::WARNING);
        }

        $this->result()->setCurrent('Forum_thread');

        // check if thread is locked and if you have access to unlock it
        if ($thread->getLocked() == 1 && !$this->checkAccess('lock_thread')) {
            throw new Exception('Thread is locked', Exception::WARNING);
        }

        // check access rights
        if (!$this->checkAccess('del_all_post')) {
            throw new Exception('Access denied', Exception::WARNING);
        }

        $this->result()->setInfo('Please confirm post deletion');
    }

    /**
     * Section -> thread -> delete post confirm
     * @throws Exception
     */
    protected function deletePostConfirm()
    {
        $request = $this->request();

        $this->result()->setCurrent('Forum_section');

        $this->assertParamsNonEmpty(['current_thread']);
        $threadId = $request['current_thread'];

        $thread = $this->dbEntity()->forumThread()->getThreadAsserted($threadId);

        // check if thread isn't deleted
        if ($thread->getIsDeleted() == 1) {
            throw new Exception('Forum thread was already deleted ' . $threadId, Exception::WARNING);
        }

        $this->result()->setCurrent('Forum_thread');

        // validate current page
        $this->assertParamsExist(['CurrentPage']);
        if (!is_numeric($request['CurrentPage'])) {
            throw new Exception('Invalid thread page', Exception::WARNING);
        }

        // check if thread is locked and if you have access to unlock it
        if ($thread->getLocked() == 1 && !$this->checkAccess('lock_thread')) {
            throw new Exception('Thread is locked', Exception::WARNING);
        }

        // check access rights
        if (!$this->checkAccess('del_all_post')) {
            throw new Exception('Access denied', Exception::WARNING);
        }

        $postId = $request['delete_post_confirm'];
        $post = $this->dbEntity()->forumPost()->getPostAsserted($postId);

        // check if post isn't deleted
        if ($post->getIsDeleted() == 1) {
            throw new Exception('Forum post was already deleted ' . $post->getPostId(), Exception::WARNING);
        }

        $post->setIsDeleted(1);
        if (!$post->save()) {
            throw new Exception('Failed to delete post');
        }

        // load last post data
        $lastPost = $this->service()->forum()->getLastPost($threadId);

        // update post count, last author and last post
        $thread
            ->setPostCount($thread->getPostCount() - 1)
            ->setLastAuthor($lastPost['author'])
            ->setLastPost($lastPost['created_at']);

        if (!$thread->save()) {
            throw new Exception('Failed to save forum thread');
        }

        $maxPage = max(ceil($thread->getPostCount() / ForumPostModel::POSTS_PER_PAGE) - 1, 0);
        $this->result()
            ->changeRequest('CurrentPage', ($request['CurrentPage'] <= $maxPage) ? $request['CurrentPage'] : $maxPage)
            ->setInfo('Post deleted');
    }

    /**
     * Section -> thread -> post -> edit post -> move post to a new thread
     * @throws Exception
     */
    protected function movePost()
    {
        $request = $this->request();

        $dbEntityForumThread = $this->dbEntity()->forumThread();

        $this->result()->setCurrent('Forum_section');

        $this->assertParamsNonEmpty(['current_thread']);
        $threadId = $request['current_thread'];

        $sourceThread = $dbEntityForumThread->getThreadAsserted($threadId);

        // check if thread isn't deleted
        if ($sourceThread->getIsDeleted() == 1) {
            throw new Exception('Forum thread was already deleted ' . $threadId, Exception::WARNING);
        }

        $this->result()->setCurrent('Forum_thread');

        // check access rights
        if (!$this->checkAccess('move_post')) {
            throw new Exception('Access denied', Exception::WARNING);
        }

        $this->assertParamsNonEmpty(['current_post', 'thread_select']);
        $postId = $request['current_post'];
        $newThreadId = $request['thread_select'];

        $targetThread = $dbEntityForumThread->getThreadAsserted($newThreadId);

        // check if thread isn't deleted
        if ($targetThread->getIsDeleted() == 1) {
            throw new Exception('Forum thread was already deleted ' . $newThreadId, Exception::WARNING);
        }

        $post = $this->dbEntity()->forumPost()->getPostAsserted($postId);

        // check if post isn't deleted
        if ($post->getIsDeleted() == 1) {
            throw new Exception('Forum post was already deleted ' . $post->getPostId(), Exception::WARNING);
        }

        $post->setThreadId($newThreadId);
        if (!$post->save()) {
            throw new Exception('Failed to change threads');
        }

        // load last post data
        $lastPost = $this->service()->forum()->getLastPost($threadId);

        // update post count, last author and last post
        $sourceThread
            ->setPostCount($sourceThread->getPostCount() - 1)
            ->setLastAuthor($lastPost['author'])
            ->setLastPost($lastPost['created_at']);

        if (!$sourceThread->save()) {
            throw new Exception('Failed to save forum thread');
        }

        // load last post data
        $lastPost = $this->service()->forum()->getLastPost($newThreadId);

        // update post count, last author and last post
        $targetThread
            ->setPostCount($targetThread->getPostCount() + 1)
            ->setLastAuthor($lastPost['author'])
            ->setLastPost($lastPost['created_at']);

        if (!$targetThread->save()) {
            throw new Exception('Failed to save forum thread');
        }

        // go to first page of target thread on success
        $this->result()
            ->changeRequest('CurrentPage', 0)
            ->setInfo('Thread changed')
            ->setCurrent('Forum_post_edit');
    }
}
