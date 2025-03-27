<?php
function changeCookiesFb($cookies) {
    $result = [];
    $cookieParts = explode(';', $cookies);
    foreach ($cookieParts as $part) {
        $cookie = explode('=', $part, 2);
        if (count($cookie) === 2) {
            $result[trim($cookie[0])] = trim($cookie[1]);
        }
    }
    return $result;
}

function changeToken($appId, $accessToken) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "https://api.facebook.com/method/auth.getSessionforApp");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
        'access_token' => $accessToken,
        'format' => 'json',
        'new_app_id' => $appId,
        'generate_session_cookies' => '0'
    ]));

    $response = curl_exec($ch);
    curl_close($ch);
    $response = json_decode($response, true);

    if (isset($response['access_token'])) {
        $sessionAp = $response['access_token'];
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://graph.facebook.com/me/permissions?method=DELETE&access_token=$accessToken");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_exec($ch);
        curl_close($ch);

        return $sessionAp;
    }

    throw new Exception("Unable to change token. Response: " . json_encode($response));
}
function getFbDtsg($cookies) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "https://www.facebook.com/v2.3/dialog/oauth?redirect_uri=fbconnect://success&response_type=token,code&client_id=356275264482347");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_COOKIE, implode('; ', array_map(
        function($k, $v) { return "$k=$v"; }, array_keys($cookies), $cookies
    )));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/117.0.0.0 Safari/537.36',
        'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/jxl,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.7',
        'Accept-Language: vi,en-US;q=0.9,en;q=0.8',
        'Cache-Control: max-age=0',
        'DNT: 1',
        'Sec-Fetch-Dest: document',
        'Sec-Fetch-Mode: navigate',
        'Sec-Fetch-Site: same-origin',
        'Upgrade-Insecure-Requests: 1'
    ]);

    $response = curl_exec($ch);
    curl_close($ch);

    if (preg_match('/DTSGInitialData.*?\\{\"token\":\"(.*?)\"/', $response, $matches)) {
        return $matches[1];
    } else {
        throw new Exception("Unable to fetch fb_dtsg token. Response: " . substr($response, 0, 500));
    }
}
function generate_uuid() {
    return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
        mt_rand(0, 0xffff), mt_rand(0, 0xffff),
        mt_rand(0, 0xffff),
        mt_rand(0, 0x0fff) | 0x4000,
        mt_rand(0, 0x3fff) | 0x8000,
        mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
    );
}
function run($cookies, $appId) {
    $fbDtsg = getFbDtsg($cookies);
    $cUser = $cookies['c_user'];
    $uuid = generate_uuid();
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'https://www.facebook.com/api/graphql/');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, 'av='.$cUser.'&__user='.$cUser.'&fb_dtsg='.$fbDtsg.'&fb_api_caller_class=RelayModern&fb_api_req_friendly_name=useCometConsentPromptEndOfFlowBatchedMutation&variables=%7B%22input%22%3A%7B%22client_mutation_id%22%3A%224%22%2C%22actor_id%22%3A%22'.$cUser.'%22%2C%22config_enum%22%3A%22GDP_CONFIRM%22%2C%22device_id%22%3Anull%2C%22experience_id%22%3A%22'.$uuid.'%22%2C%22extra_params_json%22%3A%22%7B%5C%22app_id%5C%22%3A%5C%22350685531728%5C%22%2C%5C%22kid_directed_site%5C%22%3A%5C%22false%5C%22%2C%5C%22logger_id%5C%22%3A%5C%22%5C%5C%5C%22'.generate_uuid().'%5C%5C%5C%22%5C%22%2C%5C%22next%5C%22%3A%5C%22%5C%5C%5C%22confirm%5C%5C%5C%22%5C%22%2C%5C%22redirect_uri%5C%22%3A%5C%22%5C%5C%5C%22https%3A%5C%5C%5C%5C%5C%5C%2F%5C%5C%5C%5C%5C%5C%2Fwww.facebook.com%5C%5C%5C%5C%5C%5C%2Fconnect%5C%5C%5C%5C%5C%5C%2Flogin_success.html%5C%5C%5C%22%5C%22%2C%5C%22response_type%5C%22%3A%5C%22%5C%5C%5C%22token%5C%5C%5C%22%5C%22%2C%5C%22return_scopes%5C%22%3A%5C%22false%5C%22%2C%5C%22scope%5C%22%3A%5C%22%5B%5C%5C%5C%22user_subscriptions%5C%5C%5C%22%2C%5C%5C%5C%22user_videos%5C%5C%5C%22%2C%5C%5C%5C%22user_website%5C%5C%5C%22%2C%5C%5C%5C%22user_work_history%5C%5C%5C%22%2C%5C%5C%5C%22friends_about_me%5C%5C%5C%22%2C%5C%5C%5C%22friends_actions.books%5C%5C%5C%22%2C%5C%5C%5C%22friends_actions.music%5C%5C%5C%22%2C%5C%5C%5C%22friends_actions.news%5C%5C%5C%22%2C%5C%5C%5C%22friends_actions.video%5C%5C%5C%22%2C%5C%5C%5C%22friends_activities%5C%5C%5C%22%2C%5C%5C%5C%22friends_birthday%5C%5C%5C%22%2C%5C%5C%5C%22friends_education_history%5C%5C%5C%22%2C%5C%5C%5C%22friends_events%5C%5C%5C%22%2C%5C%5C%5C%22friends_games_activity%5C%5C%5C%22%2C%5C%5C%5C%22friends_groups%5C%5C%5C%22%2C%5C%5C%5C%22friends_hometown%5C%5C%5C%22%2C%5C%5C%5C%22friends_interests%5C%5C%5C%22%2C%5C%5C%5C%22friends_likes%5C%5C%5C%22%2C%5C%5C%5C%22friends_location%5C%5C%5C%22%2C%5C%5C%5C%22friends_notes%5C%5C%5C%22%2C%5C%5C%5C%22friends_photos%5C%5C%5C%22%2C%5C%5C%5C%22friends_questions%5C%5C%5C%22%2C%5C%5C%5C%22friends_relationship_details%5C%5C%5C%22%2C%5C%5C%5C%22friends_relationships%5C%5C%5C%22%2C%5C%5C%5C%22friends_religion_politics%5C%5C%5C%22%2C%5C%5C%5C%22friends_status%5C%5C%5C%22%2C%5C%5C%5C%22friends_subscriptions%5C%5C%5C%22%2C%5C%5C%5C%22friends_videos%5C%5C%5C%22%2C%5C%5C%5C%22friends_website%5C%5C%5C%22%2C%5C%5C%5C%22friends_work_history%5C%5C%5C%22%2C%5C%5C%5C%22ads_management%5C%5C%5C%22%2C%5C%5C%5C%22create_event%5C%5C%5C%22%2C%5C%5C%5C%22create_note%5C%5C%5C%22%2C%5C%5C%5C%22export_stream%5C%5C%5C%22%2C%5C%5C%5C%22friends_online_presence%5C%5C%5C%22%2C%5C%5C%5C%22manage_friendlists%5C%5C%5C%22%2C%5C%5C%5C%22manage_notifications%5C%5C%5C%22%2C%5C%5C%5C%22manage_pages%5C%5C%5C%22%2C%5C%5C%5C%22photo_upload%5C%5C%5C%22%2C%5C%5C%5C%22publish_stream%5C%5C%5C%22%2C%5C%5C%5C%22read_friendlists%5C%5C%5C%22%2C%5C%5C%5C%22read_insights%5C%5C%5C%22%2C%5C%5C%5C%22read_mailbox%5C%5C%5C%22%2C%5C%5C%5C%22read_page_mailboxes%5C%5C%5C%22%2C%5C%5C%5C%22read_requests%5C%5C%5C%22%2C%5C%5C%5C%22read_stream%5C%5C%5C%22%2C%5C%5C%5C%22rsvp_event%5C%5C%5C%22%2C%5C%5C%5C%22share_item%5C%5C%5C%22%2C%5C%5C%5C%22sms%5C%5C%5C%22%2C%5C%5C%5C%22status_update%5C%5C%5C%22%2C%5C%5C%5C%22user_online_presence%5C%5C%5C%22%2C%5C%5C%5C%22video_upload%5C%5C%5C%22%2C%5C%5C%5C%22xmpp_login%5C%5C%5C%22%5D%5C%22%2C%5C%22steps%5C%22%3A%5C%22%7B%7D%5C%22%2C%5C%22tp%5C%22%3A%5C%22%5C%5C%5C%22unspecified%5C%5C%5C%22%5C%22%2C%5C%22cui_gk%5C%22%3A%5C%22%5C%5C%5C%22%5BPASS%5D%3A%5C%5C%5C%22%5C%22%2C%5C%22is_limited_login_shim%5C%22%3A%5C%22false%5C%22%7D%22%2C%22flow_name%22%3A%22GDP%22%2C%22flow_step_type%22%3A%22STANDALONE%22%2C%22outcome%22%3A%22APPROVED%22%2C%22source%22%3A%22gdp_delegated%22%2C%22surface%22%3A%22FACEBOOK_COMET%22%7D%7D&server_timestamps=true&doc_id=6494107973937368');
    curl_setopt($ch, CURLOPT_COOKIE, implode('; ', array_map(
        function($k, $v) { return "$k=$v"; }, array_keys($cookies), $cookies
    )));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'authority: www.facebook.com',
        'accept: */*',
        'accept-language: vi-VN,vi;q=0.9,fr-FR;q=0.8,fr;q=0.7,en-US;q=0.6,en;q=0.5',
        'content-type: application/x-www-form-urlencoded',
        'dnt: 1',
        'origin: https://www.facebook.com',
        'sec-ch-prefers-color-scheme: dark',
        'sec-ch-ua: "Chromium";v="117", "Not;A=Brand";v="8"',
        'sec-ch-ua-full-version-list: "Chromium";v="117.0.5938.157", "Not;A=Brand";v="8.0.0.0"',
        'sec-ch-ua-mobile: ?0',
        'sec-ch-ua-model: ""',
        'sec-ch-ua-platform: "Windows"',
        'sec-ch-ua-platform-version: "15.0.0"',
        'sec-fetch-dest: empty',
        'sec-fetch-mode: cors',
        'sec-fetch-site: same-origin',
        'user-agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/117.0.0.0 Safari/537.36',
        'x-fb-friendly-name: useCometConsentPromptEndOfFlowBatchedMutation',
    ]);

    $response = curl_exec($ch);
    curl_close($ch); 
    $response = json_decode($response, true);
    if (isset($response['data']['run_post_flow_action']['uri'])) {
        $uri = $response['data']['run_post_flow_action']['uri'];
        $parsedUrl = parse_url($uri);
        parse_str($parsedUrl['query'], $queryParams);
        $closeUri = urldecode($queryParams['close_uri'] ?? '');
        $fragmentUrl = parse_url($closeUri);
        parse_str($fragmentUrl['fragment'] ?? '', $fragmentParams);

        $accessToken = $fragmentParams['access_token'] ?? null;
        return $accessToken;
    }

    throw new Exception("Unable to fetch access token.");
}

try {
    $cookieInput = "";
    $cookies = changeCookiesFb($cookieInput);

    $appId = '275254692598279';  
    $token = run($cookies, $appId);
    $token = changeToken($appId, $token);

    echo "Access Token: " . $token . "\n";
} catch (Exception $e) {
    echo "Lỗi: " . $e->getMessage() . "\n";
}
?>
