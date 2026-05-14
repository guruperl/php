<?php
declare (strict_types = 1);

namespace Tabilet\Admin;

use Tabilet;

class Beacon extends \Tabilet\Beacon
{
	public function GET(string $query=null) {
		return parent::get_mock("admin", $query);
	}

	public function POST(array $data) {
		return parent::post_mock("admin", $data);
	}
}
