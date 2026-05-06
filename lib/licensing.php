<?php
/**
 * Lemon Squeezy licensing for Docs Raptor.
 *
 * @package docsraptor
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function docsraptor_get_license_option_name() {
	return 'docsraptor_license';
}

function docsraptor_get_license_page_slug() {
	return 'docsraptor-license';
}

function docsraptor_get_license_defaults() {
	return array(
		'license_key'       => '',
		'instance_id'       => '',
		'instance_name'     => '',
		'status'            => 'unlicensed',
		'license_id'        => 0,
		'activation_limit'  => 0,
		'activation_usage'  => 0,
		'expires_at'        => '',
		'store_id'          => 0,
		'order_id'          => 0,
		'order_item_id'     => 0,
		'product_id'        => 0,
		'product_name'      => '',
		'variant_id'        => 0,
		'variant_name'      => '',
		'customer_id'       => 0,
		'customer_name'     => '',
		'customer_email'    => '',
		'last_validated_at' => 0,
		'last_error'        => '',
	);
}

function docsraptor_get_license_data() {
	$data = get_option( docsraptor_get_license_option_name(), array() );
	if ( ! is_array( $data ) ) {
		$data = array();
	}

	return wp_parse_args( $data, docsraptor_get_license_defaults() );
}

function docsraptor_update_license_data( $data ) {
	return update_option( docsraptor_get_license_option_name(), wp_parse_args( $data, docsraptor_get_license_data() ), false );
}

function docsraptor_clear_license_data( $license_key = '' ) {
	$data                = docsraptor_get_license_defaults();
	$data['license_key'] = trim( (string) $license_key );

	return update_option( docsraptor_get_license_option_name(), $data, false );
}

function docsraptor_get_license_constraints() {
	$constraints = array(
		'store_id'   => defined( 'DOCSRAPTOR_LEMON_STORE_ID' ) ? absint( DOCSRAPTOR_LEMON_STORE_ID ) : 0,
		'product_id' => defined( 'DOCSRAPTOR_LEMON_PRODUCT_ID' ) ? absint( DOCSRAPTOR_LEMON_PRODUCT_ID ) : 0,
		'variant_id' => defined( 'DOCSRAPTOR_LEMON_VARIANT_ID' ) ? absint( DOCSRAPTOR_LEMON_VARIANT_ID ) : 0,
	);

	$constraints = apply_filters( 'docsraptor_license_constraints', $constraints );
	if ( ! is_array( $constraints ) ) {
		return array(
			'store_id'   => 0,
			'product_id' => 0,
			'variant_id' => 0,
		);
	}

	return array(
		'store_id'   => isset( $constraints['store_id'] ) ? absint( $constraints['store_id'] ) : 0,
		'product_id' => isset( $constraints['product_id'] ) ? absint( $constraints['product_id'] ) : 0,
		'variant_id' => isset( $constraints['variant_id'] ) ? absint( $constraints['variant_id'] ) : 0,
	);
}

function docsraptor_get_license_instance_name() {
	$host = wp_parse_url( home_url(), PHP_URL_HOST );
	if ( ! is_string( $host ) || '' === trim( $host ) ) {
		$host = get_bloginfo( 'name' );
	}

	$instance_name = (string) apply_filters( 'docsraptor_license_instance_name', trim( (string) $host ) );

	return '' !== trim( $instance_name ) ? $instance_name : 'WordPress Site';
}

function docsraptor_get_license_page_url() {
	return admin_url( 'admin.php?page=' . docsraptor_get_license_page_slug() );
}

function docsraptor_is_license_page() {
	return is_admin() && isset( $_GET['page'] ) && docsraptor_get_license_page_slug() === sanitize_key( wp_unslash( $_GET['page'] ) );
}

function docsraptor_is_licensed() {
	$data = docsraptor_get_license_data();

	return 'active' === $data['status'] && '' !== trim( (string) $data['license_key'] ) && '' !== trim( (string) $data['instance_id'] );
}

function docsraptor_license_blocks_backend_editing() {
	return ! docsraptor_is_licensed();
}

function docsraptor_is_backend_edit_context() {
	return is_admin() || wp_doing_ajax() || ( defined( 'REST_REQUEST' ) && REST_REQUEST );
}

function docsraptor_require_active_license_for_ajax() {
	if ( docsraptor_is_licensed() ) {
		return;
	}

	wp_send_json_error(
		array(
			'message'    => __( 'Docs Raptor requires an active license before editing docs.', 'docsraptor' ),
			'licenseUrl' => docsraptor_get_license_page_url(),
		),
		403
	);
}

function docsraptor_license_request( $endpoint, $body ) {
	$response = wp_remote_post(
		'https://api.lemonsqueezy.com/v1/licenses/' . ltrim( $endpoint, '/' ),
		array(
			'timeout' => 15,
			'headers' => array(
				'Accept'       => 'application/json',
				'Content-Type' => 'application/x-www-form-urlencoded',
			),
			'body'    => $body,
		)
	);

	if ( is_wp_error( $response ) ) {
		return $response;
	}

	$payload = json_decode( wp_remote_retrieve_body( $response ), true );
	if ( ! is_array( $payload ) ) {
		return new WP_Error( 'docsraptor_license_invalid_response', __( 'Licensing server returned an invalid response.', 'docsraptor' ) );
	}

	if ( (int) wp_remote_retrieve_response_code( $response ) >= 400 ) {
		return new WP_Error( 'docsraptor_license_api_error', ! empty( $payload['error'] ) ? (string) $payload['error'] : __( 'Licensing request failed.', 'docsraptor' ) );
	}

	return $payload;
}

function docsraptor_license_payload_matches_constraints( $payload ) {
	$constraints = docsraptor_get_license_constraints();
	$meta        = isset( $payload['meta'] ) && is_array( $payload['meta'] ) ? $payload['meta'] : array();

	foreach ( $constraints as $key => $expected ) {
		if ( ! $expected ) {
			continue;
		}

		$actual = isset( $meta[ $key ] ) ? absint( $meta[ $key ] ) : 0;
		if ( $actual !== $expected ) {
			return new WP_Error( 'docsraptor_license_product_mismatch', __( 'This license key does not belong to Docs Raptor.', 'docsraptor' ) );
		}
	}

	return true;
}

function docsraptor_normalize_license_payload( $payload, $overrides = array() ) {
	$defaults    = docsraptor_get_license_defaults();
	$license_key = isset( $payload['license_key'] ) && is_array( $payload['license_key'] ) ? $payload['license_key'] : array();
	$instance    = isset( $payload['instance'] ) && is_array( $payload['instance'] ) ? $payload['instance'] : array();
	$meta        = isset( $payload['meta'] ) && is_array( $payload['meta'] ) ? $payload['meta'] : array();

	return wp_parse_args(
		array(
			'license_key'       => isset( $overrides['license_key'] ) ? trim( (string) $overrides['license_key'] ) : ( isset( $license_key['key'] ) ? trim( (string) $license_key['key'] ) : '' ),
			'instance_id'       => isset( $overrides['instance_id'] ) ? trim( (string) $overrides['instance_id'] ) : ( isset( $instance['id'] ) ? trim( (string) $instance['id'] ) : '' ),
			'instance_name'     => isset( $overrides['instance_name'] ) ? trim( (string) $overrides['instance_name'] ) : ( isset( $instance['name'] ) ? trim( (string) $instance['name'] ) : '' ),
			'status'            => isset( $overrides['status'] ) ? sanitize_key( $overrides['status'] ) : ( isset( $license_key['status'] ) ? sanitize_key( $license_key['status'] ) : 'unlicensed' ),
			'license_id'        => isset( $license_key['id'] ) ? absint( $license_key['id'] ) : 0,
			'activation_limit'  => isset( $license_key['activation_limit'] ) ? (int) $license_key['activation_limit'] : 0,
			'activation_usage'  => isset( $license_key['activation_usage'] ) ? (int) $license_key['activation_usage'] : 0,
			'expires_at'        => isset( $license_key['expires_at'] ) ? (string) $license_key['expires_at'] : '',
			'store_id'          => isset( $meta['store_id'] ) ? absint( $meta['store_id'] ) : 0,
			'order_id'          => isset( $meta['order_id'] ) ? absint( $meta['order_id'] ) : 0,
			'order_item_id'     => isset( $meta['order_item_id'] ) ? absint( $meta['order_item_id'] ) : 0,
			'product_id'        => isset( $meta['product_id'] ) ? absint( $meta['product_id'] ) : 0,
			'product_name'      => isset( $meta['product_name'] ) ? (string) $meta['product_name'] : '',
			'variant_id'        => isset( $meta['variant_id'] ) ? absint( $meta['variant_id'] ) : 0,
			'variant_name'      => isset( $meta['variant_name'] ) ? (string) $meta['variant_name'] : '',
			'customer_id'       => isset( $meta['customer_id'] ) ? absint( $meta['customer_id'] ) : 0,
			'customer_name'     => isset( $meta['customer_name'] ) ? (string) $meta['customer_name'] : '',
			'customer_email'    => isset( $meta['customer_email'] ) ? (string) $meta['customer_email'] : '',
			'last_validated_at' => isset( $overrides['last_validated_at'] ) ? absint( $overrides['last_validated_at'] ) : time(),
			'last_error'        => isset( $overrides['last_error'] ) ? (string) $overrides['last_error'] : '',
		),
		$defaults
	);
}

function docsraptor_get_license_page_state() {
	$data        = docsraptor_get_license_data();
	$is_licensed = docsraptor_is_licensed();

	return array(
		'licenseKey'        => (string) $data['license_key'],
		'statusLabel'       => $is_licensed ? __( 'Licensed', 'docsraptor' ) : __( 'Not licensed', 'docsraptor' ),
		'statusClass'       => $is_licensed ? 'success' : 'warning',
		'statusDescription' => $is_licensed ? __( 'Backend docs editing is enabled.', 'docsraptor' ) : __( 'Frontend docs remain available, but backend docs editing is disabled until a valid license key is activated.', 'docsraptor' ),
		'instanceName'      => ! empty( $data['instance_id'] ) ? (string) $data['instance_name'] : __( 'None', 'docsraptor' ),
		'customerName'      => ! empty( $data['customer_name'] ) ? (string) $data['customer_name'] : '—',
		'customerEmail'     => (string) $data['customer_email'],
		'lastError'         => (string) $data['last_error'],
		'canRefresh'        => '' !== trim( (string) $data['license_key'] ),
		'canDeactivate'     => '' !== trim( (string) $data['instance_id'] ),
	);
}

function docsraptor_get_license_action_result( $success, $message ) {
	return array(
		'success' => (bool) $success,
		'message' => (string) $message,
		'state'   => docsraptor_get_license_page_state(),
	);
}

function docsraptor_activate_license_key( $license_key ) {
	$license_key = trim( (string) $license_key );
	if ( '' === $license_key ) {
		return docsraptor_get_license_action_result( false, __( 'Enter a license key first.', 'docsraptor' ) );
	}

	$current_data = docsraptor_get_license_data();
	$instance_name = docsraptor_get_license_instance_name();
	$result        = docsraptor_license_request(
		'activate',
		array(
			'license_key'   => $license_key,
			'instance_name' => $instance_name,
		)
	);

	if ( is_wp_error( $result ) ) {
		docsraptor_update_license_data( array( 'license_key' => $license_key, 'status' => 'unlicensed', 'instance_id' => '', 'last_error' => $result->get_error_message() ) );
		return docsraptor_get_license_action_result( false, $result->get_error_message() );
	}

	if ( empty( $result['activated'] ) || empty( $result['instance']['id'] ) ) {
		$message = ! empty( $result['error'] ) ? (string) $result['error'] : __( 'License activation failed.', 'docsraptor' );
		docsraptor_update_license_data( array( 'license_key' => $license_key, 'status' => 'unlicensed', 'instance_id' => '', 'last_error' => $message ) );
		return docsraptor_get_license_action_result( false, $message );
	}

	$constraint_check = docsraptor_license_payload_matches_constraints( $result );
	if ( is_wp_error( $constraint_check ) ) {
		docsraptor_license_request( 'deactivate', array( 'license_key' => $license_key, 'instance_id' => (string) $result['instance']['id'] ) );
		docsraptor_update_license_data( array( 'license_key' => $license_key, 'status' => 'unlicensed', 'instance_id' => '', 'last_error' => $constraint_check->get_error_message() ) );
		return docsraptor_get_license_action_result( false, $constraint_check->get_error_message() );
	}

	$new_data = docsraptor_normalize_license_payload( $result, array( 'license_key' => $license_key, 'instance_name' => $instance_name, 'status' => 'active', 'last_error' => '' ) );
	docsraptor_update_license_data( $new_data );

	if ( $current_data['instance_id'] && $current_data['license_key'] && ( $current_data['license_key'] !== $new_data['license_key'] || $current_data['instance_id'] !== $new_data['instance_id'] ) ) {
		docsraptor_license_request( 'deactivate', array( 'license_key' => (string) $current_data['license_key'], 'instance_id' => (string) $current_data['instance_id'] ) );
	}

	return docsraptor_get_license_action_result( true, __( 'Docs Raptor is now licensed.', 'docsraptor' ) );
}

function docsraptor_refresh_license_state() {
	$data = docsraptor_get_license_data();
	if ( '' === trim( (string) $data['license_key'] ) ) {
		return docsraptor_get_license_action_result( false, __( 'There is no saved license key to validate.', 'docsraptor' ) );
	}

	$request = array( 'license_key' => (string) $data['license_key'] );
	if ( $data['instance_id'] ) {
		$request['instance_id'] = (string) $data['instance_id'];
	}

	$result = docsraptor_license_request( 'validate', $request );
	if ( is_wp_error( $result ) ) {
		docsraptor_update_license_data( array( 'last_error' => $result->get_error_message() ) );
		return docsraptor_get_license_action_result( false, $result->get_error_message() );
	}

	$constraint_check = docsraptor_license_payload_matches_constraints( $result );
	if ( ! empty( $result['valid'] ) && ! is_wp_error( $constraint_check ) ) {
		docsraptor_update_license_data(
			docsraptor_normalize_license_payload(
				$result,
				array(
					'license_key'   => (string) $data['license_key'],
					'instance_id'   => ! empty( $result['instance']['id'] ) ? (string) $result['instance']['id'] : (string) $data['instance_id'],
					'instance_name' => ! empty( $result['instance']['name'] ) ? (string) $result['instance']['name'] : (string) $data['instance_name'],
					'status'        => 'active',
					'last_error'    => '',
				)
			)
		);
		return docsraptor_get_license_action_result( true, __( 'License validated.', 'docsraptor' ) );
	}

	$message = is_wp_error( $constraint_check ) ? $constraint_check->get_error_message() : ( ! empty( $result['error'] ) ? (string) $result['error'] : __( 'License is no longer valid.', 'docsraptor' ) );
	docsraptor_update_license_data( array( 'status' => isset( $result['license_key']['status'] ) ? sanitize_key( $result['license_key']['status'] ) : 'unlicensed', 'last_validated_at' => time(), 'last_error' => $message ) );

	return docsraptor_get_license_action_result( false, $message );
}

function docsraptor_deactivate_license_state() {
	$data = docsraptor_get_license_data();
	if ( '' === trim( (string) $data['license_key'] ) || '' === trim( (string) $data['instance_id'] ) ) {
		docsraptor_clear_license_data();
		return docsraptor_get_license_action_result( true, __( 'Stored license data cleared.', 'docsraptor' ) );
	}

	$result = docsraptor_license_request( 'deactivate', array( 'license_key' => (string) $data['license_key'], 'instance_id' => (string) $data['instance_id'] ) );
	if ( is_wp_error( $result ) ) {
		return docsraptor_get_license_action_result( false, $result->get_error_message() );
	}

	if ( empty( $result['deactivated'] ) ) {
		return docsraptor_get_license_action_result( false, ! empty( $result['error'] ) ? (string) $result['error'] : __( 'License deactivation failed.', 'docsraptor' ) );
	}

	docsraptor_clear_license_data();

	return docsraptor_get_license_action_result( true, __( 'License deactivated.', 'docsraptor' ) );
}

function docsraptor_register_license_page() {
	add_submenu_page( null, __( 'Docs Raptor Licensing', 'docsraptor' ), __( 'Docs Raptor Licensing', 'docsraptor' ), 'manage_options', docsraptor_get_license_page_slug(), 'docsraptor_render_license_page' );
}

function docsraptor_enqueue_license_assets() {
	if ( ! docsraptor_is_license_page() ) {
		return;
	}

	wp_enqueue_script( 'jquery' );
	wp_enqueue_script( 'docsraptor-license-js', DOCSRAPTOR_PATH . 'assets/js/licensing.js', array( 'jquery' ), docsraptor_get_asset_version( 'assets/js/licensing.js' ), true );
	wp_localize_script(
		'docsraptor-license-js',
		'DocsRaptorLicense',
		array(
			'ajaxUrl'      => admin_url( 'admin-ajax.php' ),
			'nonce'        => wp_create_nonce( 'docsraptor_license_ajax' ),
			'initialState' => docsraptor_get_license_page_state(),
			'strings'      => array(
				'checking'     => __( 'Checking license key...', 'docsraptor' ),
				'refreshing'   => __( 'Refreshing license status...', 'docsraptor' ),
				'deactivating' => __( 'Deactivating license...', 'docsraptor' ),
				'empty'        => __( 'Enter a license key first.', 'docsraptor' ),
				'requestFailed'=> __( 'The request could not be completed. Reload the page and try again.', 'docsraptor' ),
			),
		)
	);
}

function docsraptor_render_license_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'You do not have permission to access this page.', 'docsraptor' ) );
	}

	$state = docsraptor_get_license_page_state();
	?>
	<div class="wrap" id="docsraptor-license-page">
		<h1><?php echo esc_html__( 'Docs Raptor Licensing', 'docsraptor' ); ?></h1>
		<div id="docsraptor-license-notices"></div>
		<div class="notice notice-<?php echo esc_attr( $state['statusClass'] ); ?>" id="docsraptor-license-status">
			<p><strong id="docsraptor-license-status-label"><?php echo esc_html( $state['statusLabel'] ); ?></strong></p>
			<p id="docsraptor-license-status-description"><?php echo esc_html( $state['statusDescription'] ); ?></p>
		</div>
		<table class="form-table" role="presentation">
			<tbody>
				<tr>
					<th scope="row"><label for="docsraptor_license_key"><?php echo esc_html__( 'License Key', 'docsraptor' ); ?></label></th>
					<td>
						<input type="text" class="regular-text code" id="docsraptor_license_key" value="<?php echo esc_attr( $state['licenseKey'] ); ?>" data-current-license-key="<?php echo esc_attr( $state['licenseKey'] ); ?>" autocomplete="off" spellcheck="false" />
						<p class="description"><?php echo esc_html__( 'Frontend docs remain available without a license. A license is required to edit docs in wp-admin.', 'docsraptor' ); ?></p>
						<p class="description" id="docsraptor-license-activity" style="display:none;"></p>
					</td>
				</tr>
				<tr><th scope="row"><?php echo esc_html__( 'Current Activation', 'docsraptor' ); ?></th><td><p id="docsraptor-license-instance-name"><?php echo esc_html( $state['instanceName'] ); ?></p></td></tr>
				<tr>
					<th scope="row"><?php echo esc_html__( 'Customer', 'docsraptor' ); ?></th>
					<td>
						<p id="docsraptor-license-customer-name"><?php echo esc_html( $state['customerName'] ); ?></p>
						<p class="description" id="docsraptor-license-customer-email"<?php echo $state['customerEmail'] ? '' : ' style="display:none;"'; ?>><?php echo esc_html( $state['customerEmail'] ); ?></p>
					</td>
				</tr>
			</tbody>
		</table>
		<p class="submit">
			<button type="button" class="button button-secondary" id="docsraptor-license-refresh"<?php echo $state['canRefresh'] ? '' : ' disabled'; ?>><?php echo esc_html__( 'Refresh Status', 'docsraptor' ); ?></button>
			<button type="button" class="button button-link-delete" id="docsraptor-license-deactivate"<?php echo $state['canDeactivate'] ? '' : ' disabled'; ?>><?php echo esc_html__( 'Deactivate License', 'docsraptor' ); ?></button>
			<span class="spinner" id="docsraptor-license-spinner" style="float:none;"></span>
		</p>
	</div>
	<?php
}

function docsraptor_verify_license_ajax_request() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( array( 'message' => __( 'You do not have permission to manage licensing.', 'docsraptor' ), 'state' => docsraptor_get_license_page_state() ) );
	}
	if ( ! check_ajax_referer( 'docsraptor_license_ajax', 'nonce', false ) ) {
		wp_send_json_error( array( 'message' => __( 'Security check failed. Reload the page and try again.', 'docsraptor' ), 'state' => docsraptor_get_license_page_state() ) );
	}
}

function docsraptor_ajax_activate_license() {
	docsraptor_verify_license_ajax_request();
	$result = docsraptor_activate_license_key( isset( $_POST['license_key'] ) ? sanitize_text_field( wp_unslash( $_POST['license_key'] ) ) : '' );
	$result['success'] ? wp_send_json_success( $result ) : wp_send_json_error( $result );
}

function docsraptor_ajax_refresh_license() {
	docsraptor_verify_license_ajax_request();
	$result = docsraptor_refresh_license_state();
	$result['success'] ? wp_send_json_success( $result ) : wp_send_json_error( $result );
}

function docsraptor_ajax_deactivate_license() {
	docsraptor_verify_license_ajax_request();
	$result = docsraptor_deactivate_license_state();
	$result['success'] ? wp_send_json_success( $result ) : wp_send_json_error( $result );
}

function docsraptor_add_plugin_action_links( $links ) {
	array_unshift( $links, sprintf( '<a href="%s">%s</a>', esc_url( docsraptor_get_license_page_url() ), esc_html__( 'Licensing', 'docsraptor' ) ) );
	return $links;
}

function docsraptor_maybe_show_unlicensed_notice() {
	if ( ! is_admin() || ! current_user_can( 'manage_options' ) || docsraptor_is_license_page() || docsraptor_is_licensed() ) {
		return;
	}

	$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
	$is_docs_screen = $screen && ( 'docs' === $screen->post_type || in_array( $screen->taxonomy, array( 'docs-categories', 'docs-collections' ), true ) );
	if ( ! $is_docs_screen ) {
		return;
	}
	?>
	<div class="notice notice-warning">
		<p>
			<?php echo esc_html__( 'Docs Raptor backend editing requires an active license. Frontend docs remain available.', 'docsraptor' ); ?>
			<a href="<?php echo esc_url( docsraptor_get_license_page_url() ); ?>"><?php echo esc_html__( 'Open licensing.', 'docsraptor' ); ?></a>
		</p>
	</div>
	<?php
}

function docsraptor_block_unlicensed_admin_routes() {
	if ( ! docsraptor_license_blocks_backend_editing() || ! current_user_can( 'edit_posts' ) || docsraptor_is_license_page() ) {
		return;
	}

	global $pagenow;
	$is_blocked = false;

	if ( 'post-new.php' === $pagenow && isset( $_GET['post_type'] ) && 'docs' === sanitize_key( wp_unslash( $_GET['post_type'] ) ) ) {
		$is_blocked = true;
	}

	if ( 'post.php' === $pagenow && isset( $_GET['post'], $_GET['action'] ) && 'edit' === sanitize_key( wp_unslash( $_GET['action'] ) ) ) {
		$post = get_post( absint( $_GET['post'] ) );
		$is_blocked = $post && 'docs' === $post->post_type;
	}

	if ( in_array( $pagenow, array( 'term.php', 'edit-tags.php' ), true ) && isset( $_GET['taxonomy'] ) && in_array( sanitize_key( wp_unslash( $_GET['taxonomy'] ) ), array( 'docs-categories', 'docs-collections' ), true ) ) {
		$is_blocked = true;
	}

	if ( $is_blocked ) {
		wp_safe_redirect( docsraptor_get_license_page_url() );
		exit;
	}
}

function docsraptor_block_unlicensed_docs_rest_save( $prepared_post, $request ) {
	if ( docsraptor_is_licensed() ) {
		return $prepared_post;
	}

	return new WP_Error( 'docsraptor_license_required', __( 'Docs Raptor requires an active license before editing docs.', 'docsraptor' ), array( 'status' => 403 ) );
}

function docsraptor_block_unlicensed_docs_terms( $term, $taxonomy ) {
	if ( docsraptor_is_licensed() || ! docsraptor_is_backend_edit_context() || ! in_array( $taxonomy, array( 'docs-categories', 'docs-collections' ), true ) ) {
		return $term;
	}

	return new WP_Error( 'docsraptor_license_required', __( 'Docs Raptor requires an active license before editing docs taxonomy terms.', 'docsraptor' ) );
}

function docsraptor_block_unlicensed_docs_delete_term( $delete, $term, $taxonomy ) {
	if ( docsraptor_is_licensed() || ! docsraptor_is_backend_edit_context() || ! in_array( $taxonomy, array( 'docs-categories', 'docs-collections' ), true ) ) {
		return $delete;
	}

	return new WP_Error( 'docsraptor_license_required', __( 'Docs Raptor requires an active license before deleting docs taxonomy terms.', 'docsraptor' ) );
}

function docsraptor_block_unlicensed_docs_caps( $caps, $cap, $user_id, $args ) {
	if ( docsraptor_is_licensed() || ! docsraptor_is_backend_edit_context() || ! in_array( $cap, array( 'edit_post', 'delete_post' ), true ) || empty( $args[0] ) ) {
		return $caps;
	}

	$post = get_post( absint( $args[0] ) );
	if ( ! $post || 'docs' !== $post->post_type ) {
		return $caps;
	}

	return array( 'do_not_allow' );
}

function docsraptor_maybe_revalidate_license() {
	if ( ! is_admin() || ! current_user_can( 'manage_options' ) || wp_doing_ajax() || docsraptor_is_license_page() ) {
		return;
	}

	$data = docsraptor_get_license_data();
	if ( 'active' !== $data['status'] || ! $data['license_key'] || ! $data['instance_id'] ) {
		return;
	}

	$interval = (int) apply_filters( 'docsraptor_license_validation_interval', 12 * HOUR_IN_SECONDS );
	if ( $interval < HOUR_IN_SECONDS ) {
		$interval = HOUR_IN_SECONDS;
	}

	if ( $data['last_validated_at'] && ( time() - (int) $data['last_validated_at'] ) < $interval ) {
		return;
	}

	docsraptor_refresh_license_state();
}

add_action( 'admin_menu', 'docsraptor_register_license_page' );
add_action( 'admin_enqueue_scripts', 'docsraptor_enqueue_license_assets' );
add_action( 'admin_notices', 'docsraptor_maybe_show_unlicensed_notice' );
add_action( 'admin_init', 'docsraptor_block_unlicensed_admin_routes' );
add_action( 'admin_init', 'docsraptor_maybe_revalidate_license' );
add_action( 'wp_ajax_docsraptor_license_activate', 'docsraptor_ajax_activate_license' );
add_action( 'wp_ajax_docsraptor_license_refresh', 'docsraptor_ajax_refresh_license' );
add_action( 'wp_ajax_docsraptor_license_deactivate', 'docsraptor_ajax_deactivate_license' );
add_filter( 'plugin_action_links_' . DOCSRAPTOR_BASENAME, 'docsraptor_add_plugin_action_links' );
add_filter( 'rest_pre_insert_docs', 'docsraptor_block_unlicensed_docs_rest_save', 10, 2 );
add_filter( 'pre_insert_term', 'docsraptor_block_unlicensed_docs_terms', 10, 2 );
add_filter( 'pre_delete_term', 'docsraptor_block_unlicensed_docs_delete_term', 10, 3 );
add_filter( 'map_meta_cap', 'docsraptor_block_unlicensed_docs_caps', 10, 4 );
