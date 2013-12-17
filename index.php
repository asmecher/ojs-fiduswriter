<?php

/**
 * @defgroup plugins_generic_fiduswriter
 */
 
/**
 * @file plugins/generic/fiduswriter/index.php
 *
 * Copyright (c) 2013 Simon Fraser University Library
 * Copyright (c) 2003-2013 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @ingroup plugins_generic_fiduswriter
 * @brief Wrapper for the FidusWriter plugin.
 *
 */

require_once('FidusWriterPlugin.inc.php');

return new FidusWriterPlugin();

?> 
