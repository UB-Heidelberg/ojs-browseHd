{**
 * templates/frontend/pages/searchAuthorDetails.tpl
 *
 * Copyright (c) 2014-2019 Simon Fraser University
 * Copyright (c) 2003-2019 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Index of published articles by author.
 *
 *}
{strip}
{assign var="pageTitle" value="search.authorDetails"}
{include file="frontend/components/header.tpl"}
{/strip}
<nav class="cmp_breadcrumbs" role="navigation" aria-label="{translate key="navigation.breadcrumbLabel"}">
        <ol>
                <li>
                        <a href="{url page="index" router=$smarty.const.ROUTE_PAGE}">
                                {translate key="common.homepageNavigationLabel"}
                        </a>
                        <span class="separator">{translate key="navigation.breadcrumbSeparator"}</span>
                </li>
                <li>
                        <a href="{url page="browse" op="authors"}">
                               {translate key="search.authorIndex"}
                        </a>
                        <span class="separator">{translate key="navigation.breadcrumbSeparator"}</span>
                </li>
                <li class="current">
                                        {$authorName|escape}
                </li>
        </ol>
</nav>


<h1>{$authorName|escape}{if $affiliation}, {$affiliation|escape}{/if}{if $country}, {$country|escape}{/if}</h1>
<div class="browseHd">
<ul class="browseHd_author_articles_listing">
{iterate from=results item=result}
{assign var=publishedArticle value=$result.publishedArticle}
{assign var=article value=$result.article}
{assign var=issue value=$result.issue}
{assign var=issueAvailable value=$result.issueAvailable}
{assign var=journal value=$result.journal}
	{if $issue->getPublished() && $journal}
        <li>
                <div class="browse_author_title"><a href="{url journal=$journal->getPath() page="article" op="view" path=$article->getBestArticleId()}">{$article->getLocalizedTitle()|strip_unsafe_html}</a></div>
                <div class="browse_author_issue"><a href="{url journal=$journal->getPath() page="issue" op="view" path=$issue->getBestIssueId()}">{$journal->getLocalizedName()|escape} {$issue->getIssueIdentification()|strip_unsafe_html|nl2br}</a></div>
        </li>
        {/if}
{/iterate}
</ul>
{if $results->wasEmpty()}
<p>
{translate key="search.noResults"}
</p>
{else}
                {if $prevPage > 1}
                        {capture assign=prevUrl}{url op="authorDetails" givenName=$givenName familyName=$familyName authorName=$authorName searchPage=$prevPage}{/capture}
                {elseif $prevPage === 1}
                        {capture assign=prevUrl}{url op="authorDetails" givenName=$givenName familyName=$familyName authorName=$authorName searchPage=$prevPage}{/capture}
                {/if}
                {if $nextPage}
                        {capture assign=nextUrl}{url op="authorDetails" givenName=$givenName familyName=$familyName authorName=$authorName searchPage=$nextPage}{/capture}
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
{include file="frontend/components/footer.tpl"}

