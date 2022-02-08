<?php if (!defined('TL_ROOT')) die('You cannot access this file directly!');

/**
 * Contao Open Source CMS
 *
 * Copyright (C) 2005-2013 Leo Feyer
 *
 * @package   bdf
 * @author    Frank Hoppe
 * @license   GNU/LGPL
 * @copyright Frank Hoppe 2014
 */

/**
 * Backend-Module
 */

$GLOBALS['BE_MOD']['content']['forum'] = array
(
	'tables'         => array('tl_forum', 'tl_forum_threads', 'tl_forum_topics'),
	'icon'           => 'bundles/contaoforum/images/icon.png',
);

/**
 * Frontend-Module
 */
$GLOBALS['FE_MOD']['application']['forum'] = 'Schachbulle\ContaoForumBundle\Modules\Forum';  

// Standard-CSS einbinden
if(TL_MODE == 'FE') 
{
	$GLOBALS['TL_CSS'][] = 'bundles/contaoforum/css/style.css'; 
	$GLOBALS['TL_CSS'][] = 'bundles/contaoforum/js/upload2.css'; 
	$GLOBALS['TL_HEAD'][] = '<script src="bundles/contaoforum/tinymce/tiny_mce.js"></script>';
	$GLOBALS['TL_HEAD'][] = '<script src="bundles/contaoforum/tinymce/tinymce_config.js"></script>'; 
	$GLOBALS['TL_HEAD'][] = '<script src="bundles/contaoforum/js/upload2.js"></script>'; 
}
if(TL_MODE == 'BE') $GLOBALS['TL_CSS'][] = 'bundles/contaoforum/css/be.css'; 
