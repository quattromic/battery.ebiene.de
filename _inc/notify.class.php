<?php

class Webhook_Notification {

    private $webhooks_file = 'webhooks.json';

    function __construct( $msg ) {
        $webhooks = $this->get_available_webhooks();

        foreach ( $webhooks as $service => $url ) {
            $func = sprintf('send_%s_notification', $service);

            if ( method_exists($this, $func) ) {
                $this->$func($url, $msg);
            }
        }
    }

    function get_available_webhooks() {
        return json_decode(
            file_get_contents(
                $this->webhooks_file
            )
        );
    }

    function send_ifttt_notification($url, $msg) {
        if ( empty($url) OR empty($msg) ) {
            die( 'Notify: No webhook data, exit' );
        }

        $c = curl_init();
        curl_setopt($c, CURLOPT_URL, $url);
        curl_setopt($c, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($c, CURLOPT_HTTPHEADER, array('Content-Type: application/json') );
        curl_setopt($c, CURLOPT_POSTFIELDS, json_encode( array('value1' => $msg) ) );

        curl_exec($c);
        curl_close($c);
    }

    function send_slack_notification($url, $msg) {
        if ( empty($url) OR empty($msg) ) {
            die( 'Notify: No webhook data, exit' );
        }

        $c = curl_init();
        curl_setopt($c, CURLOPT_URL, $url);
        curl_setopt($c, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($c, CURLOPT_CRLF, true);
        curl_setopt($c, CURLOPT_HTTPHEADER, array('Content-Type: application/json') );
        curl_setopt($c, CURLOPT_POSTFIELDS, json_encode( array('text' => $msg) ) );

        curl_exec($c);
        curl_close($c);
    }
}
