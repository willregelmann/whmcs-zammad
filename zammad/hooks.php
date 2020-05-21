<?php

function zammad_getconfig(){
    $result = select_query( 'tbladdonmodules', '*', array( 'module' => 'zammad' ) );
    $return = array();
    while ($data = mysql_fetch_array($result)) {
        if ($data['setting'] == 'url') {
            $return['url'] = $data['value'];
        } elseif ($data['setting'] == 'token') {
            $return['token'] = $data['value'];
        } elseif ($data['setting'] == 'group') {
            $return['group'] = $data['value'];
        }
    }
    return $return;
}

add_hook('TicketOpen', 1, function($vars){

    $config = zammad_getconfig();
    $customer = localAPI('GetClientsDetails', ['clientid'=>$vars['userid']], 'whmsystem');

    $params = [
        "title" => $vars['subject'],
        "group" => 'Server Team',
        "article" => [
            "content_type" => "text/html",
            "body" => $vars['message'],
            "internal" => false,
            "sender_id" => 2,
            "type" => "email",
            "from" => $customer['email'],
            "reply_to" => $customer['email']
        ],
        "customer_id" => sprintf("guess:%s", $customer['email']),
        "whmcs" => $vars['ticketid']
    ];

    $zammad = curl_init();
    $curl_opts = [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_URL => sprintf("%s/tickets",$config['url']),
        CURLOPT_HTTPHEADER => [
            'Content-type: application/json',
            sprintf('Authorization: Token %s', $config['token'])
        ],
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_POST => 1,
        CURLOPT_POSTFIELDS => json_encode($params)
    ];
    curl_setopt_array($zammad, $curl_opts);  
    curl_exec($zammad);

});

add_hook('TicketUserReply', 1, function($vars){

    $config = zammad_getconfig();
    $customer = localAPI('GetClientsDetails', ['clientid'=>$vars['userid']], 'whmsystem');

    $zammad = curl_init();
    $curl_opts = [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_URL => sprintf("%s/tickets/search?query=whmcs:%s",$config['url'],$vars['ticketid']),
        CURLOPT_HTTPHEADER => [
            'Content-type: application/json',
            sprintf('Authorization: Token %s', $config['token'])
        ],
        CURLOPT_SSL_VERIFYPEER => false
    ];
    curl_setopt_array($zammad, $curl_opts);
    $tickets = json_decode(curl_exec($zammad),false);
    $ticket = array_values((array)$tickets->assets->Ticket)[0];

    $params = [
        "ticket_id" => $ticket->id,
        "content_type" => "text/html",
        "body" => $vars['message'],
        "origin_by_id" => $ticket->customer_id,
        "from" => $customer['email'],
        "reply_to" => $customer['email'],
        "type" => "email",
        "sender_id" => 2,
        "internal" => false
    ];

    $curl_opts = [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_URL => sprintf("%s/ticket_articles", $config['url']),
        CURLOPT_HTTPHEADER => [
            'Content-type: application/json',
            sprintf('Authorization: Token %s', $config['token'])
        ],
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_POST => 1,
        CURLOPT_POSTFIELDS => json_encode($params)
    ];
    curl_setopt_array($zammad, $curl_opts);
    curl_exec($zammad);

});

add_hook('TicketClose', 1, function($vars){

    $config = zammad_getconfig();

    $zammad = curl_init();
    $curl_opts = [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_URL => sprintf("%s/tickets/search?query=whmcs:%s",$config['url'],$vars['ticketid']),
        CURLOPT_HTTPHEADER => [
            'Content-type: application/json',
            sprintf('Authorization: Token %s', $config['token'])
        ],
        CURLOPT_SSL_VERIFYPEER => false
    ];
    curl_setopt_array($zammad, $curl_opts);
    $tickets = json_decode(curl_exec($zammad),false);
    $ticket = array_values((array)$tickets->assets->Ticket)[0];

    $params = [
        "state" => "closed"
    ];
    $curl_opts = [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_URL => sprintf("%s/tickets/%s", $config['url'], $ticket->id),
        CURLOPT_HTTPHEADER => [
            'Content-type: application/json',
            sprintf('Authorization: Token %s', $config['token'])
        ],
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_CUSTOMREQUEST => 'PUT',
        CURLOPT_POST => 1,
        CURLOPT_POSTFIELDS => json_encode($params)
    ];
    curl_setopt_array($zammad, $curl_opts);
    curl_exec($zammad);

});
