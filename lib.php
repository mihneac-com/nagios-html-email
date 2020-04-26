<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require 'vendor/autoload.php';

function draw_table_values ( $values, $colors ) {
    $string = '';
    $i = 0;
    foreach ( $values as $key => $val ) {
	$string .= '<tr style="background-color:'.$colors[$i].'"><td>'.$key.'</td>';
	$string .= '<td>'.$val.'</td></tr>';
	if ( $i==0 ) { $i = 1; }
	else { $i = 0; }
    }
    return $string;
}

function draw_notif_comment ( $f_notif_author, $f_notif_comment, $f_notify_type ) {
    $return = '';
    if ( !empty($f_notif_author) && !empty($f_notif_comment)) {
	$type = ( $f_notify_type == 'ACKNOWLEDGEMENT' ) ? 'acknowledgement' : 'comment' ;
	$return = '<strong>[' . $type . '] '.$f_notif_author.':</strong> '.$f_notif_comment.'
	<br />
	<br /> ';
    }
    return $return;
}

function draw_baseuri_links ( $baseuri, $f_host_alias, $service='_HOST_' ) {
    $return  = '';
    if (!empty($baseuri)) {
	$return = '
	<ul>
	    <li><a href="'.$baseuri.'">'.$baseuri.'</a></li>
	    <li><a href="'.$baseuri.'/cgi-bin/extinfo.cgi?type=2&host='.urlencode($f_host_alias).'&srv='.urlencode($service).'">Alert Status Page</a> - all unhandled nagios alerts</li>
	    <li><a href="'.str_replace('nagios', '', $baseuri).'/pnp4nagios/index.php/graph?host='.urlencode($f_host_alias).'&srv='.urlencode($service).'">Host Graphs</a></li>
	</ul>';
    }
    return $return;
};

function pnp4nagios_link ( $base, $host, $srv ) {
	return $base.'/pnp4nagios/index.php/graph?host='.urlencode($host).'&srv='.urlencode($srv);
}

function draw_link ( $url, $name ) {
    return '<a href="'.$url.'">'.$name.'</a>';
}

function draw_ul ( $lis ) {
    $return = '<ul>';
    foreach ( $lis as $li ) {
	$return .= '<li>'.$li.'</li>';
    }
    $return .= '</ul>';
    return $return;
}

function replace_in_string($values, &$string) {
    foreach ( $values as $key => $val ) {
	$string = str_replace('{{'.$key.'}}', $val, $string);
    }
//    return $string;
}


function count_datasources($perfdata) {
    $ds = explode(' ', $perfdata);
    return count($ds);
}

class graph {
    //pnp4nagios baseuri
    public $baseuri;
    public $urls;
    public $noverify_ssl = false;
	
    public function __construct($baseuri) {
	$this->baseuri = $baseuri;
	$this->urls = array();
    }
    public function gen_url ($url_params) {
	$this->baseuri .= '/index.php/image?';
	foreach ( $url_params as $param => $value ) {
	    $this->baseuri .= $param.'='.urlencode($value).'&';
	}
    }
    public function gen_urls ( $ds_count ) {
	for ($i=0; $i<$ds_count; $i++ ) {
	    $this->urls[$i] = $this->baseuri.'source='.$i;
	}
    }
    public function get_urls () {
	return $this->urls;
    }
    public function get_images() {
	$images = array();
	foreach ($this->urls as $url) {
	    $ch = curl_init();
	    curl_setopt($ch, CURLOPT_URL, $url);
	    curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
	    if ( isset($this->noverify_ssl) && $this->noverify_ssl ) {
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
	    }
	    $images[] = curl_exec($ch);
	    curl_close($ch);
	}
	return $images;
    }
}

class Mail {
    public $mailer;
    public function __construct($config_mail) {
	$mail = new PHPMailer;
	$mail->Host = $config_mail['smtp_server'];
	if ( $config_mail['smtp_auth'] and !empty($config_mail['smtp_username']) and !empty($config_mail['smtp_password']) ) {
	    $mail->SMTPAuth = $config_mail['smtp_auth'];
	    //Provide username and password
	    $mail->Username = $config_mail['smtp_username'];
	    $mail->Password = $config_mail['smtp_password'];
	}
	if ( isset($config_mail['smtp_port']) ) {
	    $mail->Port = $config_mail['smtp_port'];
	}
	if ( isset($config_mail['smtp_secure']) ) {
	    $mail->SMTPSecure = $config_mail['smtp_secure'];
	}
	$mail->setFrom($config_mail['from'], $config_mail['from_name']);
	$mail->Subject = $config_mail['subject'];
	$mail->isHTML(true);
	$this->mailer = $mail;
    }
    public function dest ( $dst ) {
		foreach ( $dst  as $addr ) {
			$this->mailer->addAddress($addr);
		}
    }
	public function subject ( $subject ) {
		$this->mailer->Subject = $subject;
	}
    public function setbody ( $body ) {
		$this->mailer->Body = $body;
    }
    public function attach_image ( $img, $cid ) {
	$this->mailer->addStringEmbeddedImage($img, $cid, $cid.'.png', 'base64', 'image/png');
    }
    public function send () {
	return $this->mailer->send();
    }
}

function draw_graphs ($enable, $gr) {
	if ( !$enable ) return '';
	$ret = '';
	foreach ( $gr as $idx => $img ) {
		$ret .= '<img src="cid:image'.$idx.'" alt="perfdata graph"/>';
	}
	return $ret;
}

?>