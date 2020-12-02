<?php

namespace DevCoding\Mac\Objects;

use DevCoding\Mac\Utility\ShellTrait;
use Symfony\Component\Process\Process;

class MacDevice
{
  use ShellTrait;

  /** @var int */
  protected $_cores;
  /** @var bool */
  protected $_t2;
  /** @var bool */
  protected $_secureBoot;
  /** @var MacOs */
  protected $_OS;

  /**
   * @return MacOs
   */
  public function getOs()
  {
    if (is_null($this->_OS))
    {
      $this->_OS = new MacOs();
    }

    return $this->_OS;
  }

  /**
   * Returns the number of physical CPU cores.
   *
   * @return int
   */
  public function getCpuCores()
  {
    if (empty($this->_cores))
    {
      $this->_cores = (int) $this->getShellExec('sysctl -n hw.physicalcpu');
    }

    return $this->_cores;
  }

  /**
   * Returns free disk space in Gibibyte (1024), as returned by the DF binary.
   *
   * @see    https://en.wikipedia.org/wiki/Gibibyte
   * @return string|null
   */
  public function getFreeDiskSpace()
  {
    return $this->getShellExec("/bin/df -g / | /usr/bin/awk '(NR == 2){print $4}'");
  }

  /**
   * Determines if the system is running off of battery power, or AC power.
   *
   * @return bool
   */
  public function isBatteryPowered()
  {
    $battery = $this->getShellExec('/usr/bin/pmset -g ps');

    return false !== strpos($battery, 'Battery Power');
  }

  /**
   * Determines if the display is prevented from sleeping by an assertation, usually an indicator that a presentation
   * or video conference is currently running.
   *
   * @return string|null
   */
  public function isDisplaySleepPrevented()
  {
    $a = $this->getShellExec("/usr/bin/pmset -g assertions | /usr/bin/awk '/NoDisplaySleepAssertion | PreventUserIdleDisplaySleep/ && match($0,/\(.+\)/) && ! /coreaudiod/ {gsub(/^\ +/,\"\",$0); print};'");

    return !empty($a);
  }

  /**
   * Determines if running on a Mac.
   *
   * @return bool
   */
  public function isMac()
  {
    return  PHP_OS === 'Darwin';
  }

  /**
   * Determines if the Mac has a T2 security chip.
   *
   * @return bool
   */
  public function isSecurityChip()
  {
    if (is_null($this->_t2))
    {
      $bridge = $this->getShellExec("/usr/sbin/system_profiler SPiBridgeDataType | /usr/bin/awk -F: '/Model Name/ { gsub(/.*: /,\"\"); print $0}'");

      $this->_t2 = !empty($bridge);
    }

    return $this->_t2;
  }

  /**
   * Determines if the MacOS Secure Boot feature is set to "full".
   *
   * @return bool
   */
  public function isSecureBoot()
  {
    if (is_null($this->_secureBoot))
    {
      $P = Process::fromShellCommandline("nvram 94b73556-2197-4702-82a8-3e1337dafbfb:AppleSecureBootPolicy | awk '{ print $2 }'");
      $P->run();

      if ($P->isSuccessful())
      {
        $this->_secureBoot = (false !== strpos($P->getOutput(), '%02'));
      }
    }

    return $this->_secureBoot;
  }
}
