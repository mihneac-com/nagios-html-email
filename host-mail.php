#!/usr/bin/php
<?php

require __DIR__.'/lib.php';
require __DIR__.'/config.php';


$f_notify_type = $argv[1];
$f_host_name = $argv[2];
$f_host_alias = $argv[3];
$f_host_state = $argv[4];
$f_host_address = $argv[5];
$f_host_output = $argv[6];
$f_short_date = $argv[7];
$f_to = $argv[8];
$f_totalup = $argv[9];
$f_totaldown = $argv[10];
$f_notif_author = $argv[11];
$f_notif_comment = $argv[12];
$f_long_date = $argv[13];
$f_duration = $argv[14];
$f_perfdata = $argv[21];


$table_values = [
    'Notification Type' => $f_notify_type,
    'Host' => $f_host_alias,
    'Host Address' => $f_host_address,
    'Date and Time' => $f_long_date,
    'Host State' => $f_host_state,
    'Host Output' => $f_host_output,
    'Duration' => $f_duration,
    'Host Perfata' => $f_perfdata,
];

$footer_links = [
    $nagios_baseuri => $nagios_baseuri,
    $nagios_baseuri.'cgi-bin/extingo.cgi?type=2&host='.urlencode($f_host_alias).'&srv=_HOST_' => 'Alert status page',
    pnp4nagios_link($pnp4nagios_baseuri, $f_host_name, '_HOST_') => 'Host Graphs',
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
    'srv' => '_HOST_',
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
    'notif-comment' => draw_notif_comment($f_notif_author, $f_notif_comment, $f_notify_type),
    'table-values' => draw_table_values($table_values, $colors['table']),
//    'baseuri-links' => draw_baseuri_links($baseuri, $f_host_alias),
    'baseuri-links' => draw_ul($footer_links),
    'longdate' => $f_long_date,
    'perfdata-graphs' => draw_graphs($config_enable_graphs, $graphs),
];

// parse template
$html = file_get_contents($host_template_file);
replace_in_string($tpl_values, $html);

// email

$replace_in_subject = [
    'host_name' => $f_host_name,
    'host_state' => $f_host_state,
    'extra_subj' => '',
    'notif_type' => $f_notify_type,
];
if ( isset($config_mail_host) ) $config_mail = array_merge($config_mail, $config_mail_host);
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
