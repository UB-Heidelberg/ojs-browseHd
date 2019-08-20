{**
 * templates/frontend/pages/browseSectionDetailsHd.tpl
 *
 * Copyright (c) 2014-2019 Simon Fraser University
 * Copyright (c) 2003-2019 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Index of published articles by author.
 *
 *}

<nav class="cmp_breadcrumbs cmp_breadcrumbs_catalog_browseHd" role="navigation" aria-label="{translate key="navigation.breadcrumbLabel"}">
        <ol>
                <li>
                        <a href="{url page="index" router=$smarty.const.ROUTE_PAGE}">
                                {translate key="common.homepageNavigationLabel"}
                        </a>
                        <span class="separator">{translate key="navigation.breadcrumbSeparator"}</span>
                </li>
                <li>
                        <a href="{url page="browse" op="categories"}">
                               {translate key="plugins.generic.browseHd.search.categoryIndex"}
                        </a>
                        <span class="separator">{translate key="navigation.breadcrumbSeparator"}</span>
                </li>
                {if $parent}
                        <li>
                                <a href="{url op=$type path=$parent->getPath()}">
                                        {$parent->getLocalizedTitle()|escape}
                                </a>
                                <span class="separator">{translate key="navigation.breadcrumbSeparator"}</span>
                        </li>
                {/if}
                <li class="current">
                                        {$currentTitle|escape}
                </li>
        </ol>
</nav>


