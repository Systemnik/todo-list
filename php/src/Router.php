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
        if (empty($lastPart) || !array_key_exists($lastPart, $map)) {
            return 'index';
        }

        return $map[$lastPart];
    }
}
