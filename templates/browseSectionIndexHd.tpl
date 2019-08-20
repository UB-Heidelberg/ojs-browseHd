{**
 * templates/frontend/pages/searchSectionIndexHd.tpl
 *
 * Copyright (c) 2014-2019 Simon Fraser University
 * Copyright (c) 2003-2019 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Index of sections.
 *
 *}
{strip}
{assign var="pageTitle" value="plugins.generic.browseHd.search.sectionIndex"}
{include file="frontend/components/header.tpl"}
{/strip}

<nav class="cmp_breadcrumbs cmp_breadcrumbs_sections_browseHd" role="navigation" aria-label="{translate key="navigation.breadcrumbLabel"}">
        <ol>
                <li>
                        <a href="{url page="index" router=$smarty.const.ROUTE_PAGE}">
                                {translate key="common.homepageNavigationLabel"}
                        </a>
                        <span class="separator">{translate key="navigation.breadcrumbSeparator"}</span>
                </li>
                <li class="current">
                                        {translate key="plugins.generic.browseHd.search.sectionIndex"}
                </li>
        </ol>
</nav>


<h1>{translate key="plugins.generic.browseHd.search.sectionIndex"}</h1>
<div class="browseHd">
<ul class="browseHd_section_listing">
{iterate from=results key=id item=value}
<li class="browseHd_section">
<span class="browseHd_section_title"><a href="{url op="section" sectionId=$value.id}">{$value.title|escape}</a></span> <span class="browseHd_section_count">{$value.count}</span> {if $value.description}<span class="browseHd_section_extend"><i class="fa fa-plus-circle" aria-hidden="true"></i></span>{/if}
{if $value.description}<div class="browseHd_section_description">{$value.description}</div>{/if}
</li>
{/iterate}
</ul>
{if !$results->wasEmpty()}
                {if $prevPage > 1}
                        {capture assign=prevUrl}{url op="sections" searchPage=$prevPage}{/capture}
                {elseif $prevPage === 1}
                        {capture assign=prevUrl}{url op="sections" searchPage=$prevPage}{/capture}
                {/if}
                {if $nextPage}
                        {capture assign=nextUrl}{url op="sections" searchPage=$nextPage}{/capture}
                {/if}
                {include
                        file="frontend/components/pagination.tpl"
                        prevUrl=$prevUrl
                        nextUrl=$nextUrl
                        showingStart=$showingStart
                        showingEnd=$showingEnd
                        total=$total
                }
{else}
        <p>
        {translate key="search.noResults"}
        </p>
{/if}
</div>

{include file="frontend/components/footer.tpl"}

