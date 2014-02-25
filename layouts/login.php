<?php
$data->title = 'Форма входа';

$data->breadcrumbs =array(
	array(
		'text' => 'Главная',
		'url' => 'index.php'
	),
	array(
		'text' => 'Форма входа',
		'active' => TRUE
	),
);
?>

<div class="panel-heading">
	Форма фхода
</div>
<div class="panel-body">
	<?php

	if(isset($_GET['logout']) && $data->user)
	{
		unset($_SESSION['user']);
		header("Location: index.php");
	}

	if($data->user)
		header("Location: index.php");

	if($login = filter_input(INPUT_POST, 'login'))
	{
		$pass = filter_input(INPUT_POST, 'password');
		if(!$data->login($login, $pass))
			echo '<div class="alert alert-danger">Неверные имя пользователя или пароль</div>';
	}


	$data->title = 'Форма входа';?>
	<form method="post" action="index.php?page=login" class="form-horizontal" role="form">
		<div class="form-group">
			<label for="login" class="col-sm-3 control-label">Логин</label>
			<div class="col-sm-9">
				<input type="text" class="form-control" id="login" name="login" placeholder="Логин">
			</div>
		</div>
		<div class="form-group">
			<label for="password" class="col-sm-3 control-label">Пароль</label>
			<div class="col-sm-9">
				<input type="password" class="form-control" id="password" name="password" placeholder="Пароль">
			</div>
		</div>
		<div class="form-group">
			<div class="col-sm-offset-3 col-sm-9">
				<button type="submit" class="btn btn-default">Войти</button>
			</div>
		</div>
	</form>
</div>