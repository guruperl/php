<?php
declare (strict_types = 1);
namespace Tabilet\Tests;

use PHPUnit\Framework\TestCase;
use Tabilet\Application;
use Tabilet\Filter;

final class FilterTest extends TestCase
{
    private function init(): object
    {
        $_SERVER["REQUEST_URI"] = "/bb/a/html/car?action=act";
        $str = '{
    "actions":{
        "startnew":{"groups":["cc","a"],"options":["no_db","no_method"]},
        "topics":{},
        "insert":{},
        "edit":{"groups":["a"],"validate":["id"]},
        "delete":{"groups":["m"]}
    },
    "fks":{
        "a":["m_id",false,"id","id_md5"]
    },
    "current_table": "testing",
    "current_key" : "id",
    "current_id_auto" : "id",
    "insupd_pars" : ["x","y"],
    "insert_pars" : ["x","y","z"],
    "update_pars" : ["x","y","z","id"],
    "edit_pars" : ["x","y","z","id"],
    "topics_pars" : ["id","x"]
    }';
        return json_decode($str);
    }

    public function testFilterCan(): void
    {
        $_REQUEST = [];
        $filter = new Filter(self::init(), "startnew", "car", Application::config(), "a", "html");
		$_REQUEST["x"] = "bbb";
        $this->assertEquals("bbb", $_REQUEST["x"]);
        $this->assertEquals("a", $filter->actionHash["groups"][1]);
		$this->assertFalse($filter->Is_public());
		$this->assertTrue($filter->Is_admin());
		$this->assertFalse($filter->Is_normal_role());
	}

    public function testFilterPreset(): void
    {
        $_REQUEST = [];
        $filter = new Filter(self::init(), "topics", "car", Application::config(), "a", "html");
		$err = $filter->Preset();
		$this->assertNull($err);
        $this->assertEquals(100, $_REQUEST["rowcount"]);
        $this->assertEquals(1, $_REQUEST["pageno"]);

        $_REQUEST = [];
        $filter = new Filter(self::init(), "insert", "car", Application::config(), "a", "html");
		$_SERVER["REMOTE_ADDR"] = "1.1.1.1";
		$_SERVER["REQUEST_TIME"] = 0;
		$err = $filter->Preset();
		$this->assertNull($err);
        $this->assertEquals("1970-01-01 00:00:00", $_REQUEST["created"]);
        $this->assertEquals("1.1.1.1", $_REQUEST["ip"]);
	}
}
