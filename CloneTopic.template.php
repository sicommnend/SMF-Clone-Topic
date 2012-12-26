<?php
/**
 * Based on MoveTopic.template.php by Simple Machines Forum (SMF)
 *
 * @package SMF
 * @author Simple Machines
 * @copyright 2011 Simple Machines
 * @license http://www.simplemachines.org/about/smf/license.php BSD
 *
 * @version 2.0
 */

// Show an interface for selecting which board to clone a post to.
function template_main()
{
	global $context, $settings, $txt, $scripturl;

	echo '
	<div id="move_topic" class="lower_padding">
		<form action="', $scripturl, '?action=clonetopic2;topic=', $context['current_topic'], '.0" method="post" accept-charset="', $context['character_set'], '" onsubmit="submitonce(this);">
			<div class="cat_bar">
				<h3 class="catbg">'.$txt['clone_topic'].'</h3>
			</div>
			<div class="windowbg centertext">
				<span class="topslice"><span></span></span>
				<div class="content">
					<div class="move_topic">
						<dl class="settings">
							<dt>
								<strong>'.$txt['clone_to'].':</strong>
							</dt>
							<dd>
								<select name="toboard">';

	foreach ($context['categories'] as $category)
	{
		echo '
									<optgroup label="', $category['name'], '">';

		foreach ($category['boards'] as $board) {
			if (empty($context['topic_clones'][$board['id']])) { //Hide any that are already cloned.
				echo '
										<option value="', $board['id'], '"', $board['selected'] ? ' selected="selected"' : '', $board['id'] == $context['current_board'] ? ' disabled="disabled"' : '', '>', $board['child_level'] > 0 ? str_repeat('==', $board['child_level']-1) . '=&gt; ' : '', $board['name'], '</option>';
			}
		}
		echo '
									</optgroup>';
	}

	echo '
								</select>
							</dd>';

	// Disable the reason textarea when the postRedirect checkbox is unchecked...
	echo '
						</dl>
						<div class="righttext">
							<input type="submit" value="'.$txt['clone_topic'].'" onclick="return submitThisOnce(this);" accesskey="s" class="button_submit" />
						</div>
					</div>
				</div>
				<span class="botslice"><span></span></span>
			</div>';

	if ($context['back_to_topic'])
		echo '
			<input type="hidden" name="goback" value="1" />';

	echo '
			<input type="hidden" name="', $context['session_var'], '" value="', $context['session_id'], '" />
			<input type="hidden" name="seqnum" value="', $context['form_sequence_number'], '" />
		</form>
	</div>';

	if ($context['topic_clones']) {
		echo '
		<div class="lower_padding">
			<form action="', $scripturl, '?action=clonetopic2;topic=', $context['current_topic'], '.0" method="post" accept-charset="', $context['character_set'], '" onsubmit="submitonce(this);">
				<div class="cat_bar">
					<h3 class="catbg">'.$txt['clones_topic'].'</h3>
				</div>
				<div class="windowbg centertext">
					<span class="topslice"><span></span></span>
					<div class="content">
						<dl class="settings">
							<dt><strong>'.$txt['clone_remove'].'</strong></dt><dd><strong>'.$txt['board'].'</strong></dd>';
		foreach ($context['topic_clones'] as $clone) {
			echo '<dt><input type="checkbox" name="remove_clone[]" value="'.$clone['id'].'" /></dt><dd>'.$clone['name'].'</dd>';
		}
		echo '
						</dl>
						<div class="righttext">
							<input type="submit" value="'.$txt['clones_remove'].'" onclick="return submitThisOnce(this);" accesskey="s" class="button_submit" />
						</div>
					</div>
					<span class="botslice"><span></span></span>
				</div>';

	if ($context['back_to_topic'])
		echo '
				<input type="hidden" name="goback" value="1" />';

	echo '
				<input type="hidden" name="remove" value="true" />
				<input type="hidden" name="', $context['session_var'], '" value="', $context['session_id'], '" />
				<input type="hidden" name="seqnum" value="', $context['form_sequence_number'], '" />
			</form>
		</div>';
	 }
}

?>