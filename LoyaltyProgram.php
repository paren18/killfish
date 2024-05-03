<?php

class LoyaltyProgram
{
    private $mysqli;

    public function __construct($db_host, $db_user, $db_pass, $db_name)
    {
        $this->mysqli = new mysqli($db_host, $db_user, $db_pass, $db_name);

        if ($this->mysqli->connect_error) {
            die('Ошибка подключения к базе данных: ' . $this->mysqli->connect_error);
        }
    }

    public function __destruct()
    {
        $this->mysqli->close();
    }

    public function processLoyalty()
    {
        // Определение текущего дня недели
        $current_day = date('l');

        // Если сегодня понедельник
        if ($current_day == 'Monday') {
            // Получаем список гостей, которые посетили бары в пятницу и субботу
            $guests = $this->getEligibleGuests();

            // Перебираем каждого гостя
            foreach ($guests as $guest) {
                $total_price = $this->getTotalPriceOfGuest($guest['id']);

                // Если сумма чеков больше или равна 1500 рублей, начисляем 300 рублей
                if ($total_price >= 150000) {
                    $this->awardMoneyToGuest($guest['id']);
                }
            }
        } else {
            echo 'Сегодня не понедельник, начисление денег не производится.';
        }
    }

    private function getEligibleGuests()
    {
        $query = "
            SELECT DISTINCT u.id
            FROM users u
            JOIN checks c ON u.id = c.user_id
            WHERE WEEKDAY(c.add_time) IN (4, 5) AND u.active = 1
        ";

        $result = $this->mysqli->query($query);

        $guests = [];
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $guests[] = $row;
            }
            $result->free();
        } else {
            echo 'Ошибка выполнения запроса: ' . $this->mysqli->error;
        }

        return $guests;
    }

    private function getTotalPriceOfGuest($user_id)
    {
        $query = "
            SELECT SUM(price) AS total_price
            FROM checks
            WHERE user_id = $user_id
        ";

        $result = $this->mysqli->query($query);
        $row = $result->fetch_assoc();
        $total_price = $row['total_price'];

        return $total_price;
    }

    private function awardMoneyToGuest($user_id)
    {
        $query = "
            INSERT INTO money (user_id, dt, sum)
            VALUES ($user_id, NOW(), 30000)
        ";

        $this->mysqli->query($query);
    }
}

$loyaltyProgram = new LoyaltyProgram('localhost', 'username', 'password', 'database');
$loyaltyProgram->processLoyalty();


