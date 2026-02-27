<?php
/**
 * RetainWoo Admin
 *
 * Handles admin menu, settings registration, and dashboard pages.
 *
 * @package RetainWoo
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * RetainWoo Admin class.
 */
class RetainWoo_Admin {

	/**
	 * Initialize the admin.
	 */
	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'menu' ) );
		add_action( 'admin_init', array( __CLASS__, 'register_settings' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue' ) );
	}

	/**
	 * Register admin menu pages.
	 */
	public static function menu() {
		add_menu_page(
			__( 'RetainWoo', 'retainwoo' ),
			__( 'RetainWoo', 'retainwoo' ),
			'manage_woocommerce', // phpcs:ignore WordPress.WP.Capabilities.Unknown
			'retainwoo',
			array( __CLASS__, 'dashboard' ),
			'dashicons-shield-alt',
			58
		);
		add_submenu_page( 'retainwoo', __( 'Dashboard', 'retainwoo' ), __( 'Dashboard', 'retainwoo' ), 'manage_woocommerce', 'retainwoo', array( __CLASS__, 'dashboard' ) ); // phpcs:ignore WordPress.WP.Capabilities.Unknown
		add_submenu_page( 'retainwoo', __( 'Settings', 'retainwoo' ), __( 'Settings', 'retainwoo' ), 'manage_woocommerce', 'retainwoo-settings', array( __CLASS__, 'settings' ) ); // phpcs:ignore WordPress.WP.Capabilities.Unknown
	}

	/**
	 * Register plugin settings.
	 */
	public static function register_settings() {
		$keys = array(
			'retainwoo_enabled',
			'retainwoo_offer_pause',
			'retainwoo_offer_skip',
			'retainwoo_skip_cooldown',
			'retainwoo_offer_discount',
			'retainwoo_discount_amount',
			'retainwoo_discount_type',
			'retainwoo_headline',
			'retainwoo_subheadline',
			'retainwoo_winback_enabled',
			'retainwoo_winback_subject',
			'retainwoo_winback_delay',
		);

		foreach ( $keys as $k ) {
			register_setting(
				'retainwoo_options',
				$k,
				array(
					'sanitize_callback' => array( __CLASS__, 'sanitize_option' ),
				)
			);
		}
	}

	/**
	 * Sanitize a single option value.
	 *
	 * @param mixed $value The option value to sanitize.
	 * @return mixed
	 */
	public static function sanitize_option( $value ) {
		if ( is_array( $value ) ) {
			return array_map( 'sanitize_text_field', $value );
		}

		if ( is_numeric( $value ) ) {
			return absint( $value );
		}

		if ( is_email( $value ) ) {
			return sanitize_email( $value );
		}

		return sanitize_text_field( $value );
	}

	/**
	 * Enqueue admin styles.
	 *
	 * @param string $hook Current admin page hook.
	 */
	public static function enqueue( $hook ) {
		if ( false === strpos( $hook, 'retainwoo' ) ) {
			return;
		}
		// Shared tokens first.
		wp_enqueue_style( 'retainwoo-common', RETAINWOO_URL . 'assets/css/retainwoo-common.css', array(), RETAINWOO_VERSION );
		wp_enqueue_style( 'retainwoo-admin', RETAINWOO_URL . 'assets/css/retainwoo-admin.css', array(), RETAINWOO_VERSION );
	}

	/**
	 * Render admin navigation tabs.
	 *
	 * @param string $current Current page slug.
	 */
	private static function nav( $current ) {
		$pages = array(
			'retainwoo'          => array(
				'icon'  => '📊',
				'label' => __( 'Dashboard', 'retainwoo' ),
			),
			'retainwoo-settings' => array(
				'icon'  => '⚙️',
				'label' => __( 'Settings', 'retainwoo' ),
			),
		);
		echo '<div class="cs-nav">';
		foreach ( $pages as $slug => $item ) {
			$url    = admin_url( 'admin.php?page=' . $slug );
			$active = ( $current === $slug ) ? 'cs-active' : '';
			echo '<a href="' . esc_url( $url ) . '" class="' . esc_attr( $active ) . '">' . esc_html( $item['icon'] ) . ' ' . esc_html( $item['label'] ) . '</a>';
		}
		echo '</div>';
	}

	/**
	 * Render the dashboard page.
	 */
	public static function dashboard() {
		$stats  = RetainWoo_Tracker::get_stats( 30 );
		$plugin = RetainWoo_Compat::get_plugin_name();
		?>
		<div class="wrap cs-wrap">

			<!-- Header -->
			<div class="cs-page-header">
				<div class="cs-page-header-left">
					<?php
						$logo_file = file_exists( RETAINWOO_PATH . 'assets/images/logo.svg' ) ? 'logo.svg' : '';
						$logo_url  = $logo_file ? RETAINWOO_URL . 'assets/images/' . $logo_file : '';
					?>
					<div class="cs-logo">
					<?php
					if ( $logo_url ) :
						?>
						<img src="<?php echo esc_url( $logo_url ); ?>" alt="<?php esc_attr_e( 'RetainWoo', 'retainwoo' ); ?>" width="42" height="42" />
						<?php
					else :
						?>
						🛡️<?php endif; ?></div>
					<div>
						<h1><?php esc_html_e( 'RetainWoo', 'retainwoo' ); ?></h1>
						<div class="cs-version">v<?php echo esc_html( RETAINWOO_VERSION ); ?> <?php esc_html_e( 'Free', 'retainwoo' ); ?></div>
					</div>
				</div>
				<div class="cs-plugin-badge">
					<span class="cs-plugin-badge-dot"></span>
					<?php esc_html_e( 'Connected:', 'retainwoo' ); ?> <?php echo esc_html( $plugin ); ?>
				</div>
			</div>

			<?php self::nav( 'retainwoo' ); ?>

			<!-- Stats -->
			<div class="cs-section-title"><?php esc_html_e( 'Last 30 days', 'retainwoo' ); ?></div>
			<div class="cs-stats-row">
				<div class="cs-stat cs-green">
					<span class="cs-stat-icon"><img src="<?php echo esc_url( RETAINWOO_URL . 'assets/images/icon-cancellations-saved.svg' ); ?>" alt="<?php esc_attr_e( 'Cancellations Saved', 'retainwoo' ); ?>" width="64" height="64" /></span>
					<div class="cs-stat-num"><?php echo esc_html( $stats['saved'] ); ?></div>
					<div class="cs-stat-lbl"><?php esc_html_e( 'Cancellations Saved', 'retainwoo' ); ?></div>
				</div>
				<div class="cs-stat cs-blue">
					<span class="cs-stat-icon"><img src="<?php echo esc_url( RETAINWOO_URL . 'assets/images/icon-revenue-saved.svg' ); ?>" alt="<?php esc_attr_e( 'Est. Revenue Saved', 'retainwoo' ); ?>" width="64" height="64" /></span>
					<div class="cs-stat-num">$<?php echo esc_html( number_format( $stats['rev_saved'], 0 ) ); ?></div>
					<div class="cs-stat-lbl"><?php esc_html_e( 'Est. Revenue Saved', 'retainwoo' ); ?></div>
				</div>
				<div class="cs-stat cs-purple">
					<span class="cs-stat-icon"><img src="<?php echo esc_url( RETAINWOO_URL . 'assets/images/icon-save-rate.svg' ); ?>" alt="<?php esc_attr_e( 'Save Rate', 'retainwoo' ); ?>" width="64" height="64" /></span>
					<div class="cs-stat-num"><?php echo esc_html( $stats['save_rate'] ); ?>%</div>
					<div class="cs-stat-lbl"><?php esc_html_e( 'Save Rate', 'retainwoo' ); ?></div>
				</div>
				<div class="cs-stat cs-amber">
					<span class="cs-stat-icon"><img src="<?php echo esc_url( RETAINWOO_URL . 'assets/images/icon-popups-shown.svg' ); ?>" alt="<?php esc_attr_e( 'Popups Shown', 'retainwoo' ); ?>" width="64" height="64" /></span>
					<div class="cs-stat-num"><?php echo esc_html( $stats['shown'] ); ?></div>
					<div class="cs-stat-lbl"><?php esc_html_e( 'Popups Shown', 'retainwoo' ); ?></div>
				</div>
			</div>

			<!-- Two column -->
			<div class="cs-two-col">

				<!-- Offer breakdown -->
				<div class="cs-card">
					<div class="cs-card-head">
						<h2>🎯 <?php esc_html_e( 'Offer Performance', 'retainwoo' ); ?></h2>
					</div>
					<div class="cs-card-body">
						<?php if ( empty( $stats['breakdown'] ) ) : ?>
							<div class="cs-empty">
								<span class="cs-empty-icon">📭</span>
								<?php esc_html_e( 'No offers accepted yet. Your popup is live and tracking — data will appear here once subscribers interact with it.', 'retainwoo' ); ?>
							</div>
						<?php else : ?>
							<table class="cs-table">
								<thead>
									<tr>
										<th><?php esc_html_e( 'Offer', 'retainwoo' ); ?></th>
										<th><?php esc_html_e( 'Accepted', 'retainwoo' ); ?></th>
									</tr>
								</thead>
								<tbody>
									<?php
									foreach ( $stats['breakdown'] as $row ) :
										$icons = array(
											'pause_1'  => '⏸',
											'pause_3'  => '⏸',
											'skip'     => '⏭',
											'discount' => '🎁',
										);
										$icon  = isset( $icons[ $row->offer ] ) ? $icons[ $row->offer ] : '•';
										?>
										<tr>
											<td><?php echo esc_html( $icon . ' ' . ucfirst( str_replace( '_', ' ', $row->offer ) ) ); ?></td>
											<td><span class="cs-pill"><?php /* translators: %s: number of times */ printf( esc_html__( '%s times', 'retainwoo' ), esc_html( $row->cnt ) ); ?></span></td>
										</tr>
									<?php endforeach; ?>
								</tbody>
							</table>
						<?php endif; ?>
					</div>
				</div>

				<!-- Tips -->
				<div class="cs-card">
					<div class="cs-card-head">
						<h2>💡 <?php esc_html_e( 'Tips to Improve', 'retainwoo' ); ?></h2>
					</div>
					<div class="cs-card-body">
						<ul class="cs-tips">
							<li><span class="cs-tip-icon">🎯</span> <?php esc_html_e( 'Enable all 3 offers — more options = higher save rate', 'retainwoo' ); ?></li>
							<li><span class="cs-tip-icon">💸</span> <?php esc_html_e( '20% discount is the sweet spot for most stores', 'retainwoo' ); ?></li>
							<li><span class="cs-tip-icon">📧</span> <?php esc_html_e( 'Win-back emails convert at 5-15% — keep them enabled', 'retainwoo' ); ?></li>
							<li><span class="cs-tip-icon">📊</span> <?php esc_html_e( 'A save rate above 20% is considered excellent', 'retainwoo' ); ?></li>
							<li><span class="cs-tip-icon">✏️</span> <?php esc_html_e( 'Customize your headline to match your brand voice', 'retainwoo' ); ?></li>
						</ul>
					</div>
				</div>
			</div>

		</div>
		<?php
	}

	/**
	 * Render the settings page.
	 */
	public static function settings() {
		if ( isset( $_GET['settings-updated'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			echo '<div class="notice notice-success is-dismissible"><p>✅ ' . esc_html__( 'Settings saved successfully.', 'retainwoo' ) . '</p></div>';
		}
		?>
		<div class="wrap cs-wrap">

			<!-- Header -->
			<div class="cs-page-header">
				<div class="cs-page-header-left">
					<?php
						$logo_file = file_exists( RETAINWOO_PATH . 'assets/images/logo.svg' ) ? 'logo.svg' : '';
						$logo_url  = $logo_file ? RETAINWOO_URL . 'assets/images/' . $logo_file : '';
					?>
					<div class="cs-logo">
					<?php
					if ( $logo_url ) :
						?>
						<img src="<?php echo esc_url( $logo_url ); ?>" alt="<?php esc_attr_e( 'RetainWoo', 'retainwoo' ); ?>" width="42" height="42" />
						<?php
					else :
						?>
						🛡️<?php endif; ?></div>
					<div>
						<h1><?php esc_html_e( 'RetainWoo', 'retainwoo' ); ?></h1>
						<div class="cs-version">v<?php echo esc_html( RETAINWOO_VERSION ); ?> <?php esc_html_e( 'Free', 'retainwoo' ); ?></div>
					</div>
				</div>
			</div>

			<?php self::nav( 'retainwoo-settings' ); ?>

			<form method="post" action="options.php">
				<?php settings_fields( 'retainwoo_options' ); ?>

				<!-- General -->
				<div class="cs-settings-section">
					<div class="cs-settings-head">
						<span class="cs-head-icon">⚙️</span>
						<h3><?php esc_html_e( 'General', 'retainwoo' ); ?></h3>
					</div>
					<div class="cs-settings-body">
						<div class="cs-field">
							<div class="cs-field-label">
								<strong><?php esc_html_e( 'Enable RetainWoo', 'retainwoo' ); ?></strong>
								<span><?php esc_html_e( 'Show popup when subscribers click cancel', 'retainwoo' ); ?></span>
							</div>
							<div class="cs-field-control">
								<label class="cs-toggle">
									<input type="checkbox" name="retainwoo_enabled" value="1"
										<?php checked( get_option( 'retainwoo_enabled' ), '1' ); ?>>
									<span class="cs-toggle-track"><span class="cs-toggle-knob"></span></span>
									<span class="cs-toggle-label"><?php esc_html_e( 'Active', 'retainwoo' ); ?></span>
								</label>
							</div>
						</div>
						<div class="cs-field">
							<div class="cs-field-label">
								<strong><?php esc_html_e( 'Detected Plugin', 'retainwoo' ); ?></strong>
								<span><?php esc_html_e( 'Auto-detected subscription plugin', 'retainwoo' ); ?></span>
							</div>
							<div class="cs-field-control">
								<span class="cs-plugin-badge">
									<span class="cs-plugin-badge-dot"></span>
									<?php echo esc_html( RetainWoo_Compat::get_plugin_name() ); ?>
								</span>
							</div>
						</div>
					</div>
				</div>

				<!-- Popup text -->
				<div class="cs-settings-section">
					<div class="cs-settings-head">
						<span class="cs-head-icon">✏️</span>
						<h3><?php esc_html_e( 'Popup Text', 'retainwoo' ); ?></h3>
					</div>
					<div class="cs-settings-body">
						<div class="cs-field">
							<div class="cs-field-label">
								<strong><?php esc_html_e( 'Headline', 'retainwoo' ); ?></strong>
								<span><?php esc_html_e( 'Main title shown in popup', 'retainwoo' ); ?></span>
							</div>
							<div class="cs-field-control">
								<input type="text" name="retainwoo_headline" style="width:100%"
									value="<?php echo esc_attr( get_option( 'retainwoo_headline' ) ); ?>">
							</div>
						</div>
						<div class="cs-field">
							<div class="cs-field-label">
								<strong><?php esc_html_e( 'Subheadline', 'retainwoo' ); ?></strong>
								<span><?php esc_html_e( 'Supporting message below headline', 'retainwoo' ); ?></span>
							</div>
							<div class="cs-field-control">
								<input type="text" name="retainwoo_subheadline" style="width:100%"
									value="<?php echo esc_attr( get_option( 'retainwoo_subheadline' ) ); ?>">
							</div>
						</div>
					</div>
				</div>

				<!-- Retention offers -->
				<div class="cs-settings-section">
					<div class="cs-settings-head">
						<span class="cs-head-icon">🎯</span>
						<h3><?php esc_html_e( 'Retention Offers', 'retainwoo' ); ?></h3>
					</div>
					<div class="cs-settings-body">
						<div class="cs-field">
							<div class="cs-field-label">
								<strong>⏸ <?php esc_html_e( 'Pause Subscription', 'retainwoo' ); ?></strong>
								<span><?php esc_html_e( 'Let customers pause for 1 or 3 months', 'retainwoo' ); ?></span>
							</div>
							<div class="cs-field-control">
								<label class="cs-toggle">
									<input type="checkbox" name="retainwoo_offer_pause" value="1"
										<?php checked( get_option( 'retainwoo_offer_pause' ), '1' ); ?>>
									<span class="cs-toggle-track"><span class="cs-toggle-knob"></span></span>
									<span class="cs-toggle-label"><?php esc_html_e( 'Enabled', 'retainwoo' ); ?></span>
								</label>
							</div>
						</div>
						<div class="cs-field">
							<div class="cs-field-label">
								<strong>⏭ <?php esc_html_e( 'Skip Next Payment', 'retainwoo' ); ?></strong>
								<span><?php esc_html_e( 'Skip one billing cycle', 'retainwoo' ); ?></span>
							</div>
							<div class="cs-field-control">
								<label class="cs-toggle">
									<input type="checkbox" name="retainwoo_offer_skip" value="1"
										<?php checked( get_option( 'retainwoo_offer_skip' ), '1' ); ?>>
									<span class="cs-toggle-track"><span class="cs-toggle-knob"></span></span>
									<span class="cs-toggle-label"><?php esc_html_e( 'Enabled', 'retainwoo' ); ?></span>
								</label>
							</div>
						</div>
						<div class="cs-field">
							<div class="cs-field-label">
								<strong><?php esc_html_e( 'Skip Cooldown', 'retainwoo' ); ?></strong>
								<span><?php esc_html_e( 'Number of months before the skip offer can be used again', 'retainwoo' ); ?></span>
							</div>
							<div class="cs-field-control">
								<input type="number" name="retainwoo_skip_cooldown" min="0" step="1" style="width:60px"
									value="<?php echo esc_attr( get_option( 'retainwoo_skip_cooldown', 3 ) ); ?>"> <?php esc_html_e( 'months', 'retainwoo' ); ?>
							</div>
						</div>
						<div class="cs-field">
							<div class="cs-field-label">
								<strong>🎁 <?php esc_html_e( 'Discount Offer', 'retainwoo' ); ?></strong>
								<span><?php esc_html_e( 'Offer a discount to stay', 'retainwoo' ); ?></span>
							</div>
							<div class="cs-field-control">
								<label class="cs-toggle">
									<input type="checkbox" name="retainwoo_offer_discount" value="1"
										<?php checked( get_option( 'retainwoo_offer_discount' ), '1' ); ?>>
									<span class="cs-toggle-track"><span class="cs-toggle-knob"></span></span>
									<span class="cs-toggle-label"><?php esc_html_e( 'Enabled', 'retainwoo' ); ?></span>
								</label>
							</div>
						</div>
						<div class="cs-field">
							<div class="cs-field-label">
								<strong><?php esc_html_e( 'Discount Amount', 'retainwoo' ); ?></strong>
								<span><?php esc_html_e( 'How much to offer', 'retainwoo' ); ?></span>
							</div>
							<div class="cs-field-control">
								<div class="cs-inline-fields">
									<input type="number" name="retainwoo_discount_amount" min="1" max="100"
										style="width:80px"
										value="<?php echo esc_attr( get_option( 'retainwoo_discount_amount', 20 ) ); ?>">
									<select name="retainwoo_discount_type">
										<option value="percent" <?php selected( get_option( 'retainwoo_discount_type' ), 'percent' ); ?>><?php esc_html_e( '% off', 'retainwoo' ); ?></option>
										<option value="fixed"   <?php selected( get_option( 'retainwoo_discount_type' ), 'fixed' ); ?>><?php esc_html_e( 'Fixed amount', 'retainwoo' ); ?></option>
									</select>
								</div>
							</div>
						</div>
					</div>
				</div>

				<!-- Win-back email -->
				<div class="cs-settings-section">
					<div class="cs-settings-head">
						<span class="cs-head-icon">📧</span>
						<h3><?php esc_html_e( 'Win-Back Email', 'retainwoo' ); ?> <span class="cs-badge"><?php esc_html_e( 'New', 'retainwoo' ); ?></span></h3>
					</div>
					<div class="cs-settings-body">
						<div class="cs-field">
							<div class="cs-field-label">
								<strong><?php esc_html_e( 'Enable Win-Back', 'retainwoo' ); ?></strong>
								<span><?php esc_html_e( 'Auto-email subscribers who cancel anyway', 'retainwoo' ); ?></span>
							</div>
							<div class="cs-field-control">
								<label class="cs-toggle">
									<input type="checkbox" name="retainwoo_winback_enabled" value="1"
										<?php checked( get_option( 'retainwoo_winback_enabled' ), '1' ); ?>>
									<span class="cs-toggle-track"><span class="cs-toggle-knob"></span></span>
									<span class="cs-toggle-label"><?php esc_html_e( 'Enabled', 'retainwoo' ); ?></span>
								</label>
							</div>
						</div>
						<div class="cs-field">
							<div class="cs-field-label">
								<strong><?php esc_html_e( 'Email Subject', 'retainwoo' ); ?></strong>
								<span><?php esc_html_e( 'Subject line for win-back email', 'retainwoo' ); ?></span>
							</div>
							<div class="cs-field-control">
								<input type="text" name="retainwoo_winback_subject" style="width:100%"
									value="<?php echo esc_attr( get_option( 'retainwoo_winback_subject' ) ); ?>">
							</div>
						</div>
						<div class="cs-field">
							<div class="cs-field-label">
								<strong><?php esc_html_e( 'Send Delay', 'retainwoo' ); ?></strong>
								<span><?php esc_html_e( 'Days after cancellation', 'retainwoo' ); ?></span>
							</div>
							<div class="cs-field-control">
								<div class="cs-inline-fields">
									<input type="number" name="retainwoo_winback_delay" min="0" max="30"
										style="width:70px"
										value="<?php echo esc_attr( get_option( 'retainwoo_winback_delay', 1 ) ); ?>">
									<span style="color:#888; font-size:13px"><?php esc_html_e( 'day(s) after cancellation', 'retainwoo' ); ?></span>
								</div>
							</div>
						</div>
					</div>
				</div>

				<button type="submit" class="cs-save-btn">💾 <?php esc_html_e( 'Save Settings', 'retainwoo' ); ?></button>

			</form>
		</div>
		<?php
	}
}
