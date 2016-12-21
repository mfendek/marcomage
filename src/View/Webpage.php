<?php
/**
 * Webpage - webpage related view module
 */

namespace View;

use ArcomageException as Exception;

class Webpage extends TemplateDataAbstract
{
    /**
     * @throws Exception
     * @return Result
     */
    protected function webpage()
    {
        $data = array();
        $input = $this->input();

        // decide what screen is default (depends on whether the user is logged in)
        $defaultPage = ($this->isSession()) ? 'News' : 'Main';
        $data['selected'] = $subsectionName = $selected = isset($input['WebSection']) ? $input['WebSection'] : $defaultPage;

        $webSections = ['Main', 'News', 'Archive', 'Modified', 'Faq', 'Credits', 'History'];
        if (!in_array($selected, $webSections)) {
            throw new Exception('Invalid web section', Exception::WARNING);
        }

        // case 1: display all news when viewing news archive
        if ($selected == 'Archive') {
            $selected = 'News';
            $data['recent_news_only'] = 'no';
        }
        // case 2: display only recent news otherwise
        else {
            $data['recent_news_only'] = 'yes';
        }

        // list the names of the files to display
        // (all files whose name matches up to the first space character)
        $files = preg_grep('/^' . $selected . '( .*)?\.xml/i', scandir('templates/pages', 1));

        $setting = $this->getCurrentSettings();

        $data['files'] = $files;
        $data['timezone'] = $setting->getSetting('timezone');

        return new Result(['webpage' => $data], $subsectionName);
    }

    /**
     * @return Result
     */
    protected function help()
    {
        $data = array();
        $input = $this->input();

        $data['part'] = $subsectionName = (isset($input['help_part'])) ? $input['help_part'] : 'Introduction';

        return new Result(['help' => $data], $subsectionName);
    }

    /**
     * @return Result
     */
    protected function registration()
    {
        $data = array();
        $config = $this->getDic()->config();

        $data['captcha_key'] = ($config['captcha']['enabled']) ? $config['captcha']['public_key'] : '';

        return new Result(['registration' => $data]);
    }
}
