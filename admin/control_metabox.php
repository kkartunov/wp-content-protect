<?php
// Render the WP_ContentProtect GUI on the edit post/page/custom page.
// Plugin is in protected state?
if( $post -> post_status == 'publish' && $meta_protected_until > current_time('timestamp') ){
    echo '<div class="WPCP_row">',
            $this->minVer('3.8')? '<i class="dashicons dashicons-lock"></i>':NULL,
            '<p>',
                sprintf( __('Protected by %s for %s until %s.', 'WP_ContentProtect_Textdomain'),
                        '<b>'.$meta_wpcp['last_edited_by'].'</b>',
                        '<b>'.human_time_diff($meta_protected_until, current_time('timestamp')).'</b>',
                        '<b>'.date($this -> settings['time_format'], $meta_protected_until).'</b>'
                       ),
            '</p>',
        '</div>';
    include 'protect_alter_form.php';
}else if( $post -> post_status == 'publish' && $meta_protected_until && $meta_protected_until < current_time('timestamp') ){
    echo '<div class="WPCP_row">',
            $this->minVer('3.8')? '<i class="dashicons dashicons-info"></i>':NULL,
            '<p>',
                sprintf( __('Content is publicly available since %s', 'WP_ContentProtect_Textdomain'),
                        '<b>'.date($this -> settings['time_format'], $meta_protected_until).'</b><em>'
                       ),
            '</p>',
        '</div>';
    include 'protect_again_form.php';
}else{
    include 'protect_setup_form.php';
}
