<?php

/**
 * Методы для работы с базой
 */
class Tasks
{
    /**
     * Ограничение на создание новых задач в час
     */
    public const RATE_LIMIT = 10;

    /**
     * Подключения persistent, поэтому не страшно что "новое"
     */
    public function __construct(
        private ?Db $db = null,
        private ?PDO $pdo = null,
    ) {
        if (is_null($this->db)) {
            $this->db = new Db;
        }
        if (is_null($this->pdo)) {
            $this->pdo = $this->db->getPDO();
        }
    }

    /**
     *
     */
    public function getOne(int $id): array|false
    {
        $sql = 'select * from tasks where id = ' . $id;
        return $this->pdo->query($sql)->fetch();
    }

    /**
     * select с возможной сортировкой и лимитом/отступом
     */
    public function getList(array $params = []): array
    {
        $sql = 'select * from tasks';
        if (!empty($params['field']) && !empty($params['order'])) {
            $field = $this->db->quoteColumn($params['field']);
            $order = trim($params['order']) === 'desc' ? 'desc' : 'asc';
            $sql .= " order by {$field} {$order}, id asc";
        } else {
            $sql .= ' order by id asc';
        }
        if (!empty($params['limit'])) {
            $sql .= ' limit ' . intval($params['limit']);
            if (!empty($params['offset'])) {
                $sql .= ' offset ' . intval($params['offset']);
            }
        }
        return $this->pdo->query($sql)->fetchAll();
    }

    /**
     *
     */
    public function getCount(): int
    {
        $sql = 'select count(*) from tasks';
        return $this->pdo->query($sql)->fetchColumn();
    }

    /**
     *
     */
    public function create(array $fields): int|false
    {
        $requiredFields = [
            'author',
            'email',
            'content',
            'client_ip',
        ];
        foreach ($requiredFields as $k) {
            if (!isset($fields[$k])) {
                return false;
            }
        }

        // Лимит
        $sql = "select count(*) from tasks
        where client_ip = ? and created_at > now() - '1 hour'::interval";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$fields['client_ip']]);
        $count = $stmt->fetchColumn();
        if ($count > self::RATE_LIMIT) {
            return false;
        }

        $sql = '
        insert into tasks (author, email, content, client_ip)
        values (?, ?, ?, ?)
        returning id
        ';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            $fields['author'],
            $fields['email'],
            $fields['content'],
            $fields['client_ip'],
        ]);
        return $stmt->fetchColumn();
    }

    /**
     *
     */
    public function update(int $id, array $fields): bool
    {
        $possibleFields = ['is_done', 'content'];

        $this->pdo->beginTransaction();

        $sql = 'select *, is_done::int as is_done from tasks where id = ? for no key update';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$id]);
        $item = $stmt->fetch();

        $sqlParts = [];
        $sqlVals = [];
        foreach ($possibleFields as $k) {
            if (!isset($fields[$k])) {
                continue;
            }
            if ($item[$k] === $fields[$k]) {
                unset($fields[$k]);
                continue;
            }
            $sqlParts[] = "\"{$k}\" = ?";
            $sqlVals[] = $fields[$k];
        }
        if (empty($sqlParts)) {
            $this->pdo->rollBack();
            return false;
        }
        $sqlVals[] = $id;

        $sql = 'update tasks
        set ' . implode(', ', $sqlParts) . ', updated_at = now(), is_updated = true
        where id = ?
        returning id';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($sqlVals);
        $id = $stmt->fetchColumn();

        $this->pdo->commit();

        return !empty($id);
    }
}
