<div class='wrap'>
    <div id="WPCPsettings">
        <div id="left">
            <div id="icon-options-general" class="icon32">
                <br>
            </div>
            <h2><?php _e('Content protect settings', 'WP_ContentProtect_Textdomain')?></h2>
            <form action="options.php" method="post">
                <?php settings_fields('Wp_ContentProtect_settings'); do_settings_sections( 'Wp_ContentProtect' ); ?>
                <p class="submit">
                    <input name="submit" type="submit" id="submit" class="button button-primary" value="<?php esc_attr_e('Save Changes'); ?>" />
                </p>
            </form>
        </div>
    </div>
</div>
