<?php
// config.php

// Настройки подключения к базе данных
$db   = 'bets';
$host = 'dpg-d4847mbipnbc73d8hlm0-a.singapore-postgres.render.com';
$user = 'user';
$pass = 'RFa4bfOpswyRFBK3cZvaU9okEORsFxYO';

$dsn  = "pgsql:host=$host;dbname=$db";

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (PDOException $e) {
    // В реальном приложении здесь лучше логировать ошибку, а не выводить ее пользователю
    die('Ошибка подключения к базе данных: ' . $e->getMessage());
}

// Функция для перевода названий столбцов
function translate($column) {
static $map = [
        // Clients (Клиенты)
        'client_id' => 'ID клиента',
        'full_name' => 'ФИО клиента',
        'phone' => 'Телефон',
        'email' => 'E-mail',
        'address' => 'Адрес',

        // Employees (Сотрудники)
        'employee_id' => 'ID сотрудника',
        'full_name' => 'ФИО сотрудника',
        'position' => 'Должность',
        'phone' => 'Телефон',
        'salary' => 'Зарплата',
        'password' => 'Пароль',

        // Events (События)
        'event_id' => 'ID события',
        'event_name' => 'Название события',
        'event_date' => 'Дата события',
        'location' => 'Место проведения',
        'created_by' => 'ID создавшего сотрудника',

        // Bets (Ставки)
        'bet_id' => 'ID ставки',
        'client_id' => 'ID клиента',
        'event_id' => 'ID события',
        'bet_amount' => 'Сумма ставки',
        'bet_type' => 'Тип ставки',
        'odds' => 'Коэффициент',
        'placed_at' => 'Время размещения ставки',

        // Payouts (Выплаты)
        'payout_id' => 'ID выплаты',
        'bet_id' => 'ID ставки',
        'payout_amount' => 'Сумма выплаты',
        'payout_date' => 'Дата выплаты',

        // Event_Stats (Статистика событий)
        'stat_id' => 'ID статистики',
        'event_id' => 'ID события',
        'description' => 'Описание статистики',
        'value' => 'Значение',
    ];
    return $map[$column] ?? ucfirst(str_replace('_', ' ', $column));
}
?>
