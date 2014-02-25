<?php
$data->breadcrumbs =array(
	array(
		'text' => 'Главная',
		'active' => TRUE
	),
);

if(isset($_POST['captcha']))
{
	// На всякитй случай проверим данные еще и тут
	if(!preg_match('#^([a-z0-9]+)$#i', $_POST['nickname']))
		exit($data->jsonError('error', 'nickname', 'Неверно введен ник! Только латинские буквы и цифры'));

	if(!preg_match('#(STEAM_|VALVE_)[0-9]:[0-9]:[0-9]{5,12}#', $_POST['steamid']))
		exit($data->jsonError('error', 'steamid', 'Неверно введен SteamID'));

	if(!filter_var($_POST['email'], FILTER_VALIDATE_EMAIL))
		exit($data->jsonError('error', 'email', 'Неверно введена почта'));

	if(!isset($_POST['captcha']) || ($_POST['captcha'] != $_SESSION['captcha_keystring']))
		exit($data->jsonError('error', 'captcha', 'Неверно введен код проверки'));

	$items = array();
	
	foreach($_POST as $key => $val)
	{
		if($key === 'captcha') continue;
		$items[$key] = $val;
	}
	$newId = $data->addAppeal($items);
	if(is_numeric($newId))
	{
		exit($data->jsonError('success', '', 'Заявка создана успешно'
				. '. Проследить за статусом заявки можно '
				. '<a href="http://'.$_SERVER['HTTP_HOST'] . BASEURL 
				.'index.php?action=appeal&id='.intval($newId).'">тут</a>', FALSE));
	}
	exit($data->jsonError('error', '', 'Какаето ошипко', $newId));
}
?>

<div class="panel-heading">
	<h3>Подать заявку на разбан</h3>
</div>
<div class="panel-body">
	<div class="row">
		<div class="col-md-6 col-sm-6 col-xs-6">
			<form role="form" action="" method="post" id="claimForm">
				<div class="form-group">
					<input type="text" class="form-control" id="nickname" name="nickname" placeholder="Ваш ник" />
					<p class="help-block hidden" id="nickname-msg"></p>
				</div>

				<div class="form-group">
					<input type="text" class="form-control" id="steamid" name="steamid" placeholder="Ваш SteamID" />
					<p class="help-block hidden" id="steamid-msg"></p>
				</div>

				<div class="form-group">
					<input type="email" class="form-control" id="email" name="email" placeholder="Ваш E-mail" />
					<p class="help-block hidden" id="email-msg"></p>
				</div>

				<div class="form-group">
					<input type="text" class="form-control" id="adminNick" name="adminNick" placeholder="Ник админа" />
					<p class="help-block hidden" id="adminNick-msg"></p>
				</div>

				<div class="form-group">
					<input type="text" class="form-control" id="reason" name="reason" placeholder="Причина бана" />
					<p class="help-block hidden" id="reason-msg"></p>
				</div>

				<div class="form-group">
					<textarea class="form-control" name="history" id="history" rows="6" placeholder="Ваша история"></textarea>
					<p class="help-block hidden" id="history-msg"></p>
				</div>

				<div class="form-group">
					<label for="captcha">
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
				<div class="form-group text-center">
					<button type="button" id="claimFormSubmit" class="btn btn-info">Отправить</button>
				</div>
			</form>
		</div>
		<div class="col-md-6 col-sm-6 col-xs-6">
			<div class="well">
				<b>Общие правила заявок:</b>
				<ul>
					<li>Запрещено использовать маты</li>
					<li>Запрещено оскорблять админов</li>
				</ul>
				<b>Правила заполнения полей:</b>
				<ul>
					<li>Ваш ник<br />(Вводим ник под которым нас забанили)</li>
					<li>Ваш SteamID:<br />(Узнать свой SteamID можно <a href="http://steamidfinder.com/" target="_blank">здесь</a>)</li>
					<li>Ник админа:<br />(Введите ник админа который Вас забанил)</li>
					<li>Причина бана:<br />(Введите причину бана, например: Спамер)</li>
					<li>Ваша злополучная история:<br />(Здесь, детально описываем за что вас забанили, оправдайте себя)</li>
					<li>Капча:<br /> (Введите код с картинки)</li>
				</ul>
			</div>
		</div>
	</div>
</div>
<script>
$(document).ready(function(){
	$("img.captcha").click(function(){
		$(this).attr("src", "<?php echo BASEURL ?>include/kcaptcha/?<?php echo session_name()?>=<?php echo session_id()?>");
		$("#captcha").val("");
		return false;
	});
});
</script>
<?php unset($_SESSION['captcha_keystring']);