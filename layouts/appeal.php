<?php

// Определенная заявка
if($id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT)):
	
// Тайтл страницы
$data->title = 'Заявка №' . $id;

$data->breadcrumbs =array(
	array(
		'text' => 'Главная',
		'url' => 'index.php'
	),
	array(
		'text' => 'Заявки',
		'url' => 'index.php?page=appeal'
	),
	array(
		'text' => 'Заявка № ' . $id,
		'active' => TRUE
	),
);

// Получаем заявку
$item = $data->getRecord('appeal', $id);

//Если заявка не найдена, отдаем 404 браузеру и алерт пользователю
if(!$item):?>
<div class="panel-heading text-center">
	<h1>Заявка №<?php echo $id;?></h1>
</div>
<div class="panel-body">
<?php 
echo $data->error('danger', 'Заявка не найдена', System::ERROR_NOTFOUND);

// Если же такая заявка есть, работаем с ней.
else:
	
// Редактирование/удаление комментария
if($commentId = filter_input(INPUT_POST, 'commentid'))
{
	// Проверка на админа
	if(!$data->admin)
	{
		header("HTTP/1.0 403 Not Authorized");
		exit("alert('Недостаточно прав.');");
	}
	
	// Если комментарий не найден, отдаем 404
	if(!$comment = $data->getRecord('comment', $commentId))
	{
		header("HTTP/1.0 404 Not Found");
		exit("alert('Комментарий не найден.');");
	}
	
	// Получаем действие над комментарием (Изменение статуса или удаление)
	$act = filter_input(INPUT_POST, 'commentStatus');
	
	// Если действие - удалить, то удаляем
	if($act === 'DELETE' && $data->deleteRecord('comment', $commentId))
		exit("alert('Комментарий успешно удален.');$('#comment{$commentId}').remove();");
	
	// Подготовка параметров запроса
	$params = array(
		'table' => 'comment',
		'params' => array(
			'status' => $act
		),
		'where' => array(
			array(
				'key' => 'id',
				'operator' => '=',
				'needle' => $commentId
			)
		)
	);
	
	// Запрос на обновление
	if($data->updateRecord($params))
	{
		// Если запрос прошел удачно, формируем вывод для аякса
		$js = "<button data-toggle=\"dropdown\" class=\"btn btn-".$data->commStatus[$act]['class']."\">";
		$js .= $data->commStatus[$act]['status'];
		$js .= "</button>";
		$js .= "<ul class=\"dropdown-menu\" role=\"menu\">";
		foreach($data->commStatus as $name => $status)
		{
			if($name === $act) continue;
			$js .= "<li><a href=\"#\" id=\"{$name}\" class=\"changeStatus\">{$status['status']}</a></li>";
		}
		$js .= "<li><a href=\"#\" id=\"DELETE\" class=\"changeStatus\">Удалить</a></li></ul>";
		
		// Останавливаем скрипт с выводом нового выпадающего меню
		exit("$('#commAct{$commentId}').html('{$js}').removeClass('open');");
	}
	
	// Если запись не обновилась, выводим такую вот ошибку
	exit("alert('Произошла неведомая ошибка');");
}

// Редактирование/удаление заявки, добавление комментария
if($action = filter_input(INPUT_POST, 'action'))
{
	// В $action действие с заявкой (одобрение, неодобрение, удаление...)
	
	// Добавление комментария
	if($action === 'addComment')
	{
		// Получаем данные из запроса
		$username = filter_input(INPUT_POST, 'username');
		$email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
		$text = filter_input(INPUT_POST, 'comment');
		
		// Проверяем капчу
		if(!isset($_POST['captcha']) || ($_POST['captcha'] != $_SESSION['captcha_keystring']))
		{
			// Если капча неверна, возвращаем ошибку
			exit($data->jsonError('error', 'captcha', 'Неверно введен код проверки'));
		}
		// Добавляем комментарий
		exit($data->addComment($id, $text, $username, $email));
	}
	
	// Проверка на админа
	if(!$data->admin)
	{
		header("HTTP/1.0 403 Not Authorized");
		exit("alert('Недостаточно прав.');");
	}
	
	// Удаление заявки
	if($action == 'DELETE' && $data->deleteRecord('appeal', $id))
	{
		// Если удаление успешно, отправим пользователю мыло
		$data->sendMail(
			$item['email'], 
			'Заявка удалена',
			'Здравствуйте, ' . $item['userName'] . 
				'<br />' .
				'Администратор удалил заявку на разбан со словами: "'.$data->safe($_POST['statusComment']).'"'
		);
		
		// Останавливаем скрипт с выводом JS
		exit("alert('Заявка удалена.');location.href='index.php';");
	}
	
	/** Изменение статуса заявки **/
	
	// Подготовка запроса
	$params = array(
		'table' => 'appeal',
		'params' => array(
			'status' => $action,
			'statusComment' => $data->safe($_POST['statusComment'])
		),
		'where' => array(
			array(
				'key' => 'id',
				'operator' => '=',
				'needle' => $id
			)
		)
	);
	
	// Запрос на изменение данных в таблице
	if($data->updateRecord($params))
	{
		// Если обновление в базе успешно
			// Отправляем мыло пользователю
		$data->sendMail(
			$item['email'], 
			'Заявка рассмотрена',
			'Здравствуйте, ' . $item['nickname'] . 
				'<br />' .
				'Администратор рассмотрел Вашу заявку на разбан<br />' .
				'И установил ей статус <b>'.$data->status[$action]['status'].'</b>.<br />' .
				'С комментарием: "'.$data->safe($_POST['statusComment']).'"'.
				'Посмотреть детали Вы можете по '
				. '<a href="http://'.$_SERVER['HTTP_HOST'] . BASEURL .'index.php?page=appeal&id='.$id.'">этой ссылке</a>'
		);
		
			// Формируем вывод для аякса
		$js = "<button data-toggle=\"dropdown\" class=\"btn btn-".$data->status[$action]['class']."\">";
		$js .= $data->status[$action]['status'];
		$js .= "</button>";
		$js .= "<ul class=\"dropdown-menu\" role=\"menu\">";
		foreach($data->status as $name => $status)
		{
			if($name === $action) continue;
			$js .= "<li><a href=\"#\" id=\"{$name}\" class=\"changeStatus\">{$status['status']}</a></li>";
		}
		$js .= "<li><a href=\"#\" id=\"DELETE\" class=\"changeStatus\">Удалить</a></li></ul>";
		
		// Останавливаем скрипт с выводом JS
		exit("$('#cDrop').html('{$js}').removeClass('open');");
	}
	
	// Если запись не обновилась, выводим вот такой алерт
	exit("alert('Произошла неведомая ошибка');");
}
?>
<div class="panel-heading text-center">
	<h1>Заявка №<?php echo $id;?></h1>
</div>
<div class="panel-body">
	<div class="row">
		<div class="col-lg-6 col-md-6 col-sm-6">
			<div class="panel panel-warning">
				<div class="panel-heading text-center">
					<h3>Детали</h3>
				</div>
				<div class="panel-body">
					<table class="table">
						<tr>
							<td>
								<span class="glyphicon glyphicon-hand-right"></span>
								<b>Ник:</b>
							</td>
							<td>
								<?php echo $data->safe($item['nickname'])?>
							</td>
						</tr>
						
						<?php 
						// Если админ, выводим стим
						if($data->admin):
						?>
						<tr>
							<td>
								<span class="glyphicon glyphicon-filter"></span>
								<b>SteamID:</b>
							</td>
							<td>
								<?php echo $item['steamid']?>
							</td>
						</tr>
						
						<tr>
							<td>
								<span class="glyphicon glyphicon-globe"></span>
								<b>IP:</b>
							</td>
							<td>
								<?php echo long2ip($item['userip'])?>
							</td>
						</tr>
						<?php endif;?>
						
						<tr>
							<td>
								<span class="glyphicon glyphicon-user"></span>
								<b>Админ:</b>
							</td>
							<td>
								<?php echo $data->safe($item['adminNick'])?>
							</td>
						</tr>
						<tr>
							<td>
								<span class="glyphicon glyphicon-fire"></span>
								<b>Причина:</b>
							</td>
							<td>
								<?php echo $data->safe($item['reason'])?>
							</td>
						</tr>
						<tr>
							<td>
								<span class="glyphicon glyphicon-calendar"></span>
								<b>Дата:</b>
							</td>
							<td>
								<?php echo date('d.m.Y H:i:s', $item['appealDate'])?>
							</td>
						</tr>
						<tr>
							<td style="vertical-align: middle">
								<span class="glyphicon glyphicon-exclamation-sign"></span>
								<b>Статус:</b>
							</td>
							<td>
								
								<?php
								// Если админ, выводим дропдаун для смены статуса заявки
								if($data->admin):
								?>
								<div class="dropdown" id="cDrop">
									<button 
										data-toggle="dropdown" 
										class="btn btn-<?php echo $data->status[$item['status']]['class']?>">
										<?php echo $data->status[$item['status']]['status']?>
									</button>
									<ul class="dropdown-menu" role="menu">
										<?php foreach($data->status as $name => $status):?>
										<?php if($name === $item['status']) continue;?>
										<li>
											<a 
												href="#" 
												id="<?php echo $name?>" 
												class="changeStatus">
													<?php echo $status['status']?>
											</a>
										</li>
										<?php endforeach;?>
										<li>
											<a 
												href="#" 
												id="DELETE" 
												class="changeStatus">
													Удалить
											</a>
										</li>
									</ul>
								</div>
								<?php 
								// А если не админ, а простой обыватель, то выводим ему текущий статус
								else:
								?>
								<div class="label label-<?php echo $data->status[$item['status']]['class']?>">
									<?php echo $data->status[$item['status']]['status']?>
								</div>
								<?php endif;?>
								
							</td>
						</tr>
						
						<?php 
						// Если есть комментарий статуса, то выводим его
						if($item['statusComment']):
						?>
						<tr>
							<td>
								<span class="glyphicon glyphicon-pencil"></span>
								<b>Комментарий админа:</b>
							</td>
							<td>
								<?php echo $data->safe($item['statusComment'])?>
							</td>
						</tr>
						<?php endif;?>
						
						<tr>
							<td colspan="2">
								<span class="glyphicon glyphicon-list-alt"></span>
								<b>Объяснительная:</b>
								<div class="well"><?php echo $data->safe($item['history'])?></div>
							</td>
						</tr>
					</table>
				</div>
			</div>
		</div>
		<div class="col-lg-6 col-md-6 col-sm-6">
			<div class="panel panel-warning">
				<div class="panel-heading text-center">
					<h3>Комментарии</h3>
				</div>
				<div class="panel-body">
					<?php
					// Подготовка запроса в базу
					$params = array(
						'from' => 'comment', 
						'where' => array(
							array(
								'key' => 'appealId',
								'operator' => '=',
								'needle' => intval($id)
							),
							array(
								'key' => 'status',
								'operator' => '<>',
								'needle' => System::COMMENT_CREATED,
								'inQuery' => !$data->admin
							),
						),
						'order' => array(
							'id' => 'asc'
						)
					);
					?>
					<?php 
					// Получаем комментарии по запросу и выводим их в цикле
					foreach($data->getData($params) as $comment):?>
					<div class="panel panel-<?php 
					// Если коммент заблочен, то класс danger иначе класс default
					echo ($comment['status'] === System::COMMENT_BLOCKED 
							? 
						"danger" 
							: 
						"default")
					?>" id="comment<?php echo intval($comment['id'])?>">
						<div class="panel-heading">
							<span class="badge">
								<?php echo date('d.m.Y H:i', $comment['date']);?>
							</span>
							<b><?php echo htmlspecialchars_decode($comment['userName']);?></b>
							<?php
							// Если админ, то выводим мыло и IP комментатора
							if($data->admin):?>
							<div class="pull-right">
								<?php echo $comment['userEmail'];?> 
								<?php echo long2ip ($comment['userIp'])?>
							</div>
							<?php endif?>
							
						</div>
						<div class="panel-body">
							<?php 
							// Если комментарий заблокирован, то пользователю выдаем не коммент, а ошибку. 
							// А админу коммент так и выдается.
							echo $comment['status'] === System::COMMENT_BLOCKED && !$data->admin ? "<b>Комментарий заблокирован администратором</b>" : nl2br(htmlspecialchars_decode($comment['text']));?>
						</div>
						
						<?php 
						// Админу выводим дропдаун для смены статуса/удаления комментария, 
						// а юзерам ничего не выводим тут =)
						if($data->admin):?>
						<div class="dropdown" id="commAct<?php echo $comment['id']?>">
							<button 
								data-toggle="dropdown" 
								class="btn btn-<?php echo $data->commStatus[$comment['status']]['class']?>">
								<?php echo $data->commStatus[$comment['status']]['status']?>
							</button>
							<ul class="dropdown-menu" role="menu">
								<?php foreach($data->commStatus as $name => $status):?>
								<?php if($name === $comment['status']) continue;?>
								<li>
									<a 
										href="#"  
										onclick="commentStatus(<?php echo $comment['id']?>, '<?php echo $name?>');">
											<?php echo $status['status']?>
									</a>
								</li>
								<?php endforeach;?>
								<li>
									<a 
										href="#" 
										onclick="commentStatus(<?php echo $comment['id']?>, 'DELETE');">
											Удалить
									</a>
								</li>
							</ul>
						</div>
						<?php endif;?>
						
					</div>
					<?php endforeach;?>
				</div>
				<div class="panel-footer text-center">
					<button 
						type="button" 
						class="btn btn-primary btn-sm" 
						onclick="$('#addCommentForm').slideToggle();">Добавить комментарий</button>
					<div id="addCommentForm" style="display: none">
						<br />
						<div class="form-group">
							<input type="text" class="form-control" id="username" maxlength="20" placeholder="Ваше имя">
							<p class="help-block hidden" id="username-msg"></p>
						</div>
						<div class="form-group">
							<input type="email" class="form-control" id="email" placeholder="Ваш E-mail">
							<p class="help-block hidden" id="email-msg"></p>
						</div>
						<div class="form-group">
							<textarea class="form-control" id="comment" rows="5" placeholder="Ваш комментарий"></textarea>
							<p class="help-block hidden" id="comment-msg"></p>
						</div>
						<div class="form-group">
							<label>
								Проверочный код: 
								<img 
									class="captcha" 
									style="cursor: pointer"
									title="Сменить картинку"
									src="<?php echo BASEURL ?>include/kcaptcha/?<?php echo session_name()?>=<?php echo session_id()?>" />
							</label>
							<input type="text" class="form-control" id="captcha" name="captcha" />
							<p class="help-block hidden" id="captcha-msg"></p>
						</div>
						<button type="button" id="addComment" class="btn btn-default">Отправить</button>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>

<script>
	<?php 
	// JS функции так же будем разделять. Нефиг пользователям ковырять исходник
	if($data->admin):?>
	function commentStatus(id, status)
	{
		if(!confirm('Подтвердите действие'))
			return false;
		$.post("", {"commentid": id, "commentStatus": status}, function(data){eval(data);});
		return false;
	}
	<?php endif?>
	$(document).ready(function(){
		<?php if($data->admin):?>
		
		$(".changeStatus").live("click" ,function(){
			if(!confirm('Подтвердите действие'))
				return false;
			if(this.id !== 'DELETE')
			{
				var comment = prompt("Введите комментарий");
			}
			$.post("",{"action": this.id, "statusComment": comment},function(data){eval(data);});
			return false;
		});
		<?php endif;?>
		$("img.captcha").click(function(){
			$(this).attr("src", "<?php echo BASEURL ?>include/kcaptcha/?<?php echo session_name()?>=<?php echo session_id()?>");
			$("#captcha").val("");
			return false;
		});
		$("#addComment").click(function(){
			$("div.form-group").removeClass("has-error");
			$("p.help-block").html("").hide();
			var err = 0;
			if(!$("#username").val())
			{
				err++;
				addError('username', 'Введите Ваше имя');
			}
			if(!(/^([a-z0-9_-]+.)*[a-z0-9_-]+@([a-z0-9][a-z0-9-]*[a-z0-9].)+[a-z]{2,4}$/i).test($("#email").val()))
			{
				err++;
				addError('email', 'Неверно введена почта');
			}
			if($("#comment").val().length < 10)
			{
				err++;
				addError('comment', 'Вы собираетесь оставить комментариф, но написали так мало букв, что смысл самого комментария настолько низок, что лучше его вообще не оставлять =)');
			}
			if(!$("#captcha").val())
			{
				err++;
				addError('captcha', 'Введите код проверки');
			}
			
			if(err > 0)
			{
				return $().toastmessage('showToast', {
					text		: 'Исправьте ошибки',
					stayTime	: 3000,
					sticky		: false,
					position	: 'top-center',
					type		: 'error'
				});
			}

			$.post(
				"",
				{
					"username": $("#username").val(),
					"email": $("#email").val(),
					"comment": $("#comment").val(),
					"captcha": $("#captcha").val(),
					"action": "addComment"
				},
				function(data)
				{
					data = $.parseJSON(data);
					if(data.error)
						addError(data.input, data.text);
					else {
						$(".form-control").val("");
						$('#addCommentForm').hide("slow");
					}
					return $().toastmessage('showToast', {
						text		: data.text,
						stayTime	: 3000,
						sticky		: false,
						position	: 'top-center',
						type		: data.type
					});
				}
			);
			return false;
		});
	});
</script>
<?php endif;?>

<?php 
/** Все заявки **/
else:
// Тайтл страницы
$data->title = 'Список заявок';

$data->breadcrumbs =array(
	array(
		'text' => 'Главная',
		'url' => 'index.php'
	),
	array(
		'text' => 'Заявки',
		'active' => TRUE
	),
);
?>
<div class="panel-heading">
	<h3>Список всех заявок</h3>
</div>
<div class="panel-body">
	<?php 
	// Подключаем/формируем пагинацию
	include_once ROOTPATH . '/include/paginator/Manager.php';
	include_once ROOTPATH . '/include/paginator/Helper.php';
	$pagination = new Krugozor_Pagination_Manager(30, 30, $_GET);
	
	// Получаем заявки
	$appeals = $data->getData(array(
		'from' => 'appeal',
		'order' => array(
			'id' => 'desc'
		),
		'limit' => array(
			'start' => $pagination->getStartLimit(),
			'count' => $pagination->getStopLimit()
		)
	));
	
	// Настройка пагинатора
	$pagination->setCount($data->count('appeal'));
	$paginationHelper = new Krugozor_Pagination_Helper($pagination);
	$paginationHelper->setPaginationType(Krugozor_Pagination_Helper::PAGINATION_NORMAL_TYPE)
			->setCssNormalLinkClass("normal_link")
			->setCssActiveLinkClass("active_link")
			->setRequestUriParameter("action", "appeal");
	?>
	<div>
		Всего записей: <b><?php echo $paginationHelper->getPagination()->getCount()?></b>
	</div>
	<table class="table table-bordered table-condensed table-hover table-striped">
		<thead>
			<tr>
				<th class="text-center">
					<span class="glyphicon glyphicon-hand-right"></span>
					Ник
				</th>
				<th class="text-center">
					<span class="glyphicon glyphicon-filter"></span>
					SteamID
				</th>
				<th class="text-center">
					<span class="glyphicon glyphicon-user"></span>
					Админ
				</th>
				<th class="text-center">
					<span class="glyphicon glyphicon-fire"></span>
					Причина
				</th>
				<th class="text-center">
					<span class="glyphicon glyphicon-calendar"></span>
					Дата
				</th>
				<th class="text-center">
					<span class="glyphicon glyphicon-exclamation-sign"></span>
					Статус
				</th>
			</tr>
		</thead>
		<tbody>
			<?php foreach($appeals as $app):?>
			<tr id="appeal<?php echo intval($app['id'])?>" class="appeal" style="cursor: pointer">
				<td><?php echo $data->safe($app['nickname']);?></td>
				<td><?php echo $app['steamid']?></td>
				<td><?php echo $data->safe($app['adminNick']);?></td>
				<td><?php echo $data->safe($app['reason']);?></td>
				<td><?php echo date('d.m.Y H:i', $app['appealDate']);?></td>
				<td class="text-center">
					<div class="label label-<?php echo $data->status[$app['status']]['class']?>">
						<?php echo $data->status[$app['status']]['status']?>
					</div>
				</td>
			</tr>
			<?php endforeach;?>
		</tbody>
	</table>
	<?php if ($paginationHelper->getPagination()->getCount()): ?>
		<?php echo $paginationHelper->getHtml()?>
	<?php endif; ?>
</div>
<script>
	$(document).ready(function(){
		$(".appeal").click(function(){
			var id = this.id.substr(6);
			document.location.href="index.php?page=appeal&id=" + id;
		});
	});
</script>
<?php endif; 
unset($_SESSION['captcha_keystring']);?>