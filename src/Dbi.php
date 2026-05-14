<?php

declare(strict_types=1);

namespace Genelet;

class Dbi
{
    public $Conn;
    public $Last_id;
    public $Affected;
    public $logger;

    public function __construct(\PDO $pdo, Logger $logger = null)
    {
        $this->Conn = $pdo;
        $this->Conn->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_SILENT);
        if (isset($logger)) $this->logger = $logger;
    }

    private function errstr(): string
    {
        return implode("; ", $this->Conn->errorInfo());
    }
    private function errsmt(object $sth): string
    {
        return implode("; ", $sth->errorInfo());
    }

    private function logSql(string $sql, array $args = null): void
    {
        if (isset($this->logger)) {
            if ($args === null) {
                $this->logger->info($sql);
            } else {
                $this->logger->info($sql, $args);
            }
        }
    }

    private function prepareStatement(string $sql, array $args = array(), array $options = array()): array
    {
        $this->logSql($sql, $args);
        $sth = empty($options) ? $this->Conn->prepare($sql) : $this->Conn->prepare($sql, $options);
        if ($sth === false) {
            return array(null, new Gerror(1071, $this->errstr()));
        }
        return array($sth, null);
    }

    private function executeStatement(object $sth, array $args): ?Gerror
    {
        $result = @$sth->execute($args);
        if ($result === false) {
            $message = self::errsmt($sth);
            $sth->closeCursor();
            return new Gerror(1072, $message);
        }
        return null;
    }

    private function buildCallSql(string $proc_name, int $arg_count, array $names = null): array
    {
        $str = "CALL " . $proc_name . "(" . implode(',', array_fill(0, $arg_count, '?'));
        if ($names === null) {
            $str .= ")";
            return array($str, null);
        }
        $str_n = "@" . implode(", @", $names);
        $str .= ", " . $str_n . ")";
        return array($str, "SELECT " . $str_n);
    }

    public function Exec_sql(string $sql): ?Gerror
    {
        $this->logSql($sql);
        $n = $this->Conn->exec($sql);
        if ($n === false) {
            return new Gerror(1071, $this->errstr());
        }
        $this->Affected = $n;
        return null;
    }

    public function Do_sql(string $sql, ...$args): ?Gerror
    {
        list($sth, $err) = $this->prepareStatement($sql, $args);
        if ($err !== null) {
            return $err;
        }
        $err = $this->executeStatement($sth, $args);
        if ($err !== null) {
            return $err;
        }

        $this->Last_id = intval($this->Conn->lastInsertId());
        $sth->closeCursor();
        return null;
    }

    public function Do_sqls(string $sql, ...$args): ?Gerror
    {
        list($sth, $err) = $this->prepareStatement($sql, $args);
        if ($err !== null) {
            return $err;
        }
        foreach ($args as $item) {
            $err = $this->executeStatement($sth, $item);
            if ($err !== null) {
                return $err;
            }
            $this->Last_id = intval($this->Conn->lastInsertId());
        }
        $sth->closeCursor();
        return null;
    }

    public function Get_args(array &$res, string $sql, ...$args): ?Gerror
    {
        $lists = array();
        $err = $this->Select_sql($lists, $sql, ...$args);
        if ($err != null) {
            return $err;
        }
        if (sizeof($lists) === 1) {
            foreach ($lists[0] as $k => $v) {
                $res[$k] = $v;
            }
        }
        return null;
    }

    public function Get_sql_label(array &$res, array $select_labels, string $sql, ...$args): ?Gerror
    {
        $lists = array();
        $err = $this->Select_sql_label($lists, $select_labels, $sql, ...$args);
        if ($err != null) {
            return $err;
        }
        if (sizeof($lists) === 1) {
            foreach ($lists[0] as $k => $v) {
                $res[$k] = $v;
            }
        }
        return null;
    }

    public function Select_sql(array &$lists, string $sql, ...$args): ?Gerror
    {
        list($sth, $err) = $this->prepareStatement($sql, $args);
        if ($err !== null) {
            return $err;
        }
        $err = $this->executeStatement($sth, $args);
        if ($err !== null) {
            return $err;
        }
        $lists = $sth->fetchAll(\PDO::FETCH_ASSOC);
        if ($lists === false) {
            $err = new Gerror(1073, self::errsmt($sth));
            $sth->closeCursor();
            return $err;
        }
        $sth->closeCursor();
        return null;
    }

    public function Select_sql_label(array &$lists, array $select_labels, string $sql, ...$args): ?Gerror
    {
        list($sth, $err) = $this->prepareStatement($sql, $args, array(\PDO::ATTR_CURSOR => \PDO::CURSOR_SCROLL));
        if ($err !== null) {
            return $err;
        }
        $err = $this->executeStatement($sth, $args);
        if ($err !== null) {
            return $err;
        }
        $is_map = count(array_filter(array_keys($select_labels), 'is_string')) > 0;
        $xs = array();
        $i = 0;
        foreach ($select_labels as $k => $v) {
            if ($is_map) {
                // PDO::PARAM_BOOL (integer) PDO::PARAM_NULL (integer) PDO::PARAM_INT (integer) PDO::PARAM_STR (integer) PDO::PARAM_STR_NATL (integer) PDO::PARAM_STR_CHAR (integer) PDO::PARAM_LOB (integer) PDO::PARAM_INPUT_OUTPUT (integer)
                $sth->bindColumn($i + 1, $xs[$i], $v);
            } else {
                $sth->bindColumn($i + 1, $xs[$i]);
            }
            $i++;
        }
        while ($row = $sth->fetch(\PDO::FETCH_ASSOC, \PDO::FETCH_ORI_NEXT)) {
            $item = array();
            $i = 0;
            foreach ($select_labels as $k => $v) {
                if ($is_map) {
                    $item[$k] = $xs[$i];
                } else {
                    $item[$v] = $xs[$i];
                }
                $i++;
            }
            // array_push only pushes references, push $labels directly makes it contain only the last item, many times
            array_push($lists, $item);
        }
        $sth->closeCursor();
        return null;
    }

    public function Do_proc(string $proc_name, ...$args): ?Gerror
    {
        list($str) = $this->buildCallSql($proc_name, sizeof($args));

        return $this->Do_sql($str, ...$args);
    }

    public function Do_proc_label(array &$hash, array $names, string $proc_name, ...$args): ?Gerror
    {
        list($str, $select) = $this->buildCallSql($proc_name, sizeof($args), $names);

        $err = $this->Do_sql($str, ...$args);
        if ($err != null) {
            return $err;
        }
        return $this->Get_sql_label($hash, $names, $select);
    }

    public function Select_proc_label(array &$lists, array $select_labels, string $proc_name, ...$args): ?Gerror
    {
        list($str) = $this->buildCallSql($proc_name, sizeof($args));

        return $this->Select_sql_label($lists, $select_labels, $str, ...$args);
    }

    public function Select_do_proc_label(array &$lists, array $select_labels, array &$hash, array $names, string $proc_name, ...$args): ?Gerror
    {
        list($str, $select) = $this->buildCallSql($proc_name, sizeof($args), $names);

        $err = $this->Select_sql_label($lists, $select_labels, $str, ...$args);
        if ($err != null) {
            return $err;
        }

        return $this->Get_sql_label($hash, $names, $select);
    }
}
