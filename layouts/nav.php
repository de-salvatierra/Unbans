<?php $page = filter_input(INPUT_GET, 'page')?>
<header class="navbar" role="navigation">
	<div class="navbar-header">
		<a class="navbar-brand" href="http://example.com">Какойто сайт</a>
	</div>
	<div class="collapse navbar-collapse" id="bs-example-navbar-collapse-1">
		<ul class="nav navbar-nav">
			<li<?php if(!$page) echo ' class="active"';?>>
				<a href="<?php echo BASEURL?>index.php">Главная</a>
			</li>
			<li<?php if($page && $page == 'appeal') echo ' class="active"';?>>
				<a href="<?php echo BASEURL?>index.php?page=appeal">Заявки</a>
			</li>
		</ul>

		<ul class="nav navbar-nav navbar-right">
			<li class="dropdown">
				<a href="#" class="dropdown-toggle" data-toggle="dropdown">
					<?php echo $data->user ? $data->user : "Войти"?> <b class="caret"></b>
				</a>
				<ul class="dropdown-menu">
					<?php if($data->user):?>
					<li><a href="index.php?page=login&logout=1">Выйти</a></li>
					<?php else:?>
					<li>
						<form class="navbar-form" method="post" action="index.php?page=login">
							<div class="form-group">
								<input type="text" class="form-control" name="login" placeholder="Логин">
							</div>
							<div class="form-group">
								<input type="password" class="form-control" name="password" placeholder="Пароль">
							</div>
							<button type="submit" class="btn btn-default">Войти</button>
						</form>
					</li>
					<?php endif;?>
				</ul>
			</li>
		</ul>
	</div>
</header>