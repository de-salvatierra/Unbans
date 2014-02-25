<!DOCTYPE html>
<html lang="ru">
	<head>
		<meta charset="utf-8" />
		<title>Заявка на разбан<?php if(isset($data->title)) echo ' :: '.$data->safe($data->title);?></title>
		<script src="<?php  echo BASEURL?>js/jquery-1.8.1.js"></script>
		<script src="<?php  echo BASEURL?>js/bootstrap/bootstrap.js"></script>
		<script src="<?php  echo BASEURL?>js/jquery.toastmessage.js"></script>
		<script src="<?php  echo BASEURL?>js/core.js"></script>
		<link	href="<?php echo BASEURL?>css/bootstrap.css" rel="stylesheet">
		<link	href="<?php echo BASEURL?>css/core.css" rel="stylesheet">
		<link	href="<?php echo BASEURL?>css/jquery.toastmessage.css" rel="stylesheet" />
	</head>
	<body>
		<div class="container">
			<div id="content">
				<?php require 'nav.php';?>
				<?php if($data->breadcrumbs):?>
					<div class="page-title">
						<ul class="breadcrumb">
						<?php foreach($data->breadcrumbs as $bc):?>
							<?php if(isset($bc['active']) && $bc['active'] === TRUE):?>
							<li class="active"><?php echo $bc['text']?></li>
							<?php else:?>
							<li><a href="<?php echo $bc['url']?>"><?php echo $bc['text']?></a></li>
							<?php endif;?>
						<?php endforeach;?>
						</ul>
					</div>
				<?php endif;?>
				<div class="panel panel-default">
					<?php echo $content;?>
				</div>
			</div>
		</div>
	</body>
</html>