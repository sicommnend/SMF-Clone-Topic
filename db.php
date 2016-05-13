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

	// Repair any old bugs.
	// Delete dupes
	$smcFunc['db_query']('', '
		DELETE c FROM {db_prefix}topic_clones AS c
			LEFT JOIN {db_prefix}topics AS t ON (t.id_topic = c.id_topic)
		WHERE t.id_board = c.id_board AND t.id_topic = c.id_topic',
		array()
	);

	// Delete non exists
	$smcFunc['db_query']('', '
		DELETE c FROM {db_prefix}topic_clones AS c
			LEFT JOIN {db_prefix}messages AS m ON (m.id_topic = c.id_topic)
		WHERE m.id_topic IS NULL',
		array()
	);
?>
