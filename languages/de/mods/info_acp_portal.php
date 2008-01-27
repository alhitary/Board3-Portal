<?php

/**
*
* @package - Board3portal
* @version $Id$
* @copyright (c) kevin / saint ( http://www.board3.de/ ), (c) Ice, (c) nickvergessen ( http://mods.flying-bits.org/ ), (c) redbull254 ( http://www.digitalfotografie-foren.de )
* @license http://opensource.org/licenses/gpl-license.php GNU Public License 
*
*/

if (!defined('IN_PHPBB'))
{
	exit;
}
if (empty($lang) || !is_array($lang))
{
	$lang = array();
}

$lang = array_merge($lang, array(
	'ACP_PORTAL_INFO'								=> 'Portal',
	'ACP_PORTAL_GENERAL_INFO'					=> 'Allgemein',
	'ACP_PORTAL_ANNOUNCE_INFO'					=> 'Bekanntmachungen',
	'ACP_PORTAL_NEWS_INFO'						=> 'Aktuelle Beiträge',
	'ACP_PORTAL_RECENT_INFO'						=> 'Aktuelle Themen',
	'ACP_PORTAL_WORDGRAPH_INFO'				=> 'Wordgraph',
	'ACP_PORTAL_GENERAL_INFO'					=> 'Allgemeine Einstellungen',
	'ACP_PORTAL_PAYPAL_INFO'						=> 'Paypal Spenden',
	'ACP_PORTAL_ATTACHMENTS_NUMBER_INFO'	=> 'Dateianhänge',
	'ACP_PORTAL_MEMBERS_INFO'					=> 'Die neuesten Mitglieder',
	'ACP_PORTAL_POLLS_INFO'						=> 'Umfragen',
	'ACP_PORTAL_BOTS_INFO'						=> 'Die letzten Bots',
	'ACP_PORTAL_MOST_POSTER_INFO'				=> 'Die Vielschreiber',
	'ACP_PORTAL_WELCOME_INFO'					=> 'Willkommens Nachricht',
	'ACP_PORTAL_ADS_INFO'							=> 'Werbung',
	'ACP_PORTAL_MINICALENDAR_INFO'				=> 'Mini Kalender',
));

?>