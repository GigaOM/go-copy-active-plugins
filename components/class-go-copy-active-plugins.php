<?php

class GO_Copy_Active_Plugins
{
	public $script_version = 1;
	private $successes = array(
		'activate' => array(),
		'deactivate' => array(),
	);
	private $failures = array(
		'activate' => array(),
		'deactivate' => array(),
	);
	private $output = array();

	/**
	 * constructor
	 */
	public function __construct()
	{
		add_action( 'init', array( $this, 'init' ) );
	}//end __construct

	/**
	 * Hook into the init action to initialize the admin hooks (admin_init is too late)
	 */
	public function init()
	{
		if ( ! current_user_can( 'activate_plugins' ) )
		{
			return;
		}//end if

		add_action( 'admin_menu', array( $this, 'admin_menu' ) );
	}//end init

	/**
	 * Add the "Copy Layout" link to the admin sidebar.
	 */
	public function admin_menu()
	{
		add_plugins_page( 'Copy Active Plugins', 'Copy Active Plugins', 'activate_plugins', 'copy-active-plugins', array( $this, 'page' ) );
	}//end admin_menu

	/**
	 * Display the page getting/setting the layout.
	 */
	public function page()
	{
		if ( ! current_user_can('activate_plugins') )
		{
			wp_die( __('You do not have sufficient permissions to access this page.') );
		}//end if

		if ( isset( $_POST['plugins'] ) )
		{
			if ( ! check_admin_referer( 'go-copy-active-plugins-override', '_go_copy_active_plugins_override_nonce' ) )
			{
				wp_die( __('Invalid nonce') );
			}//end if

			return $this->replace_plugins( $_POST['plugins'] );
		}//end if

		$options = wp_load_alloptions();
		$option = (array) unserialize( $options['active_plugins'] );
		$current = array();

		foreach ( $option as $plugin )
		{
			$current[] = $plugin;
		}//end foreach

		$current = implode( "\n", $current );

		include_once __DIR__ . '/templates/admin.php';
	}//end page

	/**
	 * Replace the current layout with a user-submitted layout.
	 *
	 * @param
	 */
	private function replace_plugins( $plugins )
	{
		?>
		<div class="wrap go-copy-active-plugins">
			<h2>Copy Plugin Activations</h2>
			<div class="content-container">
			<style>
				.go-copy-active-plugins .success { color: #157c38; font-weight: bold; }
				.go-copy-active-plugins .fail { color: #941010; font-weight: bold; }
			</style>
		<?php

		$plugins = str_replace( '\\/', '/', $plugins );
		$plugins = stripslashes( $plugins );
		$new_plugins = explode( "\n", $plugins );

		// get rid of any extra spaces, specifically \r
		foreach ( $new_plugins as &$plugin )
		{
			$plugin = trim( $plugin );
		}//end foreach

		if ( FALSE === $new_plugins )
		{
			wp_die( 'Error during unserialize operation. <a href="plugins.php?page=copy-active-plugins">Go back</a>?' );
		}//end if

		$options = wp_load_alloptions();
		$current_plugins = (array) unserialize( $options['active_plugins'] );

		$activate = array_diff( $new_plugins, $current_plugins );
		$deactivate = array_diff( $current_plugins, $new_plugins );

		$output = array();
		$successes = $failures = array(
			'activate' => array(),
			'deactivate' => array(),
		);

		if ( $activate )
		{
			$this->plugin_state_change( 'activate', $activate );
		}//end if

		if ( $deactivate )
		{
			$this->plugin_state_change( 'deactivate', $deactivate );
		}//end if

		if ( ! $activate && ! $deactivate )
		{
			?>
			<p>
				<em>There's nothing new to activate or deactivate!</em>
			</p>
			<p>
				Well, <em>that</em> form submission was meaningless.  Thanks for making me spin my wheels for no reason. You should be ashamed of yourself.
			</p>
			<?php
		}//end if

		foreach ( array( 'activate', 'deactivate' ) as $state )
		{
			if ( $$state )
			{
				if ( count( $this->successes[ $state ] ) > 0 )
				{
					?><p>Successfully <?php echo $state; ?>d: <?php echo count( $this->successes[ $state ] ); ?></p><?php
				}//end if

				if ( count( $this->failures[ $state ] ) > 0 )
				{
					?><p>Failed to <?php echo $state; ?>: <?php echo count( $this->failures[ $state ] ); ?></p><?php
				}//end if

				echo $this->output[ $state ];
			}//end if
		}//end foreach

		echo '</div></div>';
	}//end replace_plugins

	/**
	 * activate/deactivate plugins and update reporting variables
	 *
	 * @param $type string Type of state change being executed: (activate or deactivate)
	 * @param $plugins array Array of plugins to change state on
	 */
	private function plugin_state_change( $type, $plugins )
	{
		ob_start();

		$title = ucwords( $type );
		$title = substr( $type, 0, -1 );
		?>
		<h3><?php echo esc_html( $title ); ?>ing the following:</h3>
		<ul>
		<?php
		// deactivate plugins
		foreach ( $plugins as $plugin )
		{
			if ( 'activate' == $type )
			{
				$result = @activate_plugin( WP_CONTENT_DIR . '/plugins/' . $plugin, '', FALSE, FALSE );
			}//end if
			else
			{
				$result = @deactivate_plugins( WP_CONTENT_DIR . '/plugins/' . $plugin );
			}//end else

			$success = ! is_wp_error( $result );
			$state = $success ? 'success' : 'fail';

			if ( $success )
			{
				$this->successes[ $type ][] = $plugin;
			}//end if
			else
			{
				$this->failures[ $type ][] = $plugin;
			}//end else
			?>
				<li><?php echo esc_html( $plugin ); ?> (<span class="<?php echo $state; ?>"><?php echo $state; ?></span>)</li>
			<?php
		}//end foreach

		?>
		</ul>
		<?php

		$this->output[ $type ] = ob_get_clean();
	}//end plugin_state_change
}//end class

function go_copy_active_plugins()
{
	global $go_copy_active_plugins;

	if ( ! $go_copy_active_plugins )
	{
		$go_copy_active_plugins = new GO_Copy_Active_Plugins;
	}//end if

	return $go_copy_active_plugins;
}//end go_copylayout
