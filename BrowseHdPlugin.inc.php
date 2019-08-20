<?php

/**
 * @file plugins/blocks/browseHd/BrowseHdBlockPlugin.inc.php
 *
 * Copyright (c) 2014-2018 Simon Fraser University
 * Copyright (c) 2003-2018 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class DevelopedByBlockPlugin
 * @ingroup plugins_blocks_browseHd
 *
 * @brief Class for "browse" block plugin
 */



import('lib.pkp.classes.plugins.GenericPlugin');

class BrowseHdPlugin extends GenericPlugin {
        /**
         * Get the display name of this plugin.
         * @return String
         */
        function getDisplayName() {
                return __('plugins.generic.browseHd.displayName');
        }

        /**
         * Get a description of the plugin.
         */
        function getDescription() {
                return __('plugins.generic.browseHd.description');
        }

        function register($category, $path, $mainContextId = null) {
                if (parent::register($category, $path, $mainContextId)) {
                        if ($this->getEnabled($mainContextId)) {
                                $this->import('BrowseHdBlockPlugin');
                                $blockPlugin = new BrowseHdBlockPlugin($this);
                                PluginRegistry::register('blocks', $blockPlugin, $this->getPluginPath());
                                // Insert the OrcidHandler to handle ORCID redirects
                                HookRegistry::register('LoadHandler', array($this, 'setupCallbackHandler'));
                                HookRegistry::register('sectiondao::getAdditionalFieldNames', array($this, 'addSectionDAOFieldNames'));
                                HookRegistry::register('sectiondao::getLocaleFieldNames', array($this, 'addSectionDAOLocaleFieldNames'));
                                HookRegistry::register('Templates::Manager::Sections::SectionForm::AdditionalMetadata', array($this, 'addSectionFormFields'));
                                HookRegistry::register('sectionform::initdata', array($this, 'initDataSectionFormFields'));
                                HookRegistry::register('sectionform::readuservars', array($this, 'readSectionFormFields'));
                                HookRegistry::register('sectionform::execute', array($this, 'executeSectionFormFields'));
                        }
                        return true;
                }
                return false;
                
        }

	/**
	 * Install default settings on system install.
	 * @return string
	 */
	function getInstallSitePluginSettingsFile() {
		return $this->getPluginPath() . '/settings.xml';
	}

	/**
	 * Install default settings on journal creation.
	 * @return string
	 */
	function getContextSpecificPluginSettingsFile() {
		return $this->getPluginPath() . '/settings.xml';
	}

        /**
         * Get page handler path for this plugin.
         * @return string Path to plugin's page handler
         */
        function getHandlerPath() {
                return $this->getPluginPath() . DIRECTORY_SEPARATOR . 'pages';
        }

        /**
         * Hook callback: register pages for each sushi-lite method
         * This URL is of the form: browse/{$browserequest}
         * @see PKPPageRouter::route()
         */
        function setupCallbackHandler($hookName, $params) {
                $page = $params[0];
                if ($this->getEnabled() && $page == 'browse') {
                        $this->import('pages/BrowseHdHandler');
                        define('HANDLER_CLASS', 'BrowseHdHandler');
                        return true;
                }
                return false;
        }

        /**
         * Add section settings to SectionDAO
         *
         * @param $hookName string
         * @param $args array [
         *              @option SectionDAO
         *              @option array List of additional fields
         * ]
         */
        public function addSectionDAOFieldNames($hookName, $args) {
                $fields =& $args[1];
                $fields[] = 'browseHdByEnabled';
        }

        /**
         * Add localized section settings to SectionDAO
         *
         * @param $hookName string
         * @param $args array [
         *              @option SectionDAO
         *              @option array List of additional localized fields
         * ]
         */
        public function addSectionDAOLocaleFieldNames($hookName, $args) {
                $fields =& $args[1];
                $fields[] = 'browseHdByDescription';
        }

        /**
         * Add fields to the section editing form
         *
         * @param $hookName string `Templates::Manager::Sections::SectionForm::AdditionalMetadata`
         * @param $args array [
         *              @option array [
         *                              @option name string Hook name
         *                              @option sectionId int
         *              ]
         *              @option Smarty
         *              @option string
         * ]
         * @return bool
         */
        public function addSectionFormFields($hookName, $args) {
                $smarty =& $args[1];
                $output =& $args[2];
                $output .= $smarty->fetch($this->getTemplateResource('controllers/grids/settings/section/form/sectionFormAdditionalFields.tpl'));

                return false;
        }

        /**
         * Initialize data when form is first loaded
         *
         * @param $hookName string `sectionform::initData`
         * @parram $args array [
         *              @option SectionForm
         * ]
         */
        public function initDataSectionFormFields($hookName, $args) {
                $sectionForm = $args[0];
                $request = Application::getRequest();
                $context = $request->getContext();
                $contextId = $context ? $context->getId() : CONTEXT_ID_NONE;

                $sectionDao = DAORegistry::getDAO('SectionDAO');
                $section = $sectionDao->getById($sectionForm->getSectionId(), $contextId);

                if ($section) {
                        $sectionForm->setData('browseHdByEnabled', $section->getData('browseHdByEnabled'));
                        $sectionForm->setData('browseHdByDescription', $section->getData('browseHdByDescription'));
                }
        }
        /**
         * Read user input from additional fields in the section editing form
         *
         * @param $hookName string `sectionform::readUserVars`
         * @parram $args array [
         *              @option SectionForm
         *              @option array User vars
         * ]
         */
        public function readSectionFormFields($hookName, $args) {
                $sectionForm =& $args[0];
                $request = Application::getRequest();

                $sectionForm->setData('browseHdByEnabled', $request->getUserVar('browseHdByEnabled'));
                $sectionForm->setData('browseHdByDescription', $request->getUserVar('browseHdByDescription', null));
        }

        /**
         * Save additional fields in the section editing form
         *
         * @param $hookName string `sectionform::execute`
         * @param $args array [
         *              @option SectionForm
         * ]
         */
        public function executeSectionFormFields($hookName, $args) {
                $sectionDao = DAORegistry::getDAO('SectionDAO');
                $sectionForm = $args[0];
                $section = $sectionDao->getById($sectionForm->getSectionId(), Application::getRequest()->getContext()->getId());

                $section->setData('browseHdByEnabled', $sectionForm->getData('browseHdByEnabled'));
                $section->setData('browseHdByDescription', $sectionForm->getData('browseHdByDescription'));
                $sectionDao->updateObject($section);
        }

}

?>
