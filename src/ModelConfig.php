<?php

declare(strict_types=1);

namespace Genelet;

class ModelConfig
{
    public static function apply(Model $model, object $comp): void
    {
        $model->SORTBY = isset($comp->{"sortby"}) ? $comp->{"sortby"} : "sortby";
        $model->SORTREVERSE = isset($comp->{"sortreverse"}) ? $comp->{"sortreverse"} : "sortreverse";
        $model->PAGENO = isset($comp->{"pageno"}) ? $comp->{"pageno"} : "pageno";
        $model->ROWCOUNT = isset($comp->{"rowcount"}) ? $comp->{"rowcount"} : "rowcount";
        $model->TOTALNO = isset($comp->{"totalno"}) ? $comp->{"totalno"} : "totalno";
        $model->MAXPAGENO = isset($comp->{"maxpageno"}) ? $comp->{"maxpageno"} : "maxpageno";
        $model->FIELDS = isset($comp->{"fields"}) ? $comp->{"fields"} : "fields";
        $model->EMPTIES = isset($comp->{"empties"}) ? $comp->{"empties"} : "empties";

        if (isset($comp->{"nextpages"})) {
            $model->Nextpages = array();
            foreach ($comp->{"nextpages"} as $action => $obj_ms) {
                $ms = array();
                foreach ($obj_ms as $obj_m) {
                    $table = array();
                    foreach ($obj_m as $k => $v) {
                        if ($k == "relate_item") {
                            $table[$k] = array();
                            foreach ($v as $kk => $vv) {
                                $table[$k][$kk] = $vv;
                            }
                        } else {
                            $table[$k] = $v;
                        }
                    }
                    array_push($ms, $table);
                }
                $model->Nextpages[$action] = $ms;
            }
        }

        $model->Current_table = $comp->{"current_table"};
        if (isset($comp->{"current_tables"})) {
            $model->Current_tables = array();
            foreach ($comp->{"current_tables"} as $obj_tbl) {
                $table = array();
                foreach ($obj_tbl as $k => $v) {
                    $table[$k] = $v;
                }
                array_push($model->Current_tables, $table);
            }
        }
        foreach (array(
            "current_key" => "Current_key",
            "current_id_auto" => "Current_id_auto",
            "key_in" => "Key_in",
            "insert_pars" => "Insert_pars",
            "edit_pars" => "Edit_pars",
            "update_pars" => "Update_pars",
            "insupd_pars" => "Insupd_pars",
            "topics_pars" => "Topics_pars",
        ) as $configName => $property) {
            if (isset($comp->{$configName})) {
                $model->{$property} = $comp->{$configName};
            }
        }

        if (isset($comp->{"topics_hash"})) {
            $model->Topics_hashpars = array();
            foreach ($comp->{"topics_hash"} as $k => $v) {
                $model->Topics_hashpars[$k] = $v;
            }
        }
        $model->Total_force = isset($comp->{"total_force"}) ? $comp->{"total_force"} : 1;
    }
}
