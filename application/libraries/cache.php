<?php

class Cache extends CodeBreeder_Cache
{
	public function cleanup($pattern = '')
	{
		if ( !is_array($pattern) )
		{
			$pattern = array($pattern);
		}
		elseif ( !$pattern )
		{
			$pattern = array('');
		}

		foreach ( $pattern as $str )
		{
			foreach ( @scandir(DOCPATH . 'cache') as $file )
			{
				if ( $file != '.' && $file != '..' && ( $str == 'kaboom' || $str == '' && strpos($file, 'core_install') !== 0 || $str != '' && strpos($file, $str) === 0 ) )
				{
					@unlink(DOCPATH . 'cache/' . $file);
				}
			}
		}

		return true;
	}
}