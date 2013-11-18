<?php
/**
*
* @package Board3 Portal v2.1
* @copyright (c) 2013 Board3 Group ( www.board3.de )
* @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
*
*/

namespace board3\portal\modules;

/**
* @package Poll
*/
class poll extends module_base
{
	/**
	* Allowed columns: Just sum up your options (Exp: left + right = 10)
	* top		1
	* left		2
	* center	4
	* right		8
	* bottom	16
	*/
	public $columns = 21;

	/**
	* Default modulename
	*/
	public $name = 'PORTAL_POLL';

	/**
	* Default module-image:
	* file must be in "{T_THEME_PATH}/images/portal/"
	*/
	public $image_src = 'portal_poll.png';

	/**
	* module-language file
	* file must be in "language/{$user->lang}/mods/portal/"
	*/
	public $language = 'portal_poll_module';

	/**
	* custom acp template
	* file must be in "adm/style/portal/"
	*/
	public $custom_acp_tpl = '';

	/** @var \phpbb\auth\auth */
	protected $auth;

	/** @var \phpbb\config\config */
	protected $config;

	/** @var \phpbb\template */
	protected $template;

	/** @var \phpbb\db\driver */
	protected $db;

	/** @var php file extension */
	protected $php_ext;

	/** @var phpbb root path */
	protected $phpbb_root_path;

	/** @var \phpbb\user */
	protected $user;

	/**
	* Construct a poll object
	*
	* @param \phpbb\auth\auth $auth phpBB auth service
	* @param \phpbb\config\config $config phpBB config
	* @param \phpbb\template $template phpBB template
	* @param \phpbb\db\driver $db Database driver
	* @param string $phpEx php file extension
	* @param string $phpbb_root_path phpBB root path
	* @param \phpbb\user $user phpBB user object
	*/
	public function __construct($auth, $config, $db, $template, $phpbb_root_path, $phpEx, $user)
	{
		$this->auth = $auth;
		$this->config = $config;
		$this->db = $db;
		$this->template = $template;
		$this->php_ext = $phpEx;
		$this->phpbb_root_path = $phpbb_root_path;
		$this->user = $user;
	}

	/**
	* @inheritdoc
	*/
	public function get_template_center($module_id)
	{
		return $this->parse_template($module_id);
	}

	/**
	* @inheritdoc
	*/
	public function get_template_side($module_id)
	{
		return $this->parse_template($module_id, 'side');
	}

	/**
	* @inheritdoc
	*/
	public function get_template_acp($module_id)
	{
		return array(
			'title'	=> 'ACP_PORTAL_POLLS_SETTINGS',
			'vars'	=> array(
				'legend1'							=> 'ACP_PORTAL_POLLS_SETTINGS',
				'board3_poll_topic_id_' . $module_id	=> array('lang' => 'PORTAL_POLL_TOPIC_ID'				,	'validate' => 'string',		'type' => 'custom',			'explain' => true, 'method' => 'select_forums', 'submit' => 'store_selected_forums'),
				'board3_poll_exclude_id_' . $module_id	=> array('lang' => 'PORTAL_POLL_EXCLUDE_ID'				,	'validate' => 'bool',		'type' => 'radio:yes_no',	'explain' => true),
				'board3_poll_limit_' . $module_id		=> array('lang' => 'PORTAL_POLL_LIMIT'					,	'validate' => 'int',		'type' => 'text:3:3',	 	'explain' => true),
				'board3_poll_allow_vote_' . $module_id	=> array('lang' => 'PORTAL_POLL_ALLOW_VOTE'				,	'validate' => 'ibool',		'type' => 'radio:yes_no',	 'explain' => true),
				'board3_poll_hide_' . $module_id		=> array('lang' => 'PORTAL_POLL_HIDE'					,	'validate' => 'bool',		'type' => 'radio:yes_no',	 'explain' => true),
			)
		);
	}

	/**
	* @inheritdoc
	*/
	public function install($module_id)
	{
		set_config('board3_poll_allow_vote_' . $module_id, 1);
		set_config('board3_poll_topic_id_' . $module_id, '');
		set_config('board3_poll_exclude_id_' . $module_id, 0);
		set_config('board3_poll_hide_' . $module_id, 0);
		set_config('board3_poll_limit_' . $module_id, 3);
		return true;
	}

	/**
	* @inheritdoc
	*/
	public function uninstall($module_id, $db)
	{
		$del_config = array(
			'board3_poll_allow_vote_' . $module_id,
			'board3_poll_topic_id_' . $module_id,
			'board3_poll_exclude_id_' . $module_id,
			'board3_poll_hide_' . $module_id,
			'board3_poll_limit_' . $module_id,
		);
		$sql = 'DELETE FROM ' . CONFIG_TABLE . '
			WHERE ' . $db->sql_in_set('config_name', $del_config);
		return $db->sql_query($sql);
	}

	/**
	* Create forum select box
	*
	* @param mixed $value Value of input
	* @param string $key Key name
	* @param int $module_id Module ID
	*
	* @return null
	*/
	public function select_forums($value, $key, $module_id)
	{
		$forum_list = make_forum_select(false, false, true, true, true, false, true);

		$selected = array();
		if(isset($this->config[$key]) && strlen($this->config[$key]) > 0)
		{
			$selected = explode(',', $this->config[$key]);
		}
		// Build forum options
		$s_forum_options = '<select id="' . $key . '" name="' . $key . '[]" multiple="multiple">';
		foreach ($forum_list as $f_id => $f_row)
		{
			$s_forum_options .= '<option value="' . $f_id . '"' . ((in_array($f_id, $selected)) ? ' selected="selected"' : '') . (($f_row['disabled']) ? ' disabled="disabled" class="disabled-option"' : '') . '>' . $f_row['padding'] . $f_row['forum_name'] . '</option>';
		}
		$s_forum_options .= '</select>';

		return $s_forum_options;
	}

	/**
	* Store selected forums
	*
	* @param string $key Key name
	* @param int $module_id Module ID
	*
	* @return null
	*/
	public function store_selected_forums($key, $module_id)
	{
		// Get selected forums
		$values = request_var($key, array(0 => ''));

		$news = implode(',', $values);

		set_config($key, $news);

	}

	/**
	* Parse template variables for module
	*
	* @param int $module_id	Module ID
	* @param string $type	Module type (center or side)
	*
	* @return string HTML filename
	*/
	protected function parse_template($module_id, $type = '')
	{
		$this->user->add_lang('viewtopic');

		// check if we need to include the bbcode class
		if(!class_exists('bbcode'))
		{
			include($this->phpbb_root_path . 'includes/bbcode.' . $this->php_ext);
		}

		$view = request_var('view', '');
		$update = request_var('update', false);
		$poll_view = request_var('polls', '');

		$poll_view_ar = (strpos($poll_view, ',') !== FALSE) ? explode(',', $poll_view) : (($poll_view != '') ? array($poll_view) : array());

		if ($update && $this->config['board3_poll_allow_vote_' . $module_id])
		{
			$up_topic_id = request_var('t', 0);
			$up_forum_id = request_var('f', 0);
			$voted_id = request_var('vote_id', array('' => 0));

			$cur_voted_id = array();
			if ($this->user->data['is_registered'])
			{
				$sql = 'SELECT poll_option_id
					FROM ' . POLL_VOTES_TABLE . '
					WHERE topic_id = ' . $up_topic_id . '
						AND vote_user_id = ' . $this->user->data['user_id'];
				$result = $this->db->sql_query($sql);

				while ($row = $this->db->sql_fetchrow($result))
				{
					$cur_voted_id[] = $row['poll_option_id'];
				}
				$this->db->sql_freeresult($result);
			}
			else
			{
				// Cookie based guest tracking ... I don't like this but hum ho
				// it's oft requested. This relies on "nice" users who don't feel
				// the need to delete cookies to mess with results.
				if (isset($_COOKIE[$this->config['cookie_name'] . '_poll_' . $up_topic_id]))
				{
					$cur_voted_id = explode(',', request_var($this->config['cookie_name'] . '_poll_' . $up_topic_id, 0, false, true));
					$cur_voted_id = array_map('intval', $cur_voted_id);
				}
			}

			$sql = 'SELECT t.poll_length, t.poll_start, t.poll_vote_change, t.topic_status, f.forum_status, t.poll_max_options
					FROM ' . TOPICS_TABLE . ' t, ' . FORUMS_TABLE . " f
					WHERE t.forum_id = f.forum_id AND t.topic_id = " . (int) $up_topic_id . " AND t.forum_id = " . (int) $up_forum_id;
			$result = $this->db->sql_query_limit($sql, 1);
			$topic_data = $this->db->sql_fetchrow($result);
			$this->db->sql_freeresult($result);

			$s_can_up_vote = (((!sizeof($cur_voted_id) && $this->auth->acl_get('f_vote', $up_forum_id)) ||
				($this->auth->acl_get('f_votechg', $up_forum_id) && $topic_data['poll_vote_change'])) &&
				(($topic_data['poll_length'] != 0 && $topic_data['poll_start'] + $topic_data['poll_length'] > time()) || $topic_data['poll_length'] == 0) &&
				$topic_data['topic_status'] != ITEM_LOCKED &&
				$topic_data['forum_status'] != ITEM_LOCKED) ? true : false;

			if($s_can_up_vote)
			{
				$redirect_url = append_sid("{$this->phpbb_root_path}app.{$this->php_ext}/portal");

				if (!sizeof($voted_id) || sizeof($voted_id) > $topic_data['poll_max_options'] || in_array(VOTE_CONVERTED, $cur_voted_id))
				{
					meta_refresh(5, $redirect_url);
					if (!sizeof($voted_id))
					{
						$message = 'NO_VOTE_OPTION';
					}
					else if (sizeof($voted_id) > $topic_data['poll_max_options'])
					{
						$message = 'TOO_MANY_VOTE_OPTIONS';
					}
					else
					{
						$message = 'VOTE_CONVERTED';
					}

					$message = $this->user->lang[$message] . '<br /><br />' . sprintf($this->user->lang['RETURN_PORTAL'], '<a href="' . $redirect_url . '">', '</a>');
					trigger_error($message);
				}

				foreach ($voted_id as $option)
				{
					if (in_array($option, $cur_voted_id))
					{
						continue;
					}

					$sql = 'UPDATE ' . POLL_OPTIONS_TABLE . '
						SET poll_option_total = poll_option_total + 1
						WHERE poll_option_id = ' . (int) $option . '
							AND topic_id = ' . (int) $up_topic_id;
					$this->db->sql_query($sql);

					if ($this->user->data['is_registered'])
					{
						$sql_ary = array(
							'topic_id'			=> (int) $up_topic_id,
							'poll_option_id'	=> (int) $option,
							'vote_user_id'		=> (int) $this->user->data['user_id'],
							'vote_user_ip'		=> (string) $this->user->ip,
						);

						$sql = 'INSERT INTO ' . POLL_VOTES_TABLE . ' ' . $this->db->sql_build_array('INSERT', $sql_ary);
						$this->db->sql_query($sql);
					}
				}

				foreach ($cur_voted_id as $option)
				{
					if (!in_array($option, $voted_id))
					{
						$sql = 'UPDATE ' . POLL_OPTIONS_TABLE . '
							SET poll_option_total = poll_option_total - 1
							WHERE poll_option_id = ' . (int) $option . '
								AND topic_id = ' . (int) $up_topic_id;
						$this->db->sql_query($sql);

						if ($this->user->data['is_registered'])
						{
							$sql = 'DELETE FROM ' . POLL_VOTES_TABLE . '
								WHERE topic_id = ' . (int) $up_topic_id . '
									AND poll_option_id = ' . (int) $option . '
									AND vote_user_id = ' . (int) $this->user->data['user_id'];
							$this->db->sql_query($sql);
						}
					}
				}

				if ($this->user->data['user_id'] == ANONYMOUS && !$this->user->data['is_bot'])
				{
					$this->user->set_cookie('poll_' . $up_topic_id, implode(',', $voted_id), time() + 31536000);
				}

				$sql = 'UPDATE ' . TOPICS_TABLE . '
					SET poll_last_vote = ' . time() . "
					WHERE topic_id = $up_topic_id";
				//, topic_last_post_time = ' . time() . " -- for bumping topics with new votes, ignore for now
				$this->db->sql_query($sql);

				meta_refresh(5, $redirect_url);
				trigger_error($this->user->lang['VOTE_SUBMITTED'] . '<br /><br />' . sprintf($this->user->lang['RETURN_PORTAL'], '<a href="' . $redirect_url . '">', '</a>'));
			}
		}

		$where = '';
		$poll_forums = false;

		// Get readable forums
		$forum_list = array();

		$forum_list = array_unique(array_keys($this->auth->acl_getf('f_read', true)));

		if($this->config['board3_poll_topic_id_' . $module_id] !== '')
		{
			$poll_forums_config  = explode(',' ,$this->config['board3_poll_topic_id_' . $module_id]);

			if($this->config['board3_poll_exclude_id_' . $module_id])
			{
				$forum_list = array_unique(array_diff($forum_list, $poll_forums_config));
			}
			else
			{
				$forum_list = array_unique(array_intersect($poll_forums_config, $forum_list));
			}
		}

		$where = '';

		if(sizeof($forum_list))
		{
			$poll_forums = true;
			$where = 'AND ' . $this->db->sql_in_set('t.forum_id', $forum_list);
		}

		if ($this->config['board3_poll_hide_' . $module_id])
		{
			$portal_poll_hide = "AND (t.poll_start + t.poll_length > ". time() ." OR t.poll_length = 0)";
		}
		else
		{
			$portal_poll_hide = '';
		}

		if ($poll_forums === true)
		{

			$sql = 'SELECT t.poll_title, t.poll_start, t.topic_id,  t.topic_first_post_id, t.forum_id, t.poll_length, t.poll_vote_change, t.poll_max_options, t.topic_status, f.forum_status, p.bbcode_bitfield, p.bbcode_uid
					FROM ' . TOPICS_TABLE . ' t, ' . POSTS_TABLE . ' p, ' . FORUMS_TABLE . " f
					WHERE t.forum_id = f.forum_id AND t.topic_visibility = 1 AND t.poll_start > 0
					{$where}
					AND t.topic_moved_id = 0
					AND p.post_id = t.topic_first_post_id
					{$portal_poll_hide}
					ORDER BY t.poll_start DESC";
			$limit = (isset($this->config['board3_poll_limit_' . $module_id])) ? $this->config['board3_poll_limit_' . $module_id] : 3;
			$result = $this->db->sql_query_limit($sql, $limit);
			$has_poll = false;

			if ($result)
			{
				while($data = $this->db->sql_fetchrow($result))
				{
					$has_poll = true;
					$poll_has_options = false;

					$topic_id = (int) $data['topic_id'];
					$forum_id = (int) $data['forum_id'];

					$cur_voted_id = array();
					if($this->config['board3_poll_allow_vote_' . $module_id])
					{
						if ($this->user->data['is_registered'])
						{
							$vote_sql = 'SELECT poll_option_id
								FROM ' . POLL_VOTES_TABLE . '
								WHERE topic_id = ' . $topic_id . '
									AND vote_user_id = ' . $this->user->data['user_id'];
							$vote_result = $this->db->sql_query($vote_sql);

							while ($row = $this->db->sql_fetchrow($vote_result))
							{
								$cur_voted_id[] = $row['poll_option_id'];
							}
							$this->db->sql_freeresult($vote_result);
						}
						else
						{
							// Cookie based guest tracking ... I don't like this but hum ho
							// it's oft requested. This relies on "nice" users who don't feel
							// the need to delete cookies to mess with results.
							if (isset($_COOKIE[$this->config['cookie_name'] . '_poll_' . $topic_id]))
							{
								$cur_voted_id = explode(',', request_var($this->config['cookie_name'] . '_poll_' . $topic_id, 0, false, true));
								$cur_voted_id = array_map('intval', $cur_voted_id);
							}
						}

						$s_can_vote = (((!sizeof($cur_voted_id) && $this->auth->acl_get('f_vote', $forum_id)) ||
							($this->auth->acl_get('f_votechg', $forum_id) && $data['poll_vote_change'])) &&
							(($data['poll_length'] != 0 && $data['poll_start'] + $data['poll_length'] > time()) || $data['poll_length'] == 0) &&
							$data['topic_status'] != ITEM_LOCKED &&
							$data['forum_status'] != ITEM_LOCKED) ? true : false;
					}
					else
					{
						$s_can_vote = false;
					}

					$s_display_results = (!$s_can_vote || ($s_can_vote && sizeof($cur_voted_id)) || ($view == 'viewpoll' && in_array($topic_id, $poll_view_ar))) ? true : false;

					$poll_sql = 'SELECT po.poll_option_id, po.poll_option_text, po.poll_option_total
						FROM ' . POLL_OPTIONS_TABLE . " po
						WHERE po.topic_id = {$topic_id}
						ORDER BY po.poll_option_id";

					$poll_result = $this->db->sql_query($poll_sql);
					$poll_total_votes = 0;
					$poll_data = array();

					if ($poll_result)
					{
						while($polls_data = $this->db->sql_fetchrow($poll_result))
						{
							$poll_has_options = true;
							$poll_data[] = $polls_data;
							$poll_total_votes += $polls_data['poll_option_total'];
						}
					}
					$this->db->sql_freeresult($poll_result);

					$make_poll_view = array();

					if(in_array($topic_id, $poll_view_ar) === FALSE)
					{
						$make_poll_view[] = $topic_id;
						$make_poll_view = array_merge($poll_view_ar, $make_poll_view);
					}

					$poll_view_str = urlencode(implode(',', $make_poll_view));
					$portalpoll_url= append_sid("{$this->phpbb_root_path}app.{$this->php_ext}/portal", "polls=$poll_view_str");
					$portalvote_url= append_sid("{$this->phpbb_root_path}app.{$this->php_ext}/portal", "f=$forum_id&amp;t=$topic_id");
					$viewtopic_url = append_sid("{$this->phpbb_root_path}viewtopic.{$this->php_ext}", "f=$forum_id&amp;t=$topic_id");
					$poll_end = $data['poll_length'] + $data['poll_start'];

					// Parse BBCode title
					if ($data['bbcode_bitfield'])
					{
						$poll_bbcode = new bbcode();
					}
					else
					{
						$poll_bbcode = false;
					}

					$data['poll_title'] = censor_text($data['poll_title']);

					if ($poll_bbcode !== false)
					{
						$poll_bbcode->bbcode_second_pass($data['poll_title'], $data['bbcode_uid'], $data['bbcode_bitfield']);
					}

					$data['poll_title'] = bbcode_nl2br($data['poll_title']);
					$data['poll_title'] = smiley_text($data['poll_title']);
					unset($poll_bbcode);

					$this->template->assign_block_vars(($type !== '') ? 'poll_' . $type : 'poll', array(
						'S_POLL_HAS_OPTIONS'	=> $poll_has_options,
						'POLL_QUESTION'			=> $data['poll_title'],
						'U_POLL_TOPIC'			=> append_sid($this->phpbb_root_path . 'viewtopic.' . $this->php_ext . '?t=' . $topic_id . '&amp;f=' . $forum_id),
						'POLL_LENGTH'			=> $data['poll_length'],
						'TOPIC_ID'				=> $topic_id,
						'TOTAL_VOTES'			=> $poll_total_votes,
						'L_MAX_VOTES'			=> $this->user->lang('MAX_OPTIONS_SELECT', $data['poll_max_options']),
						'L_POLL_LENGTH'			=> ($data['poll_length']) ? sprintf($this->user->lang[($poll_end > time()) ? 'POLL_RUN_TILL' : 'POLL_ENDED_AT'], $this->user->format_date($poll_end)) : '',
						'S_CAN_VOTE'			=> $s_can_vote,
						'S_DISPLAY_RESULTS'		=> $s_display_results,
						'S_IS_MULTI_CHOICE'		=> ($data['poll_max_options'] > 1) ? true : false,
						'S_POLL_ACTION'			=> $portalvote_url,
						'U_VIEW_RESULTS'		=> $portalpoll_url . '&amp;view=viewpoll#viewpoll',
						'U_VIEW_TOPIC'			=> $viewtopic_url,
					));

					foreach($poll_data as $pd)
					{
						$option_pct = ($poll_total_votes > 0) ? $pd['poll_option_total'] / $poll_total_votes : 0;
						$option_pct_txt = sprintf("%.1d%%", round($option_pct * 100));

						// Parse BBCode option text
						if ($data['bbcode_bitfield'])
						{
							$poll_bbcode = new bbcode();
						}
						else
						{
							$poll_bbcode = false;
						}

						$pd['poll_option_text'] = censor_text($pd['poll_option_text']);

						if ($poll_bbcode !== false)
						{
							$poll_bbcode->bbcode_second_pass($pd['poll_option_text'], $data['bbcode_uid'], $data['bbcode_bitfield']);
						}

						$pd['poll_option_text'] = bbcode_nl2br($pd['poll_option_text']);
						$pd['poll_option_text'] = smiley_text($pd['poll_option_text']);
						unset($poll_bbcode);

						$this->template->assign_block_vars((($type !== '') ? 'poll_' . $type : 'poll') . '.poll_option', array(
							'POLL_OPTION_ID'		=> $pd['poll_option_id'],
							'POLL_OPTION_CAPTION'	=> $pd['poll_option_text'],
							'POLL_OPTION_RESULT'	=> $pd['poll_option_total'],
							'POLL_OPTION_PERCENT'	=> $option_pct_txt,
							'POLL_OPTION_PCT'		=> round($option_pct * 100),
							'POLL_OPTION_IMG'		=> $this->user->img('poll_center', $option_pct_txt, round($option_pct * 35) . 'px'),
							'POLL_OPTION_VOTED'		=> (in_array($pd['poll_option_id'], $cur_voted_id)) ? true : false
						));
					}
				}
			}
			$this->db->sql_freeresult($result);

			$this->template->assign_vars(array(
				'S_HAS_POLL'			=> $has_poll,
				'POLL_LEFT_CAP_IMG'		=> $this->user->img('poll_left'),
				'POLL_RIGHT_CAP_IMG'	=> $this->user->img('poll_right'),
			));
		}
		return (($type !== '') ? 'poll_' . $type : 'poll_center') . '.html';
	}
}
