<?php
/**
 * Avatar for Contao Open Source CMS
 *
 * Copyright (C) 2013 Kirsten Roschanski
 * Copyright (C) 2013 Tristan Lins <http://bit3.de>
 *
 * @package    Avatar
 * @license    http://opensource.org/licenses/lgpl-3.0.html LGPL
 */

/**
 * Add palette to tl_module
 */
$GLOBALS['TL_DCA']['tl_module']['palettes']['forum'] = '{title_legend},name,type;{forum_legend},forum_category;{protected_legend:hide},protected;{expert_legend:hide},guests,cssID,space';

$GLOBALS['TL_DCA']['tl_module']['fields']['forum_category'] = array
(
	'label'            => &$GLOBALS['TL_LANG']['tl_module']['forum_category'],
	'exclude'          => true,
	'inputType'        => 'select',
	'foreignKey'       => 'tl_forum.title',
	'eval'             => array('mandatory'=>true, 'tl_class'=>'long'),
	'sql'              => "int(10) unsigned NOT NULL default '0'",
	'relation'         => array('type'=>'belongsTo', 'load'=>'lazy')
); 
