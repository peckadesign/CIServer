<?php declare(strict_types = 1);

namespace CI\PhpCs;

class PhpCs
{

	/**
	 * @var string
	 */
	private $input;

	/**
	 * @var int
	 */
	private $warnings;

	/**
	 * @var int
	 */
	private $errors;


	public function __construct(string $input)
	{
		$this->input = $input;
	}


	public function getWarnings(): int
	{
		if ($this->warnings === NULL) {
			$this->parse();
		}

		return (int) $this->warnings;
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
		$this->warnings = $this->errors = 0;
		foreach (explode("\n", $this->input) as $line) {
			$line = strtoupper($line);

			if (substr($line, 0, 6) !== 'FOUND ') {
				continue;
			}

			preg_match('/^FOUND (?<errors>[0-9]+) ERRORS?( AND (?<warnings>[0-9]+) WARNINGS?)?/', $line, $matches);

			isset($matches['errors']) && $this->errors += (int) $matches['errors'];
			isset($matches['warnings']) && $this->warnings += (int) $matches['warnings'];
		}
	}
}
