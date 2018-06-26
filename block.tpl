{**
 * plugins/blocks/developedBy/block.tpl
 *
 * Copyright (c) 2014-2018 Simon Fraser University
 * Copyright (c) 2003-2018 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Common site sidebar menu -- "Developed By" block.
 *}
<div class="pkp_block block_developed_by">
	<span class="title">{translate key="plugins.block.browse.title"}</span>
	<div class="content">
		<ul>
			<li><a href="{url page="issue" op="archive"}">{translate key="navigation.browseByIssue"}</a></li>
			<li><a href="{url page="search" op="authors"}">{translate key="navigation.browseByAuthor"}</a></li>
			<li><a href="{url page="section"}">{translate key="plugins.block.browse.link.section"}</a></li>
			{call_hook name="Plugins::Blocks::Browse"}
		</ul>
	</div>
</div>
