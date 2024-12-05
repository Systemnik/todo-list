<?php

/**
 * Все возможные action'ы и вспомогательные методы.
 * Только минимально необходимый код. Намеренно мимо SRP.
 */
final class Controller
{
    /**
     * Дефолтные флаги для распечатки json'а
     */
    public const JSON_FLAGS = JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE;

    /**
     * Пока просто хардкод
     */
    private const ADMIN_LOGIN = 'admin';
    private const ADMIN_PASSWORD = '123';
    private const SESSION_COOKIE_NAME = 'session_id';

    public const PAGE_SIZE = 3;

    /**
     * Признак запроса из js
     */
    private bool $isAjax = false;

    /**
     * Для ограничений
     */
    private ?string $clientIP = null;

    /**
     * Путь к корню проекта
     */
    private ?string $rootDir = null;

    /**
     *
     */
    public function __construct(
        private $tasks = new Tasks,
    ) {
        $this->rootDir = dirname(__DIR__);

        // Сабмиты форм так обрабатывать удобнее
        if (!empty($_SERVER['HTTP_X_IS_AJAX'])) {
            $this->isAjax = true;
        }

        // Для небольшой предосторожности
        $this->clientIP = $_SERVER['HTTP_X_FORWARDED_FOR'] ??
            $_SERVER['HTTP_X_REAL_IP'] ??
            $_SERVER['REMOTE_ADDR'] ??
            null;
    }

    /**
     * Зачаток фронт-контроллера
     */
    public function run(): void
    {
        // Определение запрошенного действия
        $method = (new Router)->getRequestedAction();
        $method .= 'Action';
        if (!is_callable([$this, $method])) {
            $method = 'notFoundAction';
        }

        // Вообще так делать нельзя, но для простоты пусть сессия запускается для всех всегда
        $this->sessionStart();

        // Сразу обрезать лишние пробелы, если есть
        if (!empty($_POST)) {
            array_walk_recursive($_POST, fn($v) => is_string($v) ? trim($v) : $v);
        }
        if (!empty($_REQUEST)) {
            array_walk_recursive($_REQUEST, fn($v) => is_string($v) ? trim($v) : $v);
        }

        // Выполнение
        try {
            $res = call_user_func([$this, $method]);
        } catch (ApiException $e) {
            $this->responseJson([
                'status'=>$e->getCode(),
                'message'=>$e->getMessage(),
            ]);
            return;
        } catch (DbException) {
            $this->responseJson([
                'status'=>500,
                'message'=>'Не удалось обработать запрос, попробуйте повторить попытку позже',
            ]);
            return;
        } catch (Exception $e) {
            $this->responseJson([
                'status'=>500,
                'message'=>$e->getMessage(),
            ]);
            return;
        }

        // Если ответ в виде массива
        if (!empty($res) && is_array($res)) {
            $this->responseJson($res);
            return;
        }

        // Сейчас все сабмиты подразумевают
        // обновление страницы при успешном выполнении
        if ($this->isAjax) {
            $this->responseJson(['reload'=>1]);
        }
    }

    /**
     * Главная страница
     */
    public function indexAction()
    {
        // Здесь всегда выводится html
        $this->isAjax = false;

        // Не по всем полям можно сортировать
        $sortableFields = [
            'name'=>'author',
            'email'=>'email',
            'status'=>'is_done',
        ];

        // Валидация возможных параметров запроса
        $field = 'created_at';
        $order = 'asc';
        $sortRequested = '';
        if (!empty($_REQUEST['sort'])) {
            $parts = array_map('strtolower', explode('_', $_REQUEST['sort']));
            if (
                count($parts) === 2 &&
                array_key_exists($parts[0], $sortableFields) &&
                in_array($parts[1], ['asc', 'desc'])
            ) {
                $field = $sortableFields[$parts[0]];
                $order = $parts[1];
                $sortRequested = implode('_', $parts);
            }
        }
        $page = intval($_REQUEST['page'] ?? 1);
        if ($page < 1) {
            $page = 1;
        }
        $offset = ($page - 1) * self::PAGE_SIZE;
        if ($offset < 0 || $offset >= PHP_INT_MAX) {
            $offset = 0;
            $page = 1;
        }

        $sortOptions = [
            ''=>'Сортировать',
            'name_asc'=>'По имени',
            'name_desc'=>'По имени в обратном порядке',
            'email_asc'=>'По email',
            'email_desc'=>'По email в обратном',
            'status_asc'=>'По статусу',
            'status_desc'=>'По статусу в обратном',
        ];

        $this->tpl('index', [
            'items'=>$this->tasks->getList([
                'field'=>$field,
                'order'=>$order,
                'limit'=>self::PAGE_SIZE,
                'offset'=>$offset,
            ]),
            'sortRequested'=>$sortRequested,
            'sortOptions'=>$sortOptions,
            'page'=>$page,
            'pageSize'=>self::PAGE_SIZE,
            'total'=>$this->tasks->getCount(),
            'flashMessage'=>Utils::flashMessage(),
        ]);
    }

    /**
     * Выборка одной задачи
     */
    public function taskAction()
    {
        $item = $this->tasks->getOne(intval($_POST['id']));
        if (empty($item)) {
            throw new ApiException('Такой задачи нет в базе', 404);
        }
        return [
            'item'=>[
                'id'=>$item['id'],
                'author'=>$item['author'],
                'email'=>$item['email'],
                'content'=>$item['content'],
                'is_done'=>$item['is_done'],
                'is_updated'=>$item['is_updated'],
            ],
        ];
    }

    /**
     * Создание задачи
     */
    public function taskCreateAction()
    {
        $fields = ['author', 'email', 'content'];
        foreach ($fields as $k) {
            if (empty($_POST[$k]) || !is_string($_POST[$k])) {
                throw new ApiException('Пожалуйста, заполните все поля формы', 400);
            }
        }
        $email = filter_var($_POST['email'], FILTER_VALIDATE_EMAIL);
        if ($email === false) {
            throw new ApiException('Пожалуйста, укажите корректный Email', 400);
        }
        if (strlen($_POST['author']) > 1024) {
            throw new ApiException('Имя не должно быть слишком длинным', 400);
        }
        if (strlen($_POST['content']) > 1024*10) {
            throw new ApiException('Текст задачи не должен быть слишком длинным', 400);
        }

        $id = $this->tasks->create([
            'author'=>$_POST['author'],
            'email'=>$email,
            'content'=>$_POST['content'],
            'client_ip'=>$this->clientIP,
        ]);

        if (!empty($id)) {
            Utils::flashMessage("Задача #{$id} успешно добавлена");
        } else {
            Utils::flashMessage('Не удалось создать задачу, попробуйте повторить попытку позже', 'danger');
        }
    }

    /**
     * Редактирование задачи
     */
    public function taskUpdateAction()
    {
        if (!$this->isAuthorized()) {
            throw new ApiException('Требуется авторизация', 403);
        }
        if (empty($_POST['id'])) {
            throw new ApiException('Необходимо указать ID задачи', 400);
        }
        $id = intval($_POST['id']);
        if (strlen($_POST['content']) > 1024*10) {
            throw new ApiException('Текст задачи не должен быть слишком длинным', 400);
        }

        $fields = [];
        if (isset($_POST['is_done'])) {
            $fields['is_done'] = intval($_POST['is_done']);
        }
        if (!empty($_POST['content'])) {
            $fields['content'] = $_POST['content'];
        }

        $res = $this->tasks->update($id, $fields);
        if ($res === false) {
            Utils::flashMessage('Изменений нет, нечего обновлять', 'warning');
        } else {
            Utils::flashMessage("Изменения в задаче #{$id} сохранены");
        }
    }

    /**
     * Авторизация админа
     */
    public function authAction()
    {
        if (empty($_POST['login']) || empty($_POST['password'])) {
            throw new ApiException('Необходимо указать логин и пароль', 400);
        }

        if (
            strtolower($_POST['login']) !== self::ADMIN_LOGIN ||
            $_POST['password'] !== self::ADMIN_PASSWORD
        ) {
            throw new ApiException('Неверный логин и/или пароль', 403);
        }

        $_SESSION['authorized'] = 1;
    }

    /**
     * Выход
     */
    public function logoutAction()
    {
        unset($_SESSION['authorized']);
    }

    /**
     *
     */
    public function notFoundAction()
    {
        throw new ApiException('Такого действия нет', 400);
    }

    /**
     * Вспомогательный метод
     */
    public function isAuthorized(): bool
    {
        return !empty($_SESSION['authorized']);
    }

    /**
     * Вывод шаблона
     */
    public function tpl(string $name, array $vars = []): bool
    {
        $path = $this->rootDir . '/templates/' . $name . '.php';
        if (!file_exists($path)) {
            return false;
        }

        extract($vars);
        include $path;

        return true;
    }

    /**
     * Распечатка массива в виде json-ответа
     */
    private function responseJson(array $res): void
    {
        if (!headers_sent()) {
            header('Content-Type: application/json; charset=utf-8');
        }
        echo json_encode($res, self::JSON_FLAGS);
    }

    /**
     * Старт сессии, если это возможно
     */
    private function sessionStart(): void
    {
        if (PHP_SAPI === 'cli') {
			return;
		}
		if (isset($_SESSION)) {
			// Мог сработать автостарт
			return;
		}

        // Если в куке пришло что-то невалидное
        if (
            isset($_COOKIE[self::SESSION_COOKIE_NAME]) &&
            !preg_match('#^[a-z0-9\-\,]{15,60}$#i', $_COOKIE[self::SESSION_COOKIE_NAME])
        ) {
            unset($_COOKIE[self::SESSION_COOKIE_NAME]);
        }

        session_name(self::SESSION_COOKIE_NAME);
        session_set_cookie_params([
            'lifetime'=>0,
            'httponly'=>true,
            'secure'=>true,
            'samesite'=>'Strict',
        ]);

        if (!headers_sent()) {
            session_start();
        }
    }
}
