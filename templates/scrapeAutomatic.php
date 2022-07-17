<?php
// check user capabilities
if ( ! current_user_can( 'manage_options' ) ) {
    return;
}

// add error/update messages

// check if the user have submitted the settings
// WordPress will add the "settings-updated" $_GET parameter to the url
if ( isset( $_GET['settings-updated'] ) ) {
    // add settings saved message with the class of "updated"
    add_settings_error( 'scraper_messages', 'scraper_message', __( 'Settings Saved', 'scraper-hours' ), 'updated' );
}

// show error/update messages
settings_errors( 'scraper_messages' );
?>
<div class="wrap">
    <div class="setting-form">
        <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
        <form action="options.php" method="post">
            <?php
            settings_fields( 'scraper-settings' );
            do_settings_sections( 'scraper-hours' );
            do_settings_sections( 'scraper-quantity' );

            submit_button( 'Save Settings' );
            ?>
        </form>
    </div>

    <div class="automatic-product-log">
        <h2>Automatic Published Products Log</h2>

        <div class="log">
            <?php
            $logs = file(SC_PLUGIN_DIR."logging/automaticLog.log");

            if(!empty($logs)){
                foreach ($logs as $key => $log) {
                    echo $log. '<br>';
                }
            }else{
                echo '<p>No product log found!</p>';
            }
            ?>
        </div>
    </div>

</div>