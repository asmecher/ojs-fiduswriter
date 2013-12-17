<?php

/**
 * @file plugins/generic/fidusWriter/FidusWriterPlugin.inc.php
 *
 * Copyright (c) 2013 Simon Fraser University Library
 * Copyright (c) 2003-2013 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class FidusWriterPlugin
 * @ingroup plugins_generic_fiduswriter
 *
 * @brief Integrates the use of FidusWriter (http://www.fiduswriter.org) as a
 * content editor for article text.
 */

import('lib.pkp.classes.plugins.GenericPlugin');

class FidusWriterPlugin extends GenericPlugin {
	/**
	 * Register the plugin, if enabled.
	 * @param $category string
	 * @param $path string
	 * @return boolean
	 */
	function register($category, $path) {
		if (parent::register($category, $path)) {
			if ($this->getEnabled()) {
				// To register the gateway plugin
				HookRegistry::register('PluginRegistry::loadCategory', array(&$this, 'callbackLoadCategory'));
				// To intervene in the submission process
				HookRegistry::register('authorsubmitstep2form::display',array(&$this, 'authorUploadCallback'));
			}
			return true;
		}
		return false;
	}

	/**
	 * Get the display name of this plugin
	 * @return string
	 */
	function getDisplayName() {
		return __('plugins.generic.fidusWriter.name');
	}

	/**
	 * Get the description of this plugin
	 * @return string
	 */
	function getDescription() {
		return __('plugins.generic.fidusWriter.description');
	}

	/**
	 * Register as a gateway plugin, even though this is a generic plugin.
	 * This will allow the plugin to behave as a gateway plugin, i.e. to
	 * respond to URL requests.
	 * @param $hookName string
	 * @param $args array
	 */
	function callbackLoadCategory($hookName, $args) {
		$category =& $args[0];
		$plugins =& $args[1];
		switch ($category) {
			case 'gateways':
				$this->import('FidusWriterGatewayPlugin');
				$gatewayPlugin = new FidusWriterGatewayPlugin($this->getName());
				$plugins[$gatewayPlugin->getSeq()][$gatewayPlugin->getPluginPath()] =& $gatewayPlugin;
				break;
		}
		return false;
	}

	/**
	 * Set the page's breadcrumbs, given the plugin's tree of items
	 * to append.
	 * @param $subclass boolean
	 */
	function setBreadcrumbs($isSubclass = false) {
		$templateMgr =& TemplateManager::getManager();
		$pageCrumbs = array(
			array(
				Request::url(null, 'user'),
				'navigation.user'
			),
			array(
				Request::url(null, 'manager'),
				'user.role.manager'
			)
		);
		if ($isSubclass) $pageCrumbs[] = array(
			Request::url(null, 'manager', 'plugins'),
			'manager.plugins'
		);

		$templateMgr->assign('pageHierarchy', $pageCrumbs);
	}

	/**
	 * Display verbs for the management interface.
	 */
	function getManagementVerbs() {
		$verbs = array();
		if ($this->getEnabled()) {
			$verbs[] = array('settings', __('plugins.generic.fidusWriter.manager.settings'));
		}
		return parent::getManagementVerbs($verbs);
	}

 	/**
 	 * Execute a management verb on this plugin
 	 * @param $verb string
 	 * @param $args array
	 * @param $message string Result status message
	 * @param $messageParams array Parameters for the message key
 	 * @return boolean
 	 */
	function manage($verb, $args, &$message, &$messageParams) {
		if (!parent::manage($verb, $args, $message, $messageParams)) return false;

		switch ($verb) {
			case 'settings':
				$templateMgr =& TemplateManager::getManager();
				$templateMgr->register_function('plugin_url', array(&$this, 'smartyPluginUrl'));
				$journal =& Request::getJournal();

				$this->import('FidusWriterSettingsForm');
				$form = new FidusWriterSettingsForm($this, $journal->getId());
				if (Request::getUserVar('save')) {
					$form->readInputData();
					if ($form->validate()) {
						$form->execute();
						Request::redirect(null, 'manager', 'plugin');
						return false;
					} else {
						$this->setBreadCrumbs(true);
						$form->display();
					}
				} else {
					$this->setBreadCrumbs(true);
					$form->initData();
					$form->display();
				}
				return true;
			default:
				// Unknown management verb
				assert(false);
				return false;
		}
	}

	/**
	 * Handle a hook callback for when the author upload form is displayed
	 * @param $hookName string
	 * @param $params array
	 */
	function authorUploadCallback($hookName, $params) {
		$form =& $params[0];
		$article =& $form->article;

		// Interrupt the usual upload process to introduce FidusWriter.
		$fidusWriterConnection = $this->getConnection();

		$templateMgr =& TemplateManager::getManager();
		$templateMgr->assign('apiUrl', $this->getSetting($article->getJournalId(), 'apiUrl'));
		$templateMgr->assign('formParams', $fidusWriterConnection->getRedirectToEditParams($article));
		$templateMgr->display($this->getTemplatePath() . '/redirectEditForm.tpl');
		return true;
	}

	/**
	 * Get a connection to FidusWriter.
	 * @return FidusWriterConnection
	 */
	function getConnection() {
		$this->import('FidusWriterConnection');
		$request = Application::getRequest();
		$journal = $request->getJournal();
		return new FidusWriterConnection($this->getSetting($journal->getId(), 'apiUrl'), $this->getSetting($journal->getId(), 'apiKey'));
	}
}

?>
