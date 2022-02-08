<?php

namespace Schachbulle\ContaoForumBundle\Modules;

/*
 */

class Forum extends \Module
{

	protected $strTemplate = 'mod_forum';
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
			\Input::setGet('category', \Input::get('category')); // ID der Kategorie
			\Input::setGet('thread', \Input::get('thread')); // ID des Threads
		}

		return parent::generate(); // Weitermachen mit dem Modul
	}

	/**
	 * Generate the module
	 */
	protected function compile()
	{
		global $objPage;

		$this->import('FrontendUser', 'User');
		$session = \Session::getInstance();

		/*
		** Cookie-Auswertung
		*/

		// Cookie mit den gelesenen Themen auslesen
		//$read_topics = unserialize($this->Input->cookie('contao-forum_read'));
		$read_topics = unserialize($session->get('contao-forum_read'));
		if(!is_array($read_topics)) $read_topics = array();

		// ID's der Topics einlesen für Auswertung "Gelesen Ja/Nein"
		$objTopics = \Database::getInstance()->prepare('SELECT p.id AS topic, t.pid AS category, t.id AS thread FROM tl_forum_topics p INNER JOIN tl_forum_threads t ON (p.pid = t.id) WHERE p.published = ?')
		                                     ->execute(1);
		// ID's vergleichen und ungelesene ID's speichern
		$exist_topics = array();
		$topic = array();
		if($objTopics->numRows > 0)
		{
			while($objTopics->next())
			{
				$exist_topics[] = $objTopics->topic;
				$topic[$objTopics->topic] = array
				(
					'category'  => $objTopics->category,
					'thread'    => $objTopics->thread
				);
			}
		}

		// Ungelesene Topics ermitteln
		$unread_topics = array_diff($exist_topics, $read_topics);
		$arrCategories = array();
		$arrThreads = array();
		foreach($unread_topics as $item)
		{
			$arrCategories[] = $topic[$item]['category'];
			$arrThreads[] = $topic[$item]['thread'];
		}
		// Arrays kürzen
		$arrCategories = array_unique($arrCategories);
		$arrThreads = array_unique($arrThreads);
		//if($this->User->id == 1) $this->Template->debug = print_r($arrThreads, 1);

		/*
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

		/*
		** Ausgabe
		*/

		// Topics eines Threads ausgeben
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

			// Gelesene und ungelesene Topics abgleichen
			$unread_topics = array_diff($unread_topics, $arrRead);
			// Cookie (gelesene Topics) und gerade gelesene Topics zusammenfügen, danach doppelte entfernen
			$read_topics = array_unique(array_merge($read_topics, $arrRead));
			$duration = time() + (3600 * 24 * 30); // max. Cookie-Alter setzen
			//$this->setCookie('contao-forum_read', serialize($read_topics), $duration);
			$session->set('contao-forum_read', serialize($read_topics));

			// Template füllen
			$this->Template = new \FrontendTemplate($this->topicsTemplate);
			$this->Template->threadname = $objThread->title;
			$this->Template->category = $this->kategorie;
			$this->Template->thread = \Input::get('thread');
			$this->Template->topics = $topics;
			$this->Template->form = $this->SendTopicForm();
			$this->Template->username = $this->User->username;
		}
		// Kategorien und Threads ausgeben
		else
		{
			$this->Template = new \FrontendTemplate($this->threadsTemplate);
			// Kategorie festlegen
			$this->kategorie = (\Input::get('category')) ? \Input::get('category') : $this->forum_category;

			// Aktuelle Kategorien laden
			$objCategory = \Database::getInstance()->prepare('SELECT * FROM tl_forum WHERE published = ? AND id = ?')
			                                       ->execute(1, $this->kategorie);

			if($objCategory->numRows == 0)
			{
				// Kategorie nicht gefunden, 404 ausgeben
				$objHandler = new $GLOBALS['TL_PTY']['error_404']();
				$objHandler->generate($objPage->id);
			}

			// Unterkategorien laden
			$objCategories = \Database::getInstance()->prepare('SELECT * FROM tl_forum WHERE published = ? AND pid = ? ORDER BY sorting ASC')
			                                         ->execute(1, $this->kategorie);

			$categories = array();
			if($objCategories->numRows > 0)
			{
				// Datensätze anzeigen
				while($objCategories->next())
				{
					// Neustatus ermitteln
					$newstatus = (in_array($objCategories->id, $arrCategories)) ? $this->newstring : '';
					$class = ($class == 'odd') ? 'even' : 'odd';
					$categories[] = array
					(
						'title'     => $objCategories->title.$newstatus,
						'link'      => \Controller::generateFrontendUrl($objPage->row(), '/category/'.$objCategories->id),
						'class'     => $class,
					);
				}
			}

			if(!$categories && !$this->forum_allpost)
			{
				// Threads der aktuellen Kategorie laden
				$objThreads = \Database::getInstance()->prepare('SELECT t.id, t.title, m.username, t.actname, t.actdate, t.initdate FROM tl_forum_threads t INNER JOIN tl_member m ON (t.name = m.id) WHERE published = ? AND pid = ? ORDER BY actdate DESC')
				                                      ->execute(1, $this->kategorie);

				$threads = array();
				if($objThreads->numRows > 0)
				{
					// Datensätze anzeigen
					while($objThreads->next())
					{
						// Neustatus ermitteln
						$newstatus = (in_array($objThreads->id, $arrThreads)) ? $this->newstring : '';
						$class = ($class == 'odd') ? 'even' : 'odd';
						$threads[] = array
						(
							'title'     => $objThreads->title.$newstatus,
							'link'      => \Controller::generateFrontendUrl($objPage->row(), '/thread/'.$objThreads->id),
							'name'      => $objThreads->username,
							'actname'   => $this->member[$objThreads->actname]['username'],
							'actdate'   => date("d.m.Y H:i", $objThreads->actdate),
							'initdate'  => date("d.m.Y H:i", $objThreads->initdate),
							'class'     => $class,
						);
					}
				}
				$this->Template->form = $this->formThread();
			}

			// Template füllen
			$this->Template->category = $this->kategorie;
			$this->Template->categoryname = $objCategory->title;
			$this->Template->categories = $categories;
			$this->Template->threads = $threads;
			$this->Template->username = $this->User->username;


		}

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

	protected function saveNewThread($data)
	{
		//print_r($data);
		$zeit = time();
		$data['text'] = html_entity_decode($data['text']);

		// Threads-Tabelle aktualisieren
		$set = array
		(
			'pid' 		=> $data['category'],
			'name' 		=> $data['member'],
			'tstamp' 	=> $zeit,
			'initdate' 	=> $zeit,
			'actdate' 	=> $zeit,
			'actname'	=> $data['member'],
			'title' 	=> $data['title'],
			'published' => 1,
		);
		$objThread = \Database::getInstance()->prepare('INSERT INTO tl_forum_threads %s')
										     ->set($set)
										     ->execute();
		$insertId = $objThread->insertId;

		// Topics-Tabelle aktualisieren
		$set = array
		(
			'pid' 		=> $insertId,
			'tstamp' 	=> $zeit,
			'topicdate'	=> $zeit,
			'name' 		=> $data['member'],
			'title' 	=> $data['title'],
			'text' 		=> $data['text'],
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
		$objEmail->subject = 'Neues Thema in Swifteliblue\'s Leichtgewichte-Blog';

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
			'actdate' 	=> $zeit,
			'actname'	=> $data['member'],
		);
		$objThread = \Database::getInstance()->prepare('UPDATE tl_forum_threads %s WHERE id = ?')
										     ->set($set)
										     ->execute($data['thread']);

		// Topics-Tabelle aktualisieren
		$set = array
		(
			'pid' 		=> $data['thread'],
			'tstamp' 	=> $zeit,
			'topicdate'	=> $zeit,
			'name' 		=> $data['member'],
			'text' 		=> $data['text'],
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
		$objEmail->subject = 'Neuer Beitrag in Swifteliblue\'s Leichtgewichte-Blog';

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