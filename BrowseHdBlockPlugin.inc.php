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



import('lib.pkp.classes.plugins.BlockPlugin');

class BrowseHdBlockPlugin extends BlockPlugin {
        /** @var LogoManagerPlugin parent plugin */
        var $parentPlugin;

        function __construct($parentPlugin) {
                parent::__construct();
                $this->parentPlugin = $parentPlugin;
        }

        /**
         * Get the name of this plugin. The name must be unique within
         * its category.
         * @return String name of plugin
         */
        function getName() {
                return 'BrowseHdBlockPlugin';
        }

        /**
         * Hide this plugin from the management interface (it's subsidiary)
         */
        function getHideManagement() {
                return true;
        }

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

        /**
         * Get the supported contexts (e.g. BLOCK_CONTEXT_...) for this block.
         * @return array
         */
        function getSupportedContexts() {
                return array(BLOCK_CONTEXT_SIDEBAR);
        }

        /**
         * Get the BrowseHd plugin
         * @return BrowseHdPlugin
         */
        function getBrowseHdPlugin() {
                return $this->parentPlugin;
        }

        function register($category, $path, $mainContextId = NULL) {
                $success = parent::register($category, $path, $mainContextId);
                if ($success && $this->getEnabled($mainContextId)) {
                        // Add stylesheet and javascript
                        HookRegistry::register('TemplateManager::display',array($this, 'displayCallback'));

                }
                return $success;
        }

        function displayCallback($hookName, $params) {
                $template = $params[1];
                $templateMgr = $params[0];
                $templateMgr->addStylesheet('browseHd', Request::getBaseUrl() . DIRECTORY_SEPARATOR . $this->getPluginPath() . DIRECTORY_SEPARATOR . 'css' . DIRECTORY_SEPARATOR . 'browseHd.css');
                $templateMgr->addJavaScript('browseHd', Request::getBaseUrl() . DIRECTORY_SEPARATOR . $this->getPluginPath() . DIRECTORY_SEPARATOR . 'js' . DIRECTORY_SEPARATOR . 'main.js');

                switch ($template) {
                        case 'frontend/pages/catalogCategory.tpl':
                                $breadcrumbs=$templateMgr->registerFilter("output", array($this, 'categoryBreadcrumbs'));
                                break;
                }
                return false;
        }

        
        function categoryBreadcrumbs($output, $templateMgr) {
                if ($templateMgr->template_resource == "frontend/pages/catalogCategory.tpl" && preg_match_all('/<nav[^>]+class="cmp_breadcrumbs cmp_breadcrumbs_catalog"[^>]*>/', $output, $matches, PREG_OFFSET_CAPTURE)) {

                        $request = $this->getRequest();
                        $journal = $request->getJournal();
                        $categoryDao = DAORegistry::getDAO('CategoryDAO');

                        $router = $request->getRouter();
                        $context = $request->getContext();

                        $requestedCategoryPath = null;
                        $args = $router->getRequestedArgs($request);
                        if ($router->getRequestedPage($request) . '/' . $router->getRequestedOp($request) == 'catalog/category') $requestedCategoryPath = reset($args);

                        $category = $categoryDao->getByPath($requestedCategoryPath, $context->getId());
                        $parentId = $category->getParentId();
                        $parentCategory = $categoryDao->getById($category->getParentId());
                        
                        $templateMgr->assign(array(
                             'currentTitle' => $category->getLocalizedTitle(),
                             'parent' => $parentCategory,
                        ));
                        $categoryBreadcrumbsOutput = $templateMgr->fetch($this->getTemplateResource('category_breadcrumbs.tpl'));

                        $additionalOffet = 0;
                        foreach ($matches[0] as $matchResult) {
                                $match = $matchResult[0];
                                $offset = $matchResult[1] + $additionalOffset;
                                $output = substr_replace($output, $categoryBreadcrumbsOutput, $offset, 0);
                                $additionalOffset += strlen($categoryBreadcrumbsOutput);
                        }
//                        $templateMgr->unregisterFilter('output', array($this, 'registrationFilter'));
                }
                return $output;
                }

        function getContents($templateMgr, $request = null) {
                $sectionsEnabled = 0;
                $journal = $request->getJournal();
                $sectionDao = DAORegistry::getDAO('SectionDAO');
                $sectionsIterator = $sectionDao->getByJournalId($journal->getId());
                while ($section = $sectionsIterator->next()) {
                        if (!empty($section->getData('browseHdByEnabled'))) {
                                    $sectionsEnabled = 1;
                                }
                }
                $templateMgr->assign('sectionsEnabled', $sectionsEnabled);

                $context = $request->getContext();
                if (!$context) {
                        return '';
                }
                $categoryDao = DAORegistry::getDAO('CategoryDAO');
                $router = $request->getRouter();

                $requestedCategoryPath = null;
                $args = $router->getRequestedArgs($request);
                if ($router->getRequestedPage($request) . '/' . $router->getRequestedOp($request) == 'catalog/category') $requestedCategoryPath = reset($args);
                $templateMgr->assign(array(
                        'browseBlockSelectedCategory' => $requestedCategoryPath,
                        'browseCategoryFactory' => $categoryDao->getByContextId($context->getId()),
                ));

                return parent::getContents($templateMgr, $request);
        }

}

?>
