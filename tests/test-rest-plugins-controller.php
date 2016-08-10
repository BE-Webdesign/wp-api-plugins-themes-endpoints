<?php

class WP_Test_REST_Plugins_Controller extends WP_Test_REST_Controller_TestCase {
	protected $admin_id;

	public function setUp() {
		parent::setUp();

		$this->admin_id = $this->factory->user->create( array(
			'role' => 'administrator',
		) );

		$this->super_admin = $this->factory->user->create( array(
			'role' => 'administrator',
		) );

		$this->subscriber = $this->factory->user->create( array(
			'role' => 'subscriber',
		) );
	}

	public function test_register_routes() {
		$routes = $this->server->get_routes();
		$this->assertArrayHasKey( '/wp/v2/plugins', $routes );
		$this->assertArrayHasKey( '/wp/v2/plugins/(?P<slug>[\w-]+)', $routes );
	}

	public function test_delete_item_without_permission() {

		wp_set_current_user( 0 );

		$request = new WP_REST_Request( WP_REST_Server::DELETABLE, '/wp/v2/plugins/hello-dolly' );

		$response = $this->server->dispatch( $request );

		$this->assertErrorResponse( 'rest_forbidden', $response, 401 );

		wp_set_current_user( $this->subscriber );

		$response = $this->server->dispatch( $request );

		$this->assertErrorResponse( 'rest_forbidden', $response, 403 );
	}

	public function test_context_param() {

	}

	public function test_get_items() {
		$this->set_user_with_priviledges();

		$request = new WP_REST_Request( 'GET', '/wp/v2/plugins' );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );
		$data = $response->get_data();
		$this->assertEquals( 2, count( $data ) );
		// TODO: Check values.
		$this->assertEquals( 'Akismet', $data[0]['name'] );
	}

	public function test_get_items_multisite_permissions() {
		if ( is_multisite() ) {
			wp_set_current_user( $this->admin_id );

			// By default an admin cannot check plugins on a multisite install.
			$request = new WP_REST_Request( 'GET', '/wp/v2/plugins' );

			$response = $this->server->dispatch( $request );

			$this->assertErrorResponse( 'rest_forbidden', $response, 403 );

			// Now check when it is enabled for admins.
			$menu_permissions = get_site_option( 'menu_items' );
			$menu_permissions['plugins'] = '1';
			update_site_option( 'menu_items', $menu_permissions );

			// This should return a positive result now.
			$response = $this->server->dispatch( $request );

			$this->assertEquals( 200, $response->get_status() );

			// Check to make sure lower users can still not access plugins.
			wp_set_current_user( $this->subscriber );

			$response = $this->server->dispatch( $request );

			$this->assertErrorResponse( 'rest_forbidden', $response, 403 );

			// Clean up.
			$menu_permissions['plugins'] = '0';
			update_site_option( 'menu_items', $menu_permissions );
		}
	}

	public function test_get_item() {
		$this->set_user_with_priviledges();

		$request = new WP_REST_Request( 'GET', '/wp/v2/plugins/hello-dolly' );
		$response = $this->server->dispatch( $request );

		$this->check_get_plugins_response( $response, 'view' );
	}

	public function test_get_item_without_permissions() {
		wp_set_current_user( 0 );

		$request = new WP_REST_Request( 'GET', '/wp/v2/plugins/hello-dolly' );

		$response = $this->server->dispatch( $request );

		$this->assertErrorResponse( 'rest_forbidden', $response, 401 );

		wp_set_current_user( $this->subscriber );

		$response = $this->server->dispatch( $request );

		$this->assertErrorResponse( 'rest_forbidden', $response, 403 );
	}

	public function test_get_item_multisite_permissions() {
		if ( is_multisite() ) {
			wp_set_current_user( $this->admin_id );

			// By default an admin cannot check plugins on a multisite install.
			$request = new WP_REST_Request( 'GET', '/wp/v2/plugins/hello-dolly' );

			$response = $this->server->dispatch( $request );

			$this->assertErrorResponse( 'rest_forbidden', $response, 403 );

			// Now check when it is enabled for admins.
			$menu_permissions = get_site_option( 'menu_items' );
			$menu_permissions['plugins'] = '1';
			update_site_option( 'menu_items', $menu_permissions );

			// This should return a positive result now.
			$response = $this->server->dispatch( $request );

			$this->assertEquals( 200, $response->get_status() );

			// Check to make sure lower users can still not access plugins.
			wp_set_current_user( $this->subscriber );

			$response = $this->server->dispatch( $request );

			$this->assertErrorResponse( 'rest_forbidden', $response, 403 );

			// Clean up.
			$menu_permissions['plugins'] = '0';
			update_site_option( 'menu_items', $menu_permissions );
		}
	}

	public function test_create_item() {

	}

	public function test_update_item() {

	}

	public function test_delete_item() {

	}

	public function test_prepare_item() {

	}

	public function test_get_item_schema() {
		$request = new WP_REST_Request( 'OPTIONS', '/wp/v2/plugins' );
		$response = $this->server->dispatch( $request );
		$data = $response->get_data();
		$properties = $data['schema']['properties'];
		$this->assertEquals( 11, count( $properties ) );
		$this->assertArrayHasKey( 'name', $properties );
		$this->assertArrayHasKey( 'plugin_uri', $properties );
		$this->assertArrayHasKey( 'version', $properties );
		$this->assertArrayHasKey( 'description', $properties );
		$this->assertArrayHasKey( 'author', $properties );
		$this->assertArrayHasKey( 'author_uri', $properties );
		$this->assertArrayHasKey( 'text_domain', $properties );
		$this->assertArrayHasKey( 'domain_path', $properties );
		$this->assertArrayHasKey( 'network', $properties );
		$this->assertArrayHasKey( 'title', $properties );
		$this->assertArrayHasKey( 'author_name', $properties );
	}

	public function test_get_items_without_permissions() {
		wp_set_current_user( 0 );

		$request = new WP_REST_Request( 'GET', '/wp/v2/plugins' );

		$response = $this->server->dispatch( $request );

		$this->assertErrorResponse( 'rest_forbidden', $response, 401 );

		wp_set_current_user( $this->subscriber );

		$response = $this->server->dispatch( $request );

		$this->assertErrorResponse( 'rest_forbidden', $response, 403 );
	}

	protected function check_get_plugins_response( $response, $context = 'view' ) {
		$this->assertNotInstanceOf( 'WP_Error', $response );
		$response = rest_ensure_response( $response );
		$this->assertEquals( 200, $response->get_status() );
		$theme_data = $response->get_data();

		$plugin = array(); // fixme - get theme object
		$this->check_plugin_data( $plugin );
	}

	protected function check_plugin_data( $plugin ) {
		// todo: add plugin assertions
	}

	protected function allow_user_to_manage_multisite() {
		wp_set_current_user( $this->super_admin );
		$user = wp_get_current_user();

		if ( is_multisite() ) {
			update_site_option( 'site_admins', array( $user->user_login ) );
		}

		return;
	}

	/**
	 * Handles the difference between multisite and single site users.
	 *
	 * Special cases need to be handled in the tests still.
	 */
	protected function set_user_with_priviledges() {
		wp_set_current_user( $this->admin_id );
		if ( is_multisite() ) {
			$this->allow_user_to_manage_multisite();
		}
	}
}
