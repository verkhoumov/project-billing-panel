<?php
/**
 *	Обработка всех алгоритмов биллинг-системы.
 *
 *	@version 9.7.2 by 26.01.2015 1:05
 *	@author Dmitriy Verkhoumov
 */
session_start();
require_once("system/billing.function.php");

$title = "BillingPanel / Cocosov.net";
$page_status = 0;
$edit_page = 0;

$data = array();
$data['login'] = (string) trim(urldecode($_GET['login']));
$data['password'] = (string) trim(urldecode($_GET['password']));
$data['open_user'] = (int) trim(urldecode($_GET['open_user']));

$main_billingpage_link = '/?login='.$data['login'].'&password='.$data['password'];

$partner_name = 'Панель недоступна';
$current_time = 'неизвестно';

// Проверка пользователя.
if ($user = isBillingUser($data['login'], $data['password']))
	$page_status = 1;

if ($user['status'] == 2)
	$page_status = 2;

if ($page_status > 0)
{
	// Заголовок страницы.
	$title = trim($user['name']).' / '.$title;

	// Текущее время.
	$current_time = getNormalDate(time(), "@t1");

	// Имя пользователя.
	$partner_name = trim($user['name']) == '' ? 'Партнёр' : trim($user['name']);
}

/*
 *	Вывод статистической информации.
 */
// Если пользователь существует, формируем данные.
if ($page_status == 1 || ($page_status == 2 && !empty($_GET['open_user']) && ($user = isBillingUserID($data['open_user']))))
{
	if ($page_status == 2)
		$page_status = 3;

	$user_id = $user['id'];

	/*
	 *	Список выплат.
	 */
	$checkout_list_pagelimit = 5; // Кол-во выплат на 1 странице.
	$checkout_list_result = '';

	$checkout_list_count = getBillingUserPaymentsCount($user_id); // Общее кол-во выплат.
	$checkout_list_pagenumber = empty($_GET['checkout_page']) ? 1 : (int) $_GET['checkout_page']; // Текущая страница.
	$checkout_list_pagelimitstart = $checkout_list_pagelimit * ($checkout_list_pagenumber - 1);
	$checkout_list_pagecount = ceil($checkout_list_count / $checkout_list_pagelimit); // Общее кол-во страниц.

	$checkout_list = getBillingUserPayments($user_id, $checkout_list_pagelimitstart, $checkout_list_pagelimit);

	// Список выплат.
	if (!$checkout_list_count || $checkout_list_pagenumber > $checkout_list_pagecount)
		$checkout_list_result .= '<div class="check"><div class="null">Выплаты отсутствуют</div></div>';
	else
	{
		foreach ($checkout_list as $check) 
		{
			$check_summ = $check['payment'];
			$check_date = getNormalDate(strtotime($check['paydate']));

			$checkout_list_result .= '<div class="check">';
			$checkout_list_result .= '<div style="width: 170px;" class="column"><div class="clock"><div class="time">'.$check_date.'</div></div></div>';
			$checkout_list_result .= '<div style="width: 110px; text-align: right;" class="column"><div class="summ">'.$check_summ.' руб.</div></div>';
			$checkout_list_result .= '</div>';
		}
	}

	// Пагинация выплат.
	$checkout_paginator = getBillingPaginator($checkout_list_pagenumber, $checkout_list_pagecount, 'checkout_page');

	/*
	 *	Обработчик поиска.
	 */
	$search_startdate = '';
	$search_enddate = '';
	$search_error = '';
	$search_orders = $search_summary = $search_success = 0;

	if (isset($_POST['go_search']))
	{
		$search_data = (array) $_POST['search'];

		// Проверка дат.
		if (!empty($search_data['startdate']) && isBillingSearchDate($search_data['startdate']))
			$search_startdate = $search_data['startdate'];
		else 
			$search_error = 1;

		if (!empty($search_data['enddate']) && isBillingSearchDate($search_data['enddate']))
			$search_enddate = $search_data['enddate'];
		else 
			$search_error = 1;

		$search_startdate_unix = strtotime($search_startdate) + 60*60*1;
		$search_enddate_unix = strtotime($search_enddate) + 60*60*1 + 60*60*24; // Конец дня.

		if ($search_startdate_unix >= $search_enddate_unix)
			$search_error = 1;

		// Вывод ошибки.
		if ($search_error)
			$search_error = '<div class="form-error">Ошибка! Одна из дат указана некорректно!</div>';
		else
			$search_success = 1;
	}

	/*
	 *	Список заказов.
	 */
	$orders_list_pagelimit = 100;
	$orders_list_result = '';

	if ($search_success)
		$orders_list_pagelimit = 0;

	$order_data = getBillingUserOrdersCount($user_id);
	$orders_list_count = $order_data['limit']; // Общее кол-во заказов.
	$orders_list_pagenumber = empty($_GET['order_page']) ? 1 : (int) $_GET['order_page']; // Текущая страница.
	$orders_list_pagelimitstart = $orders_list_pagelimit * ($orders_list_pagenumber - 1);
	$orders_list_pagecount = ceil($orders_list_count / $orders_list_pagelimit); // Общее кол-во страниц.

	if ($search_success)
		$orders_list = getBillingSearchOrders($user_id, $search_startdate_unix, $search_enddate_unix);
	else
		$orders_list = getBillingUserOrders($user_id, $orders_list_pagelimitstart, $orders_list_pagelimit);

	if ($search_success && ($search_orders_count = count($orders_list)))
	{
		$search_orders = getWordByNumber($search_orders_count, array('заказов', 'заказа', 'заказ'));
		$search_summary = getWordByNumber(getBillingSearchSummary($orders_list), array('рублей', 'рубля', 'рубль'));
		$orders_list_pagecount = 1;
	}


	// Обработка заказов таким образом, чтобы заказы за каждый день были отдельно.
	$new_orders_list = getBillingOrdersByDays($orders_list, $user['percent']);

	// Список заказов.
	if (!$orders_list_count || $orders_list_pagenumber > $orders_list_pagecount)
		$orders_list_result .= '<div class="order"><div class="null">Заказы отсутствуют</div></div>';
	else
	{
		foreach ($new_orders_list as $time => $orders)
		{
			$order_list_date = getNormalDate($time + 1, '@d');
			$order_list_count = getWordByNumber($orders['orders_count'], array('заказов', 'заказа', 'заказ'));
			$order_list_checkin = $orders['checkin'].' руб.';
			$order_list_checkout = $orders['checkout'].' руб.';

			$orders_list_result .= '<div class="title">';
			$orders_list_result .= '<div style="width: 340px;" class="column"><div class="time">'.$order_list_date.'</div></div>';
			$orders_list_result .= '<div style="width: 90px;" class="column"><div class="count" title="Количество заказов">'.$order_list_count.'</div></div>';
			$orders_list_result .= '<div style="width: 135px;" class="column"><div class="summ output" title="Сумма, которую вы должны будете выплатить">'.$order_list_checkout.'</div></div>';
			$orders_list_result .= '<div style="width: 135px; text-align: right;" class="column"><div class="summ input" title="Ваша прибыль">'.$order_list_checkin.'</div></div>';
			$orders_list_result .= '</div>';

			if (!$orders['orders_count'])
				$orders_list_result .= '<div class="order"><div class="null">В данный день не было ни одного заказа</div></div>';

			foreach ($orders['orders_list'] as $order)
			{
				$order_time = getNormalDate(strtotime($order['datetime']), '@t1');
				$order_servertype = $order['server_type'] == 'cs1.6' ? 'CS 1.6' : 'CSS';
				$order_serverhost = $order['ip'];
				$order_count = getWordByNumber($order['circles'], array('кругов', 'круга', 'круг'));
				$order_checkin = getWordByNumber(($order['price'] * ((100 - $user['percent']) / 100)), array('рублей', 'рубля', 'рубль'));
				$order_checkout = getWordByNumber(($order['price'] * ($user['percent'] / 100)), array('рублей', 'рубля', 'рубль'));

				$orders_list_result .= '<div class="order">';
					$orders_list_result .= '<div style="width: 105px;" class="column"><div class="clock"><div class="time">'.$order_time.'</div></div></div>';
					$orders_list_result .= '<div style="width: 60px; text-align: right;" class="column"><div class="type">'.$order_servertype.'</div></div>';
					$orders_list_result .= '<div style="width: 175px;" class="column"><div class="host">'.$order_serverhost.'</div></div>';
					$orders_list_result .= '<div style="width: 90px;" class="column"><div class="count">'.$order_count.'</div></div>';
					$orders_list_result .= '<div style="width: 135px;" class="column"><div class="summ output">'.$order_checkout.'</div></div>';
					$orders_list_result .= '<div style="width: 135px; text-align: right;" class="column"><div class="summ input">'.$order_checkin.'</div></div>';
				$orders_list_result .= '</div>';
			}
		}
	}

	/*
	 *	Цифры для статистики.
	 */
	$super_months_stats = getBillingOrdersMonthsStats($user_id);

	$stats_checkout = floor($user['percent_balance']); // Следующая выплата
	$stats_checkin = floor($user['natural_balance'] - $user['percent_balance']); // Прибыль
	$stats_checkin_lastmonth = floor($super_months_stats['summary'] * ((100 - $user['percent']) / 100)); // Прибыль за последний месяц.
	$stats_checkin_all = floor($order_data['summary'] * ((100 - $user['percent']) / 100)); // Прибыль за всё время.
	$stats_orders_lastmonth = $super_months_stats['limit']; // Кол-во заказов за последний месяц.
	$stats_orders_all = $orders_list_count; // Общее кол-во заказов.
	$stats_percent = $user['percent']; // Процентная ставка.
	$stats_price_cs = floor($user['boost_price_cs']); // Цена 1 круга cs 1.6
	$stats_price_css = floor($user['boost_price_css']); // Цена 1 круга css

	// Пагинация заказов.
	$order_list_paginator = getBillingPaginator($orders_list_pagenumber, $orders_list_pagecount, 'order_page');

	/*
	 *	Данные для графика.
	 */
	$graph = getBillingGraphData($user_id, $user['percent']);
}

if ($page_status > 1)
{
	$partners_list = getBillingUsers();
	$link = getBillingClearLink();
	
	/*
	 *	Смотреть страницу как.
	 */
	$see_user_id = 0;

	if (isset($_POST['see_as_partner']))
	{
		$see_user_id = (int) $_POST['partner_id_onsee'];

		$link = preg_replace('#(\&open_user=[\d\w\_]+)#i', '', $link);

		if (!empty($see_user_id) && $see_user_id > 0 && isset($partners_list[$see_user_id]) && $partners_list[$see_user_id]['status'] == 1)
			header('Location: '.$link.'&open_user='.$see_user_id);

		if ($see_user_id == 0)
			header('Location: '.$link);
	}

	if (!empty($_GET['open_user']))
		$see_user_id = (int) $_GET['open_user'];

	$partners_seelist = '<select name="partner_id_onsee">';
	$partners_seelist .= getBillingPartnersSelectList($partners_list, $see_user_id, 1);
	$partners_seelist .= '</select>';
}

/*
 *	Вывод информации для админа.
 */
if ($page_status == 2 && empty($_GET['open_user']))
{
	/*
	 *	Зафиксировать выплату.
	 *	Форма.
	 */
	$partner_checkin_error = '';
	$partner_checkin_success = '';
	$form_user_id = 0;
	$form_user_summ = 0;

	/* Обработчик. */
	if (isset($_POST['add_checkin']))
	{
		// Проверка данных.
		$form_data = (array) $_POST['checkin'];
		$form_user_id = (int) $form_data['partner_id'];
		$form_user_summ = (int) $form_data['summ'];

		// Если данные корректны, обрабатываем запрос.
		if (!empty($form_user_id) && !empty($form_user_summ) && isset($partners_list[$form_user_id]) && $partners_list[$form_user_id]['status'] == 1 && $form_user_summ > 0)
		{
			if ($form_user_summ > $partners_list[$form_user_id]['percent_balance'])
				$partner_checkin_error = '<div class="form-error">Ошибка! Сумма выплаты не может быть больше суммы баланса, которую партнёру необходимо погасить. Текущий баланс партнёра - <b>'.$partners_list[$form_user_id]['percent_balance'].' руб.</b></div>';
			else
			{
				if (addBillingCheckin($form_user_id, $form_user_summ) && setBillingUserBalanceToNull($form_user_id, $form_user_summ, $partners_list[$form_user_id]['percent'], $partners_list[$form_user_id]['percent_balance']))
					header('Location: '.$link.'&checkin_success=1');
				else
					$partner_checkin_error = '<div class="form-error">Ошибка! При обработке SQL запроса произошёл неизвестный сбой!</div>';
			}
		}
		// Выводим ошибку.
		else
			$partner_checkin_error = '<div class="form-error">Данные указаны некорректно! Исправьте и попробуйте ещё раз!</div>';

	}

	if (!empty($_GET['checkin_success']))
		$partner_checkin_success .= '<div class="form-success">Выплата успешно добавлена!</div>';

	// Список партнёров для select'a.
	$partners_selectlist = '<select name="checkin[partner_id]">';
	$partners_selectlist .= getBillingPartnersSelectList($partners_list, $form_user_id);
	$partners_selectlist .= '</select>';

	/*
	 *	Список выплат.
	 */
	$checkout_list_pagelimit = 5; // Кол-во выплат на 1 странице.
	$checkout_list_result = '';

	$checkout_list_count = getBillingUserPaymentsCount(0); // Общее кол-во выплат.
	$checkout_list_pagenumber = empty($_GET['checkout_page']) ? 1 : (int) $_GET['checkout_page']; // Текущая страница.
	$checkout_list_pagelimitstart = $checkout_list_pagelimit * ($checkout_list_pagenumber - 1);
	$checkout_list_pagecount = ceil($checkout_list_count / $checkout_list_pagelimit); // Общее кол-во страниц.

	$checkout_list = getBillingUserPayments(0, $checkout_list_pagelimitstart, $checkout_list_pagelimit);

	// Список выплат.
	if (!$checkout_list_count || $checkout_list_pagenumber > $checkout_list_pagecount)
		$checkout_list_result .= '<div class="check"><div class="null">Выплаты отсутствуют</div></div>';
	else
	{
		foreach ($checkout_list as $check) 
		{
			$check_summ = $check['payment'];
			$check_date = getNormalDate(strtotime($check['paydate']));
			$check_user = trim($partners_list[$check['partner_id']]['name']);

			$checkout_list_result .= '<div class="check">';
			$checkout_list_result .= '<a href="'.$link.'&delete_checkin='.$check['id'].'" class="delete" title="Отменить выплату (баланс партнёра будет восстановлен на сумму выплаты)"></a>';
			$checkout_list_result .= '<div class="user">'.$check_user.'</div>';
			$checkout_list_result .= '<div style="width: 170px;" class="column"><div class="clock"><div class="time">'.$check_date.'</div></div></div>';
			$checkout_list_result .= '<div style="width: 110px; text-align: right;" class="column"><div class="summ">'.$check_summ.' руб.</div></div>';
			$checkout_list_result .= '</div>';
		}
	}

	// Пагинация выплат.
	$checkout_paginator = getBillingPaginator($checkout_list_pagenumber, $checkout_list_pagecount, 'checkout_page');

	/*
	 *	Обработка удаления выплаты.
	 */
	$checkin_delete_success = '';

	if (!empty($_GET['delete_checkin']))
	{
		$checkin_id = (int) $_GET['delete_checkin'];

		if (setBillingDeleteCheckin($checkin_id))
			header('Location: '.$link.'&delete_checkin_success=1');
	}

	if (!empty($_GET['delete_checkin_success']))
		$checkin_delete_success .= '<div class="form-success">Выплата успешно отменена!</div>';

	/*
	 *	Статистическая информация.
	 */
	$admin_stats = getBillingPartnersStats($partners_list);
	$admin_stats_checkin_month = getBillingCheckinsSumm(1);
	$admin_stats_checkin_all = getBillingCheckinsSumm(0);
	$admin_stats_checkin = $admin_stats['checkin'];
	$admin_stats_partners = $admin_stats['partners'];

	// Ссылка на страницу добавления нового партнёра.
	$edit_user_link = preg_replace('#(\&edit_user=[\d\w\_]+)#i', '', $link);

	/*
	 *	Список партнёров.
	 */
	$partners_listing = '';

	foreach ($partners_list as $info)
	{
		if ($info['status'] == 2)
			continue;

		$partner_list_name = trim($info['name']);
		$partner_list_percent = $info['percent'];
		$partner_list_price_cs = floor($info['boost_price_cs']);
		$partner_list_price_css = floor($info['boost_price_css']);
		$partner_list_checkin = getWordByNumber(floor($info['percent_balance']), array('рублей', 'рубля', 'рубль'));
		$partner_list_link = $edit_user_link.'&edit_user='.$info['id'];
		$partner_status = $info['status'] ? '<div class="status online" title="Партнёр активен"></div>' : '<div class="status offline" title="Партнёр заблокирован"></div>';

		$partners_listing .= '<div class="list">';
			$partners_listing .= '<a href="'.$partner_list_link.'" class="settings" title="Посмотреть/отредактировать информацию о партнёре"></a>';
			$partners_listing .= '<div style="width: 190px;" class="column"><div class="name">'.$partner_list_name.'</div>'.$partner_status.'</div>';
			$partners_listing .= '<div style="width: 95px; text-align: right;" class="column"><div class="boost_price cs" title="Стоимость 1 круга у партнёра">'.$partner_list_price_cs.' руб.</div></div>';
			$partners_listing .= '<div style="width: 95px;" class="column"><div class="boost_price css" title="Стоимость 1 круга у партнёра">'.$partner_list_price_css.' руб.</div></div>';
			$partners_listing .= '<div style="width: 100px; text-align: right;" class="column"><div class="percent" title="Процентная ставка">'.$partner_list_percent.'%</div></div>';
			$partners_listing .= '<div style="width: 175px;" class="column"><div class="checkin" title="Сумма, которую партнёр должен выплатить на данный момент">'.$partner_list_checkin.'</div></div>';
		$partners_listing .= '</div>';
	}

	if ($partners_listing == '')
		$partners_listing .= '<div class="list"><div class="null">Партнёры отсутствуют</div></div>';

	/*
	 *	Список всех заказов.
	 */
	$orders_list_pagelimit = 100;
	$orders_list_result = '';

	$order_data = getBillingUserOrdersCount(0);
	$orders_list_count = $order_data['limit']; // Общее кол-во заказов.
	$orders_list_pagenumber = empty($_GET['order_page']) ? 1 : (int) $_GET['order_page']; // Текущая страница.
	$orders_list_pagelimitstart = $orders_list_pagelimit * ($orders_list_pagenumber - 1);
	$orders_list_pagecount = ceil($orders_list_count / $orders_list_pagelimit); // Общее кол-во страниц.

	$orders_list = getBillingUserOrders(0, $orders_list_pagelimitstart, $orders_list_pagelimit);

	// Обработка заказов таким образом, чтобы заказы за каждый день были отдельно.
	$new_orders_list = getBillingOrdersByDays($orders_list, $partners_list);

	// Список заказов.
	if (!$orders_list_count || $orders_list_pagenumber > $orders_list_pagecount)
		$orders_list_result .= '<div class="order"><div class="null">Заказы отсутствуют</div></div>';
	else
	{
		foreach ($new_orders_list as $time => $orders)
		{
			$order_list_date = getNormalDate($time + 1, '@d');
			$order_list_count = getWordByNumber($orders['orders_count'], array('заказов', 'заказа', 'заказ'));
			$order_list_checkin = getWordByNumber($orders['checkin'], array('рублей', 'рубля', 'рубль'));
			$order_list_checkout = getWordByNumber($orders['checkout'], array('рублей', 'рубля', 'рубль'));

			$orders_list_result .= '<div class="title">';
			$orders_list_result .= '<div style="width: 470px;" class="column"><div class="time">'.$order_list_date.'</div></div>';
			$orders_list_result .= '<div style="width: 100px;" class="column"><div class="count" title="Количество заказов">'.$order_list_count.'</div></div>';
			$orders_list_result .= '<div style="width: 130px; text-align: right;" class="column"><div class="summ input" title="Ваша чистая прибыль">'.$order_list_checkout.'</div></div>';
			$orders_list_result .= '</div>';

			if (!$orders['orders_count'])
				$orders_list_result .= '<div class="order"><div class="null">В данный день не было ни одного заказа</div></div>';

			foreach ($orders['orders_list'] as $order)
			{
				$order_time = getNormalDate(strtotime($order['datetime']), '@t1');
				$order_servertype = $order['server_type'] == 'cs1.6' ? 'CS 1.6' : 'CSS';
				$order_serverhost = $order['ip'];
				$order_count = getWordByNumber($order['circles'], array('кругов', 'круга', 'круг'));
				$order_checkin = getWordByNumber(($order['price'] * ((100 - $partners_list[$order['id_partner']]['percent']) / 100)), array('рублей', 'рубля', 'рубль'));
				$order_checkout = getWordByNumber(($order['price'] * ($partners_list[$order['id_partner']]['percent'] / 100)), array('рублей', 'рубля', 'рубль'));
				$order_partner_name = trim($partners_list[$order['id_partner']]['name']);

				$orders_list_result .= '<div class="order">';
					$orders_list_result .= '<div style="width: 105px;" class="column"><div class="clock"><div class="time">'.$order_time.'</div></div></div>';
					$orders_list_result .= '<div style="width: 120px; text-align: right;" class="column"><div class="type">'.$order_partner_name.'</div></div>';
					$orders_list_result .= '<div style="width: 57px; text-align: right;" class="column"><div class="type">'.$order_servertype.'</div></div>';
					$orders_list_result .= '<div style="width: 188px;" class="column"><div class="host">'.$order_serverhost.'</div></div>';
					$orders_list_result .= '<div style="width: 100px;" class="column"><div class="count">'.$order_count.'</div></div>';
					$orders_list_result .= '<div style="width: 130px; text-align: right;" class="column"><div class="summ input">'.$order_checkout.'</div></div>';
				$orders_list_result .= '</div>';
			}
		}
	}

	// Пагинация заказов.
	$order_list_paginator = getBillingPaginator($orders_list_pagenumber, $orders_list_pagecount, 'order_page');

	/*
	 *	Добавление/Редактирование партнёра
	 */
	$add_user_link = $edit_user_link.'&edit_user=0';

	if (isset($_GET['edit_user']))
	{
		$edit_user_id = (int) $_GET['edit_user'];

		// Добавление нового партнёра.
		if ($edit_user_id == 0)
		{
			$edit_page = 1;

			$addu_error = array();
			$addu_data_login = $addu_data_name = '';
			$addu_data_password = getRandomString(18, '', 1, 1, 0, 1);
			$addu_data_api = getRandomString(14, '', 1, 1, 0, 1);
			$addu_data_percent = 0;
			$addu_data_boost_cs = 45;
			$addu_data_boost_css = 38;
			$addu_data_status = 1;

			// Обработка формы.
			if (isset($_POST['go_add_user']))
			{
				$addu_data = (array) $_POST['add_user'];

				// Логин.
				if (!isBillingLogin($addu_data['login']))
				{
					$addu_error['login'] = '<div class="field-error">Может содержать только символы латинского алфавита, цифры, точку и нижнее подчёркивание, от 3 до 20 знаков.</div>';
					$addu_data_login = '';
				}
				else
					$addu_data_login = $addu_data['login'];

				// Пароль.
				if (!isBillingPass($addu_data['password']))
				{
					$addu_error['password'] = '<div class="field-error">Может содержать только символы латинского алфавита и цифры, от 5 до 20 знаков.</div>';
					$addu_data_password = '';
				}
				else
					$addu_data_password = $addu_data['password'];

				// Название.
				if (!isBillingUserName($addu_data['name']))
				{
					$addu_error['name'] = '<div class="field-error">Может содержать только символы русского и латинского алфавита, цифры, точку, тире и нижнее подчёркивание, от 1 до 25 символов.</div>';
					$addu_data_name = '';
				}
				else
					$addu_data_name = $addu_data['name'];

				// API ключ.
				if (!isBillingUserAPI($addu_data['apikey']))
				{
					$addu_error['apikey'] = '<div class="field-error">Может содержать только символы латинского алфавита и цифры, от 6 до 20 знаков.</div>';
					$addu_data_api = '';
				}
				else
					$addu_data_api = $addu_data['apikey'];

				// Статус.
				$addu_data_status = (int) $addu_data['status'];
				if ($addu_data_status != 1)
					$addu_data_status = 0;

				// Процентная ставка.
				$addu_data_percent = (int) $addu_data['percent'];
				if ($addu_data_percent < 0 || $addu_data_percent > 100)
				{
					$addu_error['percent'] = '<div class="field-error">Должна быть от 0 до 100 процентов.</div>';
					$addu_data_percent = 0;
				}

				// Стоимость 1 круга CS 1.6.
				$addu_data_boost_cs = (int) $addu_data['boost_cs'];
				if ($addu_data_boost_cs <= 0)
				{
					$addu_error['boost_cs'] = '<div class="field-error">Стоимость не может быть нулевой.</div>';
					$addu_data_boost_cs = 0;
				}

				// Стоимость 1 круга CSS.
				$addu_data_boost_css = (int) $addu_data['boost_css'];
				if ($addu_data_boost_css <= 0)
				{
					$addu_error['boost_css'] = '<div class="field-error">Стоимость не может быть нулевой.</div>';
					$addu_data_boost_css = 0;
				}

				// Если ошибок нету, создаём нового партнёра в бд.
				if (!count($addu_error))
				{
					if ($new_user_id = setBillingNewUser($addu_data_login, $addu_data_password, $addu_data_name, $addu_data_status, $addu_data_api, $addu_data_percent, $addu_data_boost_cs, $addu_data_boost_css))
						header('Location: '.$main_billingpage_link.'&edit_user='.$new_user_id.'&edit_user_success=1');
				}
			}
		}

		// Редактирование старого партнёра.
		else
		{
			// Проверяем, есть ли такой партнёр.
			if (isset($partners_list[$edit_user_id]) && $partners_list[$edit_user_id]['status'] != 2)
			{
				$edit_page = 2;

				// Редактирование информации.
				$addu_error = $save_query = array();
				$addu_data_login = trim($partners_list[$edit_user_id]['login']);
				$addu_data_name = trim($partners_list[$edit_user_id]['name']);
				$addu_data_password = '';
				$addu_data_api = trim($partners_list[$edit_user_id]['apikey']);
				$addu_data_percent = (int) $partners_list[$edit_user_id]['percent'];
				$addu_data_boost_cs = (int) $partners_list[$edit_user_id]['boost_price_cs'];
				$addu_data_boost_css = (int) $partners_list[$edit_user_id]['boost_price_css'];
				$addu_data_status = (int) $partners_list[$edit_user_id]['status'];
				$addu_data_checkin = getWordByNumber(floor($partners_list[$edit_user_id]['percent_balance']), array('рублей', 'рубля', 'рубль'));
				$addu_data_date = getNormalDate(strtotime($partners_list[$edit_user_id]['regdate']), '@d @t2, @y');
				
				// Выход на главную страницу по запросу.
				if (isset($_POST['go_back']))
					header('Location: '.$main_billingpage_link);

				// Удаление аккаунта и всей связанной информации по запросу.
				if (isset($_POST['go_delete_user']))
				{
					if (setBillingUserDelete($edit_user_id))
						header('Location: '.$main_billingpage_link.'&edit_user=0&edit_user_success=3');
				}

				// Обработка данных при сохранении.
				if (isset($_POST['go_save_user']))
				{
					$addu_data = (array) $_POST['add_user'];

					// Если пароли отличаются, то проверяем правильность нового пароля.
					if ($addu_data['password'] != '' && getBillingPasswordHash($addu_data_login, $addu_data['password']) != $partners_list[$edit_user_id]['password'])
					{
						if (!isBillingPass($addu_data['password']))
							$addu_error['password'] = '<div class="field-error">Может содержать только символы латинского алфавита и цифры, от 5 до 20 знаков.</div>';
						else
						{
							$addu_data_password = $addu_data['password'];
							$save_query['password'] = getBillingPasswordHash($addu_data_login, $addu_data_password);
						}
					}

					// Название.
					if ($addu_data_name != $addu_data['name'] && !isBillingUserName($addu_data['name']))
						$addu_error['name'] = '<div class="field-error">Может содержать только символы русского и латинского алфавита, цифры, точку, тире и нижнее подчёркивание, от 1 до 25 символов.</div>';
					else
					{
						$addu_data_name = $addu_data['name'];
						$save_query['name'] = $addu_data_name;
					}

					// API ключ.
					if ($addu_data_api != $addu_data['apikey'] && !isBillingUserAPI($addu_data['apikey']))
						$addu_error['apikey'] = '<div class="field-error">Может содержать только символы латинского алфавита и цифры, от 6 до 20 знаков.</div>';
					else
					{
						$addu_data_api = $addu_data['apikey'];
						$save_query['apikey'] = $addu_data_api;
					}

					// Статус.
					if ($addu_data_status != $addu_data['status'])
					{
						$addu_data_status = (int) $addu_data['status'];

						if ($addu_data_status != 1)
							$addu_data_status = 0;

						$save_query['status'] = $addu_data_status;
					}

					// Процентная ставка.
					if ($addu_data_percent != $addu_data['percent'])
					{
						$addu_data_percent_new = (int) $addu_data['percent'];

						if ($addu_data_percent_new < 0 || $addu_data_percent_new > 100)
							$addu_error['percent'] = '<div class="field-error">Должна быть от 0 до 100 процентов.</div>';
						else
						{
							$addu_data_percent = $addu_data_percent_new;
							$save_query['percent'] = $addu_data_percent;
						}
					}

					// Стоимость 1 круга CS 1.6.
					if ($addu_data_boost_cs != $addu_data['boost_cs'])
					{
						$addu_data_boost_cs_new = (int) $addu_data['boost_cs'];

						if ($addu_data_boost_cs_new <= 0)
							$addu_error['boost_cs'] = '<div class="field-error">Стоимость не может быть нулевой.</div>';
						else
						{
							$addu_data_boost_cs = $addu_data_boost_cs_new;
							$save_query['boost_price_cs'] = $addu_data_boost_cs;
						}
					}

					// Стоимость 1 круга CSS.
					if ($addu_data_boost_css != $addu_data['boost_css'])
					{
						$addu_data_boost_css_new = (int) $addu_data['boost_css'];

						if ($addu_data_boost_css_new <= 0)
							$addu_error['boost_css'] = '<div class="field-error">Стоимость не может быть нулевой.</div>';
						else
						{
							$addu_data_boost_css = $addu_data_boost_css_new;
							$save_query['boost_price_css'] = $addu_data_boost_css;
						}
					}

					// Если ошибок нету, сохраняем информацию в бд.
					if (!count($addu_error))
					{
						if (setBillingUserSave($save_query, $edit_user_id))
							header('Location: '.$main_billingpage_link.'&edit_user='.$edit_user_id.'&edit_user_success=2');
					}
				}
			}
		}

		// Сообщение об успешном удалении/редактировании/добавлении
		$edit_result_success = '';

		if (isset($_GET['edit_user_success']))
		{
			$edit_result_status = (int) $_GET['edit_user_success'];

			switch ($edit_result_status) {
				case 1:
					$edit_result_success = '<div class="form-success">Новый партнёр успешно добавлен!</div>';
					break;

				case 2:
					$edit_result_success = '<div class="form-success">Информация успешно сохранена!</div>';
					break;

				case 3:
					$edit_result_success = '<div class="form-success">Партнёр успешно удалён!</div>';
					break;
			}	
		}
	}

	/*
	 *	Данные для графика.
	 */
	$graph = getBillingGraphData(0, $partners_list);
}