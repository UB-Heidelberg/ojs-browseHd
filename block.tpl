{**
 * plugins/blocks/developedBy/block.tpl
 *
 * Copyright (c) 2014-2018 Simon Fraser University
 * Copyright (c) 2003-2018 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Common site sidebar menu -- "Browse" block.
 *}
<div class="pkp_block block_browse">
	<span class="title">{translate key="plugins.block.browse.title"}</span>
	<div class="content">
		<ul>
			<li class="browse_by_issue"><a href="{url page="issue" op="archive"}">{translate key="navigation.browseByIssue"}</a></li>
			<li class="browse_by_author"><a href="{url page="search" op="authors"}">{translate key="navigation.browseByAuthor"}</a></li>
			<li class="browse_by_section"><a href="{url page="section"}">{translate key="plugins.block.browse.link.section"}</a></li>
			{call_hook name="Plugins::Blocks::Browse"}
		</ul>
	</div>
</div>
