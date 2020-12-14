<?php

namespace DevCoding\Mac\Utility;

use Symfony\Component\Process\Process;

trait ShellTrait
{
  protected $_binCache;

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
   * @param bool   $throw
   *
   * @return string the full path to the binary
   *
   * @throws \Exception
   */
  protected function getBinaryPath($bin, $throw = true)
  {
    if (!$this->isAlphaNumeric($bin))
    {
      throw new \Exception("Not here you don't, Buster.");
    }

    if (empty($this->_binCache[$bin]))
    {
      $output = $this->getShellExec('which '.$bin);

      if ($throw && !($output && is_file(trim($output))))
      {
        throw new \Exception(sprintf('Could not locate "%s" binary.', $bin));
      }
      else
      {
        $this->_binCache[$bin] = $output;
      }
    }

    return $this->_binCache[$bin];
  }

  protected function getShellExec($cmd, $default = null)
  {
    return (($x = shell_exec($cmd)) && !empty($x)) ? trim($x) : $default;
  }

  /**
   * @param string $cmd
   * @param mixed  $default
   *
   * @return string|null
   */
  protected function getProcessOutput($cmd, $default = null)
  {
    $P = Process::fromShellCommandline($cmd);
    $P->run();

    return $P->isSuccessful() && ($o = $P->getOutput()) && !empty($o) ? trim($o) : $default;
  }
}
