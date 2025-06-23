<?php
session_start();
require 'db_connect.php';

$message = ''; // Сообщение для пользователя
$uniqueKey = ''; // Уникальный ключ

if(isset($_SESSION['uniqueKey'])) {
    session_destroy(); 
    header('Location: index.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Получаем данные из формы
    $data = $_POST;

    do {
        // Генерируем случайную строку
        $uniqueKey = bin2hex(random_bytes(4));  // 8 символов
    } while (strpos($uniqueKey, '0') !== false || strpos($uniqueKey, 'O') !== false);
    
    $uniqueKeyHash = hash('sha256', $uniqueKey);

    $iv = "encryptionVector"; // Вектор инициализации[шифрования] (16 байт)

    // Функция для шифрования данных
    function encryptData($data, $key, $iv) { return openssl_encrypt($data, 'AES-128-CBC', $key, 0, $iv); }

    function formatDate($date) {
        $BirthYear = substr($date, 0, 4); // "Год"
        $BirthMonth = substr($date, 5, 2); // "Месяц"
        $BirthDay = substr($date, 8, 2); // "День" 
        return $BirthResult = "$BirthDay.$BirthMonth.$BirthYear"; //2024-12-20 в 20.12.2024
    }

    if (empty($_POST['representativeName']) || strlen($_POST['representativeName']) < 2 ) {$representativeName = 0;} else { $representativeName = $_POST['representativeName'];}
    if (empty($_POST['representativePhoneNumber']) || strlen($_POST['representativePhoneNumber']) < 5 ) {$representativePhoneNumber = 0;} else {$representativePhoneNumber = $_POST['representativePhoneNumber'];}
    // Рассчитываем средний балл
    $triples = (int)$data['triples'];
    $quadruples = (int)$data['quadruples'];
    $fives = (int)$data['fives'];
    $averageScore = ($triples + $quadruples + $fives) > 0 ? ($triples * 3 + $quadruples * 4 + $fives * 5) / ($triples + $quadruples + $fives) : 0;
    

    // Подготавливаем SQL-запрос
    $stmt = $pdo->prepare("INSERT INTO students (
        firstName, lastName, middleName, POL, birthDate, targetEducation, citizenship, birthPlace, passportSeriesNumber, passportData , passportIssueDate, registrationRegion, registrationCity, 
        registrationAddress, snils, educationBase, graduationYear, educationInstitution, educationDocument, educationDocumentNumber, foreignLanguage1, foreignLanguage2, phoneNumber, 
        disability, guardianship, chaes, orphan, priorityAdmission, specialConditions, dormitoryRequired, firstSecondaryEducation, representativeName, representativePhoneNumber, triples, 
        quadruples, fives, averageScore, uniqueKeyHash
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

        // Выполняем запрос
    $stmt->execute([
        encryptData($data['firstName'],$uniqueKey, $iv),
        encryptData($data['lastName'],$uniqueKey, $iv),
        encryptData($data['middleName'],$uniqueKey, $iv),
        encryptData($data['POL'],$uniqueKey, $iv),
        encryptData(formatDate($data['birthDate']),$uniqueKey, $iv),
        encryptData($data['targetEducation'],$uniqueKey, $iv),
        encryptData($data['citizenship'],$uniqueKey, $iv),
        encryptData($data['birthPlace'],$uniqueKey, $iv),
        encryptData($data['passportSeriesNumber'],$uniqueKey, $iv),
        encryptData($data['passportData'],$uniqueKey, $iv),
        encryptData(formatDate($data['passportIssueDate']),$uniqueKey, $iv),
        encryptData($data['registrationRegion'],$uniqueKey, $iv),
        encryptData($data['registrationCity'],$uniqueKey, $iv),
        encryptData($data['registrationAddress'],$uniqueKey, $iv),
        encryptData($data['snils'],$uniqueKey, $iv),
        encryptData($data['educationBase'],$uniqueKey, $iv),
        encryptData(formatDate($data['graduationYear']),$uniqueKey, $iv),
        encryptData($data['educationInstitution'],$uniqueKey, $iv),
        encryptData($data['educationDocument'],$uniqueKey, $iv),
        encryptData($data['educationDocumentNumber'],$uniqueKey, $iv),
        encryptData($data['foreignLanguage1'],$uniqueKey, $iv),
        encryptData($data['foreignLanguage2'],$uniqueKey, $iv),
        encryptData($data['phoneNumber'],$uniqueKey, $iv),
        encryptData($data['disability'],$uniqueKey, $iv),
        encryptData($data['guardianship'],$uniqueKey, $iv),
        encryptData($data['chaes'],$uniqueKey, $iv),
        encryptData($data['orphan'],$uniqueKey, $iv),
        encryptData($data['priorityAdmission'],$uniqueKey, $iv),
        encryptData($data['specialConditions'],$uniqueKey, $iv),
        encryptData($data['dormitoryRequired'],$uniqueKey, $iv),
        encryptData($data['firstSecondaryEducation'],$uniqueKey, $iv),
        encryptData($representativeName,$uniqueKey, $iv),
        encryptData($representativePhoneNumber,$uniqueKey, $iv),
        encryptData($triples,$uniqueKey, $iv),
        encryptData($quadruples,$uniqueKey, $iv),
        encryptData($fives,$uniqueKey, $iv),
        encryptData($averageScore,$uniqueKey, $iv),
        $uniqueKeyHash
    ]);

    // Сообщение об успешной отправке
    $message = "Ваши данные успешно сохранены! Ваш уникальный ключ для просмотра данных: <strong>$uniqueKey</strong>. Сохраните этот ключ, он понадобится для просмотра данных и передаче их приемной комиссии.";
    
    $_SESSION['uniqueKey'] = $uniqueKey;
    
}

?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Главная страница</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.5/dist/JsBarcode.all.min.js"></script>
    <style>
        .print-only { display: none; } /* Скрываем элемент для печати */
        @media print {
            .no-print { display: none; } /* Скрываем всё, кроме ключа, при печати */
            .print-only { display: block; } /* Показываем ключ при печати */
            body { text-align: center; }
            #barcode { display: block; margin: 0 auto; }
        }
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: rgba(0, 0, 0, 0.5);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 1050;
        }
        .modal-content {
            max-width: 500px;
            width: 100%;
        }
        #countdown {
            font-weight: bold;
            color: #dc3545;
        }
        .barcode-container {
            margin-top: 20px;
            text-align: center;
        }
    </style>
</head>
<body>
        <!-- Всплывающее окно -->
    <div class="modal-overlay" id="modalOverlay">
        <div class="modal-content">
            <div class="modal-header bg-warning">
                <h5 class="modal-title">ВАЖНОЕ УВЕДОМЛЕНИЕ</h5>
                <button type="button" class="close" id="closeButton" disabled aria-label="Close"><span aria-hidden="true">&times;</span></button>
            </div>
            <div class="modal-body">
                <p>ЗАПОЛНЕНИЕ ЭТОЙ ФОРМЫ НЕ ОСВОБОЖДАЕТ ОТ ЛИЧНОГО ПОСЕЩЕНИЯ ПРИЕМНОЙ КОМИССИИ КОЛЛЕДЖА С ОРИГИНАЛАМИ ДОКУМЕНТОВ</p>
                <p>Вы сможете закрыть это окно через <span id="countdown">5</span> секунд</p>
            </div>
        </div>
    </div>
    <div class="container">
        <h1 class="mt-5">Система автоматизированного ввода данных для приемной комисии ГБПОУ БППК</h1>
        <p class="mt-5"><span class="text-danger"><b>ПРОЧИТАЙТЕ, ЭТО ВАЖНО. ЗАПОЛНЕНИЕ ЭТОЙ ФОРМЫ НЕ ОСВОБОЖДАЕТ ОТ ЛИЧНОГО ПОСЕЩЕНИЯ ПРИЕМНОЙ КОМИССИИ КОЛЛЕДЖА С ОРИГИНАЛАМИ ДОКУМЕНТОВ</b></span>
        <br>Система предназначена для ускорения работы сотрудников приёмной комиссии. Введя данные, вы получите уникальный ключ.
        <br>Назовите этот ключ сотруднику приемной комиссии, зная ключ, сотрудник получит все заполненные вами данные.
        <br>Вы не будет ждать и терять своё время пока сотрудник заполнил бы вашу карточку. <a href="https://bppk.info/priemnaia-komissiia.html">Подробнее про поступление на нашем сайте</a></p>  
        <a href="view_data.php" class="btn btn-secondary mt-3">Просмотреть внесенные данные</a>
        <h3 class="mt-5">Введите данные</h3> 

        <?php if ($message): ?>
            <div class="alert alert-success">
                <?= $message ?>
            </div>
            <?php if (isset($_SESSION['uniqueKey'])): ?>
                <button id="printButton" class="btn btn-success no-print">Распечатать ключ</button>
                <div id="printContent" class="print-only">
                    <h2>Ваш уникальный ключ:</h2>
                    <p><?= $_SESSION['uniqueKey'] ?></p>
                    <div class="barcode-container">
                        <svg id="barcode"></svg>
                    </div>
                </div>
            <?php endif; ?>
            <a href="index.php" class="btn btn-primary">Вернуться на главную</a>
        <?php else: ?>
            <form name="dataStudent" method="POST" onsubmit="return validateCaptcha(event)">
                <!-- Личные данные -->
                <div class="form-group">
                    <label for="lastName">Фамилия</label>
                    <input type="text" class="form-control" id="lastName" name="lastName" required>
                </div>
                <div class="form-group">
                    <label for="firstName">Имя</label>
                    <input type="text" class="form-control" id="firstName" name="firstName" required>
                </div>
                <div class="form-group">
                    <label for="middleName">Отчество</label>
                    <input type="text" class="form-control" id="middleName" name="middleName" required>
                </div>
                <div class="form-group">
                <label for="POL"></label>
                    <select class="form-control" id="POL" name="POL" required>
                        <option value="мужской">мужской</option>
                        <option value="женский">женский</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="birthDate">Дата рождения</label>
                    <input type="date" class="form-control" id="birthDate" name="birthDate" id="myDateInput" placeholder="dd.mm.yyyy" value="" onchange="formatDate()" required>
                </div>
                <div class="form-group">
                    <label for="targetEducation">Целевое обучение</label>
                    <select class="form-control" id="targetEducation" name="targetEducation" required>
                        <option value="Да">Да</option>
                        <option value="Нет" selected>Нет</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="citizenship">Гражданство</label>
                    <input type="text" class="form-control" id="citizenship" name="citizenship" value="Российская Федерация" readonly>
                </div>
                <div class="form-group">
                    <label for="birthPlace">Место рождения</label>
                    <input type="text" class="form-control" id="birthPlace" name="birthPlace"  placeholder="Например: г.Брянск" required>
                </div>

                <!-- Паспортные данные -->
                <div class="form-group">
                    <label for="passportSeriesNumber">Паспорт серия и номер</label>
                    <input type="text" class="form-control" id="passportSeriesNumber" name="passportSeriesNumber" pattern="\d{4} \d{6}" placeholder="XXXX XXXXXX" required>
                </div>
                <div class="form-group">
                    <label for="passportData">Кем выдан паспорт</label>
                    <input type="text" class="form-control" id="passportData" name="passportData" placeholder="Например: Отделом УФМС России по Брянской обл г.Брянска" required>
                </div>
                <div class="form-group">
                    <label for="passportIssueDate">Дата выдачи паспорта</label>
                    <input type="date" class="form-control" id="passportIssueDate" name="passportIssueDate" placeholder="Например: 20.03.2000" required>
                </div>

                <!-- Адрес прописки -->
                <div class="form-group">
                    <label for="registrationRegion">Регион прописки</label>
                    <input type="text" class="form-control" id="registrationRegion" name="registrationRegion" placeholder="Например: Брянская область" required>
                </div>
                <div class="form-group">
                    <label for="registrationCity">Населенный пункт прописки</label>
                    <input type="text" class="form-control" id="registrationCity" name="registrationCity"  placeholder="Например: г.Брянск" required>
                </div>
                <div class="form-group">
                    <label for="registrationAddress">Улица, дом, квартира прописки</label>
                    <input type="text" class="form-control" id="registrationAddress" name="registrationAddress" placeholder="Например: ул.Почтовая д.4 кв.20" required>
                </div>

                <!-- СНИЛС -->
                <div class="form-group">
                    <label for="snils">СНИЛС</label>
                    <input type="text" class="form-control" id="snils" name="snils" pattern="\d{3}-\d{3}-\d{3} \d{2}" placeholder="XXX-XXX-XXX XX" required>
                </div>

                <!-- Образование -->
                <div class="form-group">
                    <label for="educationBase">База образования</label>
                    <select class="form-control" id="educationBase" name="educationBase" required>
                        <option value="Высшее образование">Высшее образование</option>
                        <option value="Начальное профессиональное образование">Начальное профессиональное образование</option>
                        <option value="Общее образование на базе 11 классов">Общее образование на базе 11 классов</option>
                        <option value="Общее образование на базе 9 классов" selected>Общее образование на базе 9 классов</option>
                        <option value="Среднее профессиональное образование">Среднее профессиональное образование</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="graduationYear">Дата окончания образовательной организации</label>
                    <input type="date" class="form-control" name="graduationYear" min="1950" max="2030" id="myDateInput" placeholder="dd.mm.yyyy" value="" onchange="formatDate()"  required>
                </div>
                <div class="form-group">
                    <label for="educationInstitution">Учреждение, выдавшее документ об образовании</label>
                    <input type="text" class="form-control" id="educationInstitution" name="educationInstitution" placeholder="Например: МОУ СОШ №12 г.Брянск " required>
                </div>
                <div class="form-group">
                    <label for="educationDocument">Документ об образовании</label>
                    <select class="form-control" id="educationDocument" name="educationDocument" required>
                        <option value="Аттестат" selected>Аттестат</option>
                        <option value="Диплом">Диплом</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="educationDocumentNumber">Номер документа об образовании</label>
                    <input type="text" class="form-control" id="educationDocumentNumber" name="educationDocumentNumber" placeholder="Например: 123456789012345" required>
                </div>

                <!-- Иностранные языки -->
                <div class="form-group">
                    <label for="foreignLanguage1">Иностранный язык (первый)</label>
                    <select class="form-control" id="foreignLanguage1" name="foreignLanguage1" required>
                        <option value="Английский" selected>Английский</option>
                        <option value="Немецкий">Немецкий</option>
                        <option value="Французский">Французский</option>
                        <option value="Итальянский">Итальянский</option>
                        <option value="Испанский">Испанский</option>
                        <option value="Китайский">Китайский</option>
                        <option value="Японский">Японский</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="foreignLanguage2">Иностранный язык (второй)</label>
                    <select class="form-control" id="foreignLanguage2" name="foreignLanguage2" required>
                        <option value="НеИзучался" selected>Не изучался</option>
                        <option value="Английский">Английский</option>
                        <option value="Немецкий">Немецкий</option>
                        <option value="Французский">Французский</option>
                        <option value="Итальянский">Итальянский</option>
                        <option value="Испанский">Испанский</option>
                        <option value="Китайский">Китайский</option>
                        <option value="Японский">Японский</option>
                    </select>
                </div>

                <!-- Контактные данные -->
                <div class="form-group">
                    <label for="phoneNumber">Номер телефона</label>
                    <input type="tel" class="form-control" id="phoneNumber" name="phoneNumber" pattern="\+7\(\d{3}\)\d{3}-\d{2}-\d{2}" placeholder="+7(XXX)XXX-XX-XX" required>
                </div>

                <!-- Дополнительные сведения -->
                <div class="form-group">
                    <label for="disability">Инвалидность (Наличие подтверждающего документа)</label>
                    <select class="form-control" id="disability" name="disability" required>
                        <option value="Да">Да</option>
                        <option value="Нет" selected>Нет</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="guardianship">Опека (Наличие подтверждающего документа)</label>
                    <select class="form-control" id="guardianship" name="guardianship" required>
                        <option value="Да">Да</option>
                        <option value="Нет"selected>Нет</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="chaes">ЧАЭС (Наличие подтверждающего документа)</label>
                    <select class="form-control" id="chaes" name="chaes" required>
                        <option value="Да">Да</option>
                        <option value="Нет" selected>Нет</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="orphan">Сирота (Наличие подтверждающего документа)</label>
                    <select class="form-control" id="orphan" name="orphan" required>
                        <option value="Да">Да</option>
                        <option value="Нет" selected>Нет</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="priorityAdmission">Первоочередной порядок зачисления</label>
                    <select class="form-control" id="priorityAdmission" name="priorityAdmission" required>
                        <option value="Да">Да</option>
                        <option value="Нет" selected>Нет</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="specialConditions">Нуждаюсь в специальных условиях (по состоянию здоровья)</label>
                    <select class="form-control" id="specialConditions" name="specialConditions" required>
                        <option value="Да">Да</option>
                        <option value="Нет" selected>Нет</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="dormitoryRequired">Требуется общежитие</label>
                    <select class="form-control" id="dormitoryRequired" name="dormitoryRequired" required>
                        <option value="Да">Да</option>
                        <option value="Нет" selected>Нет</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="firstSecondaryEducation">Среднее образование впервые</label>
                    <select class="form-control" id="firstSecondaryEducation" name="firstSecondaryEducation" required>
                        <option value="Да">Да</option>
                        <option value="Нет" selected>Нет</option>
                    </select>
                </div>

                <!-- Представитель -->
                <div class="form-group">
                    <label for="representativeName">ФИО представителя</label>
                    <input type="text" class="form-control" id="representativeName" name="representativeName">
                </div>
                <div class="form-group">
                    <label for="representativePhoneNumber">Номер телефона представителя</label>
                    <input type="tel" class="form-control" id="representativePhoneNumber" name="representativePhoneNumber" pattern="\+7\(\d{3}\)\d{3}-\d{2}-\d{2}" placeholder="+7(XXX)XXX-XX-XX">
                </div>

                <!-- Оценки -->
                <div class="form-group">
                    <label for="triples">Количество троек в аттестате/дипломе</label>
                    <input type="number" class="form-control" id="triples" name="triples" min="0" required>
                </div>
                <div class="form-group">
                    <label for="quadruples">Количество четверок в аттестате/дипломе</label>
                    <input type="number" class="form-control" id="quadruples" name="quadruples" min="0" required>
                </div>
                <div class="form-group">
                    <label for="fives">Количество пятерок в аттестате/дипломе</label>
                    <input type="number" class="form-control" id="fives" name="fives" min="0" required>
                </div>       
                <!--
                <div class="form-group">
                    <label for="averageScore">Средний балл</label>
                    <input type="text" class="form-control" id="averageScore" name="averageScore" readonly>
                </div>
                -->
                <hr>
                <div>
                    <label for="captcha"><span class="text-danger">Для проверки,что вы не робот решите пример: <span id="captchaQuestion"></span></span></label>
                    <input type="text" id="captcha" name="captcha" required>
                </div>

            <!-- Сообщение об ошибке или успехе -->
            <div id="message" class="mt-3"></div>

                <button type="submit" class="btn btn-primary">Отправить</button>
                <br><br>
            </form>
        <?php endif; ?>

      
    </div>
    <script>
        // Генерация штрихкода при загрузке страницы
        document.addEventListener('DOMContentLoaded', function() {
            const uniqueKey = "<?= $_SESSION['uniqueKey'] ?? '' ?>";
            if(uniqueKey) {
                JsBarcode("#barcode", uniqueKey, {
                    format: "CODE39",
                    displayValue: false,
                    width: 2,
                    height: 40,
                    margin: 10
                });
            }
            
            // Функция для печати ключа
            document.getElementById('printButton')?.addEventListener('click', () => {
                const printContent = document.getElementById('printContent').innerHTML;
                const printWindow = window.open('', '', 'height=400,width=600');
                printWindow.document.write(`
                    <html>
                        <head>
                            <title>Печать ключа</title>
                            <script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.5/dist/JsBarcode.all.min.js"><\/script>
                            <style>
                                body { font-family: Arial, sans-serif; text-align: center; }
                                h2 { color: #333; }
                                p { font-size: 24px; font-weight: bold; }
                                svg { display: block; margin: 20px auto; }
                            </style>
                        </head>
                        <body onload="JsBarcode('#barcode', '${uniqueKey}', { format: 'CODE128', displayValue: false, width: 2, height: 40, margin: 10 })">
                            ${printContent}
                        </body>
                    </html>
                `);
                printWindow.document.close();
                printWindow.print();
            });
        });
    </script>
    <script>
  // Генерация случайного математического примера
  function generateCaptcha() {
    const num1 = Math.floor(Math.random() * 10) + 1; // Случайное число от 1 до 10
    const num2 = Math.floor(Math.random() * 10) + 1; // Случайное число от 1 до 10
    const operators = ['+', '-', '*'];
    const operator = operators[Math.floor(Math.random() * operators.length)]; // Случайный оператор
    let answer;
    switch (operator) {
      case '+':
        answer = num1 + num2;
        break;
      case '-':
        answer = num1 - num2;
        break;
      case '*':
        answer = num1 * num2;
        break;
    }

    // Сохраняем правильный ответ в глобальной переменной
    window.correctAnswer = answer;

    // Отображаем пример пользователю
    document.getElementById('captchaQuestion').textContent = `${num1} ${operator} ${num2} = `;
  }

  // Проверка капчи
  function validateCaptcha(event) {
    event.preventDefault(); // Отменяем стандартное поведение формы

    const userAnswer = parseInt(document.getElementById('captcha').value, 10); // Получаем ответ пользователя

    if (userAnswer === window.correctAnswer) {
      // Если ответ правильный, отправляем форму
      //alert("Капча пройдена! Форма отправляется.");
      event.target.submit(); // Отправка формы
    } else {
      // Если ответ неправильный, выводим сообщение об ошибке
      alert("Неправильный ответ на капчу. Попробуйте еще раз.");
      generateCaptcha(); // Генерируем новый пример
    }
  }

  // Генерация капчи при загрузке страницы
  window.onload = generateCaptcha;
</script>
    <script>
    document.getElementById('snils').addEventListener('input', function (e) {
        // Удаляем все нецифровые символы
        let input = e.target.value.replace(/\D/g, '');

        // Ограничиваем длину ввода до 11 цифр
        if (input.length > 11) {
            input = input.substring(0, 11);
        }

        // Разбиваем на группы
        const groups = input.match(/^(\d{0,3})(\d{0,3})(\d{0,3})(\d{0,2})$/);

        // Форматируем ввод
        let formatted = '';
        if (groups[1]) formatted += groups[1];
        if (groups[2]) formatted += `-${groups[2]}`;
        if (groups[3]) formatted += `-${groups[3]}`;
        if (groups[4]) formatted += ` ${groups[4]}`;

        // Устанавливаем отформатированное значение
        e.target.value = formatted;
    });
    </script>
    <script>
            // Добавляем обработчик события "input" на поле с идентификатором "passportSeriesNumber"
           document.getElementById('passportSeriesNumber').addEventListener('input', function (e) {
                // Удаляем все нецифровые символы с помощью регулярного выражения /\D/g
                let input = e.target.value.replace(/\D/g, '');
                // Ограничиваем длину ввода до 10 цифр (4 для серии и 6 для номера)
                if (input.length > 10) {
                    input = input.substring(0, 10); // Обрезаем строку до 10 символов
                }
                // Разбиваем ввод на группы с помощью регулярного выражения:
                // - groups[1]: первые 4 цифры (серия паспорта)
                // - groups[2]: следующие 6 цифр (номер паспорта)
                // Разбиваем на группы
                const groups = input.match(/^(\d{0,4})(\d{0,6})$/);
                // Форматируем ввод
                let formatted = '';
                if (groups[1]) formatted += groups[1];  // Добавляем серию
                if (groups[2]) formatted += ` ${groups[2]}`;  // Добавляем номер через пробел
                // Устанавливаем отформатированное значение в поле ввода
                e.target.value = formatted;
            });
    </script>
    <script>
                // Добавляем обработчик события "input" на поле с идентификатором "phoneNumber"
        document.getElementById('representativePhoneNumber').addEventListener('input', function (e) {
        // Получаем значение из поля ввода и удаляем все нецифровые символы с помощью регулярного выражения /\D/g
        // Затем разбиваем оставшиеся цифры на группы с помощью match:
        // - x[1]: первая цифра (код страны, например, 7 для России)
        // - x[2]: следующие 3 цифры (код города или оператора)
        // - x[3]: следующие 3 цифры (первая часть номера)
        // - x[4]: следующие 2 цифры (вторая часть номера)
        // - x[5]: последние 2 цифры (третья часть номера)
        var x = e.target.value.replace(/\D/g, '').match(/(\d{0,1})(\d{0,3})(\d{0,3})(\d{0,2})(\d{0,2})/);
        // Форматируем номер телефона:
        // - Добавляем "+7" в начало (код России)
        // - Если есть x[2] (первые 3 цифры), добавляем их в скобках: (xxx)
        // - Если есть x[3] (следующие 3 цифры), добавляем их после скобок: )xxx
        // - Если есть x[4] (следующие 2 цифры), добавляем их через дефис: -xx
        // - Если есть x[5] (последние 2 цифры), добавляем их через дефис: -xx
        e.target.value = '+7' + (x[2] ? '(' + x[2] : '') + (x[3] ? ')' + x[3] : '') + (x[4] ? '-' + x[4] : '') + (x[5] ? '-' + x[5] : '');
    });
    </script>
    <script>
        // Добавляем обработчик события "input" на поле с идентификатором "phoneNumber"
    document.getElementById('phoneNumber').addEventListener('input', function (e) {
        // Получаем значение из поля ввода и удаляем все нецифровые символы с помощью регулярного выражения /\D/g
        // Затем разбиваем оставшиеся цифры на группы с помощью match:
        // - x[1]: первая цифра (код страны, например, 7 для России)
        // - x[2]: следующие 3 цифры (код города или оператора)
        // - x[3]: следующие 3 цифры (первая часть номера)
        // - x[4]: следующие 2 цифры (вторая часть номера)
        // - x[5]: последние 2 цифры (третья часть номера)
        var x = e.target.value.replace(/\D/g, '').match(/(\d{0,1})(\d{0,3})(\d{0,3})(\d{0,2})(\d{0,2})/);
        // Форматируем номер телефона:
        // - Добавляем "+7" в начало (код России)
        // - Если есть x[2] (первые 3 цифры), добавляем их в скобках: (xxx)
        // - Если есть x[3] (следующие 3 цифры), добавляем их после скобок: )xxx
        // - Если есть x[4] (следующие 2 цифры), добавляем их через дефис: -xx
        // - Если есть x[5] (последние 2 цифры), добавляем их через дефис: -xx
        e.target.value = '+7' + (x[2] ? '(' + x[2] : '') + (x[3] ? ')' + x[3] : '') + (x[4] ? '-' + x[4] : '') + (x[5] ? '-' + x[5] : '');
    });
    </script>
    <script> // Начало скрипта
        // Автоматический расчет среднего балла
        // Добавляем обработчики событий на поля ввода
        // Когда пользователь вводит данные в поля "triples", "quadruples" или "fives", вызывается функция calculateAverage
        document.getElementById('triples').addEventListener('input', calculateAverage);
        document.getElementById('quadruples').addEventListener('input', calculateAverage);
        document.getElementById('fives').addEventListener('input', calculateAverage);
        //Функция для расчета среднего балла
        function calculateAverage() {
            // Получаем значения из полей ввода и преобразуем их в числа
            // Если значение пустое или не число, используем 0
            const triples = parseFloat(document.getElementById('triples').value) || 0; // Количество троек
            const quadruples = parseFloat(document.getElementById('quadruples').value) || 0; // Количество четверок
            const fives = parseFloat(document.getElementById('fives').value) || 0; // Количество пятерок
            const total = triples + quadruples + fives; // Вычисляем общее количество оценок
        // Рассчитываем средний балл
        // Если общее количество оценок больше нуля, вычисляем средний балл, иначе возвращаем 0
            const average = total > 0 ? ((triples * 3 + quadruples * 4 + fives * 5) / total).toFixed(2) : 0; // Формула расчета среднего балла, если оценок нет, средний балл равен 0
            document.getElementById('averageScore').value = average; // Выводим результат в поле с идентификатором "averageScore"
        }
    </script> <!--Конец скрипта-->
    <script> 
        function formatDate() { const inputDate = new Date(document.getElementById("myDateInput").value); 
        let day = inputDate.getDate(); 
        let month = inputDate.toLocaleString('default', { month: 'long' }); 
        let year = inputDate.getFullYear(); 
        // Формируем строку в формате "День Месяц Год" const formattedDate = `${day} ${month} ${year}`; document.getElementById("formattedDate").innerText = formattedDate; } 
    </script>
    <script>
        function updatePOLBasedOnMiddleName() {
            // Получаем значение из поля "Отчество"
            const middleName = document.getElementById('middleName').value.trim();
            
            // Получаем элемент выпадающего списка "База образования"
            const POLSelect = document.getElementById('POL');
            
            // Проверяем последнюю букву отчества
            if (middleName.length > 0) {
                const lastChar = middleName[middleName.length - 1].toLowerCase();
                
                // Если последняя буква "а", устанавливаем "Женский", иначе "Мужской"
                if (lastChar === 'а') {
                    POLSelect.value = 'женский';
                } else {
                    POLSelect.value = 'мужской';
                }
            }
        }

        // Вызов при загрузке страницы
        updatePOLBasedOnMiddleName();

        // Вызов при изменении значения в поле "Отчество"
        document.getElementById('middleName').addEventListener('input', updatePOLBasedOnMiddleName);
    </script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const modalOverlay = document.getElementById('modalOverlay');
        const closeButton = document.getElementById('closeButton');
        const countdownElement = document.getElementById('countdown');
        let secondsLeft = 5;
        // Обновляем таймер каждую секунду
        const countdownInterval = setInterval(function() {
            secondsLeft--;
            countdownElement.textContent = secondsLeft;
            if (secondsLeft <= 0) {
                clearInterval(countdownInterval);
                closeButton.disabled = false;
            }
        }, 1000);
        // Закрытие окна при нажатии на кнопку
        closeButton.addEventListener('click', function() {
            modalOverlay.style.display = 'none';
        });
    });
</script>
</body>
</html>
