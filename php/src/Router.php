<?php

/**
 * Зачаток роутера
 */
class Router
{
    /**
     * Возможные роуты заранее захардкожены
     */
    public function getRequestedAction(): string
    {
        $uri = strtolower(trim($_SERVER['REQUEST_URI'], '/'));
        $uri = preg_replace('#\?.*$#iu', '', $uri);

        if (empty($uri)) {
            return 'index';
        }

        $map = [
            'auth'=>'auth',
            'logout'=>'logout',
            'task'=>'task',
            'task-create'=>'taskCreate',
            'task-update'=>'taskUpdate',
        ];

        $uriParts = explode('/', $uri);
        $lastPart = array_pop($uriParts);
        if (empty($lastPart)) {
            return 'index';
        }

        if(!array_key_exists($lastPart, $map)) {
            return 'notFound';
        }

        return $map[$lastPart];
    }
}
