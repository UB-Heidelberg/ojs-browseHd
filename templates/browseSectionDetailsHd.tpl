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
{include file="frontend/components/header.tpl" pageTitleTranslated=$title|escape}

{if $currentJournal}
        {assign var=numCols value=3}
{else}
        {assign var=numCols value=4}
{/if}
<div class="page page_section_index">
<nav class="cmp_breadcrumbs_sections_browseHd cmp_breadcrumbs" role="navigation" aria-label="{translate key="navigation.breadcrumbLabel"}">
        <ol>
                <li>
                        <a href="{url page="index" router=$smarty.const.ROUTE_PAGE}">
                                {translate key="common.homepageNavigationLabel"}
                        </a>
                        <span class="separator">{translate key="navigation.breadcrumbSeparator"}</span>
                </li>
                <li>
                        <a href="{url page="browse" op="sections"}">
                               {translate key="plugins.generic.browseHd.search.sectionIndex"}
                        </a>
                        <span class="separator">{translate key="navigation.breadcrumbSeparator"}</span>
                </li>
                <li class="current">
                                        {$title|escape}
                </li>
        </ol>
</nav>

{* Count of articles in this category *}
        <div class="article_count">
                {translate key="plugins.generic.browseHd.section.browseTitles" numTitles=$total}
        </div>

<h1>{$title|escape}</h1>
{if $description}
<div class="about_section has_description browseHd_description">
                <div class="description">
                        {$description|strip_unsafe_html}
                </div>
</div>
{/if}

<div class="browseHd">
{if $description}
        <h2>
                {translate key="article.articles"}
        </h2>
{/if}

<ul class="browseHd_section_articles_listing">
{iterate from=results item=result}
{assign var=publishedArticle value=$result.publishedArticle}
{assign var=article value=$result.article}
{assign var=issue value=$result.issue}
{assign var=issueAvailable value=$result.issueAvailable}
{assign var=journal value=$result.journal}
<li>
        <div class="browse_section_journal">
        {if !$currentJournal}<a href="{url journal=$journal->getPath()}">{$journal->getLocalizedName()|escape}</a>{/if}
        </div>
        <div class="browse_section_title">
        <a href="{url journal=$journal->getPath() page="article" op="view" path=$publishedArticle->getBestArticleId()}" class="browse_title">{$article->getLocalizedTitle()|strip_unsafe_html}</a>
        </div>
        {if (!$hideAuthor && !$publishedArticle->getHideAuthor())}
        <div class="browse_section_authors">
                {foreach from=$article->getAuthors() item=author name=authorList}
                        {if $author->getIncludeInBrowse() == 1}
                        {$author->getFullName()|escape}{if !$smarty.foreach.authorList.last},{/if}
                        {/if}
                {/foreach}
        </div>
        {/if}
        {if $issueAvailable}
        <div class="browse_section_issue">
            <a href="{url journal=$journal->getPath() page="issue" op="view" path=$issue->getBestIssueId()}">{$issue->getIssueIdentification()|strip_unsafe_html|nl2br}</a>{/if}
        </div>
</li>
{/iterate}
</ul>
{if $results->wasEmpty()}
<div class="no_results">
{translate key="plugins.generic.browseHd.emptySection"}
</div>
{else}
                {if $prevPage > 1}
                        {capture assign=prevUrl}{url op="section" sectionId=$sectionId searchPage=$prevPage}{/capture}
                {elseif $prevPage === 1}
                        {capture assign=prevUrl}{url op="section" sectionId=$sectionId searchPage=$prevPage}{/capture}
                {/if}
                {if $nextPage}
                        {capture assign=nextUrl}{url op="section" sectionId=$sectionId searchPage=$nextPage}{/capture}
                {/if}
                {include
                        file="frontend/components/pagination.tpl"
                        prevUrl=$prevUrl
                        nextUrl=$nextUrl
                        showingStart=$showingStart
                        showingEnd=$showingEnd
                        total=$total
                }
{/if}
</div>
</div>
{include file="frontend/components/footer.tpl"}

