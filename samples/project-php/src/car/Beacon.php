<?php
declare (strict_types = 1);

namespace Tabilet\Car;

use Tabilet;

class Beacon extends \Tabilet\Beacon
{
	public function GET(string $query=null) {
		return parent::get_mock("car", $query);
	}

	public function POST(array $data) {
		return parent::post_mock("car", $data);
	}
}
