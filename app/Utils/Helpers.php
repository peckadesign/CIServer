<?php

namespace CI\Utils;

use Nette;


class Helpers extends Nette\Object
{

	public static function dateTime($s)
	{
		return $s instanceof \DateTimeInterface ? $s->format('j. n. Y H:i:s') : $s;
	}


	public static function plural(int $count, string $zero, string $one, string $two): string
	{
		if ($count === 1) {
			return $one;
		} elseif ($count === 2) {
			return $two;
		} else {
			return $zero;
		}
	}

}
