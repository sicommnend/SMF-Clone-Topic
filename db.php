<?php
if (file_exists(dirname(__FILE__) . '/SSI.php') && !defined('SMF'))
	require_once(dirname(__FILE__) . '/SSI.php');

elseif (!defined('SMF'))
	die('<b>Error:</b> Cannot install - please verify you put this in the same place as SMF\'s index.php.');

global $smcFunc;

// First load the SMF 2's Extra DB Functions
db_extend('packages');

	$smcFunc['db_create_table']('{db_prefix}topic_clones',
	array(
		array(
			'name' => 'id_topic',
			'type' => 'mediumint',
			'size' => 8,
			'null' => false,
		),
		array(
			'name' => 'id_board',
			'type' => 'smallint',
			'size' => 5,
			'null' => false,
		),
	),
	array(
		array(
			'name' => 'id_board',
			'type' => 'index',
			'columns' => array('id_board'),
		),
	), 'update');
?>