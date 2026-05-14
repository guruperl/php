<?php

declare(strict_types=1);

namespace Genelet;

class SqlComposer
{
    public static function Table_string(array $current_tables): string
    {
        $sql = "";
        foreach ($current_tables as $i => $table) {
            $name = $table["name"];
            if (isset($table["alias"])) {
                $name .= " " . $table["alias"];
            }
            if ($i === 0) {
                $sql = $name;
            } elseif (isset($table["using"])) {
                $sql .= "\n" . $table["type"] . " JOIN " . $name . " USING (" . $table["using"] . ")";
            } elseif (isset($table["on"])) {
                $sql .= "\n" . $table["type"] . " JOIN " . $name . " ON (" . $table["on"] . ")";
            }
        }

        return $sql;
    }

    public static function Select_label_string(array $select_pars): array
    {
        $select_labels = array();
        $sql = "";
        if (isset($select_pars[0])) {
            array_push($select_labels, ...$select_pars);
            $sql = implode(", ", $select_labels);
        } else {
            $i = 0;
            foreach ($select_pars as $k => $val) {
                if ($i > 0) {
                    $sql .= ", ";
                }
                $sql .= $k;
                array_push($select_labels, $val);
                $i++;
            }
        }
        array_unshift($select_labels, $sql);
        return $select_labels;
    }

    public static function Select_condition_string(array $extra, string ...$table): array
    {
        if (empty($extra)) {
            return array("");
        }

        $sql = "";
        $values = array();
        $i = 0;
        foreach ($extra as $field => $value) {
            if ($i > 0) {
                $sql .= " AND ";
            }
            $sql .= "(";

            if (isset($table[0]) && (strpos($field, ".") === false)) {
                $field = $table[0] . "." . $field;
            }
            if (gettype($value) === "array") {
                $n = sizeof($value);
                $sql .= $field . " IN (" . implode(',', array_fill(0, $n, '?')) . ")";
                array_push($values, ...$value);
            } else {
                if (substr($field, -5, 5) === "_gsql") {
                    $sql .= $value;
                } else {
                    $sql .= $field . "=?";
                    array_push($values, $value);
                }
            }
            $sql .= ")";
            $i++;
        }

        array_unshift($values, $sql);
        return $values;
    }

    public static function Single_condition_string(array $keyids, array ...$extra): array
    {
        $sql = "";
        $extra_values = array();

        $i = 0;
        foreach ($keyids as $keyname => $val) {
            if ($i === 0) {
                $sql = "(";
            } else {
                $sql .= " AND ";
            }
            if (gettype($val) === "array") {
                $n = sizeof($val);
                $sql .= $keyname . " IN (" . implode(',', array_fill(0, $n, '?')) . ")";
                array_push($extra_values, ...$val);
            } else {
                $sql .= $keyname . "=?";
                array_push($extra_values, $val);
            }
            $sql .= ")";
            $i++;
        }

        if (!empty($extra)) {
            $arr = self::Select_condition_string($extra[0]);
            $s = array_shift($arr);
            if ($s != "") {
                $sql .= " AND " . $s;
                array_push($extra_values, ...$arr);
            }
        }

        array_unshift($extra_values, $sql);
        return $extra_values;
    }
}
