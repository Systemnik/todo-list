<?php

/**
 * Вспомогательные методы
 */
final class Utils
{
    /**
     * Чтобы не писать длинный вариант
     */
    public static function htmlize($var): bool|string
    {
        if (!is_string($var)) {
            return false;
        }
        return htmlspecialchars($var, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401, 'UTF-8', false);
    }

    /**
     * Сохраняет/получает одноразовое сообщение в сессии
     */
    public static function flashMessage(string $text = null, string $type = 'success'): bool|array
    {
        if (!is_null($text)) {
            $_SESSION['flashMessage'] = ['text'=>$text, 'type'=>$type];
            return true;
        }
        if (!empty($_SESSION['flashMessage'])) {
            $msg = $_SESSION['flashMessage'];
            unset($_SESSION['flashMessage']);
            return $msg;
        }
        return false;
    }

    /**
     * Меняет в параметрах запроса указанные значения
     */
    public static function qs(array $replace = []): string
    {
        if (empty($_GET)) {
            $_GET = [];
        }

        // Так как эти значения могут подставляться в вёрстку,
        // то желательно отфильтровать
        $validParams = [
            'page'=>'#^\d+$#iu',
            'sort'=>'#^[a-z_]+$#iu',
        ];
        foreach ($validParams as $k => $pattern) {
            if (isset($_GET[$k]) && !preg_match($pattern, $_GET[$k])) {
                unset($_GET[$k]);
            }
        }

        $params = array_intersect_key($_GET, $validParams);
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
