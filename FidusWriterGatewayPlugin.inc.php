<?php

/**
 * @file plugins/generic/fidusWriter/FidusWriterGatewayPlugin.inc.php
 *
 * Copyright (c) 2013 Simon Fraser University Library
 * Copyright (c) 2003-2013 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class FidusWriterGatewayPlugin
 * @ingroup plugins_generic_fidusWriter
 *
 * @brief Gateway component of FidusWriter plugin
 *
 */

import('classes.plugins.GatewayPlugin');

class FidusWriterGatewayPlugin extends GatewayPlugin {
	/** @var $parentPluginName string Name of parent plugin */
	var $parentPluginName;

	function FidusWriterGatewayPlugin($parentPluginName) {
		parent::GatewayPlugin();
		$this->parentPluginName = $parentPluginName;
	}

	/**
	 * Hide this plugin from the management interface (it's subsidiary)
	 */
	function getHideManagement() {
		return true;
	}

	/**
	 * Get the name of this plugin. The name must be unique within
	 * its category.
	 * @return String name of plugin
	 */
	function getName() {
		return 'FidusWriterGatewayPlugin';
	}

	function getDisplayName() {
		return __('plugins.generic.fidusWriter.displayName');
	}

	function getDescription() {
		return __('plugins.generic.fidusWriter.description');
	}

	/**
	 * Get the web feed plugin
	 * @return object
	 */
	function &getFidusWriterPlugin() {
		$plugin =& PluginRegistry::getPlugin('generic', $this->parentPluginName);
		return $plugin;
	}

	/**
	 * Override the builtin to get the correct plugin path.
	 */
	function getPluginPath() {
		$plugin =& $this->getFidusWriterPlugin();
		return $plugin->getPluginPath();
	}

	/**
	 * Override the builtin to get the correct template path.
	 * @return string
	 */
	function getTemplatePath() {
		$plugin =& $this->getFidusWriterPlugin();
		return $plugin->getTemplatePath() . 'templates/';
	}

	/**
	 * Get whether or not this plugin is enabled. (Should always return true, as the
	 * parent plugin will take care of loading this one when needed)
	 * @return boolean
	 */
	function getEnabled() {
		$plugin =& $this->getFidusWriterPlugin();
		return $plugin->getEnabled(); // Should always be true anyway if this is loaded
	}

	/**
	 * Get the management verbs for this plugin (override to none so that the parent
	 * plugin can handle this)
	 * @return array
	 */
	function getManagementVerbs() {
		return array();
	}

	/**
	 * Handle fetch requests for this plugin.
	 */
	function fetch($args) {
		$articleDao = DAORegistry::getDAO('ArticleDAO');
		$journal = Request::getJournal();
		$article = $articleDao->getArticle(Request::getUserVar('articleId'), $journal->getId());

		$plugin =& $this->getFidusWriterPlugin();
		$apiUrl = $plugin->getSetting($journal->getId(), 'apiUrl');
		$origin = isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : '';
		if ($origin && strpos($apiUrl, $origin)===0) {
			header('Access-Control-Allow-Origin: ' . $origin);
			header('Access-Control-Allow-Methods: GET, PUT, POST, DELETE, OPTIONS');
			header('Access-Control-Max-Age: 1000');
			header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, X-CSRFToken');
		}

		if ('OPTIONS' == $_SERVER['REQUEST_METHOD']) {
			// Respond to OPTIONS cross-domain post.
			return true;
		}

		// Several operations use access keys and article file manager
		import('lib.pkp.classes.security.AccessKeyManager');
		$accessKeyManager = new AccessKeyManager();
		import('classes.file.ArticleFileManager');
		$articleFileManager = new ArticleFileManager($article->getId());

		switch (Request::getUserVar('op')) {
			case 'load':
				$keyHash = $accessKeyManager->generateKeyHash(Request::getUserVar('accessKey'));
				$accessKey =& $accessKeyManager->validateKey(
					'FidusWriterLoadContext',
					$article->getUserId(),
					$keyHash,
					$article->getId()
				);
				if (!$accessKey) fatalError('Unable to validate access key.');

				// Get the most recent author file.
				$articleFileDao =& DAORegistry::getDAO('ArticleFileDAO');
				$revisedArticleFile = $articleFileDao->getArticleFile($article->getRevisedFileId());
				$submissionArticleFile = $articleFileDao->getArticleFile($article->getSubmissionFileId());

				// Use the revised file if available; fall back on submission version
				$articleFile = $revisedArticleFile?$revisedArticleFile:$submissionArticleFile;
				$articleFileManager->downloadFile($articleFile->getFileId(), $articleFile->getRevision());
				break;
			case 'save':
				$keyHash = $accessKeyManager->generateKeyHash(Request::getUserVar('accessKey'));
				$accessKey =& $accessKeyManager->validateKey(
					'FidusWriterSaveContext',
					$article->getUserId(),
					$keyHash,
					$article->getId()
				);
				if (!$accessKey) fatalError('Unable to validate access key.');

				// Verify and upload the file.
				if (!$articleFileManager->uploadedFileExists('file')) {
					fatalError('No file uploaded');
				}

				if ($article->getSubmissionProgress()) {
					// This is the submission process
					$submissionFileId = $articleFileManager->uploadSubmissionFile('file', $article->getSubmissionFileId(), true);
					if (!$submissionFileId) fatalError('Could not upload file!');
					$article->setSubmissionFileId($submissionFileId);
					$article->setSubmissionProgress(3);
					$article->setTitle(Request::getUserVar('title'), $article->getLocale());
					$article->setAbstract(Request::getUserVar('abstract'), $article->getLocale());
				} else {
					// This is the submission process
					$editorDecisionFileId = $articleFileManager->uploadEditorDecisionFile('file', $article->getRevisedFileId());
					if (!$editorDecisionFileId) fatalError('Could not upload file!');
					$article->setRevisedFileId($editorDecisionFileId);
				}

				$articleDao->updateArticle($article);
				echo 'OK';
				break;
			default: fatalError('Unknown operation.');
		}
		return true;
	}
}

?>
