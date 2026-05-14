<?php

declare(strict_types=1);

namespace Genelet;

class ModelNextpageProcessor
{
    private $model;

    public function __construct(Model $model)
    {
        $this->model = $model;
    }

    public function anotherObject(array &$item, array $page, ...$extra): ?Gerror
    {
        $model = $page["model"];
        if (empty($this->model->Storage)) {
            return new Gerror(2013);
        }
        if (empty($this->model->Storage[$model])) {
            return new Gerror(2014, $model);
        }
        $p = clone $this->model->Storage[$model];

        $action = $page["action"];
        $marker = $model . "_" . $action;
        if (isset($page["alias"])) {
            $marker = $page["alias"];
        }
        if (isset($page["ignore"]) && !empty($item[$marker])) {
            return null;
        }

        $args = array();
        foreach ($this->model->ARGS as $k => $v) {
            if ($k == "sortby" || $k == "sortreverse") {
                continue;
            }
            $args[$k] = $v;
        }

        if (isset($page["manual"])) {
            if (empty($extra)) {
                $extra = array($page["manual"]);
            } else {
                foreach ($page["manual"] as $k => $v) {
                    $extra[0][$k] = $v;
                }
            }
        }

        $lists = array();
        $other = array();
        $p->Set_defaults($args, $lists, $other, $this->model->Storage, $this->model->logger);
        $err = $p->$action(...$extra);
        if ($err !== null) {
            return $err;
        }
        if (!empty($p->LISTS)) {
            $item[$marker] = $p->LISTS;
        }
        if (!empty($p->OTHER)) {
            foreach ($p->OTHER as $k => $v) {
                $this->model->OTHER[$k] = $v;
            }
        }
        return null;
    }

    public function callOnce(array $page, ...$extra): ?Gerror
    {
        return $this->anotherObject($this->model->OTHER, $page, ...$extra);
    }

    public function callNextpage(array $page, ...$extra): ?Gerror
    {
        if (empty($this->model->LISTS)) {
            return null;
        }

        foreach ($this->model->LISTS as $i => $item) {
            foreach ($page["relate_item"] as $k => $v) {
                if (!empty($item[$k])) {
                    $extra[0][$v] = $item[$k];
                }
            }
            $err = $this->anotherObject($this->model->LISTS[$i], $page, ...$extra);
            if ($err !== null) {
                return $err;
            }
        }

        return null;
    }

    public function processAfter(string $action, ...$extra): ?Gerror
    {
        if (empty($this->model->Nextpages) || empty($this->model->Nextpages[$action])) {
            return null;
        }
        foreach ($this->model->Nextpages[$action] as $k => $page) {
            if (!empty($extra)) {
                array_shift($extra);
            }
            $err = (empty($page["relate_item"])) ? $this->callOnce($page, ...$extra) : $this->callNextpage($page, ...$extra);
            if ($err !== null) {
                return $err;
            }
        }
        return null;
    }
}
