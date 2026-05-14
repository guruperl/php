<?php

declare(strict_types=1);

namespace Genelet;

class Model extends Crud
{
    public $ARGS;
    public $LISTS;
    public $OTHER;

    public $SORTBY;
    public $SORTREVERSE;
    public $PAGENO;
    public $ROWCOUNT;
    public $TOTALNO;
    public $MAXPAGENO;
    public $FIELDS;
    public $EMPTIES;

    public $Nextpages;
    public $Storage;

    public $Current_key;
    public $Current_id_auto;
    public $Key_in;

    public $Insert_pars;
    public $Edit_pars;
    public $Update_pars;
    public $Insupd_pars;
    public $Topics_pars;
    public $Topics_hashpars;

    public $Total_force;

    private $role_name;
    private $tag_name;

    // I may move pdo to Set_default so nextpage shares the same pdo as caller?
    public function __construct(\PDO $pdo, object $comp)
    {
        self::Initialize($comp);
        if (isset($this->Current_tables)) {
            parent::__construct($pdo, $this->Current_table, $this->Current_tables);
        } else {
            parent::__construct($pdo, $this->Current_table);
        }
    }

    public function Set_defaults(array $args, array $lists, array $other, array $storage = null, Logger $logger = null, string $role = null, string $tag = null)
    {
        $this->ARGS = $args;
        $this->LISTS = $lists;
        $this->OTHER = $other;
        if ($storage != null) {
            $this->Storage = $storage;
        }
        if ($logger != null) {
            $this->logger = $logger;
        }
        if ($role != null) {
            $this->role_name = $role;
        }
        if ($tag != null) {
            $this->tag_name = $tag;
        }
    }

    public function Get_rolename()
    {
        return $this->role_name;
    }

    public function Get_tagname()
    {
        return $this->tag_name;
    }

    private function Initialize(object $comp)
    {
        ModelConfig::apply($this, $comp);
    }

    public function filtered_fields(array $pars): array
    {
        return (new ModelInput($this))->filteredFields($pars);
    }

    private function get_fv(array $pars): array
    {
        return (new ModelInput($this))->fieldValues($pars);
    }

    public function properValue(string $v, array $extra = null): ?array
    {
        return (new ModelInput($this))->properValue($v, $extra);
    }

    public function properValues(array $vs, array $extra = null): ?array
    {
        return (new ModelInput($this))->properValues($vs, $extra);
    }

    public function get_id_val(array $extra = null): array
    {
        return (new ModelInput($this))->idValue($extra);
    }

    public function topics(...$extra): ?Gerror
    {
        $ARGS = $this->ARGS;
        $totalno = $this->TOTALNO;
        $pageno = $this->PAGENO;
        if ($this->Total_force != 0 && isset($ARGS[$this->ROWCOUNT]) && (empty($ARGS[$pageno]) || $ARGS[$pageno] == "1")) {
            $nt = 0;
            if ($this->Total_force < -1) {
                $nt = abs($this->Total_force);
            } elseif ($this->Total_force == -1 || empty($ARGS[$totalno])) {
                $hash = array();
                $err = $this->Total_hash($hash, $totalno, ...$extra);
                if ($err != null) {
                    return $err;
                }
                $nt = $hash[$totalno];
            } else {
                $nt = $ARGS[$totalno];
            }
            $this->ARGS[$totalno] = $nt;
            $nr = $ARGS[$this->ROWCOUNT];
            $this->ARGS[$this->MAXPAGENO] = floor(($nt - 1) / $nr) + 1;
            $this->OTHER[$totalno] = $nt;
            $this->OTHER[$this->MAXPAGENO] = $this->ARGS[$this->MAXPAGENO];
        }

        $fields = (empty($this->Topics_hashpars)) ? // user may supply field=,,,
            $this->filtered_fields($this->Topics_pars) :
            $this->Topics_hashpars;
        $err = $this->Topics_hash($this->LISTS, $fields, $this->get_order_string(), ...$extra);
        if ($err != null) {
            return $err;
        }

        return $this->process_after("topics", ...$extra);
    }

    public function edit(...$extra): ?Gerror
    {
        $val2 = $this->get_id_val((!empty($extra)) ? $extra[0] : null);
        $id = array_shift($val2);
        if (empty($val2)) {
            return new Gerror(1040, $id);
        }
        $val = $val2[0]; // two elements and the second is the val

        $field_values = $this->filtered_fields($this->Edit_pars);
        if (empty($field_values)) {
            return new Gerror(1077);
        }

        if (isset($extra[0]) && isset($extra[0][$id])) {
            unset($extra[0][$id]); // redundant select condition
        }

        $err = $this->Edit_hash($this->LISTS, $field_values, array($id => $val), ...$extra);
        if ($err != null) {
            return $err;
        }

        return $this->process_after("edit", ...$extra);
    }

    // use 'extra' to override field_values for selected fields
    public function insert(...$extra): ?Gerror
    {
        $field_values = $this->get_fv($this->Insert_pars);

        if (!empty($extra)) {
            foreach ($extra[0] as $kkey => $value) {
                if (array_search($kkey, $this->Insert_pars) !== false) {
                    $field_values[$kkey] = $value;
                }
            }
        }
        if (empty($field_values)) {
            return new Gerror(1078);
        }

        $err = $this->Insert_hash($field_values);
        if ($err != null) {
            return $err;
        }

        if (isset($this->Current_id_auto)) {
            $field_values[$this->Current_id_auto] = $this->Last_id;
            $this->ARGS[$this->Current_id_auto] = $this->Last_id;
        }
        $this->LISTS = array($field_values);

        return $this->process_after("insert", ...$extra);
    }

    public function insupd(...$extra): ?Gerror
    {
        if (empty($this->Insupd_pars)) {
            return new Gerror(1078);
        }
        $uniques = $this->Insupd_pars;

        $field_values = $this->get_fv($this->Insert_pars);
        if (!empty($extra)) {
            foreach ($extra[0] as $kkey => $value) {
                if (array_search($kkey, $this->Insert_pars) !== false) {
                    $field_values[$kkey] = $value;
                }
            }
        }
        if (empty($field_values)) {
            return new Gerror(1078);
        }

        foreach ($uniques as $v) {
            if (empty($field_values[$v])) {
                return new Gerror(1075, $v);
            }
        }

        $upd_field_values = $this->get_fv($this->Update_pars);

        $s_hash = "";
        $keys = isset($this->Current_keys)
            ? $this->Current_keys
            : array($this->Current_key);
        $err = $this->Insupd_hash($field_values, $upd_field_values, $keys, $uniques, $s_hash);
        if ($err != null) {
            return $err;
        }

        if (isset($this->Current_id_auto) && $s_hash == "insert") {
            $field_values[$this->Current_id_auto] = $this->Last_id;
            $this->ARGS[$this->Current_id_auto] = $this->Last_id;
        }
        array_push($this->LISTS, $field_values);

        return $this->process_after("insupd", ...$extra);
    }

    public function update(...$extra): ?Gerror
    {
        $val2 = $this->get_id_val((!empty($extra)) ? $extra[0] : null);
        $id = array_shift($val2);
        if (empty($val2)) {
            return new Gerror(1040, $id);
        }
        $val = $val2[0];

        $field_values = $this->get_fv($this->Update_pars);
        if (empty($field_values)) {
            return new Gerror(1074);
        }

        if (count($field_values) == 1 && isset($field_values[$id])) {
            $this->LISTS = array($field_values);
            return $this->process_after("update", ...$extra);
        }

        $ARGS = $this->ARGS;
        $err = $this->Update_hash_nulls($field_values, array($id => $val), isset($ARGS[$this->EMPTIES]) ? $ARGS[$this->EMPTIES] : null, ...$extra);
        if ($err != null) {
            return $err;
        }

        $this->LISTS = array($field_values);

        return $this->process_after("update", ...$extra);
    }

    public function delete(...$extra): ?Gerror
    {
        $val2 = $this->get_id_val((!empty($extra)) ? $extra[0] : null);
        $id = array_shift($val2);
        if (empty($val2)) {
            return new Gerror(1040, $id);
        }
        $val = $val2[0];

        if (isset($this->Key_in)) {
            foreach ($this->Key_in as $table => $keyname) {
                foreach ($val as $v) {
                    $err = $this->existing($table, $keyname, $v);
                    if ($err != null) {
                        return $err;
                    }
                }
            }
        }

        $err = $this->Delete_hash(array($id => $val), ...$extra);
        if ($err != null) {
            return $err;
        }

        $field_values = array();
        if (gettype($id) == "array") {
            foreach ($id as $i => $v) {
                $field_values[$v] = $val[$i];
            }
        } else {
            $field_values[$id] = $val[0];
        }
        $this->LISTS = array($field_values);

        return $this->process_after("delete", ...$extra);
    }

    public function existing(string $table, string $field, $val): ?Gerror
    {
        $hash = array();
        $err = $this->Get_args(
            $hash,
            "SELECT " . $field . " FROM " . $table . " WHERE " . $field . "=?",
            $val
        );
        if ($err != null) {
            return $err;
        }
        if (!empty($hash[$field])) {
            return new Gerror(1075);
        }

        return null;
    }

    public function randomid(string $table, string $field, ...$m): ?Gerror
    {
        $mi = 0;
        $ma = 4294967295;
        $trials = 10;
        if (!empty($m)) {
            $mi = $m[0];
            $ma = $m[1];
            $trials = isset($m[2]) ? $m[2] : 10;
        }

        for ($i = 0; $i < $trials; $i++) {
            $val = rand($mi, $ma);
            $err = $this->existing($table, $field, $val);
            if ($err != null) {
                continue;
            }
            $this->ARGS[$field] = $val;
            return null;
        }

        return new Gerror(1076);
    }

    public function get_order_string(): string
    {
        return ModelQueryOptions::orderString($this);
    }

    private function another_object(array &$item, array $page, ...$extra): ?Gerror
    {
        return (new ModelNextpageProcessor($this))->anotherObject($item, $page, ...$extra);
    }

    public function call_once(array $page, ...$extra): ?Gerror
    {
        return (new ModelNextpageProcessor($this))->callOnce($page, ...$extra);
    }

    public function call_nextpage(array $page, ...$extra): ?Gerror
    {
        return (new ModelNextpageProcessor($this))->callNextpage($page, ...$extra);
    }

    public function process_after(string $action, ...$extra): ?Gerror
    {
        return (new ModelNextpageProcessor($this))->processAfter($action, ...$extra);
    }
}
