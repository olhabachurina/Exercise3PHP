<?php
session_start();

// Определяем пути к файлам
$countryFile = 'countries.txt';
$dictionaryFile = 'dictionary.txt';

// Функция генерации CSRF-токена
function generateCsrfToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// Функция для отображения сообщений из сессии
function displayFlashMessage($key) {
    if (!empty($_SESSION[$key])) {
        echo "<p class='{$key}'>{$_SESSION[$key]}</p>";
        unset($_SESSION[$key]);
    }
}

// Чтение допустимых стран из файла dictionary.txt
$validCountries = file($dictionaryFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

// Обработка отправки формы
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $newCountry = trim($_POST['country']);
    $csrfToken = $_POST['csrf_token'];
    $errors = [];

    // Проверка CSRF-токена
    if ($csrfToken !== $_SESSION['csrf_token']) {
        $errors[] = "Неверный CSRF токен.";
    }

    // Проверка ввода
    if (empty($newCountry)) {
        $errors[] = "Пожалуйста, введите название страны.";
    } elseif (!in_array($newCountry, $validCountries)) {
        $errors[] = "Название страны не найдено в списке допустимых стран.";
    } else {
        // Читаем уже добавленные страны
        $countries = file_exists($countryFile) ? file($countryFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) : [];

        // Проверка на дублирование страны
        if (in_array($newCountry, $countries)) {
            $errors[] = "Эта страна уже присутствует в списке.";
        } else {
            // Добавляем новую страну и сортируем список
            $countries[] = $newCountry;
            sort($countries, SORT_STRING);  // Сортировка массива стран

            // Записываем обновленный список стран в файл
            file_put_contents($countryFile, implode(PHP_EOL, $countries) . PHP_EOL);
            $_SESSION['success'] = "Страна {$newCountry} успешно добавлена!";
        }
    }

    if (!empty($errors)) {
        $_SESSION['error'] = implode('<br>', $errors);
    }

    header('Location: index.php');
    exit;
}

// Функция для отображения списка стран
function displayCountryList($countryFile) {
    if (file_exists($countryFile)) {
        $countries = file($countryFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if (!empty($countries)) {
            sort($countries, SORT_STRING); // Сортировка списка перед отображением
            echo "<h2>Список стран</h2>";
            echo "<select>";
            foreach ($countries as $country) {
                echo "<option value='" . htmlspecialchars($country) . "'>" . htmlspecialchars($country) . "</option>";
            }
            echo "</select>";
        } else {
            echo "<p>Пока что стран в списке нет.</p>";
        }
    }
}

?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Добавить страну</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>

<div class="container">
    <h1>Добавить страну</h1>

    <?php displayFlashMessage('error'); ?>
    <?php displayFlashMessage('success'); ?>

    <form action="index.php" method="POST">
        <label for="country">Введите название страны:</label><br>
        <input type="text" id="country" name="country" required><br><br>
        <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
        <input type="submit" value="Добавить">
    </form>

    <?php displayCountryList($countryFile); ?>

</div>

</body>
</html>