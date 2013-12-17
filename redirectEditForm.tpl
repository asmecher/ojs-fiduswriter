{**
 * plugins/generic/fidusWriter/settingsForm.tpl
 *
 * Copyright (c) 2013 Simon Fraser University Library
 * Copyright (c) 2003-2013 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Form to redirect from OJS to FidusWriter for the purpose of editing a doc.
 *
 *}
{strip}
{assign var="pageTitle" value="plugins.generic.fidusWriter.redirect"}
{include file="common/header.tpl"}
{/strip}

<form method="post" action="{$apiUrl}">
	{foreach from=$formParams item=formParamValue key=formParamName}
		<input type="hidden" name="{$formParamName|escape}" value="{$formParamValue|escape}" />
	{/foreach}
	<p>{translate key="plugins.generic.fidusWriter.redirect.description"}</p>
	<input type="submit" value="{translate key="common.ok"}" class="button defaultButton" />
</form>

{include file="common/footer.tpl"}
