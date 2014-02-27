<?php
if(!defined('UNBANS'))
{
	header("HTTP/1.0 400 Bad Request");
	exit('Ololo');
}

if(filesize(ROOTPATH . 'include/config.php') > 2)
	header("Location: index.php");

$confFile = ROOTPATH . 'include/config.php';

if($dbhost		= filter_input(INPUT_POST, 'dbhost'))
{
	$dbuser		= filter_input(INPUT_POST, 'dbuser');
	$dbpassword = filter_input(INPUT_POST, 'dbpassword');
	$dbname		= filter_input(INPUT_POST, 'dbname');
	$dbprefix	= filter_input(INPUT_POST, 'dbprefix');
	
	if
	(
		$dbhost != 'localhost'
			&&
		!filter_var($dbhost, FILTER_VALIDATE_IP, array('flags' => FILTER_FLAG_IPV4))
			&&
		!preg_match('#^[^-\._][a-z\d_\.-]+\.[a-z]{2,6}$#i', $dbhost)
	)
		exit("addError('dbhost', 'Неверный формат адреса');");
	
	$dsn = 'mysql:dbname='.$dbname.';host='.$dbhost;
	try {
		$db = new PDO($dsn, $dbuser, $dbpassword);
	} catch (PDOException $e) {
		exit("alert('Не удалось соединиться с сервером (".addslashes($e->getMessage()).")');");
	}
	
	
	
	if(!is_writable($confFile))
		exit("alert('Файл include/config не перезаписываемый. Выставьте ему права 777');");
	
	$config  = "<?php" . PHP_EOL;
	$config .= "\$dbhost		= '{$dbhost}';" . PHP_EOL;
	$config .= "\$dbuser		= '{$dbuser}';" . PHP_EOL;
	$config .= "\$dbpassword	= '{$dbpassword}';" . PHP_EOL;
	$config .= "\$dbname		= '{$dbname}';" . PHP_EOL;
	$config .= "\$dbprefix		= '{$dbprefix}';" . PHP_EOL;
	
	$database = "CREATE TABLE IF NOT EXISTS `{$dbprefix}_appeal` (
		`id` int(11) unsigned NOT NULL AUTO_INCREMENT,
		`nickname` varchar(25) NOT NULL,
		`steamid` varchar(21) NOT NULL,
		`email` varchar(100) NOT NULL,
		`adminNick` varchar(25) NOT NULL,
		`reason` varchar(50) NOT NULL,
		`history` text NOT NULL,
		`userip` int(11) unsigned NOT NULL,
		`appealDate` int(11) unsigned NOT NULL,
		`status` enum('UNBANNED','DECLINED','CONSIDERED') NOT NULL DEFAULT 'CONSIDERED',
		`statusComment` varchar(100) NOT NULL,
		PRIMARY KEY (`id`)
	  ) ENGINE=InnoDB  DEFAULT CHARSET=utf8 ;";
	
	$database .= "CREATE TABLE IF NOT EXISTS `{$dbprefix}_comment` (
		`id` int(11) unsigned NOT NULL AUTO_INCREMENT,
		`userName` varchar(25) NOT NULL,
		`userIp` int(10) unsigned NOT NULL,
		`userEmail` varchar(150) NOT NULL,
		`appealId` int(11) unsigned NOT NULL,
		`text` text NOT NULL,
		`date` int(11) unsigned NOT NULL,
		`status` enum('CREATED','ACTIVE','BLOCKED') NOT NULL,
		PRIMARY KEY (`id`),
		KEY `appealId` (`appealId`),
		KEY `appealId_2` (`appealId`)
	  ) ENGINE=InnoDB  DEFAULT CHARSET=utf8 ;";
	
	if($db->query($database) && file_put_contents($confFile, $config))
		exit("alert('Успешно');document.location.href='index.php';");
	exit("alert('Произошло какаето ошибко');");
}

$data->breadcrumbs =array(
	array(
		'text' => 'Главная',
		'url' => 'index.php'
	),
	array(
		'text' => 'Установка системы',
		'active' => TRUE
	),
);

$data->title = 'Установка системы';

?>

<div class="panel-heading">
	<h3>Установка системы</h3>
</div>
<div class="panel-body">
	<div class="row">
		<div class="col-md-7 col-sm-7 col-xs-7">
			<form role="form" action="" method="post" id="installForm">
				<div class="form-group">
					<input type="text" class="form-control" id="dbhost" name="dbhost" placeholder="Сервер MySQL" />
					<p class="help-block hidden" id="dbhost-msg"></p>
				</div>
				<div class="form-group">
					<input type="text" class="form-control" id="dbuser" name="dbuser" placeholder="Пользователь MySQL" />
					<p class="help-block hidden" id="dbuser-msg"></p>
				</div>
				<div class="form-group">
					<input type="password" class="form-control" id="dbpassword" name="dbpassword" placeholder="Пароль пользователя MySQL" />
					<p class="help-block hidden" id="dbpassword-msg"></p>
				</div>
				<div class="form-group">
					<input type="text" class="form-control" id="dbname" name="dbname" placeholder="Имя базы MySQL" />
					<p class="help-block hidden" id="dbname-msg"></p>
				</div>
				<div class="form-group">
					<input type="text" class="form-control" id="dbprefix" name="dbprefix" placeholder="Префикс таблиц MySQL" />
					<p class="help-block hidden" id="dbprefix-msg"></p>
				</div>

				<div class="form-group text-center">
					<button type="submit" name="installFormSubmit" value="1" class="btn btn-info">Отправить</button>
				</div>
			</form>
		</div>
		<div class="col-md-5 col-sm-5 col-xs-5">
			<div class="well">
				<b>Системные требования:</b>
				<ul>
					<li>Версия PHP: <?php echo version_compare(substr(PHP_VERSION, 0, 3), '5.3', '<')
						? '<span class="text-danger"><b>'.substr(PHP_VERSION, 0, 3).'</b> (рекомендуемая 5.3 или выше)</span>'
						: '<span class="text-success"><b>'.substr(PHP_VERSION, 0, 3).'</b></span>'?></li>
					<li>Файл /include/config.php: <?php echo is_writable($confFile) ? '<span class="text-success"><b>перезаписываемый</b></span>' : '<b><span class="text-danger">НЕ перезаписываемый</b></span>' ?></li>
				</ul>
			</div>
		</div>
	</div>
</div>