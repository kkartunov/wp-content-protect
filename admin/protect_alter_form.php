<?php
echo '<div class="WPCP_row">',
        '<label for="wpcp_remove_protect" class="selectit">',
            '<input type="checkbox" id="wpcp_remove_protect" name="wpcp_remove_protect" value="1">',
            __('Remove protection', 'WP_ContentProtect_Textdomain'),
        '</label>',
    '</div>',
    '<div class="WPCP_row">',
        '<label for="wpcp_add_protect" class="selectit">',
            '<input type="checkbox" id="wpcp_add_protect" name="wpcp_add_protect" value="1">',
            __('Alter protection for:', 'WP_ContentProtect_Textdomain'),
        '</label>',
    '</div>',
    '<div class="WPCP_row">',
        '<input type="text" id="wpcp_protect_for" name="wpcp_protect_for" value="',
            isset($meta_wpcp['protect_for'])?$meta_wpcp['protect_for']:$this -> settings['protect_for'],'">',
     '</div>';
if( $meta_protected_until != PHP_INT_MAX )
    echo '<div class="WPCP_row">',
            '<label for="wpcp_protect_indef" class="selectit">',
                '<input type="checkbox" id="wpcp_protect_indef" name="wpcp_protect_indef">',
                __('Lock Indefinitely', 'WP_ContentProtect_Textdomain'),
            '</label>',
        '</div>';
