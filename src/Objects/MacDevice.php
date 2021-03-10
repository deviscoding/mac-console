<?php

namespace DevCoding\Mac\Objects;

use DevCoding\Command\Base\Traits\ShellTrait;
use Symfony\Component\Process\Process;

/**
 * Class MacDevice.
 *
 * @package DevCoding\Mac\Objects
 */
class MacDevice
{
  const CPU_INTEL = 'intel';
  const CPU_APPLE = 'apple';

  use ShellTrait;

  /** @var int */
  protected $_cores;
  /** @var bool */
  protected $_t2;
  /** @var bool */
  protected $_secureBoot;
  /** @var MacOs */
  protected $_OS;
  /** @var MacNetworkSubsystem */
  protected $_Network;
  /** @var string[] */
  protected $_SPHardwareDataType;

  /**
   * @return MacNetworkSubsystem
   */
  public function getNetwork()
  {
    if (is_null($this->_Network))
    {
      $this->_Network = new MacNetworkSubsystem();
    }

    return $this->_Network;
  }

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
      $this->_cores = (int) $this->getShellExec('/usr/sbin/sysctl -n hw.physicalcpu');
    }

    return $this->_cores;
  }

  /**
   * @return string|null
   */
  public function getCpuType()
  {
    $cmd = '/usr/bin/uname -m';
    if ($cpu = $this->getShellExec($cmd))
    {
      switch ($cpu)
      {
        case 'x86_64':
        case 'i386':
          return self::CPU_INTEL;
        case 'arm64':
          return self::CPU_APPLE;
        default:
          break;
      }
    }

    return null;
  }

  /**
   * Returns free disk space in Gibibyte (1024), as returned by the DF binary.
   *
   * @see    https://en.wikipedia.org/wiki/Gibibyte
   *
   * @return string|null
   */
  public function getFreeDiskSpace()
  {
    return $this->getShellExec("/bin/df -g / | /usr/bin/awk '(NR == 2){print $4}'");
  }

  /**
   * @return string|null
   */
  public function getModelIdentifier()
  {
    return $this->getSpHardwareDataType('model_identifier');
  }

  /**
   * @return string|null
   */
  public function getModelName()
  {
    return $this->getSpHardwareDataType('model_name');
  }

  /**
   * @return string|null
   */
  public function getProcessorName()
  {
    return $this->getSpHardwareDataType('processor_name');
  }

  /**
   * @return string|null
   */
  public function getProcessorSpeed()
  {
    return $this->getSpHardwareDataType('processor_speed');
  }

  /**
   * @return string|null
   */
  public function getSerialNumber()
  {
    return $this->getSpHardwareDataType('serial_number_system');
  }

  /**
   * @return bool
   */
  public function isAppleChip()
  {
    return self::CPU_APPLE == $this->getCpuType();
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
   * @return bool
   */
  public function isIntelChip()
  {
    return self::CPU_INTEL == $this->getCpuType();
  }

  /**
   * Determines if running on a Mac.
   *
   * @return bool
   */
  public function isMac()
  {
    return PHP_OS === 'Darwin';
  }

  /**
   * @return bool
   */
  public function isMacBook()
  {
    return false !== strpos($this->getModelName(), 'MacBook');
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
      if (is_file('/usr/sbin/nvram'))
      {
        $P = Process::fromShellCommandline("/usr/sbin/nvram 94b73556-2197-4702-82a8-3e1337dafbfb:AppleSecureBootPolicy | awk '{ print $2 }'");
        $P->run();

        if ($P->isSuccessful())
        {
          $this->_secureBoot = (false !== strpos($P->getOutput(), '%02'));
        }
      }
    }

    return $this->_secureBoot;
  }

  /**
   * @param string|null $key
   *
   * @return string[]|string
   */
  protected function getSpHardwareDataType($key = null)
  {
    if (is_null($this->_SPHardwareDataType))
    {
      if ($result = $this->getShellExec('system_profiler SPHardwareDataType | grep ": "'))
      {
        if (preg_match_all('#\s*([^:]+):\s(.*)#', $result, $matches, PREG_SET_ORDER))
        {
          foreach ($matches as $match)
          {
            $key = !empty($match[1]) ? preg_replace('~(?<=\\w)([A-Z])~u', '_$1', $match[1]) : null;
            $val = !empty($match[2]) ? $match[2] : null;

            if ($key && $val)
            {
              $this->_SPHardwareDataType[$key] = $val;
            }
          }
        }
      }
    }

    if ($key)
    {
      return !empty($this->_SPHardwareDataType[$key]) ? $this->_SPHardwareDataType[$key] : null;
    }
    else
    {
      return $this->_SPHardwareDataType;
    }
  }
}
