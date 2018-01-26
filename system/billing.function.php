<?php
/**
 *	Функции по биллинг системе.
 *
 *	@version 12.3.7 by 26.01.2015 1:05
 *	@author Dmitriy Verkhoumov
 */

/**
 *	Проверка логина.
 *
 *	@param $data string
 *	@return bool
 */
function isBillingLogin($data) {
	$data = (string) $data;

	if (!preg_match('#[^\d\w\.\-\_]#i', $data))
	{
		if (strlen($data) >= 3 && strlen($data) <= 20)
			return true;
	}
	
	return false;
}

/**
 *	Проверка пароля.
 *
 *	@param $data string
 *	@return bool
 */
function isBillingPass($data) {
	$data = (string) $data;

	if (!preg_match('#[^\d\w]#i', $data))
	{
		if (strlen($data) >= 5 && strlen($data) <= 20)
			return true;
	}
	
	return false;
}

/**
 * Проверка названия.
 *
 *	@param $data string
 *	@return bool
 */
function isBillingUserName($data) {
	$data = (string) $data;

	if (!preg_match('#[^\d\w\sЕё\.\-\_]#ui', $data))
	{
		if (mb_strlen($data, 'UTF-8') >= 1 && mb_strlen($data, 'UTF-8') <= 25)
			return true;
	}
	
	return false;
}

/**
 * Проверка API ключа.
 *
 *	@param $data string
 *	@return bool
 */
function isBillingUserAPI($data) {
	$data = (string) $data;

	if (!preg_match('#[^\d\w]#i', $data))
	{
		if (strlen($data) >= 6 && strlen($data) <= 20)
			return true;
	}
	
	return false;
}

/**
 * Проверка даты из поиска.
 *
 *	@param $data string
 *	@return bool
 */
function isBillingSearchDate($data) {
	$data = (string) $data;

	if (preg_match('#^([\d]{4}-[0-1][\d]-[0-3][\d])$#i', $data))
		return true;
	
	return false;
}

/**
 *	Шифрование пароля.
 *
 *	@param $login string
 *	@param $password string
 *	@return string
 */
function getBillingPasswordHash($login, $password) {
	$result = '';
	$salt = 'cK@2$wp#4V6;s!8';

	$login = (string) $login;
	$password = (string) $password;

	$result = sha1($login.sha1($password).$salt);

	return $result;
}

/**
 *	Проверка указанных данных на существование.
 *
 *	@param $login string
 *	@param $password string
 *	@return bool/array
 */
function isBillingUser($login, $password) {
	// Проверка входных данных.
	if (!isBillingLogin($login) || !isBillingPass($password))
		return false;

	// Шифрование.
	$hash_pass = getBillingPasswordHash($login, $password);

	// Название таблицы со списком партнёров.
	$billing_db = DB_PARTNERS_LIST_NAME;

	// Поиск пользователя.
	$user = array_sql_bd("SELECT * FROM `$billing_db` WHERE `login`='$login' AND `password`='$hash_pass' AND `status`>0");

	if (empty($user['id']))
		return false;

	return $user;
}

/**
 *	Получить информацию о пользователе по его ID.
 *
 *	@param $user_id integer
 *	@return array
 */
function isBillingUserID($user_id) {
	$user_id = (int) $user_id;

	// Название таблицы со списком партнёров.
	$billing_db = DB_PARTNERS_LIST_NAME;

	// Поиск пользователя.
	$user = array_sql_bd("SELECT * FROM `$billing_db` WHERE `id`='$user_id'");

	if (empty($user['id']))
		return false;

	return $user;
}

/**
 *	Список партнёров.
 *	
 *	@param null
 *	@return array
 */
function getBillingUsers() {
	$result = array();

	// Название таблицы со списком партнёров.
	$billing_db = DB_PARTNERS_LIST_NAME;

	// Загрузка всех партнёров.
	$users = sql_bd("SELECT * FROM `$billing_db` ORDER BY `id` ASC");

	// Массив со всеми партнёрами.
	while ($data = mysqli_fetch_array($users))
		$result[$data['id']] = $data;

	return $result;
}

/**
 *	Список API ключей партнёров.
 *
 *	@param null
 *	@return array
 */
function getBillingUsersAPI() {
	$result = array();
	$users = getBillingUsers();

	foreach ($users as $key => $value)
		$result[$key] = $value['apikey'];
	
	return $result;
}

/**
 *	Список цен на 1 круг буста для разных типов 
 *	серверов у каждого партнёра.
 *
 *	@param null
 *	@return array
 */
function getBillingUsersPrices() {
	$result = array();
	$users = getBillingUsers();

	foreach ($users as $key => $value)
		$result[$key] = array('cs' => $value['boost_price_cs'], 'css' => $value['boost_price_css']);
	
	return $result;
}

/**
 *	Общее кол-во заказов.
 *	
 *	@param $user_id integer
 *	@return integer
 */
function getBillingUserOrdersCount($user_id = 0) {
	$filter = '';

	$user_id = (int) $user_id;

	// Название таблицы со списком заказов.
	$billing_db = DB_PARTNERS_ORDERS_NAME;

	// Фильтр.
	if ($user_id > 0)
		$filter = ' WHERE `id_partner`='.$user_id;

	// Загрузка заказов по ID партнёра.
	$result = array_sql_bd("SELECT COUNT(`id`) as `limit`, SUM(`price`) as `summary` FROM `$billing_db`{$filter}");

	return $result;
}

/**
 *	Список заказов.
 *
 *	@param $user_id integer
 *	@param $limit_start integer
 *	@param $limit_count integer
 *	@return array
 */
function getBillingUserOrders($user_id = 0, $limit_start = 0, $limit_count = 5) {
	$result = array();
	$filter = '';

	$user_id = (int) $user_id;
	$limit_start = (int) $limit_start;
	$limit_count = (int) $limit_count;

	// Фильтр.
	if ($user_id > 0)
		$filter = ' WHERE `id_partner`='.$user_id;
 
	// Название таблицы со списком заказов.
	$billing_db = DB_PARTNERS_ORDERS_NAME;

	// Загрузка заказов по ID партнёра.
	$orders = sql_bd("SELECT * FROM `$billing_db`{$filter} ORDER BY `datetime` DESC LIMIT $limit_start, $limit_count");

	// Массив со всеми заказами.
	while ($data = mysqli_fetch_array($orders))
		$result[] = $data;

	return $result;
}

/**
 *	Список заказов в поиске.
 *
 *	@param $user_id integer
 *	@param $start integer
 *	@param $end integer
 *	@return array
 */
function getBillingSearchOrders($user_id, $start, $end) {
	$result = array();
	$filter = '';

	$user_id = (int) $user_id;
	$start = (int) $start;
	$end = (int) $end;
 
	// Название таблицы со списком заказов.
	$billing_db = DB_PARTNERS_ORDERS_NAME;

	// Загрузка заказов по ID партнёра.
	$orders = sql_bd("SELECT * FROM `$billing_db` WHERE `id_partner`=$user_id AND UNIX_TIMESTAMP(`datetime`)>$start AND UNIX_TIMESTAMP(`datetime`)<$end ORDER BY `datetime` DESC");

	// Массив со всеми заказами.
	while ($data = mysqli_fetch_array($orders))
		$result[] = $data;

	return $result;
}

/**
 *	Общая сумма заказов.
 *
 *	@param $data array
 *	@return integer
 */
function getBillingSearchSummary($data) {
	$result = 0;
	$data = (array) $data;

	foreach ($data as $info)
		$result += $info['price'];

	return $result;
}

/**
 *	Перегруппировка списка заказов на дни.
 *
 *	@param $array array
 *	@param $percent integer/array
 *	@return array
 */
function getBillingOrdersByDays($array, $percent) {
	$result = array();
	$array = (array) $array;

	if (!count($array))
		return $array;

	// Нулевая точка сегодняшнего дня.
	$date_diff = 60*60*3;
	$start_day = mktime(0, 0, 0, gmdate('m'), gmdate('d'), gmdate('Y')) + 60*60*1;

	foreach ($array as $order)
	{
		$time = strtotime($order['datetime']) + $date_diff;

		if ($time < $start_day)
			$start_day = mktime(0, 0, 0, gmdate('m', $time), gmdate('d', $time), gmdate('Y', $time)) + 60*60*1;

		if (is_array($percent))
			$perc = $percent[$order['id_partner']]['percent'];
		else
			$perc = $percent;
			
		$result[$start_day]['orders_list'][] = $order;
		$result[$start_day]['orders_count']++; // Кол-во заказов
		$result[$start_day]['checkout'] = $result[$start_day]['checkout'] + ($order['price'] * ($perc / 100)); // Сумма необходимых выплат
		$result[$start_day]['checkin'] = $result[$start_day]['checkin'] + ($order['price'] * ((100 - $perc) / 100)); // Прибыль
	}

	return $result;
}

/**
 *	Кол-во заказов и общая прибыль за месяц.
 *
 *	@param $user_id integer
 *	@return integer
 */
function getBillingOrdersMonthsStats($user_id) {
	$result = 0;

	// Время первого дня текущего месяца.
	$start_month = mktime(0, 0, 0, gmdate('m'), 0, gmdate('Y')) + 60*60*1;

	// Название таблицы со списком заказов.
	$billing_db = DB_PARTNERS_ORDERS_NAME;

	// Загрузка заказов по ID партнёра.
	$orders = array_sql_bd("SELECT COUNT(`id`) as `limit`, SUM(`price`) as `summary` FROM `$billing_db` WHERE `id_partner`=$user_id AND UNIX_TIMESTAMP(`datetime`)>$start_month");
	$result = $orders;

	return $result;
}

/**
 *	Общее кол-во выплат.
 *
 *	@param $user_id integer
 *	@return integer
 */
function getBillingUserPaymentsCount($user_id) {
	$result = 0;
	$filter = '';

	// Название таблицы со списком выплат.
	$billing_db = DB_PARTNERS_PAYMENTS_NAME;

	if ($user_id > 0)
		$filter = ' WHERE `partner_id`='.$user_id;

	// Загрузка заказов по ID партнёра.
	$payments = array_sql_bd("SELECT COUNT(id) as `limit` FROM `$billing_db`{$filter}");
	$result = $payments['limit'];

	return $result;
}

/**
 *	Список выплат.
 *
 *	@param $user_id integer
 *	@param $limit_start integer
 *	@param $limit_count integer
 *	@return array
 */
function getBillingUserPayments($user_id, $limit_start = 0, $limit_count = 5) {
	$result = array();
	$filter = '';

	$user_id = (int) $user_id;
	$limit_start = (int) $limit_start;
	$limit_count = (int) $limit_count;
 
	// Название таблицы со списком выплат.
	$billing_db = DB_PARTNERS_PAYMENTS_NAME;

	if ($user_id > 0)
		$filter = ' WHERE `partner_id`='.$user_id;

	// Загрузка заказов по ID партнёра.
	$payments = sql_bd("SELECT * FROM `$billing_db`{$filter} ORDER BY `paydate` DESC LIMIT $limit_start, $limit_count");

	// Массив со всеми партнёрами.
	while ($data = mysqli_fetch_array($payments))
		$result[] = $data;

	return $result;
}

/**
 *	Формирование даты в читабельном виде.
 *
 *	$format (по-умолчанию: [@d @t2] = [сегодня в 16:22]):
 *	@d - day (сегодня, 17 ноября)
 *	@t1 - time (18:35)
 *	@t2 - time (в 18:35)
 *	@y - year (2014)
 *
 *	$size (по-умолчанию: [b] = [Сегодня]):
 *	s - small (сегодня)
 *	b - big (Сегодня)
 *
 *	@param $time integer / Временная метка
 *	@param $format string / Формат получения результата
 *	@param $size string / Размер первой буквы
 *
 *	@return string
 */
function getNormalDate($time, $format = '@d @t2', $size = 'b')
{
	// Исходные данные.
	$date_diff = 60*60*3; // Разница 3 часа из-за ЧП.
	$start_day = mktime(0, 0, 0, gmdate('m'), gmdate('d'), gmdate('Y')) + 60*60*1;

	// Входные данные.
	$time = (int) $time;
	$format = (string) $format;
	$size = (string) $size;

	$time += $date_diff;

	/*
	 *	Проверка данных.
	 */
	if ($format == '' || preg_match('#[^dty12\@\s\,]+#i', $format))
		return false;

	if ($size != 'b' && $size != 's')
		$size = 'b';

	/*
	 *	Если требуется указать день, проводим анализ временной метки
	 *	и выбираем наиболее подходящее слово.
	 */
	if (strpos($format, '@d') !== false)
	{
		// Например: сегодня.
		if ($time > ($start_day - 86400) && $time < ($start_day + 86400 * 2))
		{
			// Время, когда заканчивается вчерашний, текущий и 
			// завтрашний дни.
			$end_day_previous = $start_day;
			$end_day_current = $start_day + 86400;
			$end_day_next = $start_day + 86400 * 2;

			$days = array(
				array('s_name' => 'вчера', 'b_name' => 'Вчера', 'time' => $end_day_previous),
				array('s_name' => 'сегодня', 'b_name' => 'Сегодня', 'time' => $end_day_current),
				array('s_name' => 'завтра', 'b_name' => 'Завтра', 'time' => $end_day_next)
			);

			// Выбираем нужный день и формат его написания.
			foreach ($days as $day_name)
			{
				if ($time <= $day_name['time'])
				{
					$current_name = $day_name[$size.'_name'];
					$format = str_replace('@d', $current_name, $format);

					break;
				}
			}
		}

		// Например: 17 ноября.
		else
		{
			$months = array(1 => 'января', 'февраля', 'марта', 'апреля', 'мая', 'июня', 'июля', 'августа', 'сентября', 'октября', 'ноября', 'декабря');

			// Название месяца, указанного во временной метке.
			$current_month_number = gmdate('n', $time);
			$current_month = $months[$current_month_number];

			// Число, указанное во временной метке.
			$current_month_day = gmdate('j', $time).' ';

			// Формирование результата.
			$format = str_replace('@d', $current_month_day.$current_month, $format);
		}
	}

	/*
	 *	Определение формата основной временной метки: года, часы и минуты.
	 */
	$year = gmdate('Y', $time);
	$hour = gmdate('H:i', $time);
	$tmpl_timestamp = array('@t1' => $hour, '@t2' => 'в '.$hour, '@y' => $year);

	foreach ($tmpl_timestamp as $pattern => $timestamp)
		$format = str_replace($pattern, $timestamp, $format);

	return $format;
}

/**
 *	Подбор слова или окончания по числу.
 *
 *	Параметр $mode отвечает за режим функции:
 *		1 - 25 серверов, 1 сервер, 2 сервера;
 *		2 - 25-ый, 1-ый, 2-ой, 3-ий.
 *
 *	Параметр $style отвечает за оформление:
 *		1 - классическое: 75123 рубля;
 *		2 - в виде стоимости: 75 123 рубля.
 *
 *	Текущая версия функции является наиболее производительной.
 *	С первых версий скорость работы увеличилась в 3 раза.
 *
 *	@param $number integer / Число
 *	@param $array array / Список слов, подставляемых к числу
 *	@param $mode integer / Режим обработки
 *	@param $style integer / Стиль результата
 *
 *	@return integer/string
 */
function getWordByNumber($old_number, $array, $mode = 1, $style = 1)
{
	// Входные данные.
	$number = (int) $old_number;
	$array = (array) $array;
	$mode = (int) $mode;
	$style = (int) $style;

	// Исходные данные.
	$two_symbols = 0;
	$number_separator = ' ';
	$result = $old_number;

	// Если набор с приставочными словами отсутствует,
	// функция прекращает свою работу возвращая обратно
	// исходное число.
	if (!count($array))
		return $result;

	// Кол-во цифр в числе.
	$number_length = strlen($number);

	// Одно и два последних числа.
	if ($number_length === 1)
		$one_symbol = $number;
	else
		$one_symbol = substr($number, $number_length - 1);

	if ($number_length > 1)
		$two_symbols = substr($number, $number_length - 2);

	// Настройки, используемые в выбранном режиме.
	if ($mode === 1)
		// 25 серверов, 3 сервера, 1 сервер.
		$pattern = array(
			array(11 => '', 12 => '', 13 => '', 14 => '', 0 => '', 5 => '', 6 => '', 7 => '', 8 => '', 9 => ''),
			array(2 => '', 3 => '', 4 => ''),
			array(1 => '')
		);
	else
		// 45-ый, 46-ой, 23-ий.
		$pattern = array(
			array(12 => '', 13 => '', 16 => '', 17 => '', 18 => '', 1 => '', 4 => '', 5 => '', 9 => '', 0 => ''),
			array(0 => '', 2 => '', 6 => '', 7 => '', 8 => ''),
			array(3 => '')
		);

	foreach ($pattern as $id => $ends)
	{
		if (isset($ends[$one_symbol]) || ($two_symbols > 0 && isset($ends[$two_symbols])))
		{
			// Число в формате "180 735" тысячи рублей, то есть
			// с пробелом между каждой тройкой цифр из целой части.
			if ($style !== 1 && $mode !== 1)
				$number = number_format($number, 0, '.', ' ');

			// Тип отступа между числом и словом.
			if ($mode !== 1)
				$number_separator = '-';

			// Сборка результата.
			$result .= $number_separator;
			$result .= $array[$id];

			break;
		}
	}

	return $result;
}

/**
 *	Генерация кода.
 *
 *	@param $length integer / Длина генерируемого кода
 *	@param $pattern string / Пользовательские используемые символы
 *	@param $numeric integer / Использование цифр, 1 - да, 0 - нет
 *	@param $latin integer / Использование букв, 1 - да, 0 - нет
 *	@param $symbols integer / Использование символов, 1 - да, 0 - нет
 *	@param $size integer / Регистр, 1 - и большой и маленький, 0 - маленький
 *
 *	@return string
 */
function getRandomString($length = 20, $pattern = '', $numeric = 1, $latin = 1, $symbols = 1, $size = 1)
{
	// Входные данные.
	$length = (int) $length;
	$pattern = (string) $pattern;
	$numeric = (int) $numeric;
	$latin = (int) $latin;
	$symbols = (int) $symbols;
	$size = (int) $size;

	// Если набор используемых символов не был передан, используем
	// весь базовый набор.
	if ($pattern == '')
	{
		if ($numeric)
			$pattern .= '1234567890';

		if ($latin)
			$pattern .= 'abcdefghijklmnopqrstuvwxyz';

		if ($symbols)
			$pattern .= '?_!#@$^&+=-./[]{}<>:;';

		if ($size)
			$pattern .= 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
	}

	// Перемешиваем набор символов случайным
	// образом.
	$pattern = str_shuffle($pattern);

	// Исходные данные.
	$result = '';
	$history = $pattern;
	$code_length = strlen($pattern) - 1;

	// Если запрошена нулевая длина, заменяем её на базовую.
	if ($length < 1)
		$length = 20;

	// Генерация кода.
	for ($i = 0; $i < $length; $i++)
	{
		// Начиная со второго подбора случайного символа
		// длина набора сокращается на 1 символ, так как
		// убирается использованный в прошлый раз.
		if ($i === 1)
			--$code_length;

		// Выбор случайной позиции для выборки.
		$casual = mt_rand(0, $code_length);
		$result .= $history[$casual];

		// Пересохранение шаблона без использованного символа.
		$history = str_replace($history[$casual], '', $pattern);
	}

	return $result;
}

/**
 *	Ссылка для пагинатора.
 *
 *	@param $get string
 *	@param $page integer
 *	@param $type string
 *	@return string
 */
function createBillingPaginatorLink($get, $page, $type)
{
	$result = '';
	$page_number = 0;

	$link = $_SERVER['REQUEST_URI'];
	$get = (string) $get;
	$type = (string) $type;
	$page = (int) $page;

	switch ($type) {
		case 'next':
			$page_number = $page + 1;
			break;

		case 'prev':
			$page_number = $page - 1;
			break;
	}

	// Если страница есть в ссылке.
	if (strpos($link, $get))
	{
		$result = preg_replace('#'.$get.'=([0-9]+)#', $get.'='.$page_number, $link);
	}

	// Если нету, добавляем в конец.
	else
	{
		$result = $link.'&'.$get.'='.$page_number;
	}

	return $result;
}

/**
 *	Пагинатор.
 *
 *	@param $current integer
 *	@param $count integer
 *	@param $get_name string
 *	@return string
 */
function getBillingPaginator($current, $count, $get_name = 'page') {
	$result = '';

	$current = (int) $current;
	$count = (int) $count;
	$get_name = (string) $get_name;

	$result .= '<div class="paginator">';

	// Следующая страница.
	$next_link = createBillingPaginatorLink($get_name, $current, 'next');

	if ($current >= $count)
		$result .= '<div class="button right" title="Следующая страница"></div>';
	else
		$result .= '<a href="'.$next_link.'" class="button right" title="Следующая страница"></a>';

	// Предыдущая страница.
	$prev_link = createBillingPaginatorLink($get_name, $current, 'prev');

	if ($current <= 1)
		$result .= '<div class="button left" title="Предыдущая страница"></div>';
	else
		$result .= '<a href="'.$prev_link.'" class="button left" title="Предыдущая страница"></a>';

	$result .= '</div>';

	return $result;
}

/**
 *	Создание select'а для выпадающего списка
 *	с партнёрами.
 *
 *	@param $users array
 *	@param $current_value string/integer
 *	@param $type integer
 *	@return string
 */
function getBillingPartnersSelectList($users, $current_value, $type = 0) {
	$result = '';
	$users = (array) $users;

	if ($type == 0)
		$result .= '<option value="0">Выбрать партнёра</option>';
	else
		$result .= '<option value="0">Администратор</option>';

	foreach ($users as $info)
	{
		$disabled = '';
		$selected = '';

		// Пропускаем админа.
		if ($info['status'] == 2)
			continue;

		// Неактивные партнёры.
		if (!$info['status'])
		{
			$disabled = ' disabled';

			if ($type == 0)
				$info['name'] .= ' [Отключен]';
		}
		elseif ($type == 0)
			$info['name'] .= ' [Должен '.floor($info['percent_balance']).' руб.]';

		// Выбранный пользователь.
		if ($info['id'] == $current_value)
			$selected = ' selected';

		$result .= '<option value="'.$info['id'].'"'.$selected.$disabled.'>'.$info['name'].'</option>';		
	}

	return $result;
}

/**
 *	Обнуление баланса партнёра.
 *
 *	@param $user_id integer
 *	@param $user_summ integer
 *	@param $percent integer
 *	@param $percent_balance float
 *	@return bool
 */
function setBillingUserBalanceToNull($user_id, $user_summ, $percent, $percent_balance = 0) {
	$user_id = (int) $user_id;
	$user_summ = (int) $user_summ;
	$percent = (int) $percent;
	$percent_balance = (float) $percent_balance;

	// Название таблицы со списком партнёров.
	$billing_db = DB_PARTNERS_LIST_NAME;

	// Подготовка сумм.
	if ($user_summ >= $percent_balance)
	{
		$result_summ = 0;
		$natural_summ = 0;
	}
	else
	{
		$result_summ = $percent_balance - $user_summ;
		$natural_summ = $result_summ / ($percent / 100);
	}

	$sql = "UPDATE `$billing_db` SET `percent_balance`='$result_summ', `natural_balance`='$natural_summ' WHERE `id`='$user_id'";

	if (sql_bd($sql))
		return true;

	return false;
}

/**
 *	Создание новой выплаты.
 *
 *	@param $user_id integer
 *	@param $user_summ integer
 *	@return bool
 */
function addBillingCheckin($user_id, $user_summ) {
	$user_id = (int) $user_id;
	$user_summ = (int) $user_summ;

	// Название таблицы со списком выплат.
	$billing_db = DB_PARTNERS_PAYMENTS_NAME;

	$sql = "INSERT INTO `$billing_db` (`partner_id`, `payment`) VALUES ($user_id, $user_summ)";

	if (sql_bd($sql))
		return true;

	return false;
}

/**
 *	Получаем чистую ссылку без лишних запросов.
 *
 *	@return string
 */
function getBillingClearLink() {
	// Текущий запрос.
	$link = $_SERVER['REQUEST_URI'];

	// Что надо удалить.
	$delete = array('delete_checkin_success', 'delete_checkin', 'checkin_success', 'edit_user_success');

	// Чистим ссылку.
	foreach ($delete as $value)
		$link = preg_replace('#(&'.$value.'=[\w\d\_])#i', '', $link);

	return $link;
}

/**
 *	Информации о выплате по её ID.
 *
 *	@param $check_id integer
 *	@return bool/array
 */
function getBillingPaymentByID($check_id) {
	$check_id = (int) $check_id;

	// Название таблицы со списком выплат.
	$billing_db = DB_PARTNERS_PAYMENTS_NAME;

	$sql = "SELECT * FROM `$billing_db` WHERE `id`='$check_id'";

	if ($data = array_sql_bd($sql))
		return $data;

	return false;
}

/**
 *	Отмена выплаты (её удаление).
 *	При отмене надо вернуть исходный баланс партнёру.
 *
 *	@param $check_id integer
 *	@return bool
 */
function setBillingDeleteCheckin($check_id) {
	$check_id = (int) $check_id;
	$sql_status = 0;

	// Информация о выплате.
	if ($check_info = getBillingPaymentByID($check_id))
	{
		$partner_id = $check_info['partner_id'];
		$percent_balance = $check_info['payment'];

		/*
		 *	Восстановление баланса партнёра.
		 */
		// Процентная ставка партнёра.
		$partner = isBillingUserID($partner_id);
		$natural_balance = $percent_balance / ($partner['percent'] / 100);

		// Запрос на восстановление.
		// Название таблицы со списком партнёров.
		$billing_db = DB_PARTNERS_LIST_NAME;

		$sql = "UPDATE `$billing_db` SET `percent_balance`=(`percent_balance` + '$percent_balance'), `natural_balance`=(`natural_balance` + '$natural_balance') WHERE `id`='$partner_id'";

		if (sql_bd($sql))
			$sql_status = 1;

		if ($sql_status)
		{
			/*
			 *	Удаление выплаты.
			 */
			// Название таблицы со списком выплат.
			$billing_db = DB_PARTNERS_PAYMENTS_NAME;

			$sql = "DELETE FROM `$billing_db` WHERE `id`='$check_id'";

			if (sql_bd($sql))
				return true;
		}
	}

	return false;
}

/**
 *	Используя массив с информацией о партнёрах
 *	получаем общую сумму их долга, кол-во
 *	самих партнёров (активных).
 *
 *	@param $list array
 *	@return array
 */
function getBillingPartnersStats($list) {
	$result = array();
	$list = (array) $list;

	foreach ($list as $info)
	{
		if ($info['status'] != 1)
			continue;

		$result['checkin'] += $info['percent_balance'];
		$result['partners']++;
	}

	$result['checkin'] = floor($result['checkin']);

	return $result;
}

/**
 *	Общая прибыль в текущем месяце.
 *
 *	@param $mode integer
 *	@return integer
 */
function getBillingCheckinsSumm($mode = 0) {
	// Режим (за последний месяц/за всё время)
	$mode = (int) $mode;
	$limit = '';

	// Название таблицы со списком выплат.
	$billing_db = DB_PARTNERS_PAYMENTS_NAME;

	if ($mode)
	{
		$start_month = mktime(0, 0, 0, gmdate('m'), 0, gmdate('Y')) + 60*60*1;

		$limit = ' WHERE UNIX_TIMESTAMP(`paydate`)>'.$start_month;
	}

	$sql = "SELECT SUM(`payment`) as `summ` FROM `$billing_db`{$limit}";

	if ($data = array_sql_bd($sql))
		return floor($data['summ']);

	return 0;
}

/**
 * Добавление нового партнёра.
 *
 *	@param $login string
 *	@param $pass string
 *	@param $name string
 *	@param $api string
 *	@param $status integer
 *	@param $perc integer
 *	@param $cs integer
 *	@param $css integer
 */
function setBillingNewUser($login, $pass, $name, $status, $api, $perc, $cs, $css) {
	$login = (string) $login;
	$pass = (string) $pass;
	$name = (string) $name;
	$api = (string) $api;
	$status = (int) $status;
	$perc = (int) $perc;
	$cs = (int) $cs;
	$css = (int) $css;

	if ($status > 1)
		$status = 0;

	$hash_pass = getBillingPasswordHash($login, $pass);

	// Название таблицы со списком партнёров.
	$billing_db = DB_PARTNERS_LIST_NAME;

	$sql = "INSERT INTO `$billing_db` (`login`, `password`, `apikey`, `name`, `status`, `percent`, `boost_price_cs`, `boost_price_css`) VALUES ('$login', '$hash_pass', '$api', '$name', $status, $perc, $cs, $css)";

	if (sql_bd($sql))
		return mysql_insert_id();

	return false;
}

/**
 *	Сохранение информации о партнёре.
 *
 *	@param $data array
 *	@param $user_id integer
 *	@return bool
 */
function setBillingUserSave($data, $user_id) {
	$sql_key = $sql_value = '';
	$sql_array = array();

	$data = (array) $data;
	$user_id = (int) $user_id;

	// Формирование запроса.
	foreach ($data as $key => $value) 
		$sql_array[] = "`$key`='{$value}'";

	if (count($sql_array))
	{
		// Название таблицы со списком партнёров.
		$billing_db = DB_PARTNERS_LIST_NAME;

		$sql = "UPDATE `$billing_db` SET ";
		$sql .= implode(', ', $sql_array);
		$sql .= " WHERE `id`=$user_id";

		if (sql_bd($sql))
			return true;
	}

	return false;
}

/**
 *	Удаление аккаунта вместе со всей информацией.
 *
 *	@param $user_id integer
 *	@return bool
 */
function setBillingUserDelete($user_id) {
	$user_id = (int) $user_id;
	$status = 0;

	// Название таблицы со списком партнёров.
	$billing_db = DB_PARTNERS_LIST_NAME;
	$status = sql_bd("DELETE FROM `$billing_db` WHERE `id`=$user_id");

	if ($status)
	{
		// Название таблицы со списком выплат.
		$billing_db = DB_PARTNERS_PAYMENTS_NAME;
		$status = sql_bd("DELETE FROM `$billing_db` WHERE `partner_id`=$user_id");
	}

	if ($status)
	{
		// Название таблицы со списком заказов.
		$billing_db = DB_PARTNERS_ORDERS_NAME;
		$status = sql_bd("DELETE FROM `$billing_db` WHERE `id_partner`=$user_id");
	}

	if ($status)
		return true;

	return false;
}

/**
 *	Формирование данных для линейного графика.
 *	Сравнение кол-ва заказов и прибыли за последние 30
 *	дней и предпоследние 30 дней.
 *
 *	@param $user_id integer
 *	@param $percent integer
 *	@return array
 */
function getBillingGraphData($user_id = 0, $percent = 0) {
	$result = $stat = array();
	$perc_is_array = is_array($percent);

	$user_id = (int) $user_id;

	// Разница времен в бд.
	$timediff = 60*60*3;

	// Текущее время - 30 дней.
	$nullmonth = mktime(0, 0, 0, gmdate('m'), gmdate('d'), gmdate('Y')) + (60*60*1) - (60*60*24*30);

	// Начало сегодняшнего дня.
	$nullday = mktime(0, 0, 0, gmdate('m'), gmdate('d'), gmdate('Y')) + (60*60*1);

	// Фильтр по клиенту.
	$filter = $user_id ? '`id_partner`='.$user_id.' AND ' : '';

	// Название таблицы со списком заказов.
	$billing_db = DB_PARTNERS_ORDERS_NAME;
	$query = sql_bd("SELECT * FROM `$billing_db` WHERE {$filter}UNIX_TIMESTAMP(`datetime`)>$nullmonth ORDER BY `datetime` DESC");

	// Проходим по каждому заказу.
	while ($order = mysqli_fetch_array($query))
	{
		$time = strtotime($order['datetime']) + $timediff;

		// Минус 1 день.
		if ($time < $nullday)
			$nullday = mktime(0, 0, 0, gmdate('m', $time), gmdate('d', $time), gmdate('Y', $time)) + (60*60*1);

		// Увеличиваем кол-во заказов за текущий день.
		$result[$nullday]['orders_count']++;

		// Увеличиваем прибыль за текущий день.
		if ($perc_is_array)
			$result[$nullday]['orders_summ'] += $order['price'] * ($percent[$order['id_partner']]['percent'] / 100);
		else
			$result[$nullday]['orders_summ'] += $order['price'] * ((100 - $percent) / 100);
	}

	$counter = 0;
	$last_summ = 0;

	foreach ($result as $day => $stats)
	{
		// Число.
		$day_number = gmdate('d', $day);

		$stat['labels'][] = $day_number;
		$stat['orders'][] = $stats['orders_count'];
		$stat['summary'][] = $stats['orders_summ'];

		if ($perc_is_array)
		{
			$opd = (round($last_summ / $stats['orders_summ'], 2) - 1) * 100;
			$stat['opd'][] = $opd > 0 ? '+'.$opd : $opd;
		}

		$last_summ = $stats['orders_summ'];
	}

	$stat['labels'] = array_reverse($stat['labels']);
	$stat['summary'] = array_reverse($stat['summary']);
	$stat['orders'] = array_reverse($stat['orders']);

	if ($perc_is_array)
	{
		$stat['opd'] = array_reverse($stat['opd']);

		array_unshift($stat['opd'], 0);
		array_pop($stat['opd']);
	}

	return $stat;
}