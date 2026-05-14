<?php
declare (strict_types = 1);
namespace Tabilet\Admin\Tests;

use Tabilet\Admin\Beacon;
use PHPUnit\Framework\TestCase;

final class ControllerTest extends TestCase
{
	public function testCreatedBase(): void
    {
		$this->assertInstanceOf(
        	Beacon::class,
        	new Beacon("p")
    	);
	}

    /**
     * @runInSeparateProcess
     */
	public function testCanRun(): void
	{
		$beacon = new Beacon("p");
		$resp = $beacon->GET("startnew");
		$this->assertIsObject($resp);
		$this->assertEquals(404, $resp->code);
	}

    /**
     * @runInSeparateProcess
     */
	public function testLogin(): void
	{
		$_SERVER["REQUEST_TIME"] = 0;
		$beacon = new Beacon("a");
		$resp = $beacon->GET("action=topics");
		$this->assertIsObject($resp);
		$this->assertEquals(401, $resp->code);
		$this->assertEquals('{"success":false,"error_code":1020,"error_string":"Login required."}', $resp->report());
		$resp = $beacon->LOGIN(["login"=>"admin","passwd"=>"KZ2k8M]B","go_uri"=>"/"]);
		$this->assertIsObject($resp);
		$this->assertEquals(200, $resp->code);

		$resp = $beacon->GET("action=topics");
		$this->assertIsObject($resp);
		$this->assertEquals(200, $resp->code);
		$body = json_decode($resp->report(), true);
		$this->assertTrue($body["success"]);
		$this->assertEquals("topics", $body["incoming"]["action"]);
		$this->assertEquals("SUPPORT", $body["incoming"]["a_id"]);
		$this->assertEquals("admin", $body["data"][0]["login"]);
	}
}
