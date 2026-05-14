<?php

declare(strict_types=1);

namespace Genelet;

class ModelQueryOptions
{
    public static function orderString(Model $model): string
    {
        $ARGS = $model->ARGS;
        $column = "";
        if (isset($ARGS[$model->SORTBY])) {
            $column = $ARGS[$model->SORTBY];
        } elseif (isset($model->Current_tables)) {
            $table = $model->Current_tables[0];
            if (isset($table["sortby"])) {
                $column = $table["sortby"];
            } else {
                $name = isset($table["alias"]) ? $table["alias"] : $table["name"];
                $name .= ".";
                $column = $name . ((gettype($model->Current_key) == "array") ? implode(", $name", $model->Current_key) : $model->Current_key);
            }
        } else {
            $column = (gettype($model->Current_key) == "array") ? implode(", ", $model->Current_key) : $model->Current_key;
        }

        $order = "ORDER BY " . $column;
        if (isset($ARGS[$model->SORTREVERSE])) {
            $order .= " DESC";
        }

        if (isset($ARGS[$model->ROWCOUNT])) {
            $rowcount = $ARGS[$model->ROWCOUNT];
            $pageno = isset($ARGS[$model->PAGENO]) ? $ARGS[$model->PAGENO] : 1;
            $order .= " LIMIT " . $rowcount . " OFFSET " . (($pageno - 1) * $rowcount);
        }

        if (strpos($order, ";") === false && strpos($order, "'") === false && strpos($order, '"') === false) {
            return $order;
        }

        return "";
    }
}
