<?php

namespace CI\PhpCs;

class PhpCs
{

	/**
	 * @var string
	 */
	private $input;

	private $warnings;

	private $errors;


	public function __construct(string $input)
	{
		$this->input = $input;
	}


	public function getWarnings()
	{
		if ($this->warnings === NULL) {
			$this->parse();
		}

		return $this->warnings;
	}


	public function getErrors()
	{
		if ($this->errors === NULL) {
			$this->parse();
		}

		return $this->errors;
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

			isset($matches['errors']) && $this->errors += $matches['errors'];
			isset($matches['warnings']) && $this->warnings += $matches['warnings'];
		}
	}
}
