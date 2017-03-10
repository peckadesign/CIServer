<?php

namespace CI\Utils;

use Nette;

class Helpers extends Nette\Object
{

	public static function dateTime($s)
	{
		return $s instanceof \DateTimeInterface ? $s->format('j. n. Y H:i:s') : $s;
	}

}
