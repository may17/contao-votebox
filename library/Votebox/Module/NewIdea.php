<?php

/**
 * Contao Open Source CMS
 * Copyright (C) 2005-2012 Leo Feyer
 *
 * Formerly known as TYPOlight Open Source CMS.
 *
 * This program is free software: you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation, either
 * version 3 of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU
 * Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public
 * License along with this program. If not, please visit the Free
 * Software Foundation website at <http://www.gnu.org/licenses/>.
 *
 * PHP version 5
 * @copyright  terminal42 gmbh 2012-2013
 * @author     Yanick Witschi <yanick.witschi@terminal42.ch>
 * @package    votebox
 * @license    LGPL
 * @filesource
 */

namespace Votebox\Module;

use Votebox\Model\Idea;

class NewIdea extends Votebox
{

    /**
     * Template
     * @var string
     */
    protected $strTemplate = 'mod_votebox_new_idea';

    /**
     * Display a wildcard in the back end
     * @return string
     */
    public function generate()
    {
        if (TL_MODE == 'BE') {
            $objTemplate = new \BackendTemplate('be_wildcard');
            $objTemplate->wildcard = '### VOTEBOX: NEW IDEA ###';
            $objTemplate->title = $this->headline;
            $objTemplate->id = $this->id;
            $objTemplate->link = $this->name;
            $objTemplate->href = 'contao/main.php?do=themes&amp;table=tl_module&amp;act=edit&amp;id=' . $this->id;

            return $objTemplate->parse();
        }

        return parent::generate();
    }


    /**
     * Generate module
     */
    protected function compile()
    {
        $objForm = $this->prepareForm();

        if ($objForm->validate()) {
            $this->processData($objForm->fetchAll());
            
            // redirect or reload
            $this->jumpToOrReload($this->vb_new_idea_jumpTo);
        }

        $objTemplate = new \FrontendTemplate(($this->vb_new_idea_tpl) ? $this->vb_new_idea_tpl : 'votebox_new_idea_default');
        $objTemplate->form = $objForm->generate();
        
        $this->Template->content = $objTemplate->parse();
    }


    /**
     * Process the form data
     * @param array
     */
    protected function processData($arrFormData)
    {
        $objIdea = new Idea();
        $objIdea->pid               = $this->vb_archive;
        $objIdea->tstamp            = time();
        $objIdea->title             = $arrFormData['title'];
        $objIdea->creation_date     = time();
        $objIdea->text              = $arrFormData['text'];

        if ($this->objArchive->mode == 'member') {
            $objIdea->member_id = \FrontendUser::getInstance()->id;
        }

        if ($this->objArchive->mode == 'guest') {
            $objIdea->firstname     = $arrFormData['firstname'];
            $objIdea->lastname      = $arrFormData['lastname'];
            $objIdea->email         = $arrFormData['email'];
        }

        // send notification if it is moderated
        if ($this->objArchive->moderate) {
            $objEmail = new \EmailTemplate($this->admin_mail_template);
            $objEmail->send($GLOBALS['TL_ADMIN_EMAIL'], $objIdea->row());
        } else {
            $objIdea->published = 1;
        }

        $objIdea->save();
    }


    /**
     * Prepare form
     * @return HasteForm
     */
    protected function prepareForm()
    {
        $arrFields = array();

        if ($this->objArchive->mode == 'guest') {
            \System::loadLanguageFile('tl_member');
            $arrFields['firstname'] = array
            (
                'label'                        => &$GLOBALS['TL_LANG']['tl_member']['firstname'],
                'inputType'                    => 'text',
                'eval'                         => array('mandatory'=>true)
            );
            $arrFields['lastname'] = array
            (
                'label'                        => &$GLOBALS['TL_LANG']['tl_member']['lastname'],
                'inputType'                    => 'text',
                'eval'                         => array('mandatory'=>true)
            );
            $arrFields['email'] = array
            (
                'label'                        => &$GLOBALS['TL_LANG']['tl_member']['email'],
                'inputType'                    => 'text',
                'eval'                         => array('mandatory'=>true, 'rgxp'=>'email')
            );
        }

        $arrFields['title'] = array
        (
            'label'                        => &$GLOBALS['TL_LANG']['MSC']['form_votebox_new_idea']['title'],
            'inputType'                    => 'text',
            'eval'                         => array('mandatory'=>true)
        );
        $arrFields['text'] = array
        (
            'label'                        => &$GLOBALS['TL_LANG']['MSC']['form_votebox_new_idea']['text'],
            'inputType'                    => 'textarea',
            'eval'                         => array('rte'=>'tinyMCE', 'mandatory'=>true)
        );

        $objForm = new \Haste\Form('vb_new_idea', 'POST', function($objHaste) {
            return \Input::post('FORM_SUBMIT') === $objHaste->getFormId();
        });

        $objForm->addFormFields($arrFields);
        $objForm->addCaptchaFormField('captcha');
        $objForm->addSubmitFormField('submit', $GLOBALS['TL_LANG']['MSC']['form_votebox_new_idea']['submit']);

        return $objForm;
    }
}