<?php declare(strict_types = 1);

namespace CI\PhpStan;

class PhpStan
{

	/**
	 * @var string
	 */
	private $input;

	/**
	 * @var int
	 */
	private $errors;


	public function __construct(string $input)
	{
		$this->input = $input;
	}


	public function getErrors(): int
	{
		if ($this->errors === NULL) {
			$this->parse();
		}

		return (int) $this->errors;
	}


	private function parse()
	{
		$this->errors = 0;
		foreach (explode("\n", $this->input) as $line) {
			$line = trim(strtoupper($line));

			if (substr($line, 0, 7) !== '[ERROR]') {
				continue;
			}

			preg_match('/^[ERROR] Found (?<errors>[0-9]+) errors?/', $line, $matches);

			isset($matches['errors']) && $this->errors += (int) $matches['errors'];
		}
	}
}
