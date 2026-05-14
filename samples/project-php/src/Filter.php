<?php
declare (strict_types = 1);

namespace Tabilet;
use Genelet;

class Filter extends \Genelet\Filter
{

public function Preset() : ?\Genelet\Gerror  {
  $err = parent::Preset();
  if ($err != null) { return $err; }

  $ARGS =& $_REQUEST;
  $role = $this->Role_name;
  $action = $this->Action;
  $obj = $this->Component;

  if ($action === 'topics') {
    if (empty($ARGS["rowcount"])) {$ARGS["rowcount"] = 100;}
    if (empty($ARGS["pageno"])) {$ARGS["pageno"]   = 1;}
  }

  if ($action=='insert' || $action=='activate') {
	$ARGS["created"] = date('Y-m-d H:i:s', $_SERVER['REQUEST_TIME']);
    $ARGS["ip"] = self::get_lb_ip();
  }

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

public static function get_lb_ip() : string {
	if ( (substr($_SERVER["REMOTE_ADDR"],0,8)=== "192.168."
			|| substr($_SERVER["REMOTE_ADDR"],0,3)==="10.")
		&& isset($_SERVER["HTTP_X_FORWARDED_FOR"])) {
		if (preg_match_all("/(\d+\.\d+\.\d+\.\d+)$/",
			$_SERVER["HTTP_X_FORWARDED_FOR"], $matches_out)) {
				return $matches_out[0][0];
		}
	}
	return $_SERVER["REMOTE_ADDR"];
}

}
