<?php
/**
 * Класс для получения данных о городах и барах
 *
 * Class BarCityUtils
 */

abstract class BarCityUtils
{

    /**
     * Блокируем конструтор
     */
    protected function __construct()
    {
    }

    /**
     * Блокируем клонирование
     */
    protected function __clone()
    {
    }


    /**
     * Получаем массив с айдишниками тестовых баров
     * @return Array
     */
    public static function get_testing_bars() {
        $pdo = SPDO::get_exception();
        $pdo->query('SELECT DISTINCT `bar_id` FROM `bars_settings` WHERE `name` = "test_flag" AND `value` = 1 AND `active`=1');
        return $pdo->fetch_column();
    }


    /**
     * Получаем бары с включённой кнопкой delivery club
     * ОСНОВНОЕ ВХОЖДЕНИЕ! все прочие ссылаем на него!
     * @return array|bool
     */
    public static function get_dd_bars() {
        $pdo = SPDO::get_exception();
        $pdo->query('SELECT DISTINCT `bar_id` FROM `bars_settings` WHERE `name`="delivery_club" AND `value`=1 AND `active`=1');
        return $pdo->fetch_column();
    }


    /**
     * Получаем все бары, у которых настройка $name имеет значение $value
     * @param string $name - название настройки
     * @param mixed $value - требуемое значение (в формате в каком оно пишется в БД TODO конвертировать формат)
     * @return array|bool
     */
    public static function get_bars_by_setting($name,$value) {

        $config = UtilsAdv::getProjectConfigValue('bars.settings');
        if (!isset($config[$name])) return array();
        $setting = $config[$name];

        $pdo = SPDO::get_exception();
        $pdo->str = 'SELECT DISTINCT `bar_id` FROM `bars_settings` WHERE `name`=:name AND `value`=:value AND `active`=1';
        $pdo->bindData('name',$name);
        $pdo->bindData('value',$value);
        $pdo->execute();
        $bars = $pdo->fetch_column();

        if ($value!=$setting['value']) return $bars;

        // если требуемое значение соответствует дефолтному, то нам ещё нужны живые бары, где не установлено или неактивно
        $pdo->str =
            'SELECT DISTINCT `id` FROM (
                SELECT `bars`.`id`, `bars_settings`.`value`, `bars_settings`.`active`
                FROM `bars`
                LEFT JOIN `bars_settings` ON
                    `bars_settings`.`bar_id`=`bars`.`id` AND
                    `bars_settings`.`name`=:name AND
                '.UtilsAdv::$sync_bars_query_join.') as `bb`
            WHERE `value` IS NULL OR `active`=0';
        $pdo->bindData('name',$name);
        $pdo->execute();

        $bars = array_unique(array_merge($bars,$pdo->fetch_column()));
        return $bars;

    }


    /**
     * Получаем информацию о городе по его идентификатору
     * @param int $city_id - идентификатор города
     * @return array - информация о городе
     * @throws Exception - исключение, если город не найден
     */
    protected static function get_city_info($city_id) {
        $pdo = SPDO::get_exception();
        $pdo->query('SELECT * FROM `citys` WHERE `id` = :city_id', array('city_id' => $city_id));
        $city_info = $pdo->fetch_row();
        if (!$city_info) {
            throw new Exception('City not found');
        }
        return $city_info;
    }


    public static function get_bar_time($bar_id, $dt = NULL) {
        // Получаем информацию о баре
        $bar_info = self::get_bar_info($bar_id);

        // Получаем информацию о городе, в котором находится бар
        $city_info = self::get_city_info($bar_info['city_id']);

        // Получаем смещение часового пояса города относительно Московского времени
        $timezone_offset = $city_info['time_diff'] / 60; // переводим минуты в часы

        // Преобразуем переданное время к часовому поясу бара
        if ($dt === NULL) {
            $dt = gmdate('Y-m-d H:i:s'); // текущее время в формате ISO
        }
        $dt_timestamp = strtotime($dt);
        if ($dt_timestamp === false) {
            throw new Exception('Invalid datetime format');
        }
        $bar_time_timestamp = $dt_timestamp + ($timezone_offset * 3600); // добавляем смещение в секундах
        $bar_time_iso = gmdate('Y-m-d H:i:s', $bar_time_timestamp); // преобразуем к формату ISO

        return $bar_time_iso;
    }



    /**
     * Получаем объект с информацией о баре (WIP)
     * Если $fields пустая строка, выбираем всё, если строка - выбираем столбец под данным названием
     * @param $bar_id
     * @param $fields
     * @return Array
     * @throws Exception - осторожнее, если бар не найден, будет вам исключение!
     */
    public static function get_bar_info($bar_id, $fields = '') {
        $pdo = SPDO::get();

        // TODO: включить возможность передать массив в качестве параметра fields
        $fields_str = '*';
        if ($fields != '') {
            $fields_str = $fields;
        }
        $pdo->query("SELECT ".$fields_str." FROM `bars` WHERE `id` = :bar_id",array("bar_id" => $bar_id));
        if($pdo->num_rows == 0) throw new Exception('Bar not found');

        if (is_string($fields) && $fields != '') {
            $r = $pdo->fetch_row();
            return $r[$fields];
        } else {
            return $pdo->fetch_row();
        }
    }

}