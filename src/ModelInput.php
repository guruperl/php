<?php

declare(strict_types=1);

namespace Genelet;

class ModelInput
{
    private $model;

    public function __construct(Model $model)
    {
        $this->model = $model;
    }

    public function filteredFields(array $pars): array
    {
        $ARGS = $this->model->ARGS;
        if (empty($ARGS[$this->model->FIELDS])) {
            return $pars;
        }
        $in = $ARGS[$this->model->FIELDS];
        $out = array();
        if (gettype($in) == "array") {
            foreach ($in as $val) {
                if (array_search($val, $pars) !== false) {
                    array_push($out, $val);
                }
            }
        } elseif (array_search($in, $pars) !== false) {
            array_push($out, $in);
        }
        return empty($out) ? $pars : $out;
    }

    public function fieldValues(array $pars): array
    {
        $ARGS = $this->model->ARGS;
        $field_values = array();
        $filted = $this->filteredFields($pars);
        foreach ($filted as $f) {
            if (!empty($ARGS[$f])) {
                $field_values[$f] = $ARGS[$f];
            }
        }
        return $field_values;
    }

    public function properValue(string $v, array $extra = null): ?array
    {
        $ARGS = $this->model->ARGS;
        if ($extra !== null && isset($extra[$v])) {
            return (gettype($extra[$v]) == "array") ? $extra[$v] : [$extra[$v]];
        } elseif (isset($ARGS[$v])) {
            return (gettype($ARGS[$v]) == "array") ? $ARGS[$v] : [$ARGS[$v]];
        }
        return null;
    }

    public function properValues(array $vs, array $extra = null): ?array
    {
        $ARGS = $this->model->ARGS;
        $outs = array();
        foreach ($vs as $v) {
            if ($extra !== null && isset($extra[$v])) {
                array_push($outs, $extra[$v]);
            } elseif (isset($ARGS[$v])) {
                array_push($outs, $ARGS[$v]);
            } else {
                return null;
            }
        }

        return $outs;
    }

    public function idValue(array $extra = null): array
    {
        $id = $this->model->Current_key;
        $val = (gettype($id) == "array")
            ? $this->properValues($id, $extra)
            : $this->properValue($id, $extra);
        if ($val == null) {
            return array($id);
        }
        return array($id, $val);
    }
}
