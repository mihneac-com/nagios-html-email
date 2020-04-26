#!/usr/bin/php
<?php
require __DIR__.'/lib.php';
require __DIR__.'/config.php';

//print_r($argv);

$f_notify_type = $argv[1];
$f_host_name = $argv[2];
$f_host_alias = $argv[3];
$f_host_state = $argv[4];
$f_host_address = $argv[5];
$f_service_output = $argv[6];
$f_short_date = $argv[7];
$f_service_desc = $argv[8];
$f_service_state = $argv[9];
$f_to = $argv[10];
$f_service_duration_secs = $argv[11];
$f_service_exectime = $argv[12];
$f_total_serv_warn = $argv[13];
$f_total_serv_critical = $argv[14];
$f_total_serv_unknown = $argv[15];
$f_notify_recipients = $argv[16];
$f_total_serv_ok = $argv[17];
$f_service_notf_number = $argv[18];
$f_serv_ack_comm = $argv[19];
$f_hostgroup_name = $argv[20];
$f_perfdata = $argv[21];
$f_service_duration = $argv[22];
$f_notify_author = $argv[23];
$f_notify_comment = $argv[24];
$f_long_date = $argv[25];
$f_host_perfdata = $argv[26];

$table_values = [
    'Notification Type' => $f_notify_type,
    'Service' => $f_service_desc,
    'Host' => $f_host_alias,
    'Host Address' => $f_host_address,
    'Host state' => $f_host_state,
    'Service State' => $f_service_state,
    'Service Duration' => $f_service_duration,
    'Service Output' => $f_service_output,
    'Date and Time' => $f_long_date,
    'Acknowledge Comment' => $f_serv_ack_comm,
//    'Host Perfdata' => $f_host_perfdata,
    'Service Perfdata' => $f_perfdata,
];

$footer_links = [
    $nagios_baseuri => $nagios_baseuri,
    $nagios_baseuri.'cgi-bin/extingo.cgi?type=2&host='.urlencode($f_host_alias).'&srv=_HOST_' => 'Alert status page',
    pnp4nagios_link($pnp4nagios_baseuri, $f_host_name, '_HOST_') => 'Host Graphs',
    pnp4nagios_link($pnp4nagios_baseuri, $f_host_name, $f_service_desc) => 'Service Graphs',
];

foreach ( $footer_links as $link => $name ) {
    $footer_links[] = draw_link($link, $name);
    unset($footer_links[$link]);
}

if ( empty($f_perfdata) ) { $config_enable_graphs = false; }
//graphs
if ( $config_enable_graphs ) {

$g = new graph($pnp4nagios_baseuri);
$g_params = [
    'host' => $f_host_alias,
    'srv' => $f_service_desc,
    'view' => '0',
];
$g->gen_url($g_params);
$g->gen_urls(count_datasources($f_perfdata));
$g->noverify_ssl = true;
$graphs = $g->get_images();

}

// template replacements
$tpl_values = [
    'host-state-color' => $colors['host'][$f_host_state],
    'host-alias' => $f_host_alias,
    'host-state' => $f_host_state,
    'service-state-color' => $colors['service'][$f_service_state],
    'service-desc' => $f_service_desc,
    'service-state' => $f_service_state,
    'notif-comment' => draw_notif_comment($f_notify_author, $f_notify_comment, $f_notify_type),
    'table-values' => draw_table_values($table_values, $colors['table']),
    'baseuri-links' => draw_ul($footer_links),
    'longdate' => $f_long_date,
    'perfdata-graphs' => draw_graphs($config_enable_graphs, $graphs),
];

// parse template
$html = file_get_contents($service_template_file);
replace_in_string($tpl_values, $html);


// email

$replace_in_subject = [
    'host_name' => $f_host_name,
    'host_state' => $f_host_state,
    'extra_subj' => '',
    'notif_type' => $f_notify_type,
    'service' => $f_service_desc,
    'service_state' => $f_service_state,
];
if ( isset($config_mail_service) ) $config_mail = array_merge($config_mail, $config_mail_service);
replace_in_string($replace_in_subject, $config_mail['subject']);

$m = new Mail($config_mail);
$m->mailer->Body = $html;
$m->dest([$f_to]);

if ( $config_enable_graphs ) {
    foreach ($graphs as $idx => $img ) {
    $m->attach_image($img, 'image'.$idx);
    }
}

$m->send();


?>