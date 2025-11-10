<?php

namespace Schachbulle\ContaoForumBundle\Modules;

/*
 */

class Forum extends \Module
{

	protected $strTemplate = 'mod_forum';
	protected $formTemplate = 'mod_forum_formular';
	protected $threadsTemplate = 'subforum_threads';
	protected $topicsTemplate = 'subforum_topics';
	
	var $kategorie = '';
	var $newstring = ' <img src="bundles/contaoforum/images/neu.png">';
	var $member = array();
	var $arrayBBCode;

	/**
	 * Display a wildcard in the back end
	 * @return string
	 */
	public function generate()
	{
		if (TL_MODE == 'BE')
		{
			$objTemplate = new \BackendTemplate('be_wildcard');

			$objTemplate->wildcard = '### FORUM ###';
			$objTemplate->title = $this->name;
			$objTemplate->id = $this->id;

			return $objTemplate->parse();
		}
		else
		{
			// FE-Modus: URL mit allen möglichen Parametern auflösen
			\Input::setGet('view', \Input::get('view')); // Ansichtsmodus: categories (Foren), threads (Themen), newthread (neues Thema)
			\Input::setGet('fid', \Input::get('fid')); // ID des Forums/Themas
		}

		$this->import('FrontendUser', 'User');

		echo "view=".\Input::get('view')."<br>";
		echo "fid=".\Input::get('fid')."<br>";
		echo "FORM_SUBMIT=".\Input::post('FORM_SUBMIT')."<br>";

		return parent::generate(); // Weitermachen mit dem Modul
	}

	/**
	 * Generate the module
	 */
	protected function compile()
	{
		global $objPage;

		/*********************************************************
		** Mitglieder auslesen
		*/

		$objMembers = \Database::getInstance()->prepare('SELECT id, email, firstname, lastname, username FROM tl_member')
		                                      ->execute();

		if($objMembers->numRows > 0)
		{
			// Datensätze auswerten
			while($objMembers->next())
			{
				$this->member[$objMembers->id] = array
				(
					'email'     => $objMembers->email,
					'firstname' => $objMembers->firstname,
					'lastname'  => $objMembers->lastname,
					'username'  => $objMembers->username,
				);
			}
		}

		/*********************************************************
		** Ansichtsmodus
		*/

		if(\Input::get('view'))
		{
			switch(\Input::get('view'))
			{
				case 'forum': // Forum anzeigen
					self::ZeigeForum();
					break;
				case 'topic': // Thema anzeigen
					self::ZeigeThema();
					break;
				case 'newtopic': // Formular für neues Thema
					self::ZeigeThemaFormular();
					break;
				default: // Keine Parameter in der URL
					self::ZeigeForum();
			}
			return;
		}
		else
		{
			// Keine Ansicht ausgewählt, deshalb Startseite anzeigen
			self::ZeigeForum();
		}

		return;

		/*********************************************************
		** Ausgabe des Formular für ein neues Thema
		*/

		if(\Input::get('new_thread') && \Input::get('category'))
		{
			$this->Template = new \FrontendTemplate($this->formTemplate);

			// Der 1. Parameter ist die Formular-ID (hier "linkform")
			// Der 2. Parameter ist GET oder POST
			// Der 3. Parameter ist eine Funktion, die entscheidet wann das Formular gesendet wird (Third is a callable that decides when your form is submitted)
			// Der optionale 4. Parameter legt fest, ob das ausgegebene Formular auf Tabellen basiert (true)
			// oder nicht (false) (You can pass an optional fourth parameter (true by default) to turn the form into a table based one)
			$objForm = new \Codefog\HasteBundle\Form\Form('newthreadForm', 'POST', function($objHaste)
			{
				return \Input::post('FORM_SUBMIT') === $objHaste->getFormId();
			});
			
			// URL für action festlegen. Standard ist die Seite auf der das Formular eingebunden ist.
			//$objForm->setFormActionFromUri(\Controller::generateFrontendUrl($objPage->row(), '/category/'.\Input::get('category')));
			//$objForm->setFormActionFromUri(\Controller::generateFrontendUrl($objPage->row()));

			$objForm->addFormField('category', array(
				'inputType'     => 'hidden',
				'value'         => \Input::get('category')
			));
			$objForm->addFormField('title', array(
				'label'         => 'Titel des Themas',
				'inputType'     => 'text',
				'eval'          => array('mandatory'=>true, 'class'=>'form-control')
			));
			$objForm->addFormField('text', array(
				'label'         => 'Inhalt des Themas',
				'inputType'     => 'textarea',
				'eval'          => array('mandatory'=>true, 'rte'=>'tinyMCE', 'class'=>'form-control')
			));
			// Submit-Button hinzufügen
			$objForm->addFormField('submit', array(
				'label'         => 'Absenden',
				'inputType'     => 'submit',
				'eval'          => array('class'=>'btn btn-primary')
			));
			$objForm->addCaptchaFormField('captcha');
			
			// validate() prüft auch, ob das Formular gesendet wurde
			if($objForm->validate())
			{
				// Alle gesendeten und analysierten Daten holen (funktioniert nur mit POST)
				$arrData = $objForm->fetchAll();
				self::saveNewThread($arrData); // Daten sichern
				// Seite neu laden
				\Controller::addToUrl('send=1'); // Hat keine Auswirkung, verhindert aber das das Formular ausgefüllt ist
				\Controller::reload(); 
			}

			// Formular als String zurückgeben
			$this->Template->form = $objForm->generate();

		}

		/*********************************************************
		** Ausgabe der Beiträge (topics) eines Themas (thread)
		*/

		if(\Input::get('thread'))
		{
			// Eingangsbeitrag des Threads laden
			$objThread = \Database::getInstance()->prepare('SELECT * FROM tl_forum_threads WHERE id=?')
			                                     ->execute(\Input::get('thread'));

			$topics = array();
			if($objThread->numRows > 0)
			{
				$class = ($class == 'odd') ? 'even' : 'odd';
				$topics[] = array
				(
					'text'      => $this->showBBcodes($objThread->text),
					'name'      => $objThread->name == 0 ? 'Gast' : $this->member[$objThread->name]['username'].$newstatus,
					'topicdate' => date("d.m.Y H:i", $objThread->initdate),
					'class'     => $class,
				);
			}
			else
			{
				// Thread nicht gefunden, 404 ausgeben
				$objHandler = new $GLOBALS['TL_PTY']['error_404']();
				$objHandler->generate($objPage->id);
			}

			$this->kategorie = $objThread->pid;

			// Topics des aktuellen Threads laden
			$objTopics = \Database::getInstance()->prepare('SELECT t.id, t.text, m.username, t.topicdate FROM tl_forum_topics t INNER JOIN tl_member m ON (t.name = m.id) WHERE published = ? AND pid = ? ORDER BY topicdate ASC')
			                                     ->execute(1, \Input::get('thread'));

			$arrRead = array();
			if($objTopics->numRows > 0)
			{
				// Datensätze anzeigen
				while($objTopics->next())
				{
					$newstatus = (in_array($objTopics->id, $unread_topics)) ? '<br>'.$this->newstring : '';
					$arrRead[] = $objTopics->id; // Topic als gelesen markieren
					$class = ($class == 'odd') ? 'even' : 'odd';
					$topics[] = array
					(
						'text'      => $this->showBBcodes($objTopics->text),
						'name'      => $objTopics->username.$newstatus,
						'topicdate' => date("d.m.Y H:i", $objTopics->topicdate),
						'class'     => $class,
					);
				}
			}

			// Template füllen
			$this->Template = new \FrontendTemplate($this->topicsTemplate);
			$this->Template->threadname = $objThread->title;
			$this->Template->category = $this->kategorie;
			$this->Template->thread = \Input::get('thread');
			$this->Template->topics = $topics;
			$this->Template->form = $this->SendTopicForm();
			$this->Template->username = $this->User->username;
		}

	}

	/***********************************************************************************
	 * Funktion ZeigeForum
	 * Forum anzeigen bzw. Startseite (Forenübersicht)
	 ***********************************************************************************/
	protected function ZeigeForum()
	{
		global $objPage;

		$this->Template = new \FrontendTemplate('forum_categories');
		// Gewünschte Startkategorie festlegen: fid-Wert benutzen, wenn vorhanden; ansonsten die im Modul eingestellte Kategorie
		$this->kategorie = (\Input::get('fid')) ? \Input::get('fid') : $this->forum_category;

		// Aktuelles Forum laden
		$objCategory = \Database::getInstance()->prepare('SELECT * FROM tl_forum WHERE published = ? AND id = ?')
		                                       ->limit(1)
		                                       ->execute(1, $this->kategorie);

		if($objCategory->numRows == 0)
		{
			// Kategorie nicht gefunden, 404 ausgeben
			$objHandler = new $GLOBALS['TL_PTY']['error_404']();
			$objHandler->generate($objPage->id);
		}

		$categories = array();

		// Forum nur anzeigen, wenn es als Kategorie definiert ist
		if($objCategory->category)
		{
			$categories[] = array
			(
				'title'       => $objCategory->title,
				'description' => $objCategory->description,
				'link'        => $objCategory->category ? '' : \Controller::generateFrontendUrl($objPage->row(), '/view/forum/fid/'.$objCategory->id),
				'level'       => 'level0',
				'category'    => $objCategory->category ? 'category' : '',
			);
		}

		// Unterkategorien laden
		$objCategories = \Database::getInstance()->prepare('SELECT * FROM tl_forum WHERE published = ? AND pid = ? ORDER BY sorting ASC')
		                                         ->execute(1, $this->kategorie);

		if($objCategories->numRows > 0)
		{
			// Datensätze einlesen
			while($objCategories->next())
			{
				$categories[] = array
				(
					'title'       => $objCategories->title,
					'description' => $objCategories->description,
					'link'        => $objCategories->category ? '' : \Controller::generateFrontendUrl($objPage->row(), '/view/forum/fid/'.$objCategories->id),
					'level'       => 'level1',
					'category'    => $objCategories->category ? 'category' : '',
				);
			}
		}

		// Themen der aktuellen Kategorie laden
		$objThreads = \Database::getInstance()->prepare('SELECT * FROM tl_forum_threads WHERE published = ? AND pid = ? ORDER BY responsedate DESC')
		                                      ->execute(1, $this->kategorie);

		$threads = array();
		if($objThreads->numRows > 0)
		{
			// Datensätze einlesen
			while($objThreads->next())
			{
				$threads[] = array
				(
					'title'     => $objThreads->title,
					'link'      => \Controller::generateFrontendUrl($objPage->row(), '/view/topic/fid/'.$objThreads->id),
					'name'      => $objThreads->username ? $objThreads->username : 'Gast',
					'actname'   => $this->member[$objThreads->actname]['username'],
					'actdate'   => $objThreads->responsedate ? date("d.m.Y H:i", $objThreads->responsedate) : '',
					'initdate'  => date("d.m.Y H:i", $objThreads->initdate),
					'class'     => $class,
				);
			}
		}

		// Template füllen
		$this->Template->category = $this->kategorie;
		$this->Template->categoryname = $objCategory->title;
		$this->Template->categories = $categories;
		$this->Template->threads = $threads;
		$this->Template->username = $this->User->username;
		$this->Template->linkNeuesThema = $objCategory->category ? '' : \Controller::generateFrontendUrl($objPage->row(), '/view/newtopic/fid/'.$this->kategorie);
		$this->Template->nothreads = $objCategory->category ? '' : (!$threads ? 'Es gibt noch keine Themen in diesem Forum.' : '');
	}

	/***********************************************************************************
	 * Funktion ZeigeThemaFormular
	 * Formular für neues Thema anzeigen/auswerten
	 ***********************************************************************************/
	protected function ZeigeThemaFormular()
	{
		$this->Template = new \FrontendTemplate('forum_formular');

		// Der 1. Parameter ist die Formular-ID (hier "linkform")
		// Der 2. Parameter ist GET oder POST
		// Der 3. Parameter ist eine Funktion, die entscheidet wann das Formular gesendet wird (Third is a callable that decides when your form is submitted)
		// Der optionale 4. Parameter legt fest, ob das ausgegebene Formular auf Tabellen basiert (true)
		// oder nicht (false) (You can pass an optional fourth parameter (true by default) to turn the form into a table based one)
		$objForm = new \Codefog\HasteBundle\Form\Form('newthreadForm', 'POST', function($objHaste)
		{
			return \Input::post('FORM_SUBMIT') === $objHaste->getFormId();
		});
		
		// URL für action festlegen. Standard ist die Seite auf der das Formular eingebunden ist.
		//$objForm->setFormActionFromUri(\Controller::generateFrontendUrl($objPage->row(), '/category/'.\Input::get('category')));
		//$objForm->setFormActionFromUri(\Controller::generateFrontendUrl($objPage->row()));

		$objForm->addFormField('forum', array(
			'inputType'     => 'hidden',
			'value'         => \Input::get('fid')
		));
		$objForm->addFormField('title', array(
			'label'         => 'Titel des Themas',
			'inputType'     => 'text',
			'eval'          => array('mandatory'=>true, 'class'=>'form-control')
		));
		$objForm->addFormField('text', array(
			'label'         => 'Inhalt des Themas',
			'inputType'     => 'textarea',
			'eval'          => array('mandatory'=>true, 'rte'=>'tinyMCE', 'class'=>'form-control')
		));
		// Submit-Button hinzufügen
		$objForm->addFormField('submit', array(
			'label'         => 'Absenden',
			'inputType'     => 'submit',
			'eval'          => array('class'=>'btn btn-primary')
		));
		$objForm->addCaptchaFormField('captcha');
		
		// validate() prüft auch, ob das Formular gesendet wurde
		if($objForm->validate())
		{
			// Alle gesendeten und analysierten Daten holen (funktioniert nur mit POST)
			$arrData = $objForm->fetchAll();
			self::SpeichereThema($arrData); // Daten sichern
			// Seite neu laden
			//\Controller::addToUrl('send=1'); // Hat keine Auswirkung, verhindert aber das das Formular ausgefüllt ist
			//\Controller::reload(); 
		}

		// Formular als String zurückgeben
		$this->Template->form = $objForm->generate();
	}
	
	protected function SpeichereThema($data)
	{
		print_r($data);
		// Datenbank aktualisieren
		$zeit = time();
		$data['title'] = html_entity_decode($data['title']);
		$data['text'] = html_entity_decode($data['text']);

		// Threads-Tabelle aktualisieren
		$set = array
		(
			'pid'       => $data['forum'],
			'tstamp'    => $zeit,
			'initdate'  => $zeit,
			'userid'    => $this->User->id ? $this->User->id : 0,
			'username'  => $this->User->username,
			'title'     => $data['title'],
			'published' => 1,
		);
		$objThread = \Database::getInstance()->prepare('INSERT INTO tl_forum_threads %s')
		                                     ->set($set)
		                                     ->execute();
		$insertId = $objThread->insertId;

		// Topics-Tabelle aktualisieren
		$set = array
		(
			'pid'       => $insertId,
			'tstamp'    => $zeit,
			'topicdate' => $zeit,
			'userid'    => $this->User->id ? $this->User->id : 0,
			'username'  => $this->User->username,
			'title'     => $data['title'],
			'text'      => $data['text'],
			'published' => 1,
		);
		$objTopic = \Database::getInstance()->prepare('INSERT INTO tl_forum_topics %s')
		                                    ->set($set)
		                                    ->execute();

	}

	protected function formThread()
	{
		global $objPage;

		$this->import('FrontendUser', 'User');

		$content = '';
		$form = new \Schachbulle\ContaoHelperBundle\Classes\Form();
		$form->addField(array
		(
			'typ'       => 'hidden',
			'name'      => 'FORM_SUBMIT',
			'value'     => 'form_forum_thread'
		));
		$form->addField(array
		(
			'typ'       => 'hidden',
			'name'      => 'REQUEST_TOKEN',
			'value'     => REQUEST_TOKEN
		));
		$form->addField(array
		(
			'typ'       => 'hidden',
			'name'      => 'category',
			'value'     => $this->kategorie
		));
		$form->addField(array
		(
			'typ'       => 'text',
			'name'      => 'title',
			'label'     => 'Titel',
			'mandatory' => true
		));
		$form->addField(array
		(
			'typ'       => 'textarea',
			'name'      => 'text',
			'label'     => 'Text',
			'rows'      => 10,
			'cols'      => 40,
			'mandatory' => true
		));
		$form->addField(array
		(
			'typ'      => 'submit',
			'label'    => 'Absenden'
		));
		$content = $form->generate();

		if($form->validate())
		{
			$arrData = $form->fetchAll();
		}
		else
		{
			if(\Input::get('send'))
			{
			}
		}

		return $content;

	}

	protected function saveNewThread_old($data)
	{
		//print_r($data);
		$zeit = time();
		$data['text'] = html_entity_decode($data['text']);

		// Threads-Tabelle aktualisieren
		$set = array
		(
			'pid'       => $data['category'],
			'name'      => $data['member'],
			'tstamp'    => $zeit,
			'initdate'  => $zeit,
			'actdate'   => $zeit,
			'actname'   => $data['member'],
			'title'     => $data['title'],
			'published' => 1,
		);
		$objThread = \Database::getInstance()->prepare('INSERT INTO tl_forum_threads %s')
		                                     ->set($set)
		                                     ->execute();
		$insertId = $objThread->insertId;

		// Topics-Tabelle aktualisieren
		$set = array
		(
			'pid'       => $insertId,
			'tstamp'    => $zeit,
			'topicdate' => $zeit,
			'name'      => $data['member'],
			'title'     => $data['title'],
			'text'      => $data['text'],
			'published' => 1,
		);
		$objTopic = \Database::getInstance()->prepare('INSERT INTO tl_forum_topics %s')
		                                    ->set($set)
		                                    ->execute();

		// Mailinfo erstellen, alle Mitglieder auslesen
		$objTopics = \Database::getInstance()->prepare('SELECT id, email, firstname, lastname, username FROM tl_member')
 		                                     ->execute();

		$mails = array();
		if($objTopics->numRows > 0)
		{
			// Datensätze auswerten
			while($objTopics->next())
			{
				if($data['member'] == $objTopics->id) $username = $objTopics->username;
				$mails[] = $objTopics->firstname.' '.$objTopics->lastname.' <'.$objTopics->email.'>';
			}
		}

		$mails = array_unique($mails); // Doppelte Adressen entfernen

		// Email verschicken
		$objEmail = new \Email();
		$objEmail->from = $GLOBALS['TL_ADMIN_EMAIL'];
		$objEmail->fromName = $GLOBALS['TL_ADMIN_NAME'];
		$objEmail->subject = 'Neues Thema im Forum';

		$url = 'http://leichtgewicht.swifteliblue.de/index/thread/'.$insertId.'.html';

		// Kommentar zusammenbauen
		$objEmail->html = 'Autor/in: <b>'.$username."</b><br>Titel: <b>".$data['title']."</b><br>Text: <b>".$data['text'].'</b><br><a href="'.$url.'">'.$url.'</a>';
		//$objEmail->sendTo($mails);
	}

	protected function SendTopicForm()
	{
		global $objPage;

		$this->import('FrontendUser', 'User');

	}

	protected function saveNewTopic($data)
	{
		//var_dump($data);
		//print_r($_FILES);
		$zeit = time();
		$data['text'] = html_entity_decode($data['text']);

		// Threads-Tabelle aktualisieren
		$set = array
		(
			'responsedate'   => $zeit,
			'actname'        => $data['member'],
		);
		$objThread = \Database::getInstance()->prepare('UPDATE tl_forum_threads %s WHERE id = ?')
		                                     ->set($set)
		                                     ->execute($data['thread']);

		// Topics-Tabelle aktualisieren
		$set = array
		(
			'pid'       => $data['thread'],
			'tstamp'    => $zeit,
			'topicdate' => $zeit,
			'name'      => $data['member'],
			'text'      => $data['text'],
			'published' => 1,
		);
		$objTopic = \Database::getInstance()->prepare('INSERT INTO tl_forum_topics %s')
		                                    ->set($set)
		                                    ->execute();

		// Mailinfo erstellen, zuerst Mitglieder auslesen
		$objTopics = \Database::getInstance()->prepare('SELECT id, email, firstname, lastname, username FROM tl_member')
 		                                     ->execute();

		$mails = array();
		if($objTopics->numRows > 0)
		{
			// Datensätze auswerten
			while($objTopics->next())
			{
				if($data['member'] == $objTopics->id) $username = $objTopics->username;
				$mails[] = $objTopics->firstname.' '.$objTopics->lastname.' <'.$objTopics->email.'>';
			}
		}

		$mails = array_unique($mails); // Doppelte Adressen entfernen

		// Email verschicken
		$objEmail = new \Email();
		$objEmail->from = $GLOBALS['TL_ADMIN_EMAIL'];
		$objEmail->fromName = $GLOBALS['TL_ADMIN_NAME'];
		$objEmail->subject = 'Neuer Beitrag im Forum';

		$url = 'http://leichtgewicht.swifteliblue.de/index/thread/'.$data['thread'].'.html';

		// Kommentar zusammenbauen
		$objEmail->html = 'Autor/in: <b>'.$username."</b><br>Titel: <b>".$data['text'].'</b><br><a href="'.$url.'">'.$url.'</a>';
		//$objEmail->sendTo($mails);
	}

	static function showBBcodes($text)
	{
		// BBcode array
		$find = array(
			'~\[b\](.*?)\[/b\]~s',
			'~\[i\](.*?)\[/i\]~s',
			'~\[u\](.*?)\[/u\]~s',
			'~\[quote\](.*?)\[/quote\]~s',
			'~\[size=(.*?)\](.*?)\[/size\]~s',
			'~\[color=(.*?)\](.*?)\[/color\]~s',
			'~\[url\]((?:ftp|https?)://.*?)\[/url\]~s',
			'~\[img\](https?://.*?\.(?:jpg|jpeg|gif|png|bmp))\[/img\]~s',
			'~\[img\](.*?)\[/img\]~s',
		);
		// HTML tags to replace BBcode
		$replace = array(
			'<b>$1</b>',
			'<i>$1</i>',
			'<span style="text-decoration:underline;">$1</span>',
			'<pre>$1</'.'pre>',
			'<span style="font-size:$1px;">$2</span>',
			'<span style="color:$1;">$2</span>',
			'<a href="$1">$1</a>',
			'<img src="$1" alt="" />',
			'<img src="$1" alt="" />'
		);
		// Replacing the BBcodes with corresponding HTML tags
		return preg_replace($find,$replace,$text);
	}

}
