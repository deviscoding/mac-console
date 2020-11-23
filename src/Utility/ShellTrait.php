<?php

namespace DevCoding\Mac\Utility;

trait ShellTrait
{
  /**
   * Checks if the given string is alphanumeric.
   *
   * @param string $str
   *
   * @return bool
   */
  protected function isAlphaNumeric($str)
  {
    return  !preg_match('/[^a-z_\-0-9]/i', $str);
  }

  /**
   * Attempts to find a binary path from the given string.  Command names may ONLY contain alphanumeric characters.
   *
   * @param string $bin
   *
   * @return string the full path to the binary
   *
   * @throws \Exception
   */
  protected function getBinaryPath($bin)
  {
    if (!$this->isAlphaNumeric($bin))
    {
      throw new \Exception("Not here you don't, Buster.");
    }

    $output = $this->getShellExec('which '.$bin);
    if (!$output || !is_file(trim($output)))
    {
      throw new \Exception(sprintf('Could not locate "%s" binary.', $bin));
    }

    return $output;
  }

  protected function getShellExec($cmd, $default = null)
  {
    return (($x = shell_exec($cmd)) && !empty($x)) ? trim($x) : $default;
  }
}
