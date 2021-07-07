<?php

function zammad_getconfig():array {
    $result = select_query('tbladdonmodules', '*', ['module' => 'zammad']);
    $return = [];
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

add_hook('TicketOpen', 1, function(array $args):void {

    $config = zammad_getconfig();
    $customer = localAPI('GetClientsDetails', ['clientid'=>$args['userid']], 'whmsystem');

    $params = [
        'title' => $args['subject'],
        'group' => 'Server Team',
        'article' => [
            'content_type' => 'text/html',
            'body' => $args['message'],
            'internal' => false,
            'sender_id' => 2,
            'type' => 'email',
            'from' => $customer['email'],
            'reply_to' => $customer['email']
        ],
        'customer_id' => "guess:$customer[email]",
        'whmcs' => $args['ticketid']
    ];

    $zammad = curl_init();
    curl_setopt_array($zammad, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_URL => "$config[url]/tickets",
        CURLOPT_HTTPHEADER => [
            'Content-type: application/json',
            "Authorization: Token $config[token]"
        ],
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($params)
    ]);  
    curl_exec($zammad);

});

add_hook('TicketUserReply', 1, function(array $args):void {

    $config = zammad_getconfig();
    $customer = localAPI('GetClientsDetails', ['clientid'=>$args['userid']], 'whmsystem');

    $zammad = curl_init();
    $curl_opts = [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_URL => "$config[url]/tickets/search?query=whmcs:$args[ticketid]",
        CURLOPT_HTTPHEADER => [
            'Content-type: application/json',
            "Authorization: Token $config[token]"
        ],
        CURLOPT_SSL_VERIFYPEER => false
    ];
    curl_setopt_array($zammad, $curl_opts);
    $tickets = json_decode(curl_exec($zammad),false);
    $ticket = array_values((array)$tickets->assets->Ticket)[0];

    $params = [
        'ticket_id' => $ticket->id,
        'content_type' => 'text/html',
        'body' => $args['message'],
        'origin_by_id' => $ticket->customer_id,
        'from' => $customer['email'],
        'reply_to' => $customer['email'],
        'type' => 'email',
        'sender_id' => 2,
        'internal' => false
    ];

    curl_setopt_array($zammad, array_replace($curl_opts, [
        CURLOPT_URL => "$config[url]/ticket_articles",
        CURLOPT_POST => 1,
        CURLOPT_POSTFIELDS => json_encode($params)
    ]));
    curl_exec($zammad);

});

add_hook('TicketClose', 1, function(array $args):void {

    $config = zammad_getconfig();

    $zammad = curl_init();
    $curl_opts = [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_URL => "$config[url]/tickets/search?query=whmcs:$args[ticketid]",
        CURLOPT_HTTPHEADER => [
            'Content-type: application/json',
            "Authorization: Token $config[token]"
        ],
        CURLOPT_SSL_VERIFYPEER => false
    ];
    curl_setopt_array($zammad, $curl_opts);
    
    $tickets = json_decode(curl_exec($zammad), false);
    $ticket = array_values((array)$tickets->assets->Ticket)[0];

    curl_setopt_array($zammad, array_replace($curl_opts, [
        CURLOPT_URL => "$config[url]/tickets/$ticket->id",
        CURLOPT_CUSTOMREQUEST => 'PUT',
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode([
                'state' => 'closed'
            ])
    ]));
    curl_exec($zammad);

});
