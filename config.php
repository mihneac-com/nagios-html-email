<?php

$colors = [
    'host' => [
	'UP' => '#88d066',
	'DOWN' => '#f88888',
    ],
    'table' => [
	0 => '#f4f4f4',
	1 => '#e7e7e7'
    ],
    'service' => [
	'OK' => '#88d066',
	'WARNING' => '#ffff00',
	'CRITICAL'=> '#f88888',
	'UNKNOWN' => '#ffbb55'
    ],
];


$nagios_baseuri = 'http://servername/nagios';
$pnp4nagios_baseuri = 'http://servername/pnp4nagios';


$host_template_file = __DIR__.'/template/host.html';
$service_template_file = __DIR__.'/template/service.html';

$config_enable_graphs = true;


$config_mail = [
    'from' => 'nagios@yourdomain.com',
    'from_name' => 'Nagios on servername',
    'smtp_server' => 'localhost',
    'smtp_auth' => false,
    'smtp_user' => '',
    'smtp_pass' => '',
    'smtp_port' => 25,
    // available: tls, ssl, none
    'smtp_secure' => 'none',
];

$config_mail_host = [
    'subject' => '{{notif_type}}: {{host_name}} - {{host_state}}',
];
$config_mail_service = [
    'subject' => '{{notif_type}}: {{host_name}}/{{service}} - {{service_state}}',
];

?>
