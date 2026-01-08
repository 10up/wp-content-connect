<?php

namespace TenUp\ContentConnect\Tests\Integration\Helpers;

use function TenUp\ContentConnect\Helpers\get_plugin;
use TenUp\ContentConnect\Tests\Integration\ContentConnectTestCase;

class GetPluginTest extends ContentConnectTestCase {

	public function test_get_plugin_returns_plugin_instance() {
		$plugin = get_plugin();

		$this->assertInstanceOf( '\TenUp\ContentConnect\Plugin', $plugin );
	}

}

