<?php

/**
 * Based on MoveTopic.php by Simple Machines Forum (SMF)
 *
 * @package SMF
 * @author Simple Machines http://www.simplemachines.org
 * @copyright 2011 Simple Machines
 * @license http://www.simplemachines.org/about/smf/license.php BSD
 *
 * @version 2.0
 */

if (!defined('SMF'))
	die('Hacking attempt...');

/*	This file contains the functions required to clone topics from one board to
	another board.

	void CloneTopic()
		- must be called with a topic specified.
		- uses the CloneTopic template and main sub template.
		- if the member is the topic starter requires the move_own permission,
		  otherwise the move_any permission.
		- is accessed via ?action=clonetopic.

	void CloneTopic2()
		- is called on the submit of CloneTopic.
		- requires the use of the Subs-Post.php file.
		- logs that topics have been cloned as moved in the moderation log.
		- if the member is the topic starter requires the move_own permission,
		  otherwise requires the move_any permission.
		- upon successful completion redirects to topic.
		- is accessed via ?action=clonetopic2.
*/

function CloneTopic()
{
	global $txt, $board, $topic, $user_info, $context, $language, $scripturl, $settings, $smcFunc, $modSettings;

	if (empty($topic))
		fatal_lang_error('no_access', false);

	$request = $smcFunc['db_query']('', '
		SELECT t.id_member_started, ms.subject, t.approved
		FROM {db_prefix}topics AS t
			INNER JOIN {db_prefix}messages AS ms ON (ms.id_msg = t.id_first_msg)
		WHERE t.id_topic = {int:current_topic}
		LIMIT 1',
		array(
			'current_topic' => $topic,
		)
	);
	list ($id_member_started, $context['subject'], $context['is_approved']) = $smcFunc['db_fetch_row']($request);
	$smcFunc['db_free_result']($request);

	// Can they see it - if not approved?
	if ($modSettings['postmod_active'] && !$context['is_approved'])
		isAllowedTo('approve_posts');

	// Permission check!
	// !!!
	if (!allowedTo('clone_any'))
	{
		if ($id_member_started == $user_info['id'])
			isAllowedTo('clone_own');
		else
			isAllowedTo('clone_any');
	}

	loadTemplate('CloneTopic');

	// Get a list of boards this moderator can move to.
	$request = $smcFunc['db_query']('order_by_board_order', '
		SELECT b.id_board, b.name, b.child_level, c.name AS cat_name, c.id_cat
		FROM {db_prefix}boards AS b
			LEFT JOIN {db_prefix}categories AS c ON (c.id_cat = b.id_cat)
		WHERE {query_see_board}
			AND b.redirect = {string:blank_redirect}
			AND b.id_board != {int:current_board}',
		array(
			'blank_redirect' => '',
			'current_board' => $board,
		)
	);
	$context['boards'] = array();
	while ($row = $smcFunc['db_fetch_assoc']($request))
	{
		if (!isset($context['categories'][$row['id_cat']]))
			$context['categories'][$row['id_cat']] = array (
				'name' => strip_tags($row['cat_name']),
				'boards' => array(),
			);

		$context['categories'][$row['id_cat']]['boards'][] = array(
			'id' => $row['id_board'],
			'name' => strip_tags($row['name']),
			'category' => strip_tags($row['cat_name']),
			'child_level' => $row['child_level'],
			'selected' => !empty($_SESSION['move_to_topic']) && $_SESSION['move_to_topic'] == $row['id_board'] && $row['id_board'] != $board,
		);
	}
	$smcFunc['db_free_result']($request);

	if (empty($context['categories']))
		fatal_lang_error('moveto_noboards', false);

	$context['page_title'] = $txt['clone_topic'];

	$context['linktree'][] = array(
		'url' => $scripturl . '?topic=' . $topic . '.0',
		'name' => $context['subject'],
		'extra_before' => $settings['linktree_inline'] ? $txt['topic'] . ': ' : '',
	);

	$context['linktree'][] = array(
		'name' => $txt['clone_topic'],
	);

	$context['back_to_topic'] = isset($_REQUEST['goback']);

	// Register this form and get a sequence number in $context.
	checkSubmitOnce('register');

	$context['topic_clones'] = false;

	// Get current clones
	$request = $smcFunc['db_query']('', '
		SELECT c.id_board AS id, b.name
		FROM {db_prefix}topic_clones AS c
			LEFT JOIN {db_prefix}boards AS b ON (b.id_board = c.id_board)
		WHERE c.id_topic = {int:current_topic}',
		array('current_topic' => $topic)
	);
	while ($row = $smcFunc['db_fetch_assoc']($request)) {
		$context['topic_clones'][$row['id']] = array(
			'id' => $row['id'],
			'name' => $row['name']
		);
	}
	$smcFunc['db_free_result']($request);
}

// Execute the clone.
function CloneTopic2()
{
	global $txt, $board, $topic, $scripturl, $sourcedir, $modSettings, $context;
	global $board, $language, $user_info, $smcFunc;

	if (empty($topic))
		fatal_lang_error('no_access', false);

	// Make sure this form hasn't been submitted before.
	checkSubmitOnce('check');

	$request = $smcFunc['db_query']('', '
		SELECT id_member_started, id_first_msg, approved
		FROM {db_prefix}topics
		WHERE id_topic = {int:current_topic}
		LIMIT 1',
		array(
			'current_topic' => $topic,
		)
	);
	list ($id_member_started, $id_first_msg, $context['is_approved']) = $smcFunc['db_fetch_row']($request);
	$smcFunc['db_free_result']($request);

	// Can they see it?
	if (!$context['is_approved'])
		isAllowedTo('approve_posts');

	// Can they move topics on this board?
	if (!allowedTo('clone_any'))
	{
		if ($id_member_started == $user_info['id'])
		{
			isAllowedTo('clown_own');
			$boards = array_merge(boardsAllowedTo('clone_own'), boardsAllowedTo('clone_any'));
		}
		else
			isAllowedTo('clone_any');
	}
	else
		$boards = boardsAllowedTo('clone_any');

	// If this topic isn't approved don't let them move it if they can't approve it!
	if ($modSettings['postmod_active'] && !$context['is_approved'] && !allowedTo('approve_posts'))
	{
		// Only allow them to move it to other boards they can't approve it in.
		$can_approve = boardsAllowedTo('approve_posts');
		$boards = array_intersect($boards, $can_approve);
	}

	checkSession();
	require_once($sourcedir . '/Subs-Post.php');

	if (isset($_POST['toboard']) && empty($_POST['remove_clone'])) {
		// The destination board must be numeric.
		$_POST['toboard'] = (int) $_POST['toboard'];

		// Make sure they can see the board they are trying to move to (and get whether posts count in the target board).
		$request = $smcFunc['db_query']('', '
			SELECT b.count_posts, b.name, m.subject
			FROM {db_prefix}boards AS b
				INNER JOIN {db_prefix}topics AS t ON (t.id_topic = {int:current_topic})
				INNER JOIN {db_prefix}messages AS m ON (m.id_msg = t.id_first_msg)
			WHERE {query_see_board}
				AND b.id_board = {int:to_board}
				AND b.redirect = {string:blank_redirect}
			LIMIT 1',
			array(
				'current_topic' => $topic,
				'to_board' => $_POST['toboard'],
				'blank_redirect' => '',
			)
		);
		if ($smcFunc['db_num_rows']($request) == 0)
			fatal_lang_error('no_board');
		list ($pcounter, $board_name, $subject) = $smcFunc['db_fetch_row']($request);
		$smcFunc['db_free_result']($request);
	}

	// Do the clone
	if (empty($_POST['remove_clone'])) {
		
		// Remember this for later.
		$_SESSION['move_to_topic'] = $_POST['toboard'];

		$smcFunc['db_insert']('insert', '{db_prefix}topic_clones',
			array('id_topic' => 'int', 'id_board' => 'int'),
			array($topic, $_POST['toboard']),
			array()
		);
	} else {
		$conditions = '';
		foreach ($_POST['remove_clone'] as $board) {
			if(isset($nfirst)) {$conditions.= ' OR ';} else {$nfirst = true;}
			$conditions.= '(id_topic = '.$topic.' AND id_board = '.$board.')';
		}
		$smcFunc['db_query']('', 'DELETE FROM {db_prefix}topic_clones WHERE '.$conditions);
		redirectexit('action=clonetopic;topic='.$topic);
	}

	// Log that they cloned this topic.
	if (!allowedTo('clone_own') || $id_member_started != $user_info['id'])
		logAction('clone', array('topic' => $topic));

	redirectexit('topic=' . $topic . '.0');
}
?>