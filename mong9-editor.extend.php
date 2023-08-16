<?php
/*
Plugin Name: Mong9 Editor
Plugin URI: https://mong9editor.com/
Description: The most advanced frontend drag & drop content editor. Mong9 Editor is a responsive page builder which can be used to extend the Classic Editor.
Tags: post, wysiwyg, content editor, drag & drop builder, page builder.
Version: 1.2.1
Author: Mong9 Team
Author URI: https://mong9editor.com/
License: GPLv3
License URI: https://www.gnu.org/licenses/gpl-3.0.html
Text Domain: mong9-editor

	Mong9 Editor is free software: you can redistribute it and/or modify
	it under the terms of the GNU General Public License as published by
	the Free Software Foundation, either version 3 of the License, or
	any later version.

	Mong9 Editor is distributed in the hope that it will be useful,
	Mong9 Editorbut WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
	GNU General Public License for more details.

	Copyright (c) 2019 Mong9 Team. All rights reserved.
*/

/*
==================================================
==================================================
==================================================
이 파일은 ~/extend/mong9-editor.extend.php 로 이동시켜주세요.
==================================================
==================================================
==================================================
*/
if (!defined('_GNUBOARD_')) exit; // 개별 페이지 접근 불가

// You can set the constants below according to your own needs.
// 아래 상수는 본인에 맞게 설정하시면 됩니다.
$mode_m = 768; // mobile phone landscape settings(휴대폰 가로 설정값)
$mode_e = 576; // mobile phone vertical settings(휴대폰 세로 설정값)
$google_token = ''; // Google Maps Token (When using Google Maps, an authentication token is required.) // 구글지도 토큰(구글지도 사용시, 인증토큰이 필요합니다.)
$image_upload_size = 5; // Image upload capacity (5M) // 이미지 업로드 용량(5M)

$m9_folder = 'mong9-editor'; // 폴더명

define('MONG9','true');
define('MONG9_EDITOR_VERSION','1.2.1');
define('MONG9_EDITOR__MINIMUM_GNU_VERSION','5.4.6');
define('MONG9_NOW_SITE_DOMAIN',G5_URL .'/');
define('MONG9_NOW_SITE_DIR',G5_PATH .'/');
define('MONG9_EDITOR__PLUGIN_URL',MONG9_NOW_SITE_DOMAIN .'plugin/'. $m9_folder .'/');
define('MONG9_EDITOR__PLUGIN_DIR',MONG9_NOW_SITE_DIR .'plugin/'. $m9_folder .'/');
define('MONG9_EDITOR_DELETE_LIMIT',100000);
define('MONG9_SCREEN_SIZE_m',(isset($_REQUEST['mode_m']) && $_REQUEST['mode_m'] != '') ? $_REQUEST['mode_m'] : $mode_m );
define('MONG9_SCREEN_SIZE_e',(isset($_REQUEST['mode_e']) && $_REQUEST['mode_e'] != '') ? $_REQUEST['mode_e'] : $mode_e );
define('MONG9_GOOGLE_TOKEN',(isset($_REQUEST['google_token']) && $_REQUEST['google_token'] != '') ? $_REQUEST['google_token'] : $google_token );
define('MONG9_UPLOAD_DIR',MONG9_NOW_SITE_DIR .'data/mong9/'); // Image upload folder name(이미지 업로드 폴더명)
define('MONG9_IMAGE_UPLOAD_SIZE',$image_upload_size);
define('MONG9_LEVEL_PERMISSION',10); // 몽9에디터 사용가능한 권한

add_event('common_header','mong9editor_int');

function mong9editor_int() {

	global $is_admin,$member,$g5,$board;

	// php 8.0 이상에서 에러방지
	$g5['lo_location'] = (isset($g5['lo_location'])) ? $g5['lo_location'] : '';
	$g5['lo_url'] = (isset($g5['lo_url'])) ? $g5['lo_url'] : '';

	$body_script = (isset($g5['body_script']) && $g5['body_script'] != '') ? $g5['body_script'] : '';
	$g5['body_script'] = mong9_add_body_class($body_script);

	require_once(MONG9_EDITOR__PLUGIN_DIR.'includes/functions/content-filter.php');

	$mong9_editor_use = 0;
	// 최고운영자(super),회원등급 MONG9_LEVEL_PERMISSION(10)만 몽9 에디터 사용가능
	if ($is_admin == 'super' || (isset($member['mb_level']) && $member['mb_level'] >= MONG9_LEVEL_PERMISSION) ) {
		$mong9_editor_use = 1; // 사용가능
	}

	define('MONG9_EDITOR_POSSIBLE',$mong9_editor_use);

	// mong9_action
	if (isset($_REQUEST['mong9_action']) && $_REQUEST['mong9_action'] != '') {

		mong9editor_parse_request($_REQUEST['mong9_action']);

	} else {

		// common
		mong9editor_enqueue_int();

		// 관리자 페이지가 아니면(사용자 페이지이면)
		if (strpos($_SERVER['PHP_SELF'], '/adm/') === false) {
			// Add custom js,css in user mode
			mong9editor_site_enqueue_scripts();

			if (isset($board['bo_image_width']) && $board['bo_image_width'] < 100000) {
				$board['bo_image_width'] = 100000; // 이미지 줄이기 방지
			}

		}

	}

} // function

function mong9editor_enqueue_int() {

	$rn = "\n";
	$_script = '<script>'. $rn;
	$_script .= "if (!M9_SET) { var M9_SET = {}; }". $rn;
	$_script .= "M9_SET['mong9_editor_use'] = '". MONG9_EDITOR_POSSIBLE . "'; // Mong9 에디터 사용". $rn;
	$_script .= "M9_SET['mong9_url'] = '". MONG9_EDITOR__PLUGIN_URL ."'; // Mong9 에디터 주소". $rn;
	$_script .= "M9_SET['mong9_screen_size'] = { 'm' : '". MONG9_SCREEN_SIZE_m ."' , 'e' : '". MONG9_SCREEN_SIZE_e ."' };". $rn;
	$_script .= "M9_SET['google_token'] = '". MONG9_GOOGLE_TOKEN ."'; // 구글지도 토큰(구글지도 사용시, 인증토큰이 필요합니다.)". $rn;

	$mong9_window_url = MONG9_NOW_SITE_DOMAIN .'index.php?mong9_action=editor';
	if (isset($board['bo_table']) && $_REQUEST['bo_table'] != '') {
		$mong9_window_url .= '&bo_table='. $_REQUEST['bo_table'];
	}

	$_script .= "M9_SET['mong9_window_url'] = '". $mong9_window_url . "';". $rn;
	$_script .= '</script>'. $rn;

	add_javascript($_script,10);

	add_javascript('<script src="'.MONG9_EDITOR__PLUGIN_URL.'source/js/mong9.js"></script>',10);

} // function

// Add custom js,css in user mode
function mong9editor_site_enqueue_scripts() {

	add_stylesheet('<link rel="stylesheet" href="'.MONG9_EDITOR__PLUGIN_URL.'source/etc/bootstrap-icons/bootstrap-icons.min.css">',10);
	add_stylesheet('<link rel="stylesheet" href="'.MONG9_EDITOR__PLUGIN_URL.'source/css/mong9-base.css">',10);
	add_stylesheet('<link rel="stylesheet" href="'.MONG9_EDITOR__PLUGIN_URL.'source/css/mong9.css">',10);
	add_stylesheet('<link rel="stylesheet" href="'.MONG9_EDITOR__PLUGIN_URL.'source/css/mong9-m.css" media="all and (max-width: '. MONG9_SCREEN_SIZE_m .'px)">',10);
	add_stylesheet('<link rel="stylesheet" href="'.MONG9_EDITOR__PLUGIN_URL.'source/css/mong9-e.css" media="all and (max-width: '. MONG9_SCREEN_SIZE_e .'px)">',10);

} // function

// 몽9 action 처리
function mong9editor_parse_request($mong9_action = '') {

	if (MONG9_EDITOR_POSSIBLE == 1) {

		if ($mong9_action != '') {

			if (file_exists(MONG9_EDITOR__PLUGIN_DIR .'includes/'. $mong9_action .'.php')) {

				include MONG9_EDITOR__PLUGIN_DIR .'includes/'. $mong9_action .'.php';
				$func = 'mong9editor_' . $mong9_action;
				$func();
				exit();

			}

		}

    }

	print_m9_msg( m9_die_msg('Security check failed.') );
	exit();

} // function

// print ajax message
function print_m9_msg($msg = '') {
	echo $msg;
	exit();
}

function m9_die_msg($msg) {
	return $msg;
} // function

/////////////////////////////////////////////////////////////////////////////////////////////////////////////
// 아래부터는 그누보드에만 사용되는 함수들
/////////////////////////////////////////////////////////////////////////////////////////////////////////////

function mong9_add_body_class($body_script) {

	if (strpos($body_script,'class=') !== false) {
		$body_script = preg_replace('/(class="[^"]+)/','$1 m9-content',$body_script);
	} else {
		if ($body_script != '') { $body_script .= ' '; }
		$body_script .= ' class="m9-content"';
	}

	return $body_script;

} // function

?>