<?php
declare (strict_types = 1);
namespace TavolaSample\Car\Tests;

use TavolaSample\Car\Beacon;
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
		$beacon = new Beacon("public");
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
		$beacon = new Beacon("p");
		$resp = $beacon->GET("action=topics");
		$this->assertIsObject($resp);
		$this->assertEquals(200, $resp->code);
		$body = json_decode($resp->report(), true);
		$this->assertTrue($body["success"]);
		$this->assertEquals("topics", $body["incoming"]["action"]);
		$this->assertEquals("1", $body["data"][0]["ID"]);
		$this->assertEquals("Toyota", $body["data"][0]["Trademark"]);
	}
}
