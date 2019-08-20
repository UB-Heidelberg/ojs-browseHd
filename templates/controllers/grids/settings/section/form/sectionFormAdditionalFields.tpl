{**
 * templates/controllers/grid/settings/section/form/sectionFormAdditionalFields.tpl
 *
 * Copyright (c) 2017 Simon Fraser University
 * Copyright (c) 2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @brief Add fields for section browsing to the section edit form
 *
 * @uses $browseByEnabled boolean Should this section be browseable?
 *}
<div style="clear:both;">
        {fbvFormSection title="plugins.generic.browseHd.browsingLabel" list="true"}
                {fbvElement type="checkbox" value="1" id="browseHdByEnabled" checked=$browseHdByEnabled label="plugins.generic.browseHd.enableBrowsing"}
        {/fbvFormSection}
        {fbvFormSection title="plugins.generic.browseHd.browseByDescriptionLabel"}
                {fbvElement type="textarea" multilingual=true id="browseHdByDescription" value=$browseHdByDescription rich=true}
        {/fbvFormSection}
</div>


