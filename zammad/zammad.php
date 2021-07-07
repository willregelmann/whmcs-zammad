<?php

function zammad_config():array {
    return [
        "name" => "Zammad",
            "description" => "Integrates with Zammad ticketing",
            "version" => "0.1",
            "author" => "Watch Communications",
            "fields" => [
                "url" => [
                    "FriendlyName" => "URL",
                    "Type" => "text",
                    "Size" => "255",
                    "Description" => "Zammad API URL (https://*/api/v1)"
                ],
                "token" => [
                    "FriendlyName" => "Token",
                    "Type" => "text",
                    "Size" => "255",
                    "Description" => "API token"
                ],
                "group" => [
                    "FriendlyName" => "Group",
                    "Type" => "text",
                    "Size" => "255",
                    "Description" => "Zammad group name"
                ]
            ]    
        ];
}
