<?php

declare(strict_types=1);

namespace Genelet;

class Crud extends Dbi
{
    public $Current_table;
    public $Current_tables;

    public function __construct(\PDO $pdo, string $tbl, array $tbls = null, Logger $logger = null)
    {
        parent::__Construct($pdo, $logger);
        $this->Current_table = $tbl;
        if ($tbls != null) {
            $this->Current_tables = $tbls;
        }
    }

    public function Table_string(): string
    {
        return SqlComposer::Table_string($this->Current_tables);
    }

    public function Select_label_string(array $select_pars): array
    {
        return SqlComposer::Select_label_string($select_pars);
    }

    public function Select_condition_string(array $extra, string ...$table): array
    {
        return SqlComposer::Select_condition_string($extra, ...$table);
    }

    public function Single_condition_string(array $keyids, array ...$extra): array
    {
        return SqlComposer::Single_condition_string($keyids, ...$extra);
    }

    public function Insert_hash(array $field_values): ?Gerror
    {
        return $this->insert_hash_("INSERT", $field_values);
    }

    public function Replace_hash(array $field_values): ?Gerror
    {
        return $this->insert_hash_("REPLACE", $field_values);
    }

    private function insert_hash_(string $how, array $field_values): ?Gerror
    {
        $fields = array();
        $values = array();
        foreach ($field_values as $k => $v) {
            array_push($fields, $k);
            array_push($values, $v);
        }
        $sql = $how . " INTO " . $this->Current_table . " (" . implode(", ", $fields) . ") VALUES (" . implode(',', array_fill(0, sizeof($fields), '?')) . ")";
        return $this->Do_sql($sql, ...$values);
    }

    public function Update_hash(array $field_values, array $keyids, array ...$extra): ?Gerror
    {
        return $this->Update_hash_nulls($field_values, $keyids, null, ...$extra);
    }

    public function Update_hash_nulls(array $field_values, array $keyids, array $empties = null, array ...$extra): ?Gerror
    {
        $fields = array();
        $field0 = array();
        $values = array();
        foreach ($field_values as $k => $v) {
            array_push($fields, $k);
            array_push($field0, $k . "=?");
            array_push($values, $v);
        }

        $sql = "UPDATE " . $this->Current_table . " SET " . implode(", ", $field0);
        if (!empty($empties)) {
            foreach ($empties as $v) {
                if (isset($field_values[$v])) {
                    continue;
                }
                $found = false;
                foreach ($keyids as $keyname => $ids) {
                    if ($v === $keyname) {
                        $found = true;
                        break;
                    }
                }
                if ($found === true) {
                    continue;
                }
                $sql .= ", " . $v . "=NULL";
            }
        }

        $extra_values = $this->Single_condition_string($keyids, ...$extra);
        $where = array_shift($extra_values);
        if ($where != "") {
            $sql .= "\nWHERE " . $where;
        }
        array_push($values, ...$extra_values);

        return $this->Do_sql($sql, ...$values);
    }

    public function Insupd_table(array $field_values, string $keyname, array $uniques, string &$s_hash): ?Gerror
    {
        $s = "SELECT " . $keyname . " FROM " . $this->Current_table . "\nWHERE ";
        $v = array();
        foreach ($uniques as $i => $val) {
            if ($i > 0) {
                $s .= " AND ";
            }
            $s .= $val . "=?";
            array_push($v, $field_values[$val]);
        }

        $lists = array();
        $err = $this->Select_sql($lists, $s, ...$v);
        if ($err != null) {
            return $err;
        }
        if (sizeof($lists) > 1) {
            return new Gerror(1070);
        }

        if (sizeof($lists) === 1) {
            $id = $lists[0][$keyname];
            $err = $this->Update_hash($field_values, array($keyname => $id));
            if ($err != null) {
                return $err;
            }
            $s_hash = "update";
            $field_values[$keyname] = $id;
        } else {
            $err = $this->Insert_hash($field_values);
            if ($err != null) {
                return $err;
            }
            $s_hash = "insert";
            $field_values[$keyname] = $this->Last_id;
        }

        return null;
    }

    public function Insupd_hash(array $field_values, array $upd_field_values, array $keyname, array $uniques, string &$s_hash): ?Gerror
    {
        $ks = $keyname;
        $s = "SELECT " . implode(", ", $ks) . " FROM " . $this->Current_table . "\nWHERE ";
        $v = array();
        foreach ($uniques as $i => $val) {
            if ($i > 0) {
                $s .= " AND ";
            }
            $s .= $val . "=?";
            array_push($v, $field_values[$val]);
        }

        $lists = array();
        $err = $this->Select_sql($lists, $s, ...$v);
        if ($err != null) {
            return $err;
        }
        if (sizeof($lists) > 1) {
            return new Gerror(1070);
        }

        if (sizeof($lists) === 1) {
            $ids = array_fill(0, sizeof($ks), "");
            $keyids = array();
            foreach ($ks as $i => $k) {
                $ids[$i] = $lists[0][$k];
                $field_values[$k] = $ids[$i];
                $keyids[$k] = $ids;
            }
            $err = $this->Update_hash($field_values, $keyids);
            if ($err != null) {
                return $err;
            }
            $s_hash = "update";
        } else {
            $err = $this->Insert_hash($field_values);
            if ($err != null) {
                return $err;
            }
            $s_hash = "insert";
        }

        return null;
    }

    public function Delete_hash(array $keyids, array ...$extra): ?Gerror
    {
        $sql = "DELETE FROM " . $this->Current_table;
        $extra_values = $this->Single_condition_string($keyids, ...$extra);
        $where = array_shift($extra_values);
        if ($where != "") {
            $sql .= "\nWHERE " . $where;
        }

        return $this->Do_sql($sql, ...$extra_values);
    }

    public function Edit_hash(array &$lists, array $select_pars, array $keyids, array ...$extra): ?Gerror
    {
        $select_labels = $this->Select_label_string($select_pars);
        $sql = array_shift($select_labels);
        $sql = "SELECT " . $sql . "\nFROM " . $this->Current_table;
        $extra_values = $this->Single_condition_string($keyids, ...$extra);
        $where = array_shift($extra_values);
        if ($where != "") {
            $sql .= "\nWHERE " . $where;
        }

        return $this->Select_sql_label($lists, $select_labels, $sql, ...$extra_values);
    }

    public function Topics_hash(array &$lists, array $select_pars, string $order, array ...$extra): ?Gerror
    {
        $select_labels = $this->Select_label_string($select_pars);
        $sql = array_shift($select_labels);
        $table = array();
        if (!empty($this->Current_tables)) {
            $sql = "SELECT " . $sql . "\nFROM " . $this->Table_string();
            $tbl = (isset($this->Current_tables[0]["alias"])) ? $this->Current_tables[0]["alias"] : $this->Current_tables[0]["name"];
            array_push($table, $tbl);
        } else {
            $sql = "SELECT " . $sql . "\nFROM " . $this->Current_table;
        }

        if (!empty($extra) > 0) {
            $values = $this->Select_condition_string($extra[0], ...$table);
            $where = array_shift($values);
            if ($where != "") {
                $sql .= "\nWHERE " . $where;
            }
            if ($order != "") {
                $sql .= "\n" . $order;
            }
            return $this->Select_sql_label($lists, $select_labels, $sql, ...$values);
        }

        if ($order != "") {
            $sql .= "\n" . $order;
        }
        return $this->Select_sql_label($lists, $select_labels, $sql);
    }

    public function Total_hash(array &$hash, string $label, array ...$extra): ?Gerror
    {
        $table = array();
        $sql = "SELECT COUNT(*) FROM ";
        if (!empty($this->Current_tables)) {
            $sql .= $this->Table_string();
            $tbl = (isset($this->Current_tables[0]["alias"])) ? $this->Current_tables[0]["alias"] : $this->Current_tables[0]["name"];
            array_push($table, $tbl);
        } else {
            $sql .= $this->Current_table;
        }

        if (!empty($extra)) {
            $values = $this->Select_condition_string($extra[0], ...$table);
            $where = array_shift($values);
            if ($where != "") {
                $sql .= "\nWHERE " . $where;
            }
            return $this->Get_sql_label($hash, array($label), $sql, ...$values);
        }

        return $this->Get_sql_label($hash, array($label), $sql);
    }
}
