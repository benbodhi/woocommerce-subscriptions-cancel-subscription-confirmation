<?php
/**
* Plugin Name: WooCommerce Subscriptions - Cancel Subscription Confirmation
* Plugin URI: #
* Description: Adds a confirmation dialogue when cancelling a subscription.
* Author: Benbodhi
* Author URI: https://benbodhi.com/
* Version: 1.0
* License: GPLv3
* Tested up to: 5.6.1
* WC tested up to: 4.9.2
*
* GitHub Plugin URI: Benbodhi/woocommerce-subscriptions-cancel-subscription-confirmation
* GitHub Branch: master
*
* Copyright 2020 Benbodhi  (email : wp@benbodhi.com)
*
* This program is free software: you can redistribute it and/or modify
* it under the terms of the GNU General Public License as published by
* the Free Software Foundation, either version 3 of the License, or
* (at your option) any later version.
*
* This program is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
* GNU General Public License for more details.
*
* You should have received a copy of the GNU General Public License
* along with this program.  If not, see <http://www.gnu.org/licenses/>.
*
* @package		WooCommerce Subscriptions
* @author		Prospress Inc.
* @since		1.0
*/

function bodhi_wcs_cancel_subscription_confirmation()
{
    if (! function_exists('is_account_page')) {
        return;
    }
    
    $cancel_confirm_setting = false;
    
    if (('yes' == get_option('bodhi-wcs-ask-confirmation', 'no')) || ('yes' == get_option('bodhi-wcs-cancel-confirmation', 'no'))) {
        $cancel_confirm_setting = true;
    }
    
    $cancel_confirmation_required = apply_filters('bodhi_wcs_cancel_confirmation_prompt_enabled', $cancel_confirm_setting);
    
    if (is_account_page() && 'yes' == $cancel_confirmation_required) {
        wp_register_script('cancel-subscription-confirmation-script', plugin_dir_url(__FILE__) . 'woocommerce-subscriptions-cancel-subscription-confirmation.js', array( 'jquery' ), '1.0.0', true);
        
        if ('yes' == get_option('bodhi-wcs-cancel-confirmation', 'no')) {
            $prompt_msg = apply_filters('bodhi_wcs_cancel_confirmation_prompt_msg', __('Are you sure you want to cancel your subscription?\nIf so, please type the reason why you want to cancel it here:', 'bodhi-wcs-cancel-confirmation'));

            $reason_required = true;
        } else {
            $prompt_msg = apply_filters('bodhi_wcs_cancel_confirmation_prompt_msg', __('Are you sure you want to cancel your subscription?', 'bodhi-wcs-cancel-confirmation'));

            $reason_required = false;
        }

        $script_atts = array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'prompt_msg' => $prompt_msg ,
            'error_msg' => apply_filters('bodhi_wcs_cancel_confirmation_error_msg', __('There has been an error when saving the cancellation reason. Please try again.', 'bodhi-wcs-cancel-confirmation')),
            'reason_required' => $reason_required,
        );
        wp_localize_script('cancel-subscription-confirmation-script', 'ajax_object', $script_atts);
        wp_enqueue_script('cancel-subscription-confirmation-script');
    }
}
add_action('wp_enqueue_scripts', 'bodhi_wcs_cancel_subscription_confirmation');


function bodhi_wcs_cancel_confirmation()
{
    $subscription_id = intval($_POST['subscription_id']);
    $reason_to_cancel = sanitize_text_field($_POST['reason_to_cancel']);

    $subscription = wc_get_order($subscription_id);

    $note_id = $subscription->add_order_note(apply_filters('bodhi_wcs_cancel_confirmation_note_header', __('Cancellation Reason:', 'bodhi-wcs-cancel-confirmation')).'<br /><b><i>'.$reason_to_cancel.'</i></b>');

    $subscription->save();

    echo $note_id;

    wp_die();
}
add_action('wp_ajax_bodhi_wcs_cancel_confirmation', 'bodhi_wcs_cancel_confirmation');


function add_cancelation_settings($settings)
{
    $misc_section_end = wp_list_filter($settings, array( 'id' => 'woocommerce_subscriptions_miscellaneous', 'type' => 'sectionend' ));

    $spliced_array = array_splice($settings, key($misc_section_end), 0, array(

        array(
            'name'     		  => __('Cancellation Confirmation', 'bodhi-wcs-cancel-confirmation'),
            'desc'            => __('Activate cancellation confirmation daialogue', 'bodhi-wcs-cancel-confirmation'),
            'id'              => 'bodhi-wcs-ask-confirmation',
            'default'         => 'no',
            'type'            => 'checkbox',
            'desc_tip'        => __('Ask for confirmation when the customer cancels a subscription from the My Account page.'),
            'checkboxgroup'   => 'start',
            'show_if_checked' => 'option',
        ),

        array(
            'desc'     => __('Prompt the customer for a cancellation reason', 'bodhi-wcs-cancel-confirmation'),
            'id'       => 'bodhi-wcs-cancel-confirmation',
            'default'  => 'no',
            'type'     => 'checkbox',
            'desc_tip' =>  __('Ask for the cancellation reason when the customer cancels a subscription from the My Account page. The provided reason will be added as a subscription note in the backend.'),
            'checkboxgroup'   => 'end',
            'show_if_checked' => 'yes',
        ),

    ));

    return $settings;
}
add_filter('woocommerce_subscription_settings', 'add_cancelation_settings');
