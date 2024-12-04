<?php

/**
 * Вспомогательные методы
 */
final class Utils
{
    /**
     * Чтобы не писать длинный вариант
     */
    public static function htmlize($var)
    {
        if (!is_string($var)) {
            return false;
        }
        return htmlspecialchars($var, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401, 'UTF-8', false);
    }

    /**
     * Сохраняет/получает одноразовое сообщение в сессии
     */
    public static function flashMessage(string $text = null, string $type = 'success')
    {
        if (!is_null($text)) {
            $_SESSION['flashMessage'] = ['text'=>$text, 'type'=>$type];
        } elseif (!empty($_SESSION['flashMessage'])) {
            $msg = $_SESSION['flashMessage'];
            unset($_SESSION['flashMessage']);
            return $msg;
        }
    }

    /**
     * Меняет в параметрах запроса указанные значения
     */
    public static function qs(array $replace = []): string
    {
        $validParams = ['page', 'sort'];
        if (empty($_GET)) {
            $_GET = [];;
        }

        $params = array_intersect_key($_GET, array_flip($validParams));
        foreach ($replace as $k => $v) {
            if (strlen(strval($v)) < 1) {
                unset($params[$k]);
            } else {
                $params[$k] = $v;
            }
        }
        ksort($params);
        $str = http_build_query($params);
        return '?' . $str;
    }
}
