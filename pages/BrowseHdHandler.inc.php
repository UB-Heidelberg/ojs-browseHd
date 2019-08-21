<?php

/**
 * @file plugins/generic/orcidProfile/OrcidHandler.inc.php
 *
 * Copyright (c) 2015-2018 University of Pittsburgh
 * Copyright (c) 2014-2019 Simon Fraser University
 * Copyright (c) 2003-2019 John Willinsky
 * Distributed under the GNU GPL v2 or later. For full terms see the file docs/COPYING.
 *
 * @class OrcidHandler
 * @ingroup plugins_generic_orcidprofile
 *
 * @brief Pass off internal ORCID API requests to ORCID
 */

import('classes.handler.Handler');
import('classes.article.Author');
import('classes.article.Article');
import('lib.pkp.classes.submission.PKPAuthorDAO');
import('lib.pkp.classes.core.VirtualArrayIterator');

class BrowseHdHandler extends Handler {


	function getAuthorsAlphabetizedByJournalHd($journalId = null, $initial = null, $rangeInfo = null, $includeEmail = false) {
//Beginn neuer Code
                $authorDao = DAORegistry::getDAO('AuthorDAO');

                $params = $authorDao->getFetchParameters();
                if (isset($journalId)) $params[] = $journalId;
		error_log("BrowseHdHandler: journalId = " . var_export($journalId, true));
                $supportedLocales = array();
                if ($journalId !== null) {
                        $journalDao = DAORegistry::getDAO('JournalDAO');
                        $journal = $journalDao->getById($journalId);
                        $supportedLocales = $journal->getSupportedLocales();
                } else {
                        $site = Application::getRequest()->getSite();
                        $supportedLocales = $site->getSupportedLocales();;
                }
                $supportedLocalesCount = count($supportedLocales);
                $sqlJoinAuthorSettings = $sqlColumnsAuthorSettings = $initialSql = '';
                if (isset($initial)) {
                        $initialSql = ' AND (';
                }
		$index = 0;
                foreach ($supportedLocales as $locale) {			
                        $localeStr = str_replace('@', '_', $locale);
                        $sqlColumnsAuthorSettings .= ",
                                COALESCE(asg$index.setting_value, ''), ' ',
                                COALESCE(asf$index.setting_value, ''), ' '
                                
                        ";
                        $sqlJoinAuthorSettings .= "
                                LEFT JOIN author_settings asg$index ON (asg$index.author_id  = aa.author_id AND asg$index.setting_name = '" . IDENTITY_SETTING_GIVENNAME . "' AND asg$index.locale = '$locale')
                                LEFT JOIN author_settings asf$index ON (asf$index.author_id  = aa.author_id AND asf$index.setting_name = '" . IDENTITY_SETTING_FAMILYNAME . "' AND asf$index.locale = '$locale')";
				// ignore affialiation
                                /*LEFT JOIN author_settings asa$index ON (asa$index.author_id  = aa.author_id AND asa$index.setting_name = 'affiliation' AND asa$index.locale = '$locale') */
                       
                        if (isset($initial)) {
                                if ($initial == '-') {
                                        $initialSql .= "(asf$index.setting_value IS NULL OR asf$index.setting_value = '')";
                                        if ($index < $supportedLocalesCount - 1) {
                                                $initialSql .= ' AND ';
                                        }
                                } else {
                                        $params[] = PKPString::strtolower($initial) . '%';
                                        $initialSql .= "LOWER(asf$index.setting_value) LIKE LOWER(?)";
                                        if ($index < $supportedLocalesCount - 1) {
                                                $initialSql .= ' OR ';
                                        }
                                }
                        }
			$index++;
                }
                if (isset($initial)) {
                        $initialSql .= ')';
                }
		
                $result = $authorDao->retrieveRange(
                        'SELECT a.*, ug.show_title, s.locale,
                                ' . $authorDao->getFetchColumns() . '				
                        FROM    authors a
                                JOIN user_groups ug ON (a.user_group_id = ug.user_group_id)
                                JOIN submissions s ON (s.submission_id = a.submission_id)
                                ' . $authorDao->getFetchJoins() . '
                                JOIN (
                                        SELECT
                                        MIN(aa.author_id) as author_id,
                                        TRIM(CONCAT(' . ($includeEmail ? 'aa.email, " ",' : '""') . '
                                        ' . $sqlColumnsAuthorSettings . '
                                        )) as names
                                        FROM    authors aa
                                        JOIN submissions ss ON (ss.submission_id = aa.submission_id AND ss.status = ' . STATUS_PUBLISHED . ')
                                        JOIN journals j ON (ss.context_id = j.journal_id)
                                        JOIN published_submissions ps ON (ps.submission_id = ss.submission_id)
                                        JOIN issues i ON (ps.issue_id = i.issue_id AND i.published = 1)
                                        ' . $sqlJoinAuthorSettings . '
                                        WHERE j.enabled = 1 AND
                                        ' . (isset($journalId) ? 'j.journal_id = ?' : '')
                                        . $initialSql .'
                                        AND aa.include_in_browse = 1
                                        GROUP BY names
                                ) as t1 ON (t1.author_id = a.author_id)
                                ' . $authorDao->getOrderBy(),
                        $params,
                        $rangeInfo
                );

                return new DAOResultFactory($result, $authorDao, '_fromRow');
	}

/**
	 * Retrieve all published submissions associated with authors with
	 * the given name, family name, affiliation, and country.
	 * Authors are considered to be the same if they have the same given name and family name in one locale,
	 * as well as affiliation (optional) and country (optional)
	 * @param $journalId int (null if no restriction desired)
	 * @param $givenName string
	 * @param $familyName string
	 * @param $affiliation string (optional)
	 * @param $country string (optional)
	 */
	function &getPublishedArticlesForAuthor($journalId, $givenName, $familyName, $affiliation = null, $country = null) {
		$params = array();

		$supportedLocales = array();
                $authorDao = DAORegistry::getDAO('AuthorDAO');
		if ($journalId !== null) {
			$journalDao = DAORegistry::getDAO('JournalDAO');
			$journal = $journalDao->getById($journalId);
			$supportedLocales = $journal->getSupportedLocales();
		} else {
			$site = Application::getRequest()->getSite();
			$supportedLocales = $site->getSupportedLocales();;
		}
		$supportedLocalesCount = count($supportedLocales);
		$sqlJoinAuthorSettings = $sqlWhereAffiliation = $sqlWhereCountry = '';
		$sqlWhereAuthorSettings = '(';
		$index = 0;
		foreach ($supportedLocales as $locale) {		
			$sqlJoinAuthorSettings .= "
				LEFT JOIN author_settings asg$index ON (asg$index.author_id  = a.author_id AND asg$index.setting_name = '" . IDENTITY_SETTING_GIVENNAME . "' AND asg$index.locale = '$locale')
				LEFT JOIN author_settings asf$index ON (asf$index.author_id  = a.author_id AND asf$index.setting_name = '" . IDENTITY_SETTING_FAMILYNAME . "' AND asf$index.locale = '$locale')
			";
			$params[] = $givenName;
			if (empty($familyName)) {
				$sqlWhereFamilyName = "(asf$index.setting_value is NULL OR asf$index.setting_value = '')";
			} else {
				$sqlWhereFamilyName = "asf$index.setting_value = ?";
				$params[] = $familyName;
			}
			/* Ignore affiiliation
			if ($affiliation !== null) {
				$sqlJoinAuthorSettings .= "
					LEFT JOIN author_settings asa$index ON (asa$index.author_id  = a.author_id AND asa$index.setting_name = 'affiliation' AND asa$index.locale = '$locale')
				";
				if (empty($affiliation)) {
					$sqlWhereAffiliation = " AND (asa$index.setting_value is NULL OR asa$index.setting_value = '')";
				} else {
					$sqlWhereAffiliation = " AND asa$index.setting_value = ?";
					$params[] = $affiliation;
				}
			}
			*/
			$sqlWhereAuthorSettings .= "(asg$index.setting_value = ? AND " . $sqlWhereFamilyName . $sqlWhereAffiliation . ")";
			if ($index < $supportedLocalesCount - 1) {
				$sqlWhereAuthorSettings .= ' OR ';
			}
			$index++;
		}
		$sqlWhereAuthorSettings .= ')';
		/* ignore country
		if ($country !== null) {
			if (empty($country)) {
				$sqlWhereCountry = " AND (a.country IS NULL OR a.country = '')";
			} else {
				$sqlWhereCountry = " AND a.country = ?";
				$params[] = $country;
			}
		}
		*/
		if ($journalId !== null) $params[] = (int) $journalId;

		$result = $authorDao->retrieve(
			'SELECT DISTINCT
				a.submission_id
			FROM	authors a
				LEFT JOIN submissions s ON (s.submission_id = a.submission_id)
				' .$sqlJoinAuthorSettings .'
				WHERE s.status = ' . STATUS_PUBLISHED . ' AND
				' .$sqlWhereAuthorSettings
				. $sqlWhereCountry
				. (($journalId !== null) ? ' AND s.context_id = ?' : ''),
			$params
		);

		$publishedArticles = array();
		$publishedArticleDao = DAORegistry::getDAO('PublishedArticleDAO');
		while (!$result->EOF) {
			$row = $result->getRowAssoc(false);
/*
			$publishedArticle = $publishedArticleDao->getByArticleId($row['submission_id']);
			if ($publishedArticle) {
				$publishedArticles[] = $publishedArticle;
			}*/
                        $publishedArticle = $row['submission_id'];
                        if ($publishedArticle) {
                                $publishedArticles[] = $publishedArticle;
                        }
			$result->MoveNext();
		}
		$result->Close();
		return $publishedArticles;
	}


        function authors($args, $request) {
                $templateMgr = TemplateManager::getManager($request);
                $context = $request->getContext();
                $plugin = PluginRegistry::getPlugin('generic', 'browsehdplugin');
                // Show the author index
                $journal = $request->getJournal();
                $authorDao = DAORegistry::getDAO('AuthorDAO');
                $searchInitial = $request->getUserVar('searchInitial');
                $rangeInfo = $this->getRangeInfo($request, 'authors');

                $authors = $this->getAuthorsAlphabetizedByJournalHd(
                                isset($journal)?$journal->getId():null,
                                $searchInitial,
                                $rangeInfo
                );
                $templateMgr->assign(array(
                                'searchInitial' => $request->getUserVar('searchInitial'),
                                'alphaList' => array_merge(array('-'), explode(' ', __('common.alphaList'))),
                                'authors' => $authors,
                        ));
                //Pagination
                $page = $request->_requestVars['authorsPage'];
                if(!isset($page)) {$page = 1;}
             //   $page = isset($args[1]) ? (int) $args[1] : 1;
                $context = $request->getContext();
                $count = $context->getSetting('itemsPerPage') ? $context->getSetting('itemsPerPage') : Config::getVar('interface', 'items_per_page');
                $offset = $page > 1 ? ($page - 1) * $count : 0;
                $total = $authors->getCount();
                $showingStart = $offset + 1;
                $showingEnd = min($offset + $count, $offset + $total);
                $nextPage = $total > $showingEnd ? $page + 1 : null;
                $prevPage = $showingStart > 1 ? $page - 1 : null;
                $templateMgr->assign(array(
                                        'total' => $total,
                                        'showingStart' => $showingStart,
                                        'showingEnd' => $showingEnd,
                                        'nextPage' => $nextPage,
                                        'prevPage' => $prevPage,
                                ));
                $templateMgr->display($plugin->getTemplateResource('/browseAuthorIndexHd.tpl'));
        }

        function authorDetails($args, $request) {
                $templateMgr = TemplateManager::getManager($request);
                $context = $request->getContext();
                $plugin = PluginRegistry::getPlugin('generic', 'browsehdplugin');

                $this->validate(null, $request);
                $this->setupTemplate($request);

                $journal = $request->getJournal();
                $user = $request->getUser();

		$authorDao = DAORegistry::getDAO('AuthorDAO');
		// View a specific author
		$authorName = $request->getUserVar('authorName');
		$givenName = $request->getUserVar('givenName');
		$familyName = $request->getUserVar('familyName');
		$affiliation = $request->getUserVar('affiliation');
		$country = $request->getUserVar('country');

		$publishedArticleIds = $this->getPublishedArticlesForAuthor($journal?$journal->getId():null, $givenName, $familyName, $affiliation, $country);

                $rangeInfo = $this->getRangeInfo($request, 'search');
                $total = count($publishedArticleIds);
                $publishedArticleIds = array_slice($publishedArticleIds, $rangeInfo->getCount() * ($rangeInfo->getPage()-1), $rangeInfo->getCount());
                $articleSearch = new ArticleSearch();
                $results = new VirtualArrayIterator($articleSearch->formatResults($publishedArticleIds), $total, $rangeInfo->getPage(), $rangeInfo->getCount());

                //Pagination
                $page = $request->_requestVars['searchPage'];
                if(!isset($page)) {$page = 1;}
             //   $page = isset($args[1]) ? (int) $args[1] : 1;
                $context = $request->getContext();
                $count = $context->getSetting('itemsPerPage') ? $context->getSetting('itemsPerPage') : Config::getVar('interface', 'items_per_page');
                $offset = $page > 1 ? ($page - 1) * $count : 0;

                $showingStart = $offset + 1;
                $showingEnd = min($offset + $count, $offset + count($publishedArticleIds));
                $nextPage = $total > $showingEnd ? $page + 1 : null;
                $prevPage = $showingStart > 1 ? $page - 1 : null;

                $templateMgr->assign(array(
                                        'results' => $results,
                                        'givenName' => $givenName,
                                        'familyName' => $familyName,
                                        'affiliation' => $affiliation,
                                        'authorName' => $authorName,
                                        'total' => $total,
                                        'showingStart' => $showingStart,
                                        'showingEnd' => $showingEnd,
                                        'nextPage' => $nextPage,
                                        'prevPage' => $prevPage,
                                ));

		// Load information associated with each article.
/*
		$journals = array();
		$issues = array();
		$sections = array();
		$issuesUnavailable = array();

		$issueDao = DAORegistry::getDAO('IssueDAO');
		$sectionDao = DAORegistry::getDAO('SectionDAO');
		$journalDao = DAORegistry::getDAO('JournalDAO');

		foreach ($publishedArticles as $article) {
			$articleId = $article->getId();
			$issueId = $article->getIssueId();
			$sectionId = $article->getSectionId();
			$journalId = $article->getJournalId();

			if (!isset($journals[$journalId])) {
				$journals[$journalId] = $journalDao->getById($journalId);
			}
			if (!isset($issues[$issueId])) {
				import('classes.issue.IssueAction');
				$issue = $issueDao->getById($issueId);
				$issues[$issueId] = $issue;
				$issueAction = new IssueAction();
				$issuesUnavailable[$issueId] = $issueAction->subscriptionRequired($issue, $journals[$journalId]) && (!$issueAction->subscribedUser($user, $journals[$journalId], $issueId, $articleId) && !$issueAction->subscribedDomain($request, $journals[$journalId], $issueId, $articleId));
			}
			if (!isset($sections[$sectionId])) {
				$sections[$sectionId] = $sectionDao->getById($sectionId, $journalId, true);
			}
		}

		if (empty($publishedArticles)) {
			$request->redirect(null, $request->getRequestedPage());
		}
*/              
/* 
		$templateMgr = TemplateManager::getManager($request);
		$templateMgr->assign(array(
			'publishedArticles' => $publishedArticles,
			'issues' => $issues,
			'issuesUnavailable' => $issuesUnavailable,
			'sections' => $sections,
			'journals' => $journals,
			'givenName' => $givenName,
			'familyName' => $familyName,
			'affiliation' => $affiliation,
			'authorName' => $authorName
		));
*/
		$countryDao = DAORegistry::getDAO('CountryDAO');
		$country = $countryDao->getCountry($country);
		$templateMgr->assign('country', $country);

		$templateMgr->display($plugin->getTemplateResource('/browseAuthorDetailsHd.tpl'));
       }

               /**
         * Show list of journal sections.
         */
         function sections($args, $request) {
                $templateMgr = TemplateManager::getManager($request);
                $context = $request->getContext();
                $plugin = PluginRegistry::getPlugin('generic', 'browsehdplugin');
                // Show the sections index
                $journal = $request->getJournal();

                $sectionDao = DAORegistry::getDAO('SectionDAO');
                $sectionsIterator = $sectionDao->getByJournalId($journal->getId());
                $sections = array();

                while ($section = $sectionsIterator->next()) {
                        if (!empty($section->getData('browseHdByEnabled'))) {
                                $publishedArticleDao = DAORegistry::getDAO('PublishedArticleDAO');
                                $publishedArticleIds = $publishedArticleDao->getPublishedArticleIdsBySection($section->getId());
                                $count = count($publishedArticleIds);
                                $sections[$section->getId()] = array("id" => $section->getId(),
                                                                     "title" => $section->getLocalizedTitle(),
                                                                     "description" => $section->getLocalizedData('browseHdByDescription'),
                                                                     "count" => $count);
                                }
                }
 
                $rangeInfo = $this->getRangeInfo($request, 'search');
                $total = count($sections);
                $sections = array_slice($sections, $rangeInfo->getCount() * ($rangeInfo->getPage()-1), $rangeInfo->getCount());
                $results = new VirtualArrayIterator($sections, $total, $rangeInfo->getPage(), $rangeInfo->getCount());
                $templateMgr->assign('results', $results);

                //Pagination
                $page = $request->_requestVars['searchPage'];
                if(!isset($page)) {$page = 1;}
             //   $page = isset($args[1]) ? (int) $args[1] : 1;
                $context = $request->getContext();
                $count = $context->getSetting('itemsPerPage') ? $context->getSetting('itemsPerPage') : Config::getVar('interface', 'items_per_page');
                $offset = $page > 1 ? ($page - 1) * $count : 0;

                $showingStart = $offset + 1;
                $showingEnd = min($offset + $count, $offset + count($sections));
                $nextPage = $total > $showingEnd ? $page + 1 : null;
                $prevPage = $showingStart > 1 ? $page - 1 : null;
                $templateMgr->assign(array(
                                        'total' => $total,
                                        'showingStart' => $showingStart,
                                        'showingEnd' => $showingEnd,
                                        'nextPage' => $nextPage,
                                        'prevPage' => $prevPage,
                                ));
                $templateMgr->display($plugin->getTemplateResource('/browseSectionIndexHd.tpl'));
        }

        function section($args, $request) {
                $templateMgr = TemplateManager::getManager($request);
                $plugin = PluginRegistry::getPlugin('generic', 'browsehdplugin');
                $sectionId = $request->getUserVar('sectionId');
                $sectionDao = DAORegistry::getDAO('SectionDAO');
                $section = $sectionDao->getById($sectionId);
                $publishedArticleDao = DAORegistry::getDAO('PublishedArticleDAO');
                $publishedArticleIds = $publishedArticleDao->getPublishedArticleIdsBySection($sectionId);
                $rangeInfo = $this->getRangeInfo($request, 'search');
                $total = count($publishedArticleIds);
                $publishedArticleIds = array_slice($publishedArticleIds, $rangeInfo->getCount() * ($rangeInfo->getPage()-1), $rangeInfo->getCount());
                $articleSearch = new ArticleSearch();
                $results = new VirtualArrayIterator($articleSearch->formatResults($publishedArticleIds), $total, $rangeInfo->getPage(), $rangeInfo->getCount());
                //Pagination
                $page = $request->_requestVars['searchPage'];
                if(!isset($page)) {$page = 1;}
             //   $page = isset($args[1]) ? (int) $args[1] : 1;
                $context = $request->getContext();
                $count = $context->getSetting('itemsPerPage') ? $context->getSetting('itemsPerPage') : Config::getVar('interface', 'items_per_page');
                $offset = $page > 1 ? ($page - 1) * $count : 0;
                               
                $showingStart = $offset + 1;
                $showingEnd = min($offset + $count, $offset + count($publishedArticleIds));
                $nextPage = $total > $showingEnd ? $page + 1 : null;
                $prevPage = $showingStart > 1 ? $page - 1 : null;


                $templateMgr->assign(array(
                                        'results' => $results,
                                        'title' => $section->getLocalizedTitle(),
                                        'description' => $section->getLocalizedData('browseHdByDescription'),
                                        'hideAuthor' => $section->getHideAuthor(),
                                        'sectionId' => $sectionId,
                                        'total' => $total,
                                        'showingStart' => $showingStart,
                                        'showingEnd' => $showingEnd,
                                        'nextPage' => $nextPage,
                                        'prevPage' => $prevPage,
                                ));
                $templateMgr->display($plugin->getTemplateResource('/browseSectionDetailsHd.tpl'));
        }

         function categories($args, $request) {
                $templateMgr = TemplateManager::getManager($request);
                $context = $request->getContext();
                $plugin = PluginRegistry::getPlugin('generic', 'browsehdplugin');
                // Show the categories index
                $journal = $request->getJournal();

                $categoryDao = DAORegistry::getDAO('CategoryDAO');
                $categoriesIterator = $categoryDao->getByContextId($context->getId());
                $categories = array();
                while ($category = $categoriesIterator->next()) {
                                $categories[$category->getId()] = array("id" => $category->getId(),
                                                                     "title" => $category->getLocalizedTitle(),
                                                                     "parent_id" => $category->getParentId(),
                                                                     "description" => $category->getLocalizedDescription(),
                                                                     "path" => $category->getPath(),
                                                                     "count" => $count);
                }
                $rangeInfo = $this->getRangeInfo($request, 'search');
                $total = count($categories);
                $categories = array_slice($categories, $rangeInfo->getCount() * ($rangeInfo->getPage()-1), $rangeInfo->getCount());
                $results = new VirtualArrayIterator($categories, $total, $rangeInfo->getPage(), $rangeInfo->getCount());
                $templateMgr->assign('results', $results);
                //Pagination
                $page = $request->_requestVars['searchPage'];
                if(!isset($page)) {$page = 1;}
             //   $page = isset($args[1]) ? (int) $args[1] : 1;
                $count = $context->getSetting('itemsPerPage') ? $context->getSetting('itemsPerPage') : Config::getVar('interface', 'items_per_page');
                $offset = $page > 1 ? ($page - 1) * $count : 0;

                $showingStart = $offset + 1;
                $showingEnd = min($offset + $count, $offset + count($categories));

                $nextPage = $total > $showingEnd ? $page + 1 : null;
                $prevPage = $showingStart > 1 ? $page - 1 : null;
                $templateMgr->assign(array(
                                        'total' => $total,
                                        'showingStart' => $showingStart,
                                        'showingEnd' => $showingEnd,
                                        'nextPage' => $nextPage,
                                        'prevPage' => $prevPage,
                                ));

/*
                $templateMgr->assign(array(
                        'browseCategoryFactory' => $categoryDao->getByContextId($context->getId()),
                ));
*/
                
                $templateMgr->display($plugin->getTemplateResource('/browseCategoryIndexHd.tpl'));
        }
}

?>

