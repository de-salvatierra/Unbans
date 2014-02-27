<?php
/**
 * Основной клас системы
 * 
 * @author Onotole <me@onotole.tk>
 */
class System {
	protected $dbprefix;
	/**
	 * @var object Объект PDO MySQL
	 */
	public $db = FALSE;
	
	/**
	 * @var boolean Проверка запроса на аякс
	 */
	public $ajax		= FALSE;
	
	/**
	 * @var boolean Проверка пользователя на пользователя
	 */
	public $user		= FALSE;
	
	/**
	 * @var string Почта пользователя
	 */
	public $userEmail		= NULL;

	/**
	 * @var boolean Проверка пользователя на админа
	 */
	public $admin		= FALSE;

	/**
	 * @var string Тайтл страницы
	 */
	public $title = NULL;
	
	/**
	 * @var string Крошки
	 */
	public $breadcrumbs = FALSE;
	
	const ERROR_AUTH			= 403;
	const ERROR_NOTFOUND			= 404;
	
	/**
	 * Заявка на рассмотрении
	 */
	const STATUS_CONSIDERED		= 'CONSIDERED';
	
	/**
	 * Заявка отклонена
	 */
	const STATUS_DECLINED		= 'DECLINED';
	
	/**
	 * Заявка одобрена
	 */
	const STATUS_UNBANNED		= 'UNBANNED';
	
	/**
	 * Комментарий добавлен
	 */
	const COMMENT_CREATED		= 'CREATED';
	
	/**
	 * Комментарий одобрен
	 */
	const COMMENT_ACTIVE		= 'ACTIVE';
	
	/**
	 * Комментарий заблокирован
	 */
	const COMMENT_BLOCKED		= 'BLOCKED';
	
	/**
	 * @var array Массив с данными о статусе заявки
	 */
	public $status = array(
		self::STATUS_CONSIDERED => array(
			'status' =>'Рассматривается',
			'class' => 'primary'
		),
		self::STATUS_DECLINED	=> array(
			'status' =>'Отклонено',
			'class' => 'danger'
		),
		self::STATUS_UNBANNED	=> array(
			'status' =>'Разбанен',
			'class' => 'success'
		),
	);
	
	/**
	 * @var array Массив с данными о статусе комментария
	 */
	public $commStatus = array(
		self::COMMENT_CREATED	=> array(
			'status' =>'Создан',
			'class' => 'warning'
		),
		self::COMMENT_ACTIVE	=> array(
			'status' =>'Одобрен',
			'class' => 'success'
		),
		self::COMMENT_BLOCKED	=> array(
			'status' =>'Заблокирован',
			'class' => 'danger'
		),
	);


	public function __construct() {
		if(!isset($_GET['page']) || $_GET['page'] !== 'install')
		{
			if(filesize(ROOTPATH . 'include/config.php') < 2)
				header("Location: index.php?page=install");
			include ROOTPATH . 'include/config.php';
			// PDO
			$dsn = 'mysql:dbname='.$dbname.';host='.$dbhost;
			try {
				$this->db = new PDO($dsn, $dbuser, $dbpassword);
			} catch (PDOException $e) {
				exit($e->getMessage());
			}

			$this->dbprefix = $dbprefix;

			// Кодировка
			$this->db->query("SET NAMES utf8");
		}
		
		// Проверка запроса на AJAX
		$this->ajax = isset($_SERVER['HTTP_X_REQUESTED_WITH'])
			&&
		!empty($_SERVER['HTTP_X_REQUESTED_WITH'])
			&&
		strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
		
		// Проверка пользователя
		if(isset($_SESSION['user']))
		{
			// Подключаем файл с пользователями
			$users = include ROOTPATH . 'include/users.php';
			
			// Если пользователь в файле найден
			if(isset($users[$_SESSION['user']]))
			{
				// Задаем переменные
				$this->user = $_SESSION['user'];
				$this->admin = $users[$_SESSION['user']]['admin'];
				$this->userEmail = $users[$_SESSION['user']]['email'];
			}
		}
	}
	
	/**
	 * Авторизация пользователя
	 * @param string $login
	 * @param string $password
	 * @return boolean Истина в случае успешной авторицации и ложь, в случае ошибки
	 */
	public function login($login, $password)
	{
		
		// Подключение файла с пользователями
		$users = include ROOTPATH . 'include/users.php';
		//exit(print_r($users[$login]));
		// Если пользователь в файле есть, и пароль совпадает с хэшем
		if(isset($users[$login]) && $users[$login]['password'] === md5($password))
		{
			// Записываем в сессию имя пользователя и возвращаем истину
			$_SESSION['user'] = $login;
			header("Location: index.php");
			return TRUE;
		}
		return FALSE;
	}

	/**
	 * Фильтрация текста
	 * @param string $text
	 * @return string
	 */
	public function safe($text)
	{
		return htmlspecialchars($text, ENT_QUOTES);
	}
	
	/**
	 * Новая аппеляция
	 * @param array $data Данные, поступившие из формы от пользователя
	 * @return string|boolean Ошибку или ID новой записи
	 */
	public function addAppeal(Array $data)
	{
		// Подготовка запроса для проверки, вдруг такая заявка уже есть
		$params = array(
			'from' => 'appeal',
			'where' => array(
				array(
					'key' => 'nickname',
					'operator' => '=',
					'needle' => $data['nickname']
				),
				array(
					'key' => 'steamid',
					'operator' => '=',
					'needle' => $data['steamid']
				),
				array(
					'key' => 'reason',
					'operator' => '=',
					'needle' => $data['reason']
				),
			),
			'limit' => 1
		);
		
		// Если похожая заявка найдена, возвращаем об этом ошибку
		if($old = $this->getData($params))
			return 'Заявка с этими данными уже создана. Номер заявки ' . intval($old['id']);
		
		$data['userip'] = ip2long($_SERVER['REMOTE_ADDR']);
		$data['appealDate'] = time();
		$data['status'] = self::STATUS_CONSIDERED;
		
		$preAdd = array(
			'to' => 'appeal',
			'params' => $data
		);

		if ($newId = $this->addRecord($preAdd)) {
			
			// Отправка письма юзеру
			$msg = 'Привет, ' . $this->safe($data['nickname']) . '<br />';
			$msg .= 'Ваша заявка принята, ей был назначен номер '.intval($newId).'<br />';
			$msg .= 'В ближайшее время заявка будет рассмотрена администрацией.<br />';
			$msg .= 'После рассмотрения заявки Вам будет отправлено письмо с результатом.<br />';
			$msg .= 'Спасибо за обращение.';
			$this->sendMail($data['email'], 'Заявка создана', $msg);
			
			// Отправка письма админам
			$admins = include ROOTPATH . 'include/users.php';
			
			$emails = array();
			foreach($admins as $admin)
			{
				if($admin['admin'] === TRUE)
				$emails[] = $admin['email'];
			}
			
			$msg2 = 'Создана новая заявка на разбан<br />';
			$msg2 .= 'Номер новой заявки - '.intval($newId).'<br />';
			$msg2 .= 'Ник игрока - '.$this->safe($data['nickname']).'<br />';
			$msg2 .= 'Для рассмотрения заявки перейдите по <a href="http://'.$_SERVER['HTTP_HOST'] . BASEURL .'index.php?page=appeal&id='.intval($newId).'">этой ссылке</a>.';
			$this->sendMail(implode(', ', $emails), 'Новая заявка', $msg2);
			
			return $newId;
		}

		return FALSE;
	}
	/**
	 * Добавление комментария
	 * @param integer $id ID заявки
	 * @param string $text Текст комментария
	 * @param string $username Имя пользователя
	 * @param string $email Почта пользователя
	 * @return string Возвращает информацию в JSON формате
	 */
	public function addComment($id, $text, $username, $email) {
		
		// Вдруг такой заявки нет
		if(!$this->getrecord('appeal', $id))
			return $this->jsonError ('error', '', 'Заявка с ID' . $id . ' не найдена!', FALSE);
		
		// Проверка почты на валидность
		if(!filter_var($email, FILTER_VALIDATE_EMAIL))
			return $this->jsonError ('error', '', 'Неверно введен E-mail!', FALSE);
		
		// Подготовка запроса
		$params = array(
			'to' => 'comment',
			'params' => array(
				'userIp' => ip2long(filter_input(INPUT_SERVER, 'REMOTE_ADDR')),
				'userName' => $this->safe($username),
				'userEmail' => $email,
				'appealId' => intval($id),
				'text' => $this->safe($text),
				'date' => time(),
				'status' => $this->admin ? self::COMMENT_ACTIVE : self::COMMENT_CREATED
			),
		);
		
		if($this->addRecord($params))
			return $this->jsonError ('success', '', 'Ваш комментарий добавлен и ждет одобрения администратором!', FALSE);
		
		return $this->jsonError ('error', '', 'Произошла ошибка. Обратитесь к администратору!', FALSE);
	}

	/**
	 * Возвращает ошибку для HTTP (по-моему не используется)
	 * @param string $type Тип ошибки для бутстраповского алерта
	 * @param string $msg Сообщение об ошибке
	 * @param integer|boolean $http Код HTTP ошибки
	 * @return type
	 */
	public function error($type, $msg, $http = FALSE)
	{
		if($http)
		{
			switch($http)
			{
				case self::ERROR_AUTH:
					header("HTTP/1.0 403 Not Authorized");
					break;
				case self::ERROR_NOTFOUND:
					header("HTTP/1.0 404 Not Found");
					break;
				default:
					header("HTTP/1.0 200");
					break;
			}
		}
		
		return '<div class="alert alert-'.$type.'">'.$msg.'</div>';
	}
	
	/**
	 * Формирует JSON для аякса
	 * @param string $type Тип ошибки (для Toast)
	 * @param string $param Поле в форме
	 * @param string $msg Сообщение
	 * @param bolean $error Ошибка это или нет
	 * @return type
	 */
	public function jsonError($type, $param, $msg, $error = TRUE) {
		return json_encode(array(
			'type' => $type,
			'text' => $msg,
			'input' => $param,
			'error' => $error ? 1 : 0));
	}
	
	/**
	 * Отправляет письма
	 * @param string $to Кому
	 * @param string $subject Тема
	 * @param string $msg Сообщение
	 * @param boolean|string $from От кого
	 * @return boolean
	 */
	public function sendMail($to, $subject, $msg, $from = FALSE)
	{
		$from = $from ? $from : 'no-reply@' . $_SERVER['HTTP_HOST'];
		
		$headers  = 'MIME-Version: 1.0' . "\r\n";
		$headers .= 'Content-type: text/html; charset=utf-8' . "\r\n";
		$headers .= 'To: ' . $to . "\r\n";
		$headers .= 'From: ' . $from . "\r\n";
		$headers .= 'Cc: ' . $from . "\r\n";
		$headers .= 'Bcc: ' . $from . "\r\n";
		
		$message = '<html>'
				. '<head>'
				. '<meta charset="utf-8" />'
				. '<title>'.$subject.'</title>'
				. '</head>'
				. '<body>'
				. $msg
				. '</body>'
				. '</html>';
		
		return mail($to, $subject, $message, $headers);
	}
	
	/** Методы, работающие с базой **/
	
	/**
	 * Получает из базы по параметрам
	 * @param array $params
	 * @return boolean
	 */
	public function getData(Array $params)
	{
		if(!isset($params['from']))
			return FALSE;
		
		if(isset($params['where']) && is_array($params['where']))
		{
			$where = array();

			foreach($params['where'] as $w)
			{
				if(isset($w['inQuery']) && $w['inQuery'] !== TRUE)
					continue;
				
				if($w['operator'] === 'IN')
					$where[] = "`".$this->safe($w['key'])."` ".$w['operator']." (".$this->safe($w['needle']).")";
				else
					$where[] = "`".$this->safe($w['key'])."` ".$w['operator']." '".$this->safe($w['needle'])."'";
			}
			$where = " WHERE ".implode(" AND ", $where);
		}
		else
			$where = '';
		
		if(isset($params['order']) && is_array($params['order']))
		{
			$order = array();
			foreach($params['order'] as $column => $sort)
			{
				$order[] = "`{$column}` {$sort}";
			}
			$order = " ORDER BY ".implode(", ", $order);
		}
		else
			$order = "";
		
		if(isset($params['limit']))
		{
			if(is_array($params['limit']))
				$limit = " LIMIT {$params['limit']['start']},{$params['limit']['count']}";
			elseif(is_numeric($params['limit']))
				$limit = " LIMIT " . $params['limit'];
		}
		else
			$limit = "";
		
		$res = $this->db->query("SELECT * FROM `".$this->dbprefix."_".$this->safe($params['from'])."`{$where}{$order}{$limit}");
		
		if($res && $return = $res->fetchAll())
		{
			if(isset($params['limit']) && $params['limit'] == 1)
				return $return[0];
			return $return;
		}
		return array();
	}
	
	/**
	 * Получает определенную запись по ID
	 * @param string $from Имя таблицы
	 * @param integer $id ID записи
	 * @return boolean
	 */
	public function getRecord($from, $id)
	{
		$res = $this->db->query("SELECT * FROM `".$this->dbprefix."_".$this->safe($from)."` WHERE `id` = '".intval($id)."'");
		
		if($res && $data = $res->fetch())
			return $data;
		
		return FALSE;
	}
	
	/**
	 * Записывает в базу по параметрам
	 * @param array $data
	 * @return boolean
	 */
	public function addRecord(Array $data)
	{
		if(!isset($data['to']))
			return FALSE;
		$pre = $this->db->query("SHOW COLUMNS FROM `{$this->dbprefix}_{$data['to']}`");
		
		if(!$pre || !$columns = $pre->fetchAll())
			return FALSE;
		
		$cols = array();
		foreach ($columns as $column)
		{
			$cols[] = $column['Field'];
		}
		
		if(is_array($data['params']))
		{
			$to = array();
			$values = array();
			foreach($data['params'] as $key => $val)
			{
				if(!in_array($key, $cols))
					return FALSE;

				$to[] = "`{$key}`";
				$values[] = "'{$val}'";
			}
			$insert = "INSERT INTO `{$this->dbprefix}_{$data['to']}`(".implode(", ", $to).") VALUES (".implode(", ", $values).")";
			
			if($this->db->query($insert))
				return $this->db->lastInsertId();
			
			return FALSE;
		}
	}
	
	/**
	 * Обновляет запись
	 * @param array $data
	 * @return boolean
	 */
	public function updateRecord(Array $data)
	{
		$set = array();
		foreach($data['params'] as $key => $val)
		{
			$set[] = "`{$key}` = '{$val}'";
		}
		
		if(isset($data['where']) && is_array($data['where']))
		{
			$where = array();

			foreach($data['where'] as $w)
			{
				if($w['operator'] === 'IN')
					$where[] = "`".$this->safe($w['key'])."` ".$w['operator']." (".$this->safe($w['needle']).")";
				else
					$where[] = "`".$this->safe($w['key'])."` ".$w['operator']." '".$this->safe($w['needle'])."'";
			}
			$where = " WHERE ".implode(" AND ", $where);
		}
		else
			$where = '';
		//exit("UPDATE `{$data['table']}` SET ". implode(", ", $set)."{$where}");
		if($this->db->query("UPDATE `{$this->dbprefix}_{$data['table']}` SET ". implode(", ", $set)."{$where}"))
			return TRUE;
		return FALSE;
	}
	
	/**
	 * Удаляет запись
	 * @param string $from Имя таблицы
	 * @param integer $id ID записи
	 * @return type
	 */
	public function deleteRecord($from, $id)
	{
		$pre = $this->db->prepare("DELETE FROM `{$this->dbprefix}_{$from}` WHERE `id` = ?");
		return $pre->execute(array(intval($id)));
	}
	
	
	/**
	 * Получает кол-во записей в базе по параметрам
	 * @param string $table Имя таблицы
	 * @param array $params Параметры запроса
	 * @return integer Количество записей
	 */
	public function count($table, $params = FALSE) {
		
		if($params && isset($params['where']) && is_array($params['where']))
		{
			$where = array();

			foreach($params['where'] as $w)
			{
				if($w['operator'] === 'IN')
					$where[] = "`".$this->safe($w['key'])."` ".$w['operator']." (".$this->safe($w['needle']).")";
				else
					$where[] = "`".$this->safe($w['key'])."` ".$w['operator']." '".$this->safe($w['needle'])."'";
			}
			$where = " WHERE ".implode(" AND ", $where);
		}
		else
			$where = '';
		//return "SELECT COUNT(`id`) FROM `{$table}`{$where}";
		$ret = $this->db->query("SELECT COUNT(`id`) FROM `{$this->dbprefix}_{$table}`{$where}")->fetch();
		return intval($ret[0]);
	}
}
