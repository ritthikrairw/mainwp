<?php
class Keyword_Links_Utility
{
	public static function end_session() {
		session_write_close();
		if ( ob_get_length() > 0 ) { ob_end_flush(); }
	}
}
