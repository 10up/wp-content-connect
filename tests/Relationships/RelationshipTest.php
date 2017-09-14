<?php

namespace TenUp\P2P\Tests\Relationships;

use TenUp\P2P\Relationships\Relationship;

class RelationshipTest extends \PHPUnit_Framework_TestCase {

	public function get_mock() {
		$mock = $this->getMockBuilder( Relationship::class )
		             ->disableOriginalConstructor()
		             ->getMock();

		return $mock;
	}

	public function test_invalid_cpt_throws_exception() {
		$mock = $this->get_mock();

		$this->expectException( \Exception::class );

		$mock->__construct( 'post', 'fakecpt' );
	}

	public function test_valid_cpts_throw_no_exceptions() {
		$mock = $this->get_mock();

		$mock->__construct( 'post', 'post' );

		$this->assertEquals( 'post', $mock->from );
		$this->assertEquals( 'post', $mock->to );
	}

}
