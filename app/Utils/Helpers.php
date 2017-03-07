<?php

namespace CI\Utils;

use Nette;

class Helpers extends Nette\Object
{

	public static function dateTime(\DateTime $s)
	{
		return $s->format('j. n. Y H:i:s');
	}

}
