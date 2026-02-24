<?php

use Twilio\Rest\Client;

add_action('wp_ajax_send_sms_for_verification', 'send_sms_for_verification');
add_action('wp_ajax_nopriv_send_sms_for_verification', 'send_sms_for_verification');
function send_sms_for_verification()
{
    error_log('send_sms_for_verification called with phone: ' . ($_POST['phone'] ?? 'none'));

    if (empty($_POST['phone'])) {
        wp_send_json_error(['message' => 'Phone number not provided']);
    }

    $phone = sanitize_text_field($_POST['phone']);
    $code = rand(100000, 999999);
    $request_id = 'sms_ver_' . bin2hex(random_bytes(4));

    set_transient($request_id, ['phone' => $phone, 'code' => $code], 600);

    $account_sid = get_field('twilio_account_sid', 'options');
    $auth_token = get_field('twilio_auth_token', 'options');
    $twilio_number = get_field('twilio_number', 'options');

    try {
        $twilio = new Client($account_sid, $auth_token);
        $twilio->messages->create($phone, [
            'from' => $twilio_number,
            'body' => 'Your verification code: ' . $code
        ]);
        error_log('SMS sent successfully to ' . $phone . ', requestId: ' . $request_id);
        wp_send_json_success(['requestId' => $request_id]);
    } catch (Exception $e) {
        error_log('Twilio Error: ' . $e->getMessage());
        wp_send_json_error(['message' => 'Twilio Error: ' . $e->getMessage()]);
    }
}

add_filter('wpforms_process_before', function ($entry, $form_data) {
    // Check if the form has a field with 'sms-code' in its CSS class
    $has_sms_code = false;
    foreach ($form_data['fields'] as $field) {
        if (isset($field['css']) && strpos($field['css'], 'sms-code') !== false) {
            $has_sms_code = true;
            break;
        }
    }

    // If no sms-code field is found, skip processing
    if (!$has_sms_code) {
        return $entry;
    }
    error_log('wpforms_process_before triggered for form ID: ' . $form_data['id']);

    $code_field_id = null;
    $request_id_field_id = null;

    foreach ($form_data['fields'] as $field) {
        if (isset($field['css']) && strpos($field['css'], 'sms-code') !== false) {
            $code_field_id = $field['id'];
        }
        if (isset($field['css']) && strpos($field['css'], 'sms-request-id') !== false) {
            $request_id_field_id = $field['id'];
        }
    }

    if (!$code_field_id || !$request_id_field_id) {
        error_log('Required fields not found');
        wpforms()->process->errors[$form_data['id']][$code_field_id ?: 0] = 'Required fields not found';
        wpforms()->process->validating_errors = true;
        return;
    }

    $code = isset($entry['fields'][$code_field_id]) ? sanitize_text_field($entry['fields'][$code_field_id]) : '';
    $request_id = isset($entry['fields'][$request_id_field_id]) ? sanitize_text_field($entry['fields'][$request_id_field_id]) : '';

    if (empty($code) || empty($request_id)) {
        error_log('Missing code or requestId');
        wpforms()->process->errors[$form_data['id']][$code_field_id] = 'Enter the verification code.';
        wpforms()->process->validating_errors = true;
        return;
    }

    $stored = get_transient($request_id);
    if ($stored && $stored['code'] == $code) {
        error_log('Code verified successfully for requestId: ' . $request_id);
        delete_transient($request_id);
    } else {
        error_log('Invalid code for requestId: ' . $request_id);
        wpforms()->process->errors[$form_data['id']][$code_field_id] = 'Invalid verification code.';
        wpforms()->process->validating_errors = true;
        return;
    }
}, 10, 2);
