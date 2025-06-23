<?php
session_start(); // Начинаем сессию
require 'db_connect.php';

$data = null; // Данные для отображения
$message = ''; // Сообщение об ошибке или успехе
$iv = "encryptionVector"; 
// Функция для расшифровки данных
function decryptData($encryptedData, $key, $iv) {
    return openssl_decrypt($encryptedData, 'AES-128-CBC', $key, 0, $iv);
}

// Массив с переводами названий полей
$fieldTranslations = [
    'firstName' => 'Имя',
    'lastName' => 'Фамилия',
    'middleName' => 'Отчество',
    'POL' => 'Пол',
    'birthDate' => 'Дата рождения',
    'targetEducation' => 'Целевое обучение',
    'citizenship' => 'Гражданство',
    'birthPlace' => 'Место рождения',
    'passportSeriesNumber' => 'Паспорт (серия и номер)',
    'passportData' => 'Кем выдан паспорт',
    'passportIssueDate' => 'Дата выдачи паспорта',
    'registrationRegion' => 'Регион прописки',
    'registrationCity' => 'Населенный пункт прописки',
    'registrationAddress' => 'Адрес прописки',
    'snils' => 'СНИЛС',
    'educationBase' => 'База образования',
    'graduationYear' => 'Год окончания',
    'educationInstitution' => 'Учебное заведение',
    'educationDocument' => 'Документ об образовании',
    'educationDocumentNumber' => 'Номер документа',
    'foreignLanguage1' => 'Иностранный язык (первый)',
    'foreignLanguage2' => 'Иностранный язык (второй)',
    'phoneNumber' => 'Номер телефона',
    'disability' => 'Инвалидность',
    'guardianship' => 'Опека',
    'chaes' => 'ЧАЭС',
    'orphan' => 'Сирота',
    'priorityAdmission' => 'Первоочередной порядок зачисления',
    'specialConditions' => 'Специальные условия',
    'dormitoryRequired' => 'Требуется общежитие',
    'firstSecondaryEducation' => 'Среднее образование впервые',
    'representativeName' => 'ФИО представителя',
    'representativePhoneNumber' => 'Телефон представителя',
    'triples' => 'Количество троек',
    'quadruples' => 'Количество четверок',
    'fives' => 'Количество пятерок',
    'averageScore' => 'Средний балл',
];

//if ($_SERVER['REQUEST_METHOD'] === 'POST') {
if (isset($_POST['uniqueKey'])) {
    $uniqueKey = $_POST['uniqueKey'];
    $_SESSION['key'] = $_POST['uniqueKey'];
    $uniqueKeyHash = hash('sha256', $uniqueKey);
    // Ищем запись в базе данных по хэшу уникального ключа
    $stmt = $pdo->prepare("SELECT * FROM students WHERE uniqueKeyHash = :uniqueKeyHash");
    $stmt->execute(['uniqueKeyHash' => $uniqueKeyHash]);
    $students = $stmt->fetchAll();

    $found = false;
    foreach ($students as $student) {
          if (isset($student['uniqueKeyHash'])) {
            $found = true;
            $data = $student;
            $_SESSION['student_data'] = $data; // Сохраняем данные в сессии
            break;
        }
    }

    if (!$found) { $message = "Данные не найдены. Проверьте уникальный ключ."; }
}
// Функция для скачивания данных в формате XML
    function downloadXML($data, $uniqueKey, $iv) {
    $xml = new SimpleXMLElement('<student/>');
    foreach ($data as $key => $value) {
        if ($key !== 'id' && $key !== 'uniqueKeyHash' && $key !== 'averageScore') { // Исключаем ненужные поля
            $decryptedValue =  decryptData($value, $uniqueKey, $iv);
            $xml->addChild($key, htmlspecialchars($decryptedValue)); // Экранируем специальные символы
        }
    }
    header('Content-Type: application/xml');
    header('Content-Disposition: attachment; filename="data.xml"');
    echo $xml->asXML();
    exit();
}
// Функция для скачивания данных в формате HTML
function downloadHTML($data, $fieldTranslations) {
    $html = "<html><body><table border='1'>";
    foreach ($data as $key => $value) {
        if ($key !== 'id' && $key !== 'uniqueKeyHash') { // Исключаем ненужные поля
            $fieldName = $fieldTranslations[$key] ?? $key; // Используем перевод, если он есть
            $decryptedValue = decryptData($value, htmlspecialchars($uniqueKey), $iv); 
            $html .= "<tr><th>$fieldName</th><td>" . htmlspecialchars($decryptedValue) . "</td></tr>"; // Экранируем специальные символы
        }
    }
    $html .= "</table></body></html>";
    header('Content-Type: text/html');
    header('Content-Disposition: attachment; filename="data.html"');
    echo $html;
    exit();
}

// Обработка запроса на скачивание
if (isset($_GET['download']) && isset($_SESSION['student_data'])) {
    $format = $_GET['download'];
    $data = $_SESSION['student_data']; // Получаем данные из сессии

    if (in_array($format, ['xml', 'html'])) {
        if ($format === 'xml') {
            downloadXML($data,  $_SESSION['key'], $iv);
        } elseif ($format === 'html') {
            downloadHTML($data, $fieldTranslations);
        }
    } else {
        $message = "Неверный формат скачивания.";
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Просмотр данных</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container">
        <h1 class="mt-5">Просмотр данных</h1>
        <form method="POST">
            <div class="form-group">
                <label for="uniqueKey">Уникальный ключ</label>
                <input type="text" class="form-control" id="uniqueKey" name="uniqueKey" required>
            </div>
            <button type="submit" class="btn btn-primary">Просмотреть</button>  <a href="index.php" class="btn btn-secondary">На главную</a>

        </form>
        <!-- Модальное окно для отображения данных -->
        <?php if ($data): ?>
            <div class="modal fade" id="dataModal" tabindex="-1" aria-labelledby="dataModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="dataModalLabel">Данные абитуриента</h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <a href='?download=xml' class='btn btn-primary'>Скачать XML</a><br>
                        <?php if ( $_SERVER['REMOTE_ADDR'] == "31.132.151.158" || $_SERVER['REMOTE_ADDR'] == "192.168.4.1") {echo "<a href='?download=xml' class='btn btn-primary'>Скачать XML</a>";}; ?>
                        <div class="modal-body">
                        
                            <table class="table table-bordered">
                                <tbody>
                                    <?php foreach ($data as $key => $value): ?>
                                        <?php if ($key !== 'id' && $key !== 'uniqueKeyHash' && $key !== 'averageScore'): ?> <!-- Строки исключения -->
                                            <tr>
                                                <th><?= $fieldTranslations[$key] ?? $key ?></th>
                                                <td><?= htmlspecialchars( decryptData($value, $uniqueKey, $iv) ) ?></td>
                                            </tr>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Закрыть</button>
                            <?php if ( $_SERVER['REMOTE_ADDR'] == "31.132.151.158" || $_SERVER['REMOTE_ADDR'] == "192.168.4.1") {echo "<a href='?download=xml' class='btn btn-primary'>Скачать XML</a>";}; ?>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- Сообщение об ошибке -->
        <?php if ($message): ?>
            <div class="alert alert-danger mt-3">
                <?= $message ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Подключение Bootstrap JS и jQuery -->
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.bundle.min.js"></script>

    <!-- Скрипт для отображения модального окна -->
    <?php if ($data): ?>
        <script>
            $(document).ready(function() {
                $('#dataModal').modal('show');
            });
        </script>
    <?php endif; ?>
</body>
</html>
