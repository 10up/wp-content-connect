<?php

namespace TenUp\ContentConnect\Tests\Integration\Helpers;

use function TenUp\ContentConnect\Helpers\get_registry;
use TenUp\ContentConnect\Tests\Integration\ContentConnectTestCase;

class GetRegistryTest extends ContentConnectTestCase {

	public function test_get_registry_returns_registry() {
		$registry = get_registry();

		$this->assertInstanceOf( '\TenUp\ContentConnect\Registry', $registry );
	}



}
