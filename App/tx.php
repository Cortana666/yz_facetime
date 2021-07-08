<?php

function return_error($send_type = '', $message = '', $url = '', $data = []) {
    $return['status'] = 2;
    $return['send_type'] = $send_type;
    $return['message'] = $message;
    $return['url'] = $url;
    $return['data'] = $data;

    return json_encode($return);
}

function return_success($send_type = '', $message = '', $url = '', $data = []) {
    $return['status'] = 1;
    $return['send_type'] = $send_type;
    $return['message'] = $message;
    $return['url'] = $url;
    $return['data'] = $data;

    return json_encode($return);
}

function check_token($data) {
    if (empty($data['user_id']) || empty($data['student_token'])) {
        return false;
    }

    // $student_token = unserialize(decrypt_token($data['student_token']));
    // if ($student_token['user_id'] != $data['user_id']) {
    //     return false;
    // }
    // if (time() - $student_token['time'] > 10800) {
    //     return false;
    // }

    $face_token['user_id'] = $data['user_id'];
    $face_token['name'] = $data['name'];
    $face_token['channel'] = $data['channel'];
    $face_token['time'] = time();

    return encrypt_token(serialize($face_token));
}

function encrypt_token($data) {
    return openssl_encrypt($data, 'aes-256-cfb', get_eol_key(), 0, get_eol_salt());
}

function decrypt_token($data) {
    return openssl_decrypt($data, 'aes-256-cfb', get_eol_key(), 0, get_eol_salt());
}

function get_eol_key() {
    return "eol@2021#kw";
}

function get_eol_salt() {
    return 'eol@2021#kw$salt';
}