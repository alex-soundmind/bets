<?php
require_once 'config.php';
session_start();

// --- ОБЩИЕ ПЕРЕМЕННЫЕ ---
$is_logged_in = isset($_SESSION['user']);
$action = $_GET['action'] ?? 'list';
$table = $_GET['table'] ?? 'bets';
$id = $_GET['id'] ?? null;

// --- СЛОВАРЬ ТАБЛИЦ ---
$tables = [
    'bets' => 'Ставки',
    'clients' => 'Клиенты',
    'employees' => 'Сотрудники',
    'events' => 'События',
    'event_stats' => 'Статистика',
    'payouts' => 'Выплаты'
];

if (!isset($tables[$table])) {
    die('<p class="error">Неверная таблица</p>');
}

// --- ПОЛУЧЕНИЕ ИНФОРМАЦИИ О СТОЛБЦАХ И PK ---
try {
    $stmt = $pdo->query("SELECT * FROM $table LIMIT 0");
    $columns = [];
    for ($i = 0; $i < $stmt->columnCount(); $i++) {
        $meta = $stmt->getColumnMeta($i);
        $columns[] = $meta['name'];
    }
    $pk = $columns[0] ?? 'id';
} catch (PDOException $e) {
    die('<p class="error">Ошибка получения структуры таблицы.</p>');
}

// --- ЛОГИКА ОБРАБОТКИ ДАННЫХ (POST-ЗАПРОСЫ) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $is_logged_in) {
    $data = [];
    
    // *** НАЧАЛО: СЕРВЕРНАЯ ВАЛИДАЦИЯ ***
    foreach ($columns as $col) {
        if ($col === $pk) continue; // Не валидируем PK

        $value = $_POST[$col] ?? '';

        // Проверка на обязательные поля (все, кроме PK, считаем обязательными)
        if ($value === '') {
            $errors[] = "Поле '" . translate($col) . "' не может быть пустым.";
        }

        // Проверка числовых полей
        if (str_ends_with($col, '_id') || in_array($col, ['experience'])) {
            if (!filter_var($value, FILTER_VALIDATE_INT) && $value !== '') {
                $errors[] = "Поле '" . translate($col) . "' должно быть целым числом.";
            }
        }


        $data[$col] = $value === '' ? null : $value;
    }
    // *** КОНЕЦ: СЕРВЕРНАЯ ВАЛИДАЦИЯ ***

    if (empty($errors)) { // Если ошибок нет, сохраняем в БД
        try {
            if ($action === 'create') {
                $cols = implode(', ', array_keys($data));
                $placeholders = implode(', ', array_fill(0, count($data), '?'));
                $stmt = $pdo->prepare("INSERT INTO $table ($cols) VALUES ($placeholders)");
                $stmt->execute(array_values($data));
            } elseif ($action === 'edit' && $id) {
                // Если редактируем пользователя и пароль пустой - не обновляем его
                if ($table === 'emloyees' && empty($data['password'])) {
                    unset($data['password']);
                }
                $set_clauses = implode(', ', array_map(fn($k) => "$k = ?", array_keys($data)));
                $stmt = $pdo->prepare("UPDATE $table SET $set_clauses WHERE $pk = ?");
                $stmt->execute([...array_values($data), $id]);
            }
            header("Location: index.php?table=$table");
            exit;
        } catch (PDOException $e) {
            $errors[] = 'Ошибка сохранения данных: ' . $e->getMessage();
        }
    }
    // Если есть ошибки, скрипт продолжит выполнение и отобразит форму с ошибками
}

// --- ЛОГИКА УДАЛЕНИЯ (GET-ЗАПРОС) ---
if ($action === 'delete' && $id && $is_logged_in) {
    try {
        $stmt = $pdo->prepare("DELETE FROM $table WHERE $pk = ?");
        $stmt->execute([$id]);
    } catch (PDOException $e) {
        die('<p class="error">Ошибка удаления: ' . $e->getMessage() . '</p>');
    }
    
    header("Location: index.php?table=$table");
    exit;
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Букмекерская компания: <?= $tables[$table] ?></title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <header>
        <nav>
            <?php foreach ($tables as $tbl_name => $tbl_title): ?>
                <a href="?table=<?= $tbl_name ?>" class="<?= $table === $tbl_name ? 'active' : '' ?>"><?= $tbl_title ?></a>
            <?php endforeach; ?>
        </nav>
    </header>

    <div class="container">
        <?php if ($action === 'list'): ?>
            <h2><?= $tables[$table] ?></h2>
            <?php
            $stmt = $pdo->query("SELECT * FROM $table ORDER BY $pk");
            $rows = $stmt->fetchAll();

            if (!$rows): ?>
                <p>В этой таблице пока нет данных.</p>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <?php foreach ($columns as $col):
                                if ($table === 'emloyees' && $col === 'password' && !$is_logged_in) continue;
                            ?>
                                <th><?= translate($col) ?></th>
                            <?php endforeach; ?>
                            <?php if ($is_logged_in): ?><th>Действия</th><?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($rows as $row): ?>
                            <tr>
                                <?php foreach ($row as $key => $val):
                                    if ($table === 'emloyees' && $key === 'password' && !$is_logged_in) continue;
                                ?>
                                    <td><?= htmlspecialchars((string)$val, ENT_QUOTES) ?></td>
                                <?php endforeach; ?>

                                <?php if ($is_logged_in): ?>
                                    <td class="actions">
                                        <a href="?table=<?= $table ?>&action=edit&id=<?= $row[$pk] ?>" class="edit">✏️</a>
                                        <a href="?table=<?= $table ?>&action=delete&id=<?= $row[$pk] ?>" class="delete" onclick="return confirm('Вы уверены, что хотите удалить эту запись?')">❌</a>
                                    </td>
                                <?php endif; ?>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
            <?php if ($is_logged_in): ?>
                <a href="?table=<?= $table ?>&action=create" class="btn-add"><button>Добавить новую запись</button></a>
            <?php endif; ?>

        <?php elseif ($action === 'create' || $action === 'edit'): ?>
            <?php
            if (!$is_logged_in) die('Доступ запрещен.');

            $values = [];
            if ($action === 'edit' && $id) {
                $stmt = $pdo->prepare("SELECT * FROM $table WHERE $pk = ?");
                $stmt->execute([$id]);
                $values = $stmt->fetch();
                if (!$values) die('Запись не найдена.');
            }
            ?>
            <h2><?= $action === 'create' ? 'Добавление записи' : 'Редактирование записи' ?></h2>
            <form method="post" action="?table=<?= $table ?>&action=<?= $action ?><?= $id ? '&id='.$id : '' ?>">
                <?php foreach ($columns as $col):
                    if ($col === $pk) continue;
                    $val = $values[$col] ?? '';
                    $label = translate($col);
                    
                    $type = 'text';
                    if (str_contains($col, '_date')) $type = 'date';
                    elseif (str_contains($col, '_time')) $type = 'time';
                    elseif (in_array($col, ['bet_amount', 'odds', 'payout_amount'])) $type = 'number';
                    elseif (str_contains($col, 'email')) $type = 'email';
                    elseif (str_contains($col, 'password')) $type = 'password';
                    
                    if (str_contains($col, 'description')): ?>
                        <label for="<?= $col ?>"><?= $label ?></label>
                        <textarea id="<?= $col ?>" name="<?= $col ?>" required><?= htmlspecialchars($val) ?></textarea>
                    <?php else: ?>
                        <label for="<?= $col ?>"><?= $label ?></label>
                        <input type="<?= $type ?>" id="<?= $col ?>" name="<?= $col ?>" value="<?= htmlspecialchars($val) ?>" required>
                    <?php endif; ?>
                <?php endforeach; ?>

                <div class="form-actions">
                    <input type="submit" value="Сохранить">
                    <a href="?table=<?= $table ?>"><button type="button" class="danger">Отмена</button></a>
                </div>
            </form>
        <?php endif; ?>
    </div>

    <footer>
        <?php if (!$is_logged_in): ?>
            <a href="auth.php?mode=login">Войти</a>
        <?php else: ?>
            Пользователь: <b><?= htmlspecialchars($_SESSION['user']['name']) ?></b> | <a href="logout.php">Выйти</a>
        <?php endif; ?>
    </footer>
</body>
</html>