<?php

/**
 * Contao Open Source CMS
 *
 * Copyright (c) 2005-2013 Leo Feyer
 *
 * @package Core
 * @link    https://contao.org
 * @license http://www.gnu.org/licenses/lgpl-3.0.html LGPL
 */

/**
 * Table tl_forum_threads
 */
$GLOBALS['TL_DCA']['tl_forum_threads'] = array
(
 
	// Config
	'config' => array
	(
		'dataContainer'               => 'Table',
		'switchToEdit'                => true,
		'ptable'                      => 'tl_forum',
		'ctable'                      => array('tl_forum_topics'),
		'enableVersioning'            => true,
		'onsubmit_callback'           => array
		(
			array('tl_forum_threads', 'saveRecord')
		),
		'sql' => array
		(
			'keys' => array
			(
				'id'             => 'primary',
				'pid'            => 'index',
				'title'          => 'index',
				'responsedate'   => 'index'
			)
		),
	),
	
	// List
	'list' => array
	(
		'sorting' => array
		(
			'mode'                    => 4,
			'fields'                  => array('responsedate DESC'),
			'headerFields'            => array('title'),
			'panelLayout'             => 'search,limit',
			'disableGrouping'         => true,
			'child_record_callback'   => array('tl_forum_threads', 'listRecords'),
			'child_record_class'      => 'no_padding',
			//'rootPaste'               => false
		),
		'global_operations' => array
		(
			'all' => array
			(
				'label'               => &$GLOBALS['TL_LANG']['MSC']['all'],
				'href'                => 'act=select',
				'class'               => 'header_edit_all',
				'attributes'          => 'onclick="Backend.getScrollOffset()" accesskey="e"'
			),
		),
		'operations' => array
		(
			'edit' => array
			(
				'label'               => &$GLOBALS['TL_LANG']['tl_forum_threads']['edit'],
				'href'                => 'table=tl_forum_topics',
				'icon'                => 'edit.gif',
			),
			'editheader' => array
			(
				'label'               => &$GLOBALS['TL_LANG']['tl_forum_threads']['editheader'],
				'href'                => 'act=edit',
				'icon'                => 'header.gif',
			), 
			//'copy' => array
			//(
			//    'label'               => &$GLOBALS['TL_LANG']['tl_forum_threads']['copy'],
			//    'href'                => 'act=paste&mode=copy',
			//    'icon'                => 'copy.gif'
			//),
			'cut' => array
			(
				'label'               => &$GLOBALS['TL_LANG']['tl_forum_threads']['cut'],
				'href'                => 'act=paste&mode=cut',
				'icon'                => 'cut.gif'
			),
			'delete' => array
			(
				'label'               => &$GLOBALS['TL_LANG']['tl_forum_threads']['delete'],
				'href'                => 'act=delete',
				'icon'                => 'delete.gif',
				'attributes'          => 'onclick="if(!confirm(\'' . $GLOBALS['TL_LANG']['MSC']['deleteConfirm'] . '\'))return false;Backend.getScrollOffset()"'
			),
			'toggle' => array
			(
				'label'                => &$GLOBALS['TL_LANG']['tl_forum_threads']['toggle'],
				'attributes'           => 'onclick="Backend.getScrollOffset()"',
				'haste_ajax_operation' => array
				(
					'field'            => 'published',
					'options'          => array
					(
						array('value' => '', 'icon' => 'invisible.svg'),
						array('value' => '1', 'icon' => 'visible.svg'),
					),
				),
			),
			'show' => array
			(
				'label'               => &$GLOBALS['TL_LANG']['tl_forum_threads']['show'],
				'href'                => 'act=show',
				'icon'                => 'show.gif'
			)
		)
	),
	
	// Palettes
	'palettes' => array
	(
		'__selector__'                => array('protected'), 
		'default'                     => '{title_legend},title;{author_legend},name,email;{text_legend},text;{publish_legend},published'
	), 
	
	// Fields
	'fields' => array
	(
		'id' => array
		(
			'sql'                     => "int(10) unsigned NOT NULL auto_increment"
		),
		'pid' => array
		(
			'sql'                     => "int(10) unsigned NOT NULL default '0'"
		),
		'tstamp' => array
		(
			'sql'                     => "int(10) unsigned NOT NULL default '0'"
		),
		// Erstellungsdatum, wird beim Anlegen des Themas gesetzt
		'initdate' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_forum_threads']['initdate'],
			'flag'                    => 5,
			'sql'                     => "int(10) unsigned NOT NULL default '0'"
		), 
		// Aktualisierungsdatum, wird bei einer Antwort gesetzt
		'responsedate' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_forum_threads']['responsedate'],
			'flag'                    => 5,
			'sql'                     => "int(10) unsigned NOT NULL default '0'"
		), 
		// ID des Mitglieds mit der letzten Antwort
		'actname' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_forum_threads']['actname'],
			'exclude'                 => true,
			'inputType'               => 'select',
			'foreignKey'              => 'tl_member.username',
			'eval'                    => array('mandatory'=>true, 'tl_class'=>'w50', 'choosen'=>true),
			'sql'                     => "int(10) unsigned NOT NULL default '0'"
		), 
		'title' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_forum_threads']['title'],
			'exclude'                 => true,
			'search'                  => true,
			'sorting'                 => true,
			'flag'                    => 1,
			'inputType'               => 'text',
			'eval'                    => array('mandatory'=>true, 'maxlength'=>128, 'tl_class'=>'long'),
			'sql'                     => "varchar(128) NOT NULL default ''"
		),
		// Benutzer-ID des Threaderstellers (Falls leer, dann Gast)
		'userid' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_forum_threads']['userid'],
			'exclude'                 => true,
			'inputType'               => 'text',
			'eval'                    => array
			(
				'mandatory'           => false,
				'tl_class'            => 'w50',
			),
			'sql'                     => "int(10) unsigned NOT NULL default '0'"
		), 
		// Benutzer-Name des Threaderstellers (Falls leer, dann Gast)
		'username' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_forum_threads']['username'],
			'exclude'                 => true,
			'inputType'               => 'text',
			'eval'                    => array
			(
				'mandatory'           => false,
				'tl_class'            => 'w50',
			),
			'sql'                     => "varchar(64) BINARY NULL"
		), 
		// Email des Threaderstellers
		'email' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_forum_threads']['email'],
			'inputType'               => 'text',
			'eval'                    => array
			(
				'mandatory'           => false, 
				'rgxp'                => 'emails', 
				'decodeEntities'      => true, 
				'tl_class'            => 'w50'
			),
			'sql'                     => "varchar(128) NOT NULL default ''"
		), 
		'text' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_forum_thread']['text'],
			'exclude'                 => true,
			'search'                  => true,
			'inputType'               => 'textarea',
			'eval'                    => array('rte'=>'tinyMCE', 'tl_class'=>'clr'), 
			'sql'                     => "mediumtext NULL" 
		), 
		// Anzahl der Zugriffe
		'hits' => array
		(
			'exclude'                 => true,
			'inputType'               => 'text',
			'sql'                     => "int(10) unsigned NOT NULL default '0'"
		), 
		'published' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_forum_threads']['published'],
			'exclude'                 => true,
			'inputType'               => 'checkbox',
			'default'                 => 1,
			'eval'                    => array('doNotCopy'=>true),
			'sql'                     => "char(1) NOT NULL default ''"
		), 
	)
);

/**
 * Provide miscellaneous methods that are used by the data configuration array
 */
class tl_forum_threads extends Backend
{

	/**
	 * Funktion saveRecord (onsubmit_callback: Wird beim Abschicken des Backend-Formulars ausgeführt)
	 * Beim Speichern eines Datensatzes zusätzliche Änderungen vornehmen
	 * @param DataContainer
	 * @return -
	 */
	public function saveRecord(DataContainer $dc)
	{
		// Frontend-Aufruf
		if(!$dc instanceof DataContainer)
		{
			return;
		}

		// Zurück, wenn kein aktiver Datensatz vorhanden ist
		if(!$dc->activeRecord)
		{
			return;
		}

		if(!$dc->activeRecord->initdate)
		{
			// Eröffnungszeitpunkt im Thema speichern
			$zeit = time();
			$set = array
			(
				'initdate'     => $zeit,
				'responsedate' => $zeit
			);
			$this->Database->prepare("UPDATE tl_forum_threads %s WHERE id=?")
			               ->set($set)
			               ->execute($dc->id);
			return;
		}

	}
	
	/**
	 * Generiere eine Zeile als HTML
	 * @param array
	 * @return string
	 */
	public function listRecords($arrRow)
	{
		static $class;
		$class == 'odd' ? 'even' : 'odd';
		
		$line = '';
		$line .= '<div class="tl_content_left '.$class.'">';
		$line .= '<b>'.date('d.m.Y H:i', $arrRow['responsedate']).'</b> ';
		$line .= '<b>'.$arrRow['title'].'</b>';
		$line .= "</div>";
		
		return $line;
	
	}
	
}

