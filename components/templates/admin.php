<div class="wrap">
	<h2>Copy Active Plugins</h2>
	<div class="content-container">
		<h3>Current Active Plugins (Read Only)</h3>
		<p>Copy this block to save the current active plugin list for archiving or applying to another site.</p>
		<textarea cols="100" rows="15"><?php echo esc_textarea( $current ); ?></textarea><br/>

		<h3>Replace Active Plugin List</h3>
		<p>Paste a saved active plugin list here to override the current active plugins (yes, plugins will be deactivated if they aren't in the list).</p>
		<form action="plugins.php?page=copy-active-plugins" method="post">
			<?php wp_nonce_field( 'go-copy-active-plugins-override', '_go_copy_active_plugins_override_nonce' ); ?>
			<textarea cols="100" rows="15" name="plugins"></textarea><br/>
			<p class="submit">
				<input type="submit" value="Save" class="button-primary"/>
			</p>
		</form>
	</div>
</div>
