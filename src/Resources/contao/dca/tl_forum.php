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
 * Table tl_forum
 */
$GLOBALS['TL_DCA']['tl_forum'] = array
(

	// Config
	'config' => array
	(
		'label'                       => $GLOBALS['TL_LANG']['tl_forum']['maintitle'],
		'dataContainer'               => 'Table',
		'switchToEdit'                => true,
		'ctable'                      => array('tl_forum_threads'),
		'enableVersioning'            => true,
		'onload_callback'             => array
		(
			array('tl_forum', 'addBreadcrumb')
		),
		'sql'                         => array
		(
			'keys'                    => array
			(
				'id'                  => 'primary',
				'pid'                 => 'index'
			)
		)
	),

	// List
	'list' => array
	(
		'sorting' => array
		(
			'mode'                    => 5,
			'fields'                  => array('sorting'),
			'icon'                    => 'pagemounts.gif',
			'panelLayout'             => 'filter,search',
		),
		'label' => array
		(
			'fields'                  => array('title'),
			'format'                  => '%s',
			'label_callback'          => array('tl_forum', 'addIcon')
		),
		'global_operations' => array
		(
			'toggleNodes' => array
			(
				'label'               => &$GLOBALS['TL_LANG']['MSC']['toggleAll'],
				'href'                => 'ptg=all',
				'class'               => 'header_toggle',
				'showOnSelect'        => true 
			),
			'all' => array
			(
				'label'               => &$GLOBALS['TL_LANG']['MSC']['all'],
				'href'                => 'act=select',
				'class'               => 'header_edit_all',
				'attributes'          => 'onclick="Backend.getScrollOffset()" accesskey="e"'
			)
		),
		'operations' => array
		(
			'edit' => array
			(
				'label'               => &$GLOBALS['TL_LANG']['tl_forum']['edit'],
				'href'                => 'table=tl_forum_threads',
				'icon'                => 'edit.gif',
			),
			'editheader' => array
			(
				'label'               => &$GLOBALS['TL_LANG']['tl_forum']['editheader'],
				'href'                => 'act=edit',
				'icon'                => 'header.gif',
			), 
			'cut' => array
			(
				'label'               => &$GLOBALS['TL_LANG']['tl_forum']['cut'],
				'href'                => 'act=paste&amp;mode=cut',
				'icon'                => 'cut.gif',
				'attributes'          => 'onclick="Backend.getScrollOffset()"',
			),
			//'delete' => array
			//(
			//	'label'               => &$GLOBALS['TL_LANG']['tl_forum']['delete'],
			//	'href'                => 'act=delete',
			//	'icon'                => 'delete.gif',
			//	'attributes'          => 'onclick="if(!confirm(\'' . $GLOBALS['TL_LANG']['MSC']['deleteConfirm'] . '\'))return false;Backend.getScrollOffset()"',
			//),
			'toggle' => array
			(
				'label'               => &$GLOBALS['TL_LANG']['tl_forum']['toggle'],
				'icon'                => 'visible.gif',
				'attributes'          => 'onclick="Backend.getScrollOffset();return AjaxRequest.toggleVisibility(this,%s)"',
				'button_callback'     => array('tl_forum', 'toggleIcon') 
			),
			'show' => array
			(
				'label'               => &$GLOBALS['TL_LANG']['tl_forum']['show'],
				'href'                => 'act=show',
				'icon'                => 'show.gif'
			)
		)
	),

	// Palettes
	'palettes' => array
	(
		'__selector__'                => array('define_groups','define_rights'),
		'default'                     => '{title_legend},title;{groups_legend:hide},define_groups;{rights_legend:hide},define_rights;{publish_legend},published'
	), 

	// Subpalettes
	'subpalettes' => array
	(
		'define_groups'               => 'member_groups,admin_groups,default_author',
		'define_rights'               => 'guest_rights,member_rights,admin_rights',
	),
	
	// Fields
	'fields' => array
	(
		'id' => array
		(
			'label'                   => array('ID'),
			'search'                  => true,
			'sql'                     => "int(10) unsigned NOT NULL auto_increment"
		),
		'pid' => array
		(
			'sql'                     => "int(10) unsigned NOT NULL default '0'"
		),
		'sorting' => array
		(
			'sql'                     => "int(10) unsigned NOT NULL default '0'"
		),
		'tstamp' => array
		(
			'sql'                     => "int(10) unsigned NOT NULL default '0'"
		),
		'title' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_forum']['title'],
			'exclude'                 => true,
			'inputType'               => 'text',
			'search'                  => true,
			'eval'                    => array('mandatory'=>true, 'maxlength'=>255, 'decodeEntities'=>true),
			'sql'                     => "varchar(255) NOT NULL default ''"
		),
		'define_groups' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_forum']['define_groups'],
			'exclude'                 => true,
			'default'                 => '',
			'inputType'               => 'checkbox',
			'eval'                    => array('submitOnChange'=>true),
			'sql'                     => "char(1) NOT NULL default ''"
		),
		'member_groups' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_forum']['member_groups'],
			'exclude'                 => true,
			'inputType'               => 'checkbox',
			'foreignKey'              => 'tl_member_group.name',
			'eval'                    => array('mandatory'=>false, 'multiple'=>true),
			'sql'                     => "blob NULL"
		),
		'admin_groups' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_forum']['admin_groups'],
			'exclude'                 => true,
			'inputType'               => 'checkbox',
			'foreignKey'              => 'tl_member_group.name',
			'eval'                    => array('mandatory'=>false, 'multiple'=>true),
			'sql'                     => "blob NULL"
		),
		'define_rights' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_forum']['define_rights'],
			'exclude'                 => true,
			'default'                 => '',
			'inputType'               => 'checkbox',
			'eval'                    => array('submitOnChange'=>true),
			'sql'                     => "char(1) NOT NULL default ''"
		),
		'guest_rights' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_forum']['guest_rights'],
			'exclude'                 => true,
			'inputType'               => 'checkbox',
			//'options_callback'        => array('tl_forum','getGuestRightList'),
			'eval'                    => array('mandatory'=>false, 'multiple'=>true),
			'sql'                     => "blob NULL"
		),
		'member_rights' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_forum']['member_rights'],
			'exclude'                 => true,
			'inputType'               => 'checkbox',
			//'options_callback'        => array('tl_forum','getRightList'),
			'eval'                    => array('mandatory'=>false, 'multiple'=>true),
			'sql'                     => "blob NULL"
		),
		'admin_rights' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_forum']['admin_rights'],
			'exclude'                 => true,
			'inputType'               => 'checkbox',
			//'options_callback'        => array('tl_forum','getRightList'),
			'eval'                    => array('mandatory'=>false, 'multiple'=>true),
			'sql'                     => "blob NULL"
		),
		'published' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_forum']['published'],
			'exclude'                 => true,
			'inputType'               => 'checkbox',
			'eval'                    => array('doNotCopy'=>true),
			'sql'                     => "char(1) NOT NULL default ''"
		), 
	)
);

/**
 * Class tl_forum
 *
 * Provide miscellaneous methods that are used by the data configuration array.
 * @copyright  Leo Feyer 2005-2013
 * @author     Leo Feyer <https://contao.org>
 * @package    Core
 */
class tl_forum extends Backend
{

	/**
	 * Import the back end user object
	 */
	public function __construct()
	{
		parent::__construct();
		$this->import('BackendUser', 'User');
	}

	/**
	 * Add the breadcrumb menu
	 */
	public function addBreadcrumb()
	{

		// Knoten in Session speichern
		if (isset($_GET['node']))
		{
			$this->Session->set('tl_forum_node', $this->Input->get('node'));
			$this->redirect(preg_replace('/&node=[^&]*/', '', $this->Environment->request));
		}
		$cat = $this->Session->get('tl_forum_node');

		// Breadcrumb-Navigation erstellen
		$breadcrumb = array();
		if($cat) // Nur bei Unterkategorien
		{
			// Kategorienbaum einschränken
			$GLOBALS['TL_DCA']['tl_forum']['list']['sorting']['root'] = array($cat);
		
			// Infos zur aktuellen Kategorie laden
			$objActual = \Database::getInstance()->prepare('SELECT * FROM tl_forum WHERE published = ? AND id = ?')
							   				     ->execute(1, $cat);
			$breadcrumb[] = '<img src="bundles/contaoforum/images/category.png" width="18" height="18" alt=""> ' . $objActual->title;
			
			// Navigation vervollständigen
			$pid = $objActual->pid;
			while($pid > 0)
			{
				$objTemp = \Database::getInstance()->prepare('SELECT * FROM tl_forum WHERE published = ? AND id = ?')
								   			       ->execute(1, $pid);
				$breadcrumb[] = '<img src="bundles/contaoforum/images/category.png" width="18" height="18" alt=""> <a href="' . \Controller::addToUrl('node='.$objTemp->id) . '" title="'.specialchars($GLOBALS['TL_LANG']['MSC']['selectNode']).'">' . $objTemp->title . '</a>';
				$pid = $objTemp->pid;
			}
			$breadcrumb[] = '<img src="' . TL_FILES_URL . 'system/themes/' . \Backend::getTheme() . '/images/pagemounts.gif" width="18" height="18" alt=""> <a href="' . \Controller::addToUrl('node=0') . '" title="'.specialchars($GLOBALS['TL_LANG']['MSC']['selectAllNodes']).'">' . $GLOBALS['TL_LANG']['MSC']['filterAll'] . '</a>';
		}
		$breadcrumb = array_reverse($breadcrumb);

		// Insert breadcrumb menu
		if($breadcrumb)
		{
			$GLOBALS['TL_DCA']['tl_forum']['list']['sorting']['breadcrumb'] .= '
			<ul id="tl_breadcrumb">
  				<li>' . implode(' &gt; </li><li>', $breadcrumb) . '</li>
			</ul>';
		}
	}

	/**
	 * Add an image to each page in the tree
	 *
	 * @param array         $row
	 * @param string        $label
	 * @param DataContainer $dc
	 * @param string        $imageAttribute
	 * @param boolean       $blnReturnImage
	 * @param boolean       $blnProtected
	 *
	 * @return string
	 */
	public function addIcon($row, $label, DataContainer $dc=null, $imageAttribute='', $blnReturnImage=false, $blnProtected=false)
	{
		if ($blnProtected)
		{
			$row['protected'] = true;
		}

		$image = 'bundles/contaoforum/images/category.png';
		$imageAttribute = trim($imageAttribute . ' data-icon="category.png" data-icon-disabled="category.png"');

		// Return the image only
		if ($blnReturnImage)
		{
			return \Image::getHtml($image, '', $imageAttribute);
		}

		// Markiere Root-Kategorien
		if ($row['pid'] == '0')
		{
			$label = '<strong>' . $label . '</strong>';
		}

		// Rückgabe der Zeile
		return \Image::getHtml($image, '', $imageAttribute) . '<a href="' . \Controller::addToUrl('node='.$row['id']) . '" title="'.specialchars($GLOBALS['TL_LANG']['MSC']['selectNode']).'"> ' . $label . '</a> 
		(<b>'.$row['links_self'].'</b>/'.$row['links_all'].')';

	}


	/**
	 * Return the "toggle visibility" button
	 * @param array
	 * @param string
	 * @param string
	 * @param string
	 * @param string
	 * @param string
	 * @return string
	 */
	public function toggleIcon($row, $href, $label, $title, $icon, $attributes)
	{
        $this->import('BackendUser', 'User');
 
        if (strlen($this->Input->get('tid')))
        {
            $this->toggleVisibility($this->Input->get('tid'), ($this->Input->get('state') == 0));
            $this->redirect($this->getReferer());
        }
 
        // Check permissions AFTER checking the tid, so hacking attempts are logged
        if (!$this->User->isAdmin && !$this->User->hasAccess('tl_forum::published', 'alexf'))
        {
            return '';
        }
 
        $href .= '&amp;id='.$this->Input->get('id').'&amp;tid='.$row['id'].'&amp;state='.$row[''];
 
        if (!$row['published'])
        {
            $icon = 'invisible.gif';
        }
 
        return '<a href="'.$this->addToUrl($href).'" title="'.specialchars($title).'"'.$attributes.'>'.$this->generateImage($icon, $label).'</a> ';
	}

	/**
	 * Disable/enable a user group
	 * @param integer
	 * @param boolean
	 */
	public function toggleVisibility($intId, $blnPublished)
	{
		// Check permissions to publish
		if (!$this->User->isAdmin && !$this->User->hasAccess('tl_forum::published', 'alexf'))
		{
			$this->log('Not enough permissions to show/hide record ID "'.$intId.'"', 'tl_forum toggleVisibility', TL_ERROR);
			$this->redirect('contao/main.php?act=error');
		}
	
		$this->createInitialVersion('tl_forum', $intId);
	
		// Trigger the save_callback
		if (is_array($GLOBALS['TL_DCA']['tl_forum']['fields']['published']['save_callback']))
		{
			foreach ($GLOBALS['TL_DCA']['tl_forum']['fields']['published']['save_callback'] as $callback)
			{
				$this->import($callback[0]);
				$blnPublished = $this->$callback[0]->$callback[1]($blnPublished, $this);
			}
		}
	
		// Update the database
		$this->Database->prepare("UPDATE tl_forum SET tstamp=". time() .", published='" . ($blnPublished ? '' : '1') . "' WHERE id=?")
			->execute($intId);
		$this->createNewVersion('tl_forum', $intId);
	}
}

