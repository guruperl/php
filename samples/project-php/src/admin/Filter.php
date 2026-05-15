<?php
declare (strict_types = 1);

namespace TavolaSample\Admin;

use TavolaSample;

class Filter extends \TavolaSample\Filter
{

public function Preset() : ?\Genelet\Gerror  {
  $err = parent::Preset();
  if ($err != null) { return $err; }

  $ARGS =& $_REQUEST;
  $role = $this->Role_name;
  $action = $this->Action;
  $obj = $this->Component;

  return null;
}

public function Before(object &$model, array &$extra, array &$nextextra, array &$onceextra = null)  : ?\Genelet\Gerror {
  $err = parent::Before($model, $extra, $nextextra, $onceextra);
  if ($err != null) { return $err; }

  $ARGS =& $_REQUEST;
  $role = $this->Role_name;
  $action = $this->Action;
  $obj = $this->Component;

  return null;
}

public function After(object $model, array $onceextra = null) : ?\Genelet\Gerror {
  $err = parent::After($model, $onceextra);
  if ($err != null) { return $err; }

  $ARGS =& $_REQUEST;
  $role = $this->Role_name;
  $action = $this->Action;
  $obj = $this->Component;

  return null;
}

}
