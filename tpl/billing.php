<?php
/**
 *	Шаблон биллинг-системы.
 *
 *	@version 16.3.9 by 26.01.2015 1:05
 *	@author Dmitriy Verkhoumov
 */

session_start();
unset($_SESSION['error']);
?>
<!DOCTYPE html>
<html>
	<head>
		<title><?php echo $title; ?></title>
		<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
		<meta http-equiv="Content-Language" content="ru">
		<link rel="shortcut icon" href="/favicon.ico" type="image/x-icon">
		<link href="http://fonts.googleapis.com/css?family=PT+Sans:400,400italic&subset=latin,cyrillic" rel="stylesheet" type="text/css">
		<link href="/css/billing.style.css" rel="stylesheet">
		<script src="/js/Chart.js"></script>
	</head>

	<body>
		<div class="page-wrapper">
			<div class="header">
				<div class="inner">
					<div class="userbar">
						<?php if ($page_status > 1) { ?>
						<div class="watch">Смотреть страницу как</div>
						<form method="POST" action="<?php echo $link; ?>">
							<div class="form">
								<div class="field">
									<div class="field-form"><?php echo $partners_seelist; ?></div>
								</div>

								<div class="field">
									<button name="see_as_partner">Смотреть</button>
								</div>
							</div>
						</form>
						<?php } else { ?>
						<div class="time" title="Текущее время">Текущее время <?php echo $current_time; ?></div>
						<div class="partner"><?php echo $partner_name; ?></div>
						<?php } ?>
					</div>

					<div class="logotype">
						<a href="<?php echo $main_billingpage_link; ?>" title="Перейти на главную страницу"><img src="/img/billing/label.png" width="277px"></a></a>
					</div>
				</div>
			</div>

			<div class="content">
				<div class="inner">
					<?php 
					// Админ-панель
					if ($page_status == 2) {
					?>

					<div class="aside">
						<div class="add-button"><a href="<?php echo $add_user_link; ?>">Добавить нового партнёра</a></div>

						<div><div class="block input" title="Общая сумма, которую на данный момент должны выплатить партнёры">
							<div class="number"><?php echo $admin_stats_checkin; ?></div>
							<div class="about">Рублей ожидают выплаты от партнёров</div>
						</div></div>

						<div><div class="block checkout admin">
							<div class="title">
								<div class="count" title="Обшее количество выплат"><?php echo $checkout_list_count; ?></div>
								<div class="name">Выплаты</div>
							</div>

							<?php
							// Уведомления.
							echo $checkin_delete_success;

							// Выплаты.
							echo $checkout_list_result;

							// Пагинация.
							echo $checkout_paginator;
							?>
						</div></div>

						<div><div class="block" title="Общее кол-во партнёров">
							<div class="number"><?php echo $admin_stats_partners; ?></div>
							<div class="about">Партнёров используют данную биллинг-систему</div>
						</div></div>

						<div><div class="block" title="Сумма, которую партнёры выплатили Вас за текущий месяц (начиная с 1-ого числа)">
							<div class="number"><?php echo $admin_stats_checkin_month; ?></div>
							<div class="about">Ваша прибыль в текущем месяце</div>
						</div></div>

						<div><div class="block" title="Сумма, которую партнёры выплатили Вам за всё время сотрудничества">
							<div class="number"><?php echo $admin_stats_checkin_all; ?></div>
							<div class="about">Ваша общая прибыль за всё время</div>
						</div></div>
					</div>

					<?php if ($edit_page > 0) { // Добавление/редактирование партнёра ?>
					<div class="center">
						<div class="side">
							<div class="title">
								<div class="name"><?php echo $edit_page == 1 ? 'Форма добавления партнёра' : 'Информация о партнёре'; ?></div>
							</div>

							<form method="POST" action="<?php echo $link; ?>">
								<div class="form">
									<?php echo $edit_result_success; // Вывод ошибок и успешных уведомлений. ?>

									<?php if ($edit_page == 2) { ?>
									<div class="field-line">
										<div class="field">
											<div class="field-name">Дата регистрации</div>
											<div class="field-form"><div class="disabled-form date"><?php echo $addu_data_date; ?></div></div>
										</div>

										<div class="field">
											<div class="field-name">Долг</div>
											<div class="field-form"><div class="checkin"><?php echo $addu_data_checkin; ?></div></div>
										</div>
									</div>
									<? } ?>

									<div class="field-line">
										<?php if ($edit_page == 1) { ?>
										<div class="field">
											<div class="field-name">Логин</div>
											<?php echo $addu_error['login']; ?>
											<div class="field-form"><input type="text" name="add_user[login]" value="<?php echo $addu_data_login; ?>"></div>
										</div>
										<?php } elseif ($edit_page == 2) { ?>
										<div class="field">
											<div class="field-name">Логин</div>
											<div class="field-form"><div class="disabled-form"><?php echo $addu_data_login; ?></div></div>
										</div>
										<? } ?>

										<div class="field">
											<div class="field-name">Пароль</div>
											<?php echo $addu_error['password']; ?>
											<div class="field-form"><input type="text" name="add_user[password]" value="<?php echo $addu_data_password; ?>"></div>
											
											<?php if ($edit_page == 1) { ?>
											<div class="field-description">Пароль сгенерирован автоматически, Вы можете изменить его. Запишите пароль, после добавления он будет зашифрован!</div>
											<? } else { ?>
											<div class="field-description">Чтобы сменить пароль, просто укажите новый.</div>
											<? } ?>
										</div>
									</div>

									<div class="field-line">
										<div class="field">
											<div class="field-name">Название</div>
											<?php echo $addu_error['name']; ?>
											<div class="field-form"><input type="text" name="add_user[name]" value="<?php echo $addu_data_name; ?>"></div>
										</div>

										<div class="field">
											<div class="field-name">Статус</div>
											<div class="field-form">
												<select name="add_user[status]">
													<option value="1" style="background-color: #65c178;"<?php echo $addu_data_status ? ' selected' : ''; ?>>Активный</option>
													<option value="0" style="background-color: #cf5a5a;"<?php echo !$addu_data_status ? ' selected' : ''; ?>>Заблокированный</option>
												</select>
											</div>
										</div>
									</div>

									<div class="field-line">
										<div class="field">
											<div class="field-name">API ключ</div>
											<?php echo $addu_error['apikey']; ?>
											<div class="field-form"><input type="text" name="add_user[apikey]" value="<?php echo $addu_data_api; ?>"></div>
											
											<?php if ($edit_page == 1) { ?>
											<div class="field-description">Ключ сгенерирован автоматически, Вы можете изменить его.</div>
											<? } ?>
										</div>

										<div class="field">
											<div class="field-name">Процентная ставка</div>
											<?php echo $addu_error['percent']; ?>
											<div class="field-form"><input type="number" min="0" max="100" name="add_user[percent]" value="<?php echo $addu_data_percent; ?>"></div>
											<div class="field-description">Сколько процентов партнёр должен выплатить от общего дохода.</div>
										</div>
									</div>

									<div class="field-line">
										<div class="field">
											<div class="field-name">Цена для CS 1.6</div>
											<?php echo $addu_error['boost_cs']; ?>
											<div class="field-form"><input type="number" min="0" max="999" name="add_user[boost_cs]" value="<?php echo $addu_data_boost_cs; ?>"></div>
											<div class="field-description">Стоимость 1 круга для серверов Counter-Strike 1.6.</div>
										</div>

										<div class="field">
											<div class="field-name">Цена для CSS</div>
											<?php echo $addu_error['boost_css']; ?>
											<div class="field-form"><input type="number" min="0" max="999" name="add_user[boost_css]" value="<?php echo $addu_data_boost_css; ?>"></div>
											<div class="field-description">Стоимость 1 круга для серверов Counter-Strike Source.</div>
										</div>
									</div>

									<?php if ($edit_page == 1) { // Кнопка "Добавить" ?>
									<div class="field-line">
										<div class="field">
											<button name="go_add_user">Добавить</button>
										</div>
									</div>
									<?php } elseif ($edit_page == 2) { ?>
									<div class="field-line edit">
										<div class="field save">
											<button name="go_save_user">Сохранить изменения</button>
										</div>

										<div class="field back">
											<button name="go_back">Отменить</button>
										</div>
									</div>

									<div class="field-line delete">
										<div class="field">
											<button name="go_delete_user" title="При удалении партнёра вся информация о выплатах и заказах будет также удалена">Удалить партнёра</button>
										</div>
									</div>
									<?php } ?>
								</div>
							</form>
						</div>
					</div>
					<?php } ?>

					<div class="center">
						<div class="side checkin">
							<div class="title">
								<div class="name">Зафиксировать выплату</div>
							</div>

							<form method="POST" action="<?php echo $link; ?>">
								<div class="form">
									<?php echo $partner_checkin_error.$partner_checkin_success; // Вывод ошибок и успешный уведомлений. ?>

									<div class="field">
										<div class="field-name">Партнёр</div>
										<div class="field-form"><?php echo $partners_selectlist; ?></div>
									</div>

									<div class="field">
										<div class="field-name">Сумма выплаты</div>
										<div class="field-form"><input type="number" name="checkin[summ]" value="<?php echo $form_user_summ; ?>" placeholder="Рублей"></div>
									</div>

									<div class="field" style="vertical-align: bottom;">
										<button name="add_checkin">Добавить выплату</button>
									</div>
								</div>
							</form>
						</div>

						<div class="side partners-list">
							<div class="title">
								<div style="width: 190px;" class="column"><div class="name cols">Имя</div></div><!--
								--><div style="width: 95px; text-align: right;" class="column"><div class="name">CS 1.6</div></div><!--
								--><div style="width: 95px;" class="column"><div class="name cols">CSS</div></div><!--
								--><div style="width: 100px; text-align: right;" class="column"><div class="name">Ставка</div></div><!--
								--><div style="width: 120px;" class="column"><div class="name cols">Долг</div></div><!--
								--><div style="width: 100px;" class="column"><div class="name"></div></div>
							</div>

							<?php echo $partners_listing; // Список партнёров. ?>
						</div>
					</div>

					<?php if (count($graph['labels']) > 1) { ?>
					<div class="center">
						<div class="side">
							<div class="title">
								<div class="name">Уровень прибыли за последние 30 дней</div>
							</div>

							<div class="graph">
								<canvas id="canvas" height="160" width="670"></canvas>
							</div>
						</div>
					</div>
					<?php } ?>

					<div class="listing">
						<?php
						// Список заказов.
						echo $orders_list_result;

						// Пагинация.
						echo $order_list_paginator;
						?>
					</div>
					<?php } ?>

					<?php if ($page_status == 1 || $page_status == 3) { ?>
					<div class="aside">
						<?php if (!empty($stats_checkout)) { ?>
						<div><div class="block output" title="Сумма, которую Вы должны нам выплатить за текущий отчётный период">
							<div class="number"><?php echo $stats_checkout; ?></div>
							<div class="about">Рублей необходимо выплатить</div>
						</div></div>
						<?php } ?>

						<?php if (!empty($stats_checkin)) { ?>
						<div><div class="block input" title="Сумма, которая останется при Вас за текущий отчётный период">
							<div class="number"><?php echo $stats_checkin; ?></div>
							<div class="about">Ваша прибыль в текущем отчётном периоде</div>
						</div></div>
						<?php } ?>

						<?php if (!empty($checkout_list_result)) { ?>
						<div><div class="block checkout">
							<div class="title">
								<div class="count" title="Кол-во выплат"><?php echo $checkout_list_count; ?></div>
								<div class="name">Выплаты</div>
							</div>

							<?php
							// Выплаты.
							echo $checkout_list_result;

							// Пагинация.
							echo $checkout_paginator;
							?>
						</div></div>
						<?php } ?>

						<?php if (!empty($stats_checkin_lastmonth)) { ?>
						<div><div class="block" title="Ваша прибыль за текущий месяц">
							<div class="number"><?php echo $stats_checkin_lastmonth; ?></div>
							<div class="about">Ваша прибыль в текущем месяце</div>
						</div></div>
						<?php } ?>

						<?php if (!empty($stats_checkin_all)) { ?>
						<div><div class="block" title="Ваша прибыль за весь период сотрудничества с Cocosov.net">
							<div class="number"><?php echo $stats_checkin_all; ?></div>
							<div class="about">Ваша прибыль за всё время партнёрства</div>
						</div></div>
						<?php } ?>

						<?php if (!empty($stats_orders_lastmonth)) { ?>
						<div><div class="block" title="Кол-во заказов, оформленных в текущем месяце">
							<div class="number"><?php echo $stats_orders_lastmonth; ?></div>
							<div class="about">Заказов оформлено в этом месяце</div>
						</div></div>
						<?php } ?>

						<?php if (!empty($stats_orders_all)) { ?>
						<div><div class="block" title="Кол-во заказов, оформленных за весь период сотрудничество с Cocosov.net">
							<div class="number"><?php echo $stats_orders_all; ?></div>
							<div class="about">Заказов оформлено за всё время</div>
						</div></div>
						<?php } ?>
					</div>

					<div class="aside center">
						<?php if (!empty($stats_percent)) { ?>
						<div class="block" title="Столько процентов от общего дохода за заказы Вы должны выплатить">
							<div class="number"><?php echo $stats_percent; ?>%</div>
							<div class="about">Ваша процентная ставка</div>
						</div>
						<?php } ?>

						<?php if (!empty($stats_price_cs)) { ?>
						<div class="block" title="Стоимость 1 круга для серверов Counter-Strike 1.6 у Вас в системе">
							<div class="number"><?php echo $stats_price_cs; ?>₽</div>
							<div class="about">Стоимость 1 круга для CS 1.6</div>
						</div>
						<?php } ?>

						<?php if (!empty($stats_price_css)) { ?>
						<div class="block" title="Стоимость 1 круга для серверов Counter-Strike Source у Вас в системе">
							<div class="number"><?php echo $stats_price_css; ?>₽</div>
							<div class="about">Стоимость 1 круга для CSS</div>
						</div>
						<?php } ?>
					</div>

					<?php if (count($graph['labels']) > 1) { ?>
					<div class="center">
						<div class="side">
							<div class="title">
								<div class="name">Уровень прибыли за последние 30 дней</div>
							</div>

							<div class="graph">
								<canvas id="canvas" height="160" width="670"></canvas>
							</div>
						</div>
					</div>
					<?php } ?>

					<div class="center">
						<div class="side filter">
							<div class="title">
								<div class="name">Поиск заказов</div>
							</div>

							<form method="POST" action="<?php echo $link; ?>">
								<div class="form">
									<?php echo $search_error; // Вывод ошибок. ?>

									<div class="field">
										<div class="field-name">С какого числа</div>
										<div class="field-form"><input type="date" name="search[startdate]" value="<?php echo $search_startdate; ?>"></div>
									</div>

									<div class="field">
										<div class="field-name">По какое число</div>
										<div class="field-form"><input type="date" name="search[enddate]" value="<?php echo $search_enddate; ?>"></div>
									</div>

									<div class="field" style="vertical-align: bottom;">
										<button name="go_search">Показать</button>
									</div>
								</div>
							</form>

							<?php if ($search_success) { ?>
							<div class="search-result">
								<div class="form">
									<div class="field">
										<div class="field-name">Кол-во заказов</div>
										<div class="field-form"><div class="disabled-form count"><?php echo $search_orders; ?></div></div>
									</div>

									<div class="field">
										<div class="field-name">Общая сумма</div>
										<div class="field-form"><div class="disabled-form summ"><?php echo $search_summary; ?></div></div>
									</div>
								</div>
							</div>
							<?php } ?>
						</div>
					</div>

					<div class="listing">
						<?php
						// Список заказов.
						echo $orders_list_result;

						// Пагинация.
						echo $order_list_paginator;
						?>
					</div>

					<?php } elseif ($page_status == 0) { ?>
					<div class="billing-error">У Вас нету доступа к просмотру информации на данной странице!</div>
					<?php } ?>
				</div>
			</div>

			<div class="page-buffer"></div>
		</div>

		<div class="footer">
			<div class="inner">
				<div class="logotype"></div>
				<div class="copyright">Copyright © 2015, All rights reserved.<br>Разработано специально для «<a href="http://cocosov.net/" title="Раскрутка CS 1.6 / CS:Source V.34 серверов">Cocosov.net</a>».</div>
			</div>
		</div>

<script type="text/javascript">
var lineChartData = {
	labels : [<?php echo '"'.implode('", "', $graph['labels']).'"'; ?>],
	datasets : [
		{
			label: "Прибыль",
			fillColor : "rgba(151,187,205,0)",
			strokeColor : "#65c178",
			pointColor : "#65c178",
			pointStrokeColor : "#fff",
			pointHighlightFill : "#fff",
			pointHighlightStroke : "#65c178",
			data : [<?php echo '"'.implode('", "', $graph['summary']).'"'; ?>]
		}<?php if (count($graph['opd'])) { ?>,
		{
			label: "ОПД",
			fillColor : "rgba(151,187,205,0)",
			strokeColor : "#cf5a5a",
			pointColor : "#cf5a5a",
			pointStrokeColor : "#fff",
			pointHighlightFill : "#fff",
			pointHighlightStroke : "#cf5a5a",
			data : [<?php echo '"'.implode('", "', $graph['opd']).'"'; ?>]
		}<?php } ?>
	]
}

window.onload = function(){
	var ctx = document.getElementById("canvas").getContext("2d");

	window.myLine = new Chart(ctx).Line(lineChartData, {
		responsive: true,
		scaleLabel: "<%=value%> руб.",
		tooltipTemplate: "Заработано <%=value%> руб.",
		multiTooltipTemplate: "<%if (label){%><%=datasetLabel%>: <%}%> <%=value%><%if (datasetLabel == 'Прибыль') {%> руб.<%} else {%>%<%}%>"
	});
}
</script>
	</body>
</html>