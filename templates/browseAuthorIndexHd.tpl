{**
 * templates/frontend/pages/searchAuthorIndex.tpl
 *
 * Copyright (c) 2014-2019 Simon Fraser University
 * Copyright (c) 2003-2019 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Index of published articles by author.
 *
 *}
{strip}
{assign var="pageTitle" value="search.authorIndex"}
{include file="frontend/components/header.tpl"}
{/strip}

<nav class="cmp_breadcrumbs cmp_breadcrumbs_authors_browseHd" role="navigation" aria-label="{translate key="navigation.breadcrumbLabel"}">
        <ol>
                <li>
                        <a href="{url page="index" router=$smarty.const.ROUTE_PAGE}">
                                {translate key="common.homepageNavigationLabel"}
                        </a>
                        <span class="separator">{translate key="navigation.breadcrumbSeparator"}</span>
                </li>
                <li class="current">
                                        {translate key="search.authorIndex"}
                </li>
        </ol>
</nav>

<h1>{translate key="search.authorIndex"}</h1>
<div class="browseHd">
<p "browseHd_alphalist">{foreach from=$alphaList item=letter}<a href="{url op="authors" searchInitial=$letter}">{if $letter == $searchInitial}<strong>{$letter|escape}</strong>{else}{$letter|escape}{/if}</a> {/foreach}<a href="{url op="authors"}">{if $searchInitial==''}<strong>{translate key="common.all"}</strong>{else}{translate key="common.all"}{/if}</a></p>

<div class="browse_authors">
{iterate from=authors item=author}
	{assign var=lastFirstLetter value=$firstLetter}
	{assign var=firstLetter value=$author->getLocalizedFamilyName()|String_substr:0:1}

	{if $lastFirstLetter|lower != $firstLetter|lower}
			<div id="{$firstLetter|escape}">
		<h3>{$firstLetter|escape|upper}</h3>
			</div>
	{/if}

	{assign var=lastAuthorName value=$authorName}
	{assign var=lastAuthorCountry value=$authorCountry}

	{assign var=authorAffiliation value=$author->getLocalizedAffiliation()}
	{assign var=authorCountry value=$author->getCountry()}

	{assign var=authorGivenName value=$author->getLocalizedGivenName()}
	{assign var=authorFamilyName value=$author->getLocalizedFamilyName()}
	{assign var=authorName value=$author->getFullName(false, true)}

	{strip}
		<a class="browse_author_name" href="{url op="authorDetails" givenName=$authorGivenName familyName=$authorFamilyName authorName=$authorName}">{$authorName|escape}</a>
		<!--{if $authorAffiliation}, {$authorAffiliation|escape}{/if}-->
		<!--
		{if $lastAuthorName == $authorName && $lastAuthorCountry != $authorCountry}
			{* Disambiguate with country if necessary (i.e. if names are the same otherwise) *}
			{if $authorCountry} ({$author->getCountryLocalized()}){/if}
		{/if}
		-->
	{/strip}
	<br/>
{/iterate}
{if !$authors->wasEmpty()}
	<br />
	{page_info iterator=$authors}&nbsp;&nbsp;&nbsp;&nbsp;{page_links anchor="authors" iterator=$authors name="authors" searchInitial=$searchInitial}
{else}
{/if}
</div>
</div>
{include file="frontend/components/footer.tpl"}

